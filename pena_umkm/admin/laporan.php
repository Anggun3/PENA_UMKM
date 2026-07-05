<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner');

// Filter rentang waktu
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Query metrik untuk bulan & tahun terpilih
$stmt = $pdo->prepare("
    SELECT SUM(total) as omset, COUNT(*) as jml_trx 
    FROM transaksi 
    WHERE status='berhasil' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
");
$stmt->execute([$bulan, $tahun]);
$stats = $stmt->fetch();

$omset = $stats['omset'] ?? 0;
$jmlTrx = $stats['jml_trx'] ?? 0;
$rataRata = $jmlTrx > 0 ? round($omset / $jmlTrx) : 0;

// Hitung transaksi dibatalkan
$stmtBatal = $pdo->prepare("
    SELECT COUNT(*) 
    FROM transaksi 
    WHERE status='batal' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
");
$stmtBatal->execute([$bulan, $tahun]);
$jmlBatal = $stmtBatal->fetchColumn() ?? 0;

// Produk terlaris di periode ini
$stmtProduk = $pdo->prepare("
    SELECT p.nama, p.kategori, SUM(dt.qty) as total_terjual, SUM(dt.qty * dt.harga_satuan) as total_omset
    FROM detail_transaksi dt
    JOIN produk p ON dt.produk_id = p.id
    JOIN transaksi t ON dt.transaksi_id = t.id
    WHERE t.status = 'berhasil' AND MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?
    GROUP BY p.id, p.nama, p.kategori
    ORDER BY total_terjual DESC
    LIMIT 5
");
$stmtProduk->execute([$bulan, $tahun]);
$terlaris = $stmtProduk->fetchAll();

// Penjualan per kategori
$stmtKategori = $pdo->prepare("
    SELECT p.kategori, SUM(dt.qty) as total_qty, SUM(dt.qty * dt.harga_satuan) as omset_kategori
    FROM detail_transaksi dt
    JOIN produk p ON dt.produk_id = p.id
    JOIN transaksi t ON dt.transaksi_id = t.id
    WHERE t.status = 'berhasil' AND MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?
    GROUP BY p.kategori
    ORDER BY omset_kategori DESC
");
$stmtKategori->execute([$bulan, $tahun]);
$kategoriStats = $stmtKategori->fetchAll();

// Cari nilai maksimal untuk persentase bar chart
$maxTerjual = count($terlaris) > 0 ? max(array_column($terlaris, 'total_terjual')) : 1;
$maxOmsetKategori = count($kategoriStats) > 0 ? max(array_column($kategoriStats, 'omset_kategori')) : 1;

// List nama bulan Indonesian
$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: 2fr 1.3fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .stats-bar {
            background: var(--gray-100);
            border-radius: 8px;
            height: 10px;
            overflow: hidden;
            margin-top: 8px;
            width: 100%;
        }
        .stats-progress {
            background: var(--primary);
            height: 100%;
            border-radius: 8px;
        }
        .kategori-progress {
            background: #0D9488;
            height: 100%;
            border-radius: 8px;
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
        <a href="laporan.php" class="active" style="background:#3B82F6; color:white;">📊 Laporan</a>
        <a href="dompet.php">💳 Dompet Toko</a>
        <a href="kelola-user.php">👥 Kelola User</a>
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
            <h2 style="font-size:1.2rem; font-weight:600;">Laporan Performa Bisnis</h2>
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
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Laporan Penjualan</h1>
            <p>Melihat performa bulanan toko Anda dalam metrik penjualan terperinci.</p>
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <!-- MONTH YEAR SELECTOR -->
            <form action="" method="GET" id="reportFilter" style="display:flex; gap:8px; background:white; padding:6px 12px; border-radius:20px; border:1px solid var(--gray-200); font-size:0.85rem;">
                <span>📅 Periode:</span>
                <select name="bulan" onchange="document.getElementById('reportFilter').submit()" style="border:none; outline:none; font-size:0.85rem; cursor:pointer; font-family:inherit; font-weight:500;">
                    <?php foreach ($namaBulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" onchange="document.getElementById('reportFilter').submit()" style="border:none; outline:none; font-size:0.85rem; cursor:pointer; font-family:inherit; font-weight:500;">
                    <?php 
                    $startYear = date('Y') - 2;
                    $endYear = date('Y') + 1;
                    for ($y = $startYear; $y <= $endYear; $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <!-- EXPORT BUTTONS (KF-07) -->
            <a href="ekspor-laporan.php?format=excel&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-outline" style="padding:8px 16px; font-size:0.85rem; font-weight:600;">
                📊 Ekspor Excel
            </a>
            <a href="ekspor-laporan.php?format=pdf&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="btn btn-primary" style="padding:8px 16px; font-size:0.85rem; font-weight:600;">
                📄 Cetak PDF
            </a>
        </div>
    </div>

    <!-- METRIC CARDS -->
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px;">Total Omset</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--primary);"><?= formatRupiah($omset) ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Pendapatan bersih berhasil</div>
        </div>
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px;">Total Transaksi</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--gray-900);"><?= $jmlTrx ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Selesai diproses</div>
        </div>
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px;">Rata-rata Keranjang</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--gray-900);"><?= formatRupiah($rataRata) ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Nilai belanja per transaksi</div>
        </div>
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px;">Transaksi Batal</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--danger);"><?= $jmlBatal ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Jumlah refund/batal</div>
        </div>
    </div>

    <!-- MAIN REPORT LAYOUT -->
    <div class="report-grid">
        
        <!-- LEFT: Best Selling Products -->
        <div class="card" style="padding:24px 24px 8px 24px;">
            <h3 style="margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <span>🔥</span> Produk Terlaris Periode Ini
            </h3>
            
            <?php if (empty($terlaris)): ?>
                <div class="text-center" style="padding:48px 0; color:var(--gray-500);">
                    <div style="font-size:3rem; margin-bottom:12px;">📊</div>
                    <p>Belum ada data penjualan pada periode ini.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:20px;">
                    <?php foreach ($terlaris as $p): 
                        $pct = round(($p['total_terjual'] / $maxTerjual) * 100);
                    ?>
                        <div>
                            <div class="flex-between">
                                <div>
                                    <span style="font-weight:600; color:var(--gray-900); font-size:0.9rem;"><?= htmlspecialchars($p['nama']) ?></span>
                                    <span style="font-size:0.75rem; color:var(--gray-500); margin-left:8px;">(<?= htmlspecialchars($p['kategori']) ?>)</span>
                                </div>
                                <span style="font-weight:600; font-size:0.875rem; color:var(--gray-900);"><?= $p['total_terjual'] ?> Terjual</span>
                            </div>
                            <div class="stats-bar">
                                <div class="stats-progress" style="width: <?= $pct ?>%;"></div>
                            </div>
                            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px; text-align:right;">
                                Omset: <?= formatRupiah($p['total_omset']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Sales per Category -->
        <div class="card">
            <h3 style="margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <span>📂</span> Penjualan per Kategori
            </h3>

            <?php if (empty($kategoriStats)): ?>
                <div class="text-center" style="padding:48px 0; color:var(--gray-500);">
                    <p>Tidak ada kategori.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <?php foreach ($kategoriStats as $k): 
                        $pctKat = round(($k['omset_kategori'] / $maxOmsetKategori) * 100);
                    ?>
                        <div>
                            <div class="flex-between" style="font-size:0.875rem;">
                                <span style="font-weight:600; color:var(--gray-900);"><?= htmlspecialchars($k['kategori']) ?></span>
                                <span style="font-weight:600; color:var(--primary);"><?= formatRupiah($k['omset_kategori']) ?></span>
                            </div>
                            <div class="stats-bar">
                                <div class="kategori-progress" style="width: <?= $pctKat ?>%;"></div>
                            </div>
                            <div class="flex-between" style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">
                                <span><?= $k['total_qty'] ?> Pcs terjual</span>
                                <span><?= $pctKat ?>% kontribusi</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php tampilkanFooter(); ?>

</div>

</body>
</html>
