<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketentuan Layanan - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 24px;
        }
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--gray-200);
        }
        .terms-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        .last-updated {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 32px;
            display: block;
        }
        .terms-section {
            margin-bottom: 28px;
        }
        .terms-section h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .terms-section p {
            font-size: 0.95rem;
            color: var(--gray-700);
            line-height: 1.6;
        }
        .terms-section ul {
            margin-top: 8px;
            margin-left: 20px;
            color: var(--gray-700);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .terms-section li {
            margin-bottom: 6px;
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
        <h1 class="terms-title">Ketentuan Layanan</h1>
        <span class="last-updated">Terakhir Diperbarui: 22 Juni 2026</span>
        
        <div class="terms-section">
            <p>Terima kasih telah menggunakan PENA-UMKM. Dengan mendaftarkan akun toko atau menggunakan layanan di platform kami, Anda menyetujui untuk terikat oleh Ketentuan Layanan berikut. Harap baca ketentuan ini dengan saksama sebelum mulai menggunakan sistem kami.</p>
        </div>

        <div class="terms-section">
            <h3>1. Pendaftaran Akun & Keamanan</h3>
            <p>Untuk menggunakan fitur lengkap PENA-UMKM, Anda diharuskan membuat akun merchant. Anda bertanggung jawab penuh atas:</p>
            <ul>
                <li>Menyediakan data pendaftaran yang akurat, lengkap, dan terbaru.</li>
                <li>Menjaga kerahasiaan kata sandi akun Anda (Admin/Owner dan Kasir).</li>
                <li>Semua aktivitas transaksi yang terjadi di bawah akun toko Anda.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h3>2. Penggunaan Layanan POS</h3>
            <p>Platform PENA-UMKM disediakan untuk mengelola stok barang, mencatat kasir, memproses pembayaran QRIS, dan melacak omset toko Anda. Pengguna dilarang keras menggunakan platform untuk:</p>
            <ul>
                <li>Mengunggah produk ilegal, senjata tajam, zat narkotika, atau barang/jasa lain yang melanggar hukum di Indonesia.</li>
                <li>Melakukan manipulasi data transaksi atau simulasi keuangan palsu yang merugikan pihak lain.</li>
                <li>Melakukan eksploitasi, hacking, atau percobaan bypass sistem keamanan webhook pembayaran.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h3>3. Pembayaran QRIS & Pencairan Dana (Disbursement)</h3>
            <p>PENA-UMKM memproses transaksi cashless via kode QRIS interaktif secara real-time. Ketentuan pencairan dana:</p>
            <ul>
                <li>Dana yang masuk ke Dompet Toko berasal dari pembayaran QRIS pelanggan yang sah dan terverifikasi oleh signature gateway.</li>
                <li>Admin/Owner dapat mengajukan pencairan dana ke rekening bank yang didaftarkan melalui menu Dompet Toko.</li>
                <li>Pencairan dana tunduk pada limit saldo minimum dan verifikasi bank operasional (sukses/gagalnya pencairan dikirimkan via notifikasi sistem dan email).</li>
            </ul>
        </div>

        <div class="terms-section">
            <h3>4. Pembatasan Tanggung Jawab (Demo / Simulasi)</h3>
            <p>Platform ini saat ini berada dalam status **Demo Mode** untuk keperluan verifikasi pengujian. Semua pengiriman email menggunakan sandbox lokal dan semua simulasi pembayaran QRIS / disbursement dana bank bersifat mock-up untuk demonstrasi operasional tanpa membebankan biaya keuangan riil.</p>
        </div>

        <div class="terms-section">
            <h3>5. Perubahan Ketentuan Layanan</h3>
            <p>PENA-UMKM berhak untuk mengubah atau memperbarui Ketentuan Layanan ini sewaktu-waktu. Kami akan memberikan notifikasi melalui sistem kepada merchant jika terdapat perubahan materiil pada ketentuan penggunaan platform.</p>
        </div>

        <div class="terms-section" style="border-top: 1px solid var(--gray-200); padding-top: 24px; margin-top: 32px; text-align: center;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">
                © 2026 PENA-UMKM. Kelompok 7 RPL C from Universitas Muhammadiyah Malang.<br>
                Made with ❤️ by Kelompok 7 RPL C
            </p>
        </div>
    </div>
</div>

</body>
</html>
