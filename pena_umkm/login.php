<?php
session_start();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/myfunc.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/kasir.php');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['nama']        = $user['nama'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['nama_toko']   = $user['nama_toko'];
        $_SESSION['status_toko'] = $user['status_toko'];
        $_SESSION['pajak_status'] = $user['pajak_status'];
        
        if ($user['role'] === 'kasir') {
            header('Location: kasir/kasir.php');
        } else {
            header('Location: admin/dashboard.php');
        }
        exit;
    } else {
        $error = 'Email atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PENA-UMKM</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav style="display:flex; justify-content:space-between; align-items:center; padding:16px 48px; background:white; border-bottom:1px solid #E5E7EB;">
    <a href="index.php" style="text-decoration:none; font-size:1.2rem; font-weight:700; color:#1B6B4A;">PENA-UMKM</a>
    <div style="display:flex; gap:32px; align-items:center;">
        <a href="index.php" style="text-decoration:none; color:#374151; font-size:0.9rem;">Home</a>
        <a href="index.php#fitur" style="text-decoration:none; color:#374151; font-size:0.9rem;">Fitur</a>
        <a href="index.php#harga" style="text-decoration:none; color:#374151; font-size:0.9rem;">Harga</a>
        <a href="index.php#tentang" style="text-decoration:none; color:#374151; font-size:0.9rem;">Tentang</a>
        <a href="login.php" style="padding:8px 20px; background:#1B6B4A; color:white; border-radius:20px; text-decoration:none; font-size:0.9rem;">Login</a>
    </div>
</nav>

<!-- MAIN -->
<div style="display:flex; min-height:calc(100vh - 65px);">

    <!-- KIRI: Ilustrasi -->
    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:48px; background:#EEF2FF;">
        <div style="background:white; border-radius:16px; padding:32px; text-align:center; max-width:320px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
            <h3 style="color:#1B6B4A; margin-bottom:8px;">UMKM Login</h3>
            <div style="width:180px; height:180px; background:#E8F5EE; border-radius:12px; margin:16px auto; display:flex; align-items:center; justify-content:center; font-size:4rem;">🏪</div>
            <h4 style="color:#1B6B4A; font-size:1rem;">Kelola Stok & Terima<br>Pembayaran QRIS Lebih Cepat.</h4>
            <p style="font-size:0.8rem; margin-top:8px;">Solusi digital modern untuk UMKM Indonesia tumbuh lebih besar.</p>
        </div>
    </div>

    <!-- KANAN: Form Login -->
    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; padding:48px 64px;">
        <h1 style="margin-bottom:8px;">Selamat Datang Kembali!</h1>
        <p style="margin-bottom:32px;">Silakan masuk untuk mengelola toko Anda hari ini.</p>

        <?php if ($error): ?>
            <div style="background:#FEE2E2; color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Email atau Username</label>
                <input type="email" name="email" placeholder="Masukkan email Anda" required>
            </div>

            <div class="input-group">
                <label>Kata Sandi <a href="#" style="float:right; color:#0D9488; font-size:0.8rem;">Lupa Kata Sandi?</a></label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div style="display:flex; align-items:center; gap:8px; margin-bottom:24px;">
                <input type="checkbox" id="ingat" name="ingat">
                <label for="ingat" style="font-size:0.9rem;">Ingat Saya</label>
            </div>

            <button type="submit" class="btn btn-primary">Masuk ke Dashboard →</button>
        </form>

        <div style="text-align:center; margin-top:24px; color:#6B7280; font-size:0.85rem;">
            ── ATAU ──
        </div>
        <p style="text-align:center; margin-top:16px; font-size:0.9rem;">
            Belum punya akun? <a href="register.php" style="color:#0D9488; font-weight:500;">Daftar Sekarang</a>
        </p>
    </div>
</div>

<!-- FOOTER -->
<footer style="display:flex; justify-content:space-between; align-items:center; padding:20px 48px; background:white; border-top:1px solid #E5E7EB;">
    <div>
        <div style="font-weight:700; color:#1B6B4A;">PENA-UMKM</div>
        <div style="font-size:0.75rem; color:#6B7280;">© 2026 PENA-UMKM. Kelompok 7 RPL C from Universitas Muhammadiyah Malang.<br><span style="font-size:0.75rem; color:#6B7280;">Made with ❤️ by Kelompok 7 RPL C</span></div>
    </div>
    <div style="display:flex; gap:24px;">
        <a href="info/privacy-policy.php" style="text-decoration:none; color:#6B7280; font-size:0.85rem;">Kebijakan Privasi</a>
        <a href="info/terms-of-service.php" style="text-decoration:none; color:#6B7280; font-size:0.85rem;">Ketentuan Layanan</a>
        <a href="info/help-center.php" style="text-decoration:none; color:#6B7280; font-size:0.85rem;">Pusat Bantuan</a>
        <a href="info/contact-us.php" style="text-decoration:none; color:#6B7280; font-size:0.85rem;">Kontak Kami</a>
    </div>
</footer>

</body>
</html>
