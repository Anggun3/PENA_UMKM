<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi - PENA-UMKM</title>
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
        .policy-title {
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
        .policy-section {
            margin-bottom: 28px;
        }
        .policy-section h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .policy-section p {
            font-size: 0.95rem;
            color: var(--gray-700);
            line-height: 1.6;
        }
        .policy-section ul {
            margin-top: 8px;
            margin-left: 20px;
            color: var(--gray-700);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .policy-section li {
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
        <h1 class="policy-title">Kebijakan Privasi</h1>
        <span class="last-updated">Terakhir Diperbarui: 22 Juni 2026</span>
        
        <div class="policy-section">
            <p>Selamat datang di PENA-UMKM. Kami sangat menghargai kepercayaan Anda sebagai merchant dan berkomitmen untuk melindungi informasi pribadi serta data operasional toko Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi Anda saat menggunakan platform Point of Sale (POS) dan layanan pengelolaan kasir kami.</p>
        </div>

        <div class="policy-section">
            <h3>1. Informasi yang Kami Kumpulkan</h3>
            <p>Kami mengumpulkan beberapa jenis informasi untuk menyediakan dan meningkatkan layanan kami kepada Anda:</p>
            <ul>
                <li><strong>Informasi Pendaftaran:</strong> Nama lengkap, alamat email bisnis, nama toko, dan kredensial akun Anda.</li>
                <li><strong>Data Transaksi:</strong> Informasi mengenai penjualan Anda, detail pembayaran (termasuk status transaksi QRIS dan pencairan dana), nilai transaksi, dan waktu transaksi.</li>
                <li><strong>Informasi Produk:</strong> Nama produk, harga, stok, limit stok minimum, dan foto produk yang Anda unggah ke sistem.</li>
                <li><strong>Data Karyawan (Kasir):</strong> Informasi akun kasir yang dibuat oleh Admin/Owner untuk operasional harian toko.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h3>2. Bagaimana Kami Menggunakan Informasi Anda</h3>
            <p>Kami menggunakan data yang dikumpulkan untuk tujuan berikut:</p>
            <ul>
                <li>Menyediakan, mengoperasikan, dan memelihara platform POS PENA-UMKM.</li>
                <li>Memproses pembayaran cashless secara aman dan memfasilitasi pencairan dana (disbursement) ke rekening toko Anda.</li>
                <li>Mengirimkan notifikasi penting seperti stok menipis, transaksi uang masuk, dan status pencairan dana (termasuk simulasi notifikasi email).</li>
                <li>Menampilkan laporan penjualan, analisis omset, dan mengunduh laporan berformat PDF/Excel untuk pembukuan Anda.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h3>3. Perlindungan & Keamanan Data</h3>
            <p>PENA-UMKM menggunakan langkah-langkah keamanan standar industri, termasuk enkripsi password satu arah menggunakan algoritma hashing bcrypt dan validasi tanda tangan digital (signature) pada setiap webhook transaksi QRIS untuk mencegah segala bentuk manipulasi data pembayaran atau bukti bayar palsu.</p>
        </div>

        <div class="policy-section">
            <h3>4. Pengungkapan kepada Pihak Ketiga</h3>
            <p>Kami tidak menjual, menyewakan, atau memperdagangkan data merchant Anda kepada pihak ketiga. Kami hanya membagikan data kepada penyedia layanan payment gateway mitra kami untuk keperluan pemrosesan pembayaran QRIS dan disbursement dana bank Anda.</p>
        </div>

        <div class="policy-section">
            <h3>5. Hak Merchant atas Data</h3>
            <p>Sebagai pengguna, Anda memiliki hak penuh untuk mengakses, memperbarui, atau menghapus informasi akun dan data produk Anda melalui halaman Pengaturan di dasbor Admin PENA-UMKM.</p>
        </div>

        <div class="policy-section" style="border-top: 1px solid var(--gray-200); padding-top: 24px; margin-top: 32px; text-align: center;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">
                © 2026 PENA-UMKM. Kelompok 7 RPL C from Universitas Muhammadiyah Malang.<br>
                Made with ❤️ by Kelompok 7 RPL C
            </p>
        </div>
    </div>
</div>

</body>
</html>
