<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole(['owner', 'kasir']);

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil info admin saat ini
$stmt = $pdo->prepare("SELECT nama, email, nama_toko, status_toko, pajak_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama         = trim($_POST['nama']);
        $email        = trim($_POST['email']);
        $nama_toko    = $_SESSION['role'] === 'owner' ? trim($_POST['nama_toko']) : $user['nama_toko'];
        $status_toko  = $_SESSION['role'] === 'owner' ? trim($_POST['status_toko']) : ($user['status_toko'] ?? 'buka');
        $pajak_status = $_SESSION['role'] === 'owner' ? trim($_POST['pajak_status']) : ($user['pajak_status'] ?? 'nonaktif');
        
        // Cek email duplikat
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCek->execute([$email, $user_id]);
        
        if ($stmtCek->fetch()) {
            $error = "Email sudah digunakan oleh akun lain.";
        } else {
            if ($_SESSION['role'] === 'owner') {
                // Sync nama_toko, status_toko & pajak_status for all users
                $stmtUpdateAll = $pdo->prepare("UPDATE users SET nama_toko = ?, status_toko = ?, pajak_status = ?");
                $stmtUpdateAll->execute([$nama_toko, $status_toko, $pajak_status]);
            }
            
            $stmtUpdate = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
            $stmtUpdate->execute([$nama, $email, $user_id]);
            
            $_SESSION['nama']         = $nama; // Update session
            $_SESSION['nama_toko']    = $nama_toko;
            $_SESSION['status_toko']  = $status_toko;
            $_SESSION['pajak_status'] = $pajak_status;
            
            $user['nama']         = $nama;
            $user['email']        = $email;
            $user['nama_toko']    = $nama_toko;
            $user['status_toko']  = $status_toko;
            $user['pajak_status'] = $pajak_status;
            $success = "Profil dan Informasi Toko berhasil diperbarui!";
        }
    } elseif (isset($_POST['change_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi    = $_POST['konfirmasi'];
        
        // Ambil password lama di DB
        $stmtPass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtPass->execute([$user_id]);
        $hash = $stmtPass->fetchColumn();
        
        if (!password_verify($password_lama, $hash)) {
            $error = "Kata sandi saat ini salah.";
        } elseif ($password_baru !== $konfirmasi) {
            $error = "Konfirmasi kata sandi baru tidak cocok.";
        } elseif (strlen($password_baru) < 8) {
            $error = "Kata sandi baru minimal harus 8 karakter.";
        } else {
            $newHash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmtUpdatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdatePass->execute([$newHash, $user_id]);
            $success = "Kata sandi berhasil diperbarui!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .settings-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">PENA-UMKM</div>
    <div class="logo-sub">Merchant Admin</div>

    <nav>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="dashboard.php">🏠 Dashboard</a>
        <?php endif; ?>
        <a href="../kasir/kasir.php">🖥️ Kasir</a>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="kelola-produk.php">📦 Kelola Produk</a>
        <?php endif; ?>
        <a href="transaksi.php">🧾 Transaksi</a>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="laporan.php">📊 Laporan</a>
        <a href="dompet.php">💳 Dompet Toko</a>
            <a href="kelola-user.php">👥 Kelola User</a>
        <?php endif; ?>
        <a href="pengaturan.php" class="active" style="background:#3B82F6; color:white;">⚙️ Pengaturan</a>
    </nav>

    <div style="margin-top:auto; display:flex; flex-direction:column; gap:12px;">
        <a href="../kasir/kasir.php" style="display:flex; align-items:center; justify-content:center; gap:8px; background:var(--primary); color:white; padding:12px; border-radius:8px; text-decoration:none; font-weight:500;">
            🖥️ Buka Kasir
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div style="display:flex; align-items:center;">
            <h2 style="font-size:1.2rem; font-weight:600;">Pengaturan Akun</h2>
        </div>
        
        <div style="display:flex; align-items:center; gap:16px;">
            <?php tampilkanNotifikasi(); ?>
            <a href="pengaturan.php" class="topbar-profile" style="text-decoration:none; color:inherit; cursor:pointer;" title="Buka Pengaturan Akun">
                <div style="text-align:right;">
                    <div style="font-size:0.85rem; font-weight:600;"><?= $_SESSION['nama'] ?></div>
                    <div style="font-size:0.75rem; color:var(--gray-500);"><?= strtoupper($_SESSION['role']) ?></div>
                </div>
                <div class="topbar-avatar">
                    <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
                </div>
            </a>
        </div>
    </div>

    <!-- TITLE HEADER -->
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Pengaturan Toko & Profil</h1>
        <p>Kelola profil admin Anda dan amankan akun Anda di sini.</p>
    </div>

    <!-- ALERTS -->
    <?php if ($success): ?>
        <div style="background:var(--success-light); color:#065F46; padding:12px 16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem; font-weight:500;">
            ✅ <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:var(--danger-light); color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem; font-weight:500;">
            ⚠️ <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="settings-layout">
        
        <!-- LEFT: Profile Info -->
        <div class="card">
            <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <span>👤</span> Ubah Informasi Profil
            </h3>
            
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Nama Toko / UMKM</label>
                    <input type="text" name="nama_toko" value="<?= htmlspecialchars($user['nama_toko']) ?>" <?= $_SESSION['role'] !== 'owner' ? 'readonly style="background:#F3F4F6; cursor:not-allowed;"' : '' ?> required>
                </div>
                
                <div class="input-group">
                    <label>Status Operasional Toko</label>
                    <?php if ($_SESSION['role'] === 'owner'): ?>
                        <select name="status_toko" required>
                            <option value="buka" <?= ($user['status_toko'] ?? 'buka') === 'buka' ? 'selected' : '' ?>>🟢 Buka (Melayani Transaksi)</option>
                            <option value="tutup" <?= ($user['status_toko'] ?? 'buka') === 'tutup' ? 'selected' : '' ?>>🔴 Tutup (Tolak Transaksi)</option>
                        </select>
                    <?php else: ?>
                        <input type="text" value="<?= ($user['status_toko'] ?? 'buka') === 'buka' ? '🟢 Buka' : '🔴 Tutup' ?>" readonly style="background:#F3F4F6; cursor:not-allowed;">
                    <?php endif; ?>
                </div>
                
                <div class="input-group">
                    <label>Pengaturan Pajak (PPN 11%)</label>
                    <?php if ($_SESSION['role'] === 'owner'): ?>
                        <select name="pajak_status" required>
                            <option value="nonaktif" <?= ($user['pajak_status'] ?? 'nonaktif') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (Tanpa Pajak - Cocok untuk UMKM)</option>
                            <option value="aktif" <?= ($user['pajak_status'] ?? 'nonaktif') === 'aktif' ? 'selected' : '' ?>>Aktif (Terapkan Pajak PPN 11%)</option>
                        </select>
                    <?php else: ?>
                        <input type="text" value="<?= ($user['pajak_status'] ?? 'nonaktif') === 'aktif' ? 'Aktif (PPN 11%)' : 'Nonaktif (Tanpa Pajak)' ?>" readonly style="background:#F3F4F6; cursor:not-allowed;">
                    <?php endif; ?>
                </div>
                
                <div class="input-group">
                    <label>Email Akun</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- RIGHT: Change Password -->
        <div class="card">
            <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <span>🔒</span> Ganti Kata Sandi
            </h3>
            
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                
                <div class="input-group">
                    <label>Kata Sandi Saat Ini</label>
                    <input type="password" name="password_lama" placeholder="••••••••" required>
                </div>
                
                <div class="input-group">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="password_baru" placeholder="Minimal 8 karakter" minlength="8" required>
                </div>

                <div class="input-group">
                    <label>Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="konfirmasi" placeholder="••••••••" minlength="8" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                    Perbarui Kata Sandi
                </button>
            </form>
        </div>

    </div>

    <?php tampilkanFooter(); ?>

</div>

</body>
</html>
