<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner');

// Toggle status toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_toko'])) {
    $current_status = $_SESSION['status_toko'] ?? 'buka';
    $new_status = ($current_status === 'buka') ? 'tutup' : 'buka';
    
    // Update all users' status_toko
    $stmtUpdateStatus = $pdo->prepare("UPDATE users SET status_toko = ?");
    $stmtUpdateStatus->execute([$new_status]);
    
    $_SESSION['status_toko'] = $new_status;
    header("Location: dashboard.php");
    exit;
}

// Ambil data statistik
$totalPenjualan = $pdo->query("SELECT SUM(total) FROM transaksi WHERE status='berhasil'")->fetchColumn() ?? 0;
$totalTransaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='berhasil'")->fetchColumn() ?? 0;
$stokMenipis    = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= min_stok AND stok > 0")->fetchColumn() ?? 0;
$produkTerlaris = $pdo->query("
    SELECT p.nama, SUM(dt.qty) as total_qty 
    FROM detail_transaksi dt 
    JOIN produk p ON dt.produk_id = p.id 
    GROUP BY p.id, p.nama 
    ORDER BY total_qty DESC 
    LIMIT 1
")->fetch();

// Ambil transaksi terbaru
$transaksiTerbaru = $pdo->query("
    SELECT * FROM transaksi 
    ORDER BY created_at DESC 
    LIMIT 4
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">PENA-UMKM</div>
    <div class="logo-sub">Merchant Admin</div>

    <nav>
        <a href="dashboard.php" class="active" style="background:#3B82F6; color:white;">🏠 Dashboard</a>
        <a href="kelola-produk.php">📦 Kelola Produk</a>
        <a href="transaksi.php">🧾 Transaksi</a>
        <a href="laporan.php">📊 Laporan</a>
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
        <div>
            <div style="display:flex; align-items:center; gap:8px;">
                <h2 style="font-size:1.1rem; font-weight:700; color:var(--gray-900);"><?= htmlspecialchars($_SESSION['nama_toko'] ?? 'Toko Saya') ?></h2>
                <span style="color:var(--accent); font-weight:bold;">✔</span>
            </div>
        </div>
        
        <div style="display:flex; align-items:center; gap:16px;">
            <?php tampilkanNotifikasi(); ?>
            <a href="pengaturan.php" class="topbar-profile" style="text-decoration:none; color:inherit; cursor:pointer;" title="Buka Pengaturan Akun">
                <div style="text-align:right;">
                    <div style="font-size:0.85rem; font-weight:600; color:var(--gray-900);"><?= $_SESSION['nama'] ?></div>
                    <div style="font-size:0.75rem; color:var(--gray-500);"><?= strtoupper($_SESSION['role']) ?></div>
                </div>
                <div class="topbar-avatar">
                    <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
                </div>
            </a>
        </div>
    </div>

    <!-- WELCOME -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:700; margin-bottom:4px;">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_toko'] ?? 'Toko Saya') ?> 👋</h1>
            <p>Berikut adalah ringkasan performa bisnismu hari ini, <?= date('d M Y') ?>.</p>
        </div>
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="toggle_status_toko" value="1">
            <button type="submit" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit;" title="Klik untuk mengubah status operasional toko">
                <?php if (($_SESSION['status_toko'] ?? 'buka') === 'buka'): ?>
                    <div style="background:var(--success-light); color:#065F46; padding:8px 16px; border-radius:20px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; border:1px solid #A7F3D0; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        🟢 Status Toko: Buka <span style="font-size:0.75rem; color:#0D9488; font-weight:normal; margin-left:4px;">(Klik untuk Tutup)</span>
                    </div>
                <?php else: ?>
                    <div style="background:var(--danger-light); color:#991B1B; padding:8px 16px; border-radius:20px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; border:1px solid #FCA5A5; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        🔴 Status Toko: Tutup <span style="font-size:0.75rem; color:var(--danger); font-weight:normal; margin-left:4px;">(Klik untuk Buka)</span>
                    </div>
                <?php endif; ?>
            </button>
        </form>
    </div>

    <!-- LOW STOCK WARNING BANNER (KF-05) -->
    <?php
    $lowStockProducts = $pdo->query("SELECT nama, stok, min_stok FROM produk WHERE stok <= min_stok AND stok > 0")->fetchAll();
    if (!empty($lowStockProducts)):
    ?>
        <div style="background:var(--warning-light); border:1px solid var(--warning); border-radius:12px; padding:16px 20px; margin-bottom:24px; color:#92400E; display:flex; justify-content:space-between; align-items:center; box-shadow: var(--shadow-sm);">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:1.5rem;">⚠️</span>
                <div>
                    <div style="font-weight:700; font-size:0.95rem;">Peringatan Stok Menipis!</div>
                    <div style="font-size:0.85rem;">Terdapat <?= count($lowStockProducts) ?> produk yang sudah mencapai batas minimum stok. Segera restock!</div>
                </div>
            </div>
            <a href="kelola-produk.php?status_stok=Stok+Menipis" class="btn btn-outline" style="padding:6px 12px; font-size:0.75rem; border-color:#92400E; color:#92400E; background:transparent;">Lihat Detail</a>
        </div>
    <?php endif; ?>

    <!-- STATS CARDS -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;">
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px; font-weight:500;">Total Penjualan</div>
            <div style="font-size:1.35rem; font-weight:700; color:var(--primary);"><?= formatRupiah($totalPenjualan) ?></div>
            <div style="font-size:0.75rem; color:var(--success); margin-top:4px; font-weight:600;">+12% dari kemarin</div>
        </div>
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px; font-weight:500;">Total Transaksi</div>
            <div style="font-size:1.35rem; font-weight:700; color:var(--gray-900);"><?= $totalTransaksi ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Transaksi berhasil</div>
        </div>
        <div class="card">
            <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px; font-weight:500;">Produk Terlaris</div>
            <div style="font-size:1.05rem; font-weight:700; color:var(--gray-900); min-height:24px; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;"><?= $produkTerlaris ? htmlspecialchars($produkTerlaris['nama']) : '-' ?></div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Produk paling dicari</div>
        </div>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:4px; font-weight:500;">Stok Menipis</div>
                <?php if ($stokMenipis > 0): ?>
                    <span class="badge badge-danger">Perlu Cek</span>
                <?php endif; ?>
            </div>
            <div style="font-size:1.35rem; font-weight:700; color:var(--gray-900);"><?= $stokMenipis ?> Items</div>
            <div style="font-size:0.75rem; color:var(--gray-500); margin-top:4px;">Segera hubungi supplier</div>
        </div>
    </div>

    <!-- QUICK ACCESS -->
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:24px;">
        <div style="background:var(--primary); border-radius:16px; padding:24px; color:white; display:flex; flex-direction:column; box-shadow:var(--shadow-card);">
            <div style="font-size:2rem; margin-bottom:12px;">🖥️</div>
            <h3 style="color:white; margin-bottom:8px;">Kasir</h3>
            <p style="color:#A7F3D0; font-size:0.85rem; margin-bottom:20px; line-height:1.4;">Buka terminal POS untuk melayani pelanggan dengan cepat dan efisien.</p>
            <a href="../kasir/kasir.php" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:white; color:var(--primary); padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.9rem; margin-top:auto; transition: opacity 0.2s;">
                Buka Kasir Sekarang →
            </a>
        </div>
        <div class="card" style="display:flex; flex-direction:column;">
            <div style="font-size:2rem; margin-bottom:12px;">📦</div>
            <h3 style="margin-bottom:8px;">Kelola Produk</h3>
            <p style="font-size:0.85rem; margin-bottom:16px; line-height:1.4;">Update stok harian, ubah harga barang, dan tambahkan kategori produk baru untuk tokomu.</p>
            <a href="kelola-produk.php" style="color:var(--primary); text-decoration:none; font-size:0.85rem; font-weight:600; margin-top:auto; display:inline-block;">Lihat Inventaris →</a>
        </div>
        <div class="card" style="display:flex; flex-direction:column;">
            <div style="font-size:2rem; margin-bottom:12px;">📊</div>
            <h3 style="margin-bottom:8px;">Manajemen Laporan</h3>
            <p style="font-size:0.85rem; margin-bottom:16px; line-height:1.4;">Analisis laporan penjualan harian dan pantau performa tokomu secara mingguan.</p>
            <a href="laporan.php" style="color:var(--primary); text-decoration:none; font-size:0.85rem; font-weight:600; margin-top:auto; display:inline-block;">Buka Laporan →</a>
        </div>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid var(--gray-100);">
            <h3 style="font-size:1.05rem;">Transaksi Terbaru</h3>
            <a href="transaksi.php" style="color:var(--accent); text-decoration:none; font-size:0.85rem; font-weight:600;">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th style="text-align:right;">Total Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksiTerbaru)): ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding:32px; color:var(--gray-500);">
                                Belum ada transaksi
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksiTerbaru as $trx): 
                            $statusBadge = $trx['status'] === 'berhasil' 
                                ? '<span class="badge badge-success">Berhasil</span>' 
                                : '<span class="badge badge-danger">Batal</span>';
                        ?>
                            <tr style="cursor:pointer;" onclick="window.location.href='transaksi.php'">
                                <td><span class="trx-code">#<?= htmlspecialchars($trx['kode']) ?></span></td>
                                <td><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?> WIB</td>
                                <td><?= $statusBadge ?></td>
                                <td style="text-align:right; font-weight:600; color:var(--gray-900);"><?= formatRupiah($trx['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php tampilkanFooter(); ?>

</div>

</body>
</html>