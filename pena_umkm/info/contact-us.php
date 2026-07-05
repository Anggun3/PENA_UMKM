<?php
session_start();
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
        }
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--gray-200);
        }
        .info-card {
            background: linear-gradient(135deg, #1B6B4A 0%, #005638 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        .subtitle {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 32px;
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .info-block {
            margin-bottom: 24px;
        }
        .info-block h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #E6F4EA;
        }
        .info-block p {
            font-size: 0.9rem;
            color: #C2F0D8;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav style="display:flex; justify-content:space-between; align-items:center; padding:16px 48px; background:white; border-bottom:1px solid #E5E7EB; position: sticky; top:0; z-index:100; backdrop-filter: blur(8px); background: rgba(255, 255, 255, 0.95);">
    <a href="../index.php" style="text-decoration:none; font-size:1.2rem; font-weight:700; color:#1B6B4A; display:flex; align-items:center; gap:8px;">
        🏪 PENA-UMKM
    </a>
    <div>
        <a href="../index.php" class="btn btn-outline" style="padding:8px 20px; font-size:0.85rem; font-weight:600;">← Kembali ke Beranda</a>
    </div>
</nav>

<div class="container">
    <div class="content-card">
        <h1 class="title">Hubungi Kami</h1>
        <span class="subtitle">Kirimkan pesan Anda secara langsung kepada kami dan tim kami akan segera merespons.</span>

        <?php if ($submitted): ?>
            <div style="background:#D1FAE5; color:#065F46; padding:16px; border-radius:12px; margin-bottom:24px; font-size:0.95rem; font-weight:500;">
                🎉 Terima kasih! Pesan Anda telah berhasil dikirimkan ke Kelompok 7 RPL C. Kami akan membalas via email secepatnya.
            </div>
        <?php endif; ?>

        <form action="contact-us.php" method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap Anda" required>
            </div>
            
            <div class="form-group">
                <label>Email Bisnis</label>
                <input type="email" name="email" placeholder="contoh@bisnis.com" required>
            </div>

            <div class="form-group">
                <label>Subjek Pesan</label>
                <input type="text" name="subjek" placeholder="Contoh: Pertanyaan Kemitraan / Demo Fitur" required>
            </div>

            <div class="form-group">
                <label>Isi Pesan</label>
                <textarea name="pesan" rows="5" placeholder="Tuliskan pesan Anda secara detail..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600;">Kirim Pesan Sekarang</button>
        </form>
    </div>

    <div class="info-card">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 24px; color: white;">Informasi Kontak</h2>
            
            <div class="info-block">
                <h4>Pengembang</h4>
                <p>Kelompok 7 RPL C<br>Universitas Muhammadiyah Malang</p>
            </div>

            <div class="info-block">
                <h4>Alamat Kampus</h4>
                <p>Jl. Raya Tlogomas No. 246, Babatan, Kec. Lowokwaru, Kota Malang, Jawa Timur 65144</p>
            </div>

            <div class="info-block">
                <h4>Email Resmi</h4>
                <p>kelompok7rplc@webmail.umm.ac.id</p>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 20px; font-size: 0.8rem; color: #A7F3D0; line-height: 1.4;">
            © 2026 PENA-UMKM.<br>
            Kelompok 7 RPL C from Universitas Muhammadiyah Malang.
        </div>
    </div>
</div>

</body>
</html>
