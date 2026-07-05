<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PENA-UMKM - Platform POS & Manajemen Merchant Modern</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS landing page override untuk estetika super premium */
        html {
            scroll-behavior: smooth;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #EEFDF7 0%, #E6F4EA 100%);
            padding: 96px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: calc(100vh - 72px);
            overflow: hidden;
            position: relative;
        }

        .hero-content {
            flex: 1.2;
            max-width: 640px;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--gray-900);
            margin-bottom: 24px;
            letter-spacing: -0.03em;
        }

        .hero-title span {
            color: var(--primary);
            background: linear-gradient(120deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            line-height: 1.6;
            color: var(--gray-500);
            margin-bottom: 40px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .hero-visual {
            flex: 0.8;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 108, 71, 0.08);
            border: 1px solid rgba(0, 108, 71, 0.1);
            text-align: center;
            width: 100%;
            max-width: 380px;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 56px auto;
            padding: 0 24px;
        }

        .section-tag {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: var(--accent);
            margin-bottom: 12px;
            display: inline-block;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 48px;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--gray-100);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(0, 108, 71, 0.06);
            border-color: rgba(0, 108, 71, 0.15);
        }

        .feature-icon {
            font-size: 2rem;
            width: 56px;
            height: 56px;
            background: var(--primary-light);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            color: var(--primary);
        }

        .pricing-section {
            background: var(--gray-50);
            padding: 96px 48px;
            border-top: 1px solid var(--gray-100);
            border-bottom: 1px solid var(--gray-100);
        }

        .pricing-grid {
            display: flex;
            justify-content: center;
            gap: 32px;
            max-width: 900px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .price-card {
            background: white;
            border-radius: 24px;
            padding: 48px 32px;
            flex: 1;
            min-width: 280px;
            max-width: 400px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .price-card.premium {
            border: 2px solid var(--primary);
            box-shadow: 0 16px 32px rgba(0, 108, 71, 0.08);
            transform: scale(1.03);
        }

        .price-card.premium .badge-premium {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .price-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--gray-900);
            margin: 20px 0;
        }

        .price-value span {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-500);
        }

        .price-features {
            list-style: none;
            margin: 28px 0;
            padding: 0;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex: 1;
        }

        .price-features li {
            font-size: 0.9rem;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-features li::before {
            content: '✓';
            color: var(--primary);
            font-weight: 700;
        }

        .about-section {
            padding: 96px 48px;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        @media (max-width: 991px) {
            .hero-section {
                flex-direction: column;
                text-align: center;
                padding: 64px 24px;
                gap: 48px;
            }
            .hero-buttons {
                justify-content: center;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .features-grid {
                padding: 0 24px;
            }
            .price-card.premium {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav style="display:flex; justify-content:space-between; align-items:center; padding:16px 48px; background:white; border-bottom:1px solid #E5E7EB; position: sticky; top:0; z-index:100; backdrop-filter: blur(8px); background: rgba(255, 255, 255, 0.95);">
    <a href="index.php" style="text-decoration:none; font-size:1.2rem; font-weight:700; color:#1B6B4A; display:flex; align-items:center; gap:8px;">
        🏪 PENA-UMKM
    </a>
    <div style="display:flex; gap:32px; align-items:center;">
        <a href="index.php" style="text-decoration:none; color:#374151; font-size:0.9rem; font-weight: 500;">Home</a>
        <a href="#fitur" style="text-decoration:none; color:#374151; font-size:0.9rem; font-weight: 500;">Fitur</a>
        <a href="#harga" style="text-decoration:none; color:#374151; font-size:0.9rem; font-weight: 500;">Harga</a>
        <a href="#tentang" style="text-decoration:none; color:#374151; font-size:0.9rem; font-weight: 500;">Tentang</a>
        <a href="login.php" style="padding:8px 24px; background:#1B6B4A; color:white; border-radius:20px; text-decoration:none; font-size:0.9rem; font-weight:600; box-shadow: 0 4px 10px rgba(0,108,71,0.15); transition: background 0.2s;" onmouseover="this.style.background='#005638'" onmouseout="this.style.background='#1B6B4A'">Login</a>
    </div>
</nav>

<!-- HERO SECTION -->
<header class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Tumbuh Lebih Besar dengan <span>PENA-UMKM</span></h1>
        <p class="hero-subtitle">Aplikasi Point of Sale (POS) dan dompet merchant modern yang dibuat khusus untuk mempermudah operasional usaha kecil, mikro, dan menengah di Indonesia. Kelola stok barang, terima pembayaran QRIS instan, dan catat omset secara otomatis.</p>
        <div class="hero-buttons">
            <a href="register.php" class="btn btn-primary" style="padding:14px 28px; font-size:1rem; font-weight:600;">Daftar Sekarang (Gratis) →</a>
            <a href="login.php" class="btn btn-outline" style="padding:14px 28px; font-size:1rem; font-weight:600; background:white;">Masuk ke Akun</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-card">
            <div style="font-size:4.5rem; margin-bottom:16px;">☕</div>
            <h3 style="color:#006C47; font-size:1.4rem; margin-bottom:8px;">PENA-Coffee Shop</h3>
            <p style="font-size:0.85rem; color:var(--gray-500);">Uang Digital Berhasil Cair</p>
            <div style="background:#ECFDF5; color:#047857; padding:8px 16px; border-radius:20px; font-size:0.9rem; font-weight:700; margin:16px auto 0 auto; display:inline-block; border:1px solid #A7F3D0;">
                + Rp 1.450.000 (QRIS)
            </div>
        </div>
    </div>
</header>

<!-- FITUR SECTION -->
<section id="fitur" style="padding:96px 0;">
    <div class="section-header">
        <span class="section-tag">Fitur Unggulan</span>
        <h2>Semua Alat untuk Mengelola Bisnismu Secara Digital</h2>
        <p style="margin-top:12px;">Sistem fungsional lengkap yang dirancang khusus untuk kenyamanan dan keamanan operasional tokomu.</p>
    </div>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🖥️</div>
            <h3 style="margin-bottom:12px;">Kasir POS Cepat</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Proses transaksi penjualan kasir yang instan, input keranjang intuitif, kalkulasi subtotal otomatis, dan struk digital siap cetak.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3 style="margin-bottom:12px;">QRIS Dinamis & Validasi</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Hasilkan QRIS dinamis langsung per transaksi. Pembayaran cashless terkonfirmasi real-time dengan pengaman enkripsi MD5.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚠️</div>
            <h3 style="margin-bottom:12px;">Peringatan Stok Menipis</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Notifikasi terintegrasi otomatis dan email real-time langsung ke kotak masuk Anda jika stok produk mendekati batas minimum limit.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h3 style="margin-bottom:12px;">Tarik Saldo Instan</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Kumpulkan pembayaran digital di Dompet Merchant Anda, lalu cairkan dana ke rekening bank pilihan Anda kapan saja secara aman.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3 style="margin-bottom:12px;">Ekspor Laporan Keuangan</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Ekspor seluruh riwayat transaksi penjualan dan performa keuangan harian atau bulanan Anda ke format file Excel (.csv) atau PDF.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👥</div>
            <h3 style="margin-bottom:12px;">Pengaturan Akun Kasir</h3>
            <p style="font-size:0.85rem; line-height:1.5;">Kelola banyak staf kasir toko Anda dengan hak akses yang terproteksi dan sinkronisasi informasi status operasional toko.</p>
        </div>
    </div>
</section>

<!-- PRICING SECTION -->
<section id="harga" class="pricing-section">
    <div class="section-header">
        <span class="section-tag">Paket Harga</span>
        <h2>Harga Transparan, Bersahabat untuk UMKM</h2>
        <p style="margin-top:12px;">Mulai gratis sekarang dan tingkatkan ke paket Pro seiring pertumbuhan usahamu.</p>
    </div>
    
    <div class="pricing-grid">
        <div class="price-card">
            <h3 style="color:var(--gray-900); font-size:1.25rem;">Rintisan (Free Plan)</h3>
            <p style="font-size:0.8rem; color:var(--gray-500); margin-top:4px;">Untuk usaha mikro/pemula</p>
            <div class="price-value">Rp 0 <span>/ selamanya</span></div>
            <a href="register.php" class="btn btn-outline" style="background:white; border-color:var(--primary); color:var(--primary);">Mulai Gratis →</a>
            <ul class="price-features">
                <li>1 Toko Merchant</li>
                <li>Kasir POS Mandiri</li>
                <li>Hingga 5 Staf Kasir</li>
                <li>Bebas PPN (Toggle Nonaktif)</li>
                <li>Pembayaran QRIS Dinamis</li>
                <li>Laporan Ringkasan Standar</li>
            </ul>
        </div>
        
        <div class="price-card premium">
            <div class="badge-premium">Rekomendasi</div>
            <h3 style="color:var(--primary); font-size:1.25rem;">Pedagang (Pro Plan)</h3>
            <p style="font-size:0.8rem; color:var(--gray-500); margin-top:4px;">Untuk bisnis berkembang & retail</p>
            <div class="price-value">Rp 49.000 <span>/ bulan</span></div>
            <a href="register.php" class="btn btn-primary">Daftar Pro Merchant →</a>
            <ul class="price-features">
                <li>Semua fitur paket Rintisan</li>
                <li>Multi-Cabang Toko</li>
                <li>Staf Kasir Tanpa Batas</li>
                <li>Analisis Laporan Laba Rugi</li>
                <li>Notifikasi Email Instan</li>
                <li>Dukungan Pelanggan 24/7</li>
            </ul>
        </div>
    </div>
</section>

<!-- ABOUT SECTION -->
<section id="tentang" class="about-section">
    <span class="section-tag">Tentang Kami</span>
    <h2 style="margin-bottom:24px;">Misi Mendukung Digitalisasi Nusantara</h2>
    <p style="font-size:1rem; line-height:1.7; color:var(--gray-500); max-width:680px; margin:0 auto;">
        Kami percaya bahwa tulang punggung ekonomi Indonesia ada pada sektor UMKM. Melalui platform **PENA-UMKM**, kami mendedikasikan teknologi manajemen toko yang andal, cepat, dan modern agar pelaku usaha di Indonesia dapat beralih ke transaksi digital cashless secara mulus, aman, dan tanpa biaya operasional yang memberatkan.
    </p>
</section>

<!-- FOOTER -->
<footer style="display:flex; justify-content:space-between; align-items:center; padding:32px 48px; background:white; border-top:1px solid #E5E7EB; flex-wrap:wrap; gap:16px;">
    <div>
        <div style="font-weight:700; color:#1B6B4A; font-size:1.1rem; margin-bottom:4px;">PENA-UMKM</div>
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