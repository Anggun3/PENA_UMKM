<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan - PENA-UMKM</title>
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
        .help-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
            text-align: center;
        }
        .help-subtitle {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 40px;
            text-align: center;
            display: block;
        }
        .faq-item {
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 0;
        }
        .faq-item:last-child {
            border-bottom: none;
        }
        .faq-question {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--gray-900);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        .faq-question::after {
            content: '▼';
            font-size: 0.8rem;
            color: var(--gray-400);
            transition: transform 0.2s;
        }
        .faq-item.active .faq-question::after {
            transform: rotate(-180deg);
            color: var(--primary);
        }
        .faq-answer {
            font-size: 0.925rem;
            color: var(--gray-600);
            line-height: 1.6;
            margin-top: 12px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .faq-item.active .faq-answer {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
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
        <h1 class="help-title">Pusat Bantuan</h1>
        <span class="help-subtitle">Temukan jawaban atas pertanyaan umum seputar platform PENA-UMKM</span>

        <div style="margin-top: 16px;">
            
            <div class="faq-item">
                <div class="faq-question">Bagaimana cara menambahkan produk baru ke toko saya?</div>
                <div class="faq-answer">
                    Anda dapat masuk ke dasbor **Admin/Owner**, lalu navigasikan ke menu **Kelola Produk** di sidebar kiri. Klik tombol **Tambah Produk Baru**, isi formulir secara lengkap (Nama Produk, Harga Jual, Harga Modal, Stok Awal, Stok Minimum Limit, dan Foto Produk), lalu simpan. Produk Anda akan langsung tersedia untuk ditransaksikan di halaman Kasir.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Bagaimana cara menerima pembayaran QRIS dari pelanggan?</div>
                <div class="faq-answer">
                    Saat kasir memproses transaksi belanja, pilih metode pembayaran **QRIS** pada modal pembayaran, lalu klik **Selesaikan Transaksi**. Sistem akan secara otomatis memunculkan Kode QRIS digital. Pelanggan dapat memindai kode tersebut dengan aplikasi e-wallet apa pun (GoPay, OVO, ShopeePay, Dana, LinkAja, atau Mobile Banking). Setelah status sukses diterima dari payment gateway, kasir akan menerima pop-up konfirmasi dan stok barang langsung terpotong secara otomatis.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Bagaimana cara mencairkan dana (disbursement) dari Dompet Toko?</div>
                <div class="faq-answer">
                    Seluruh pembayaran pelanggan via QRIS akan masuk ke akumulasi saldo **Dompet Toko** Anda. Untuk mencairkannya, masuk ke dasbor Admin, klik menu **Dompet Toko**, lalu isi nominal pencairan dan bank tujuan Anda. Klik **Ajukan Pencairan Dana**. Permintaan pencairan akan diverifikasi secara otomatis dan Anda akan mendapat notifikasi status pencairan (sukses/gagal) di sistem notifikasi serta email toko Anda.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Bagaimana alur masuk (login) untuk kasir toko?</div>
                <div class="faq-answer">
                    Owner/Admin harus membuatkan akun kasir terlebih dahulu melalui menu **Kelola User** di dasbor Admin. Kasir yang sudah didaftarkan dapat masuk ke aplikasi dengan membuka halaman **Login**, memasukkan email kasir dan kata sandi mereka. Setelah berhasil masuk, kasir akan langsung diarahkan ke halaman kasir khusus untuk operasional harian kasir tanpa memiliki akses ke laporan laba-rugi, dompet toko, atau pengaturan merchant.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Mengapa notifikasi email tidak terkirim ke inbox email eksternal saya?</div>
                <div class="faq-answer">
                    Karena server lokal (XAMPP secara default) tidak terhubung dengan server SMTP eksternal untuk pengiriman internet riil, sistem kami menyiasatinya dengan menyimpan salinan email dalam file HTML interaktif di folder **`emails/`** proyek Anda. Anda dapat langsung membuka folder tersebut di komputer server Anda untuk melihat persis email notifikasi transaksi masuk, pencairan dana, atau stok kritis yang dikirimkan.
                </div>
            </div>

        </div>

        <div class="policy-section" style="border-top: 1px solid var(--gray-200); padding-top: 24px; margin-top: 32px; text-align: center;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">
                Tidak menemukan jawaban? Silakan hubungi kami melalui halaman <a href="contact-us.php" style="color:var(--primary); font-weight:600; text-decoration:none;">Hubungi Kami</a>.
            </p>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.faq-question').forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            parent.classList.toggle('active');
        });
    });
</script>

</body>
</html>
