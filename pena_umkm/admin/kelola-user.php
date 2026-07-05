<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner'); // Hanya Owner yang bisa mengelola user

$error = '';
$success = '';

// Tambah User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    
    // Cek email duplikat
    $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCek->execute([$email]);
    if ($stmtCek->fetch()) {
        $error = "Email sudah terdaftar.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $owner_nama_toko = $_SESSION['nama_toko'] ?? 'Toko PENA-UMKM';
        $owner_status_toko = $_SESSION['status_toko'] ?? 'buka';
        $owner_pajak_status = $_SESSION['pajak_status'] ?? 'nonaktif';
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, nama_toko, status_toko, pajak_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $hash, $role, $owner_nama_toko, $owner_status_toko, $owner_pajak_status]);
        $success = "User baru berhasil ditambahkan!";
    }
}

// Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id    = (int)$_POST['edit_id'];
    $nama  = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    
    // Cek email duplikat
    $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCek->execute([$email, $id]);
    if ($stmtCek->fetch()) {
        $error = "Email sudah digunakan oleh akun lain.";
    } else {
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $hash, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $role, $id]);
        }
        
        // Update session jika mengedit diri sendiri
        if ($id === $_SESSION['user_id']) {
            $_SESSION['nama'] = $nama;
            $_SESSION['role'] = $role;
        }
        
        $success = "User berhasil diperbarui!";
    }
}

// Hapus User
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id === $_SESSION['user_id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $success = "User berhasil dihapus!";
    }
}

// Ambil semua user
$stmt = $pdo->query("SELECT id, nama, email, role, created_at FROM users ORDER BY role ASC, nama ASC");
$userList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .btn-action {
            padding: 6px;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            cursor: pointer;
            color: var(--gray-700);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: var(--gray-100);
            border-color: var(--gray-400);
        }
        .btn-action-delete:hover {
            background: var(--danger-light);
            color: var(--danger);
            border-color: var(--danger);
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">PENA-UMKM</div>
    <div class="logo-sub">Merchant Admin</div>

    <nav>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="kelola-produk.php">📦 Kelola Produk</a>
        <a href="transaksi.php">🧾 Transaksi</a>
        <a href="laporan.php">📊 Laporan</a>
        <a href="dompet.php">💳 Dompet Toko</a>
        <a href="kelola-user.php" class="active" style="background:#3B82F6; color:white;">👥 Kelola User</a>
        <a href="pengaturan.php">⚙️ Pengaturan</a>
    </nav>

    <div style="margin-top:auto; display:flex; flex-direction:column; gap:8px;">
        <a href="../kasir/kasir.php" style="display:flex; align-items:center; justify-content:center; gap:8px; background:var(--primary); color:white; padding:10px; border-radius:8px; text-decoration:none; font-weight:500; font-size:0.9rem;">
            🖥️ Buka Kasir
        </a>
        <a href="../logout.php" style="display:flex; align-items:center; justify-content:center; gap:8px; border:1px solid var(--danger); color:var(--danger); padding:10px; border-radius:8px; text-decoration:none; font-weight:500; font-size:0.9rem; transition: background 0.2s;">
            🚪 Keluar / Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div style="display:flex; align-items:center;">
            <h2 style="font-size:1.2rem; font-weight:600;">Manajemen Pengguna</h2>
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

    <!-- HEADER TITLE -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Kelola User</h1>
            <p>Atur akun pengguna toko Anda (Owner/Admin dan Kasir) beserta hak aksesnya.</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <span>+</span> Tambah User
        </button>
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

    <!-- TABLE -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Role Akses</th>
                        <th>Tanggal Terdaftar</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userList as $u): 
                        $roleBadge = $u['role'] === 'owner' 
                            ? '<span class="badge badge-success">Owner/Admin</span>' 
                            : '<span class="badge badge-info">Kasir</span>';
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div class="topbar-avatar" style="width:32px; height:32px; font-size:0.8rem; font-weight:bold;">
                                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                    </div>
                                    <div style="font-weight:600; color:var(--gray-900);"><?= htmlspecialchars($u['nama']) ?></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $roleBadge ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <button class="btn-action" title="Edit User" onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['role'] ?>')">
                                        ✏️
                                    </button>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $u['id'] ?>" class="btn-action btn-action-delete" title="Hapus User" onclick="return confirm('Apakah Anda yakin ingin menghapus user <?= htmlspecialchars(addslashes($u['nama'])) ?>?')">
                                            🗑️
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php tampilkanFooter(); ?>

</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h3>Tambah User Baru</h3>
            <button onclick="closeAddModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action_add" value="1">
            <div class="modal-body">
                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama pengguna" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="contoh@toko.com" required>
                </div>
                <div class="input-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" placeholder="Minimal 8 karakter" minlength="8" required>
                </div>
                <div class="input-group">
                    <label>Role Akses</label>
                    <select name="role" required>
                        <option value="kasir">Kasir (Transaksi & POS saja)</option>
                        <option value="owner">Owner/Admin (Akses penuh manajemen)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Tambah User</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button onclick="closeEditModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action_edit" value="1">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="modal-body">
                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="input-group">
                    <label>Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Minimal 8 karakter" minlength="8">
                </div>
                <div class="input-group">
                    <label>Role Akses</label>
                    <select name="role" id="edit_role" required>
                        <option value="kasir">Kasir (Transaksi & POS saja)</option>
                        <option value="owner">Owner/Admin (Akses penuh manajemen)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    function openEditModal(id, nama, email, role) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

</body>
</html>
