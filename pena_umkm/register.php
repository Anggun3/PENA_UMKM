<?php
session_start();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/myfunc.php';

if (isset($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
$success = '';
$step = 1;

$nama      = '';
$email     = '';
$nama_toko = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $nama_toko = trim($_POST['nama_toko'] ?? '');

    // Cek email sudah ada atau belum
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $error = 'Email sudah terdaftar, silakan login.';
        $step = 2;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, nama_toko) VALUES (?, ?, ?, 'owner', ?)");
        $stmt->execute([$nama, $email, $hash, $nama_toko]);

        $success = 'Akun berhasil dibuat! Silakan login.';
        $nama = '';
        $email = '';
        $nama_toko = '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - PENA-UMKM</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav style="display:flex; justify-content:space-between; align-items:center; padding:16px 48px; background:white; border-bottom:1px solid #E5E7EB;">
    <a href="index.php" style="text-decoration:none; font-size:1.2rem; font-weight:700; color:#1B6B4A;">PENA-UMKM</a>
    <div style="display:flex; gap:16px; align-items:center;">
        <span style="font-size:0.9rem; color:#6B7280;">Sudah punya akun?</span>
        <a href="login.php" style="color:#0D9488; font-weight:500; text-decoration:none; font-size:0.9rem;">Masuk Sekarang</a>
    </div>
</nav>

<!-- MAIN -->
<div style="display:flex; min-height:calc(100vh - 65px);">

    <!-- KIRI -->
    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; padding:48px 64px;">
        <h1 style="color:#1B6B4A; font-size:2.5rem; margin-bottom:16px;">Tumbuhkan Bisnis<br>Anda Bersama<br>Kami.</h1>
        <p style="margin-bottom:32px; max-width:360px;">Bergabunglah dengan ribuan pelaku UMKM lainnya yang telah mendigitalisasi transaksi mereka dengan aman dan mudah.</p>

        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:32px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:1.2rem;">✅</span>
                <span style="font-size:0.9rem; font-weight:500;">Verifikasi Cepat & Aman</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:1.2rem;">📲</span>
                <span style="font-size:0.9rem; font-weight:500;">Mendukung Semua Metode QRIS</span>
            </div>
        </div>

        <div style="width:300px; height:200px; background:#E8F5EE; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:5rem;">
            🏪
        </div>
    </div>

    <!-- KANAN: Form Register -->
    <div style="flex:1; display:flex; align-items:center; justify-content:center; padding:48px;">
        <div class="card" style="width:100%; max-width:440px; padding:40px;">

            <!-- Step indicator -->
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:24px;">
                <div id="circle-1" style="width:32px; height:32px; background:#1B6B4A; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:600; transition: all 0.3s;">1</div>
                <div style="flex:1; height:2px; background:#E5E7EB;"></div>
                <div id="circle-2" style="width:32px; height:32px; background:#E5E7EB; color:#9CA3AF; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:600; transition: all 0.3s;">2</div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                <span id="label-1" style="font-size:0.8rem; font-weight:500; color:#1B6B4A; transition: all 0.3s;">Detail Toko</span>
                <span id="label-2" style="font-size:0.8rem; color:#9CA3AF; transition: all 0.3s;">Informasi Akun</span>
            </div>

            <h2 id="register-title" style="margin-bottom:4px;">Detail Toko Anda</h2>
            <p id="register-desc" style="margin-bottom:24px;">Masukkan nama toko atau UMKM Anda untuk memulai.</p>

            <?php if ($error): ?>
                <div style="background:#FEE2E2; color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background:#D1FAE5; color:#065F46; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem;">
                    <?= $success ?> <a href="login.php" style="color:#1B6B4A; font-weight:600;">Login sekarang →</a>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" onsubmit="return validateForm()">
                <!-- STEP 1: DETAIL TOKO -->
                <div id="step-1-fields">
                    <div class="input-group">
                        <label>Nama Toko / UMKM</label>
                        <input type="text" name="nama_toko" id="nama_toko_input" value="<?= htmlspecialchars($nama_toko) ?>" placeholder="Contoh: Toko Berkah Jaya" required>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="goToStep2()" style="margin-top:8px; width:100%;">Lanjut ke Informasi Akun →</button>
                </div>

                <!-- STEP 2: INFORMASI AKUN -->
                <div id="step-2-fields" style="display:none;">
                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" id="nama_input" value="<?= htmlspecialchars($nama) ?>" placeholder="Masukkan nama sesuai KTP" required>
                    </div>

                    <div class="input-group">
                        <label>Email Bisnis</label>
                        <input type="email" name="email" id="email_input" value="<?= htmlspecialchars($email) ?>" placeholder="contoh@bisnis.com" required>
                    </div>

                    <div class="input-group">
                        <label>Kata Sandi</label>
                        <input type="password" name="password" id="password_input" placeholder="Minimal 8 karakter" minlength="8" required>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:8px;">
                        <button type="button" class="btn btn-outline" onclick="goToStep1()" style="flex:1;">← Kembali</button>
                        <button type="submit" class="btn btn-primary" style="flex:2;">Daftar Sekarang</button>
                    </div>
                </div>
            </form>
        </div>
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

<script>
    const currentStep = <?= $step ?>;
    
    function goToStep2() {
        const namaToko = document.getElementById('nama_toko_input').value.trim();
        if (!namaToko) {
            alert('Silakan isi Nama Toko / UMKM terlebih dahulu.');
            return;
        }
        
        // Hide step 1, show step 2
        document.getElementById('step-1-fields').style.display = 'none';
        document.getElementById('step-2-fields').style.display = 'block';
        
        // Update title/description
        document.getElementById('register-title').innerText = 'Informasi Akun';
        document.getElementById('register-desc').innerText = 'Lengkapi data diri Anda untuk membuat akun owner.';
        
        // Update indicator
        const circle1 = document.getElementById('circle-1');
        circle1.style.background = '#D1FAE5';
        circle1.style.color = '#065F46';
        circle1.innerHTML = '✓';
        
        const circle2 = document.getElementById('circle-2');
        circle2.style.background = '#1B6B4A';
        circle2.style.color = 'white';
        
        document.getElementById('label-1').style.color = '#9CA3AF';
        document.getElementById('label-2').style.color = '#1B6B4A';
        document.getElementById('label-2').style.fontWeight = '600';
    }
    
    function goToStep1() {
        // Show step 1, hide step 2
        document.getElementById('step-1-fields').style.display = 'block';
        document.getElementById('step-2-fields').style.display = 'none';
        
        // Update title/description
        document.getElementById('register-title').innerText = 'Detail Toko Anda';
        document.getElementById('register-desc').innerText = 'Masukkan nama toko atau UMKM Anda untuk memulai.';
        
        // Update indicator
        const circle1 = document.getElementById('circle-1');
        circle1.style.background = '#1B6B4A';
        circle1.style.color = 'white';
        circle1.innerHTML = '1';
        
        const circle2 = document.getElementById('circle-2');
        circle2.style.background = '#E5E7EB';
        circle2.style.color = '#9CA3AF';
        
        document.getElementById('label-1').style.color = '#1B6B4A';
        document.getElementById('label-1').style.fontWeight = '600';
        document.getElementById('label-2').style.color = '#9CA3AF';
        document.getElementById('label-2').style.fontWeight = 'normal';
    }
    
    function validateForm() {
        const nama = document.getElementById('nama_input').value.trim();
        const email = document.getElementById('email_input').value.trim();
        const password = document.getElementById('password_input').value;
        
        if (!nama || !email || !password) {
            alert('Silakan lengkapi semua kolom informasi akun.');
            return false;
        }
        if (password.length < 8) {
            alert('Kata sandi harus minimal 8 karakter.');
            return false;
        }
        return true;
    }
    
    // Auto load to step 2 if server returned error
    document.addEventListener("DOMContentLoaded", function() {
        if (currentStep === 2) {
            goToStep2();
        }
    });
</script>

</body>
</html>