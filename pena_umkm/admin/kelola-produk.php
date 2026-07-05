<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner');

// Hapus produk
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Ambil foto lama untuk dihapus
    $stmt = $pdo->prepare("SELECT foto FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $old_foto = $stmt->fetchColumn();
    
    // Hapus dari DB
    $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
    try {
        $stmt->execute([$id]);
        if ($old_foto && file_exists($old_foto)) {
            unlink($old_foto);
        }
        $_SESSION['success_msg'] = "Produk berhasil dihapus!";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Produk gagal dihapus karena sudah memiliki riwayat transaksi.";
    }
    header("Location: kelola-produk.php");
    exit;
}

// Filter & Pencarian
$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? 'Semua');
$status_stok = trim($_GET['status_stok'] ?? 'Semua');

// Query base
$sql = "SELECT * FROM produk WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (nama LIKE ? OR sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($kategori !== 'Semua') {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}

if ($status_stok !== 'Semua') {
    if ($status_stok === 'Stok Aman') {
        $sql .= " AND stok > min_stok AND stok > 0";
    } elseif ($status_stok === 'Stok Menipis') {
        $sql .= " AND stok <= min_stok AND stok > 0";
    } elseif ($status_stok === 'Habis') {
        $sql .= " AND stok <= 0";
    }
}

// Pagination
$limit = 5;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Ambil data dengan limit
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

// Ambil semua kategori unik untuk filter tabs
$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM produk")->fetchAll(PDO::FETCH_COLUMN) ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .category-tabs {
            display: flex;
            gap: 8px;
        }
        .category-tab {
            padding: 8px 16px;
            border-radius: 20px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .category-tab:hover, .category-tab.active {
            background: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary);
        }
        .filter-dropdowns {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .product-info-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .product-img {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
        }
        .product-sku {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 2px;
        }
        .alert-toast {
            background: var(--primary);
            color: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            box-shadow: var(--shadow-md);
        }
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
        <a href="kelola-produk.php" class="active">📦 Kelola Produk</a>
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
        <form action="" method="GET" style="display:flex; align-items:center; background:var(--white); border:1px solid var(--gray-200); border-radius:30px; padding:6px 16px; width:100%; max-width:400px; box-shadow:var(--shadow-sm);">
            <span style="color:var(--gray-500); margin-right:8px;">🔍</span>
            <input type="text" name="search" placeholder="Cari produk atau SKU..." value="<?= htmlspecialchars($search) ?>" style="border:none; outline:none; font-size:0.875rem; width:100%;">
            <?php if ($kategori !== 'Semua'): ?>
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
            <?php endif; ?>
            <?php if ($status_stok !== 'Semua'): ?>
                <input type="hidden" name="status_stok" value="<?= htmlspecialchars($status_stok) ?>">
            <?php endif; ?>
        </form>
        
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

    <!-- TOAST ALERT -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert-toast">
            <div style="display:flex; align-items:center; gap:8px;">
                <span>✅</span>
                <span><?= $_SESSION['success_msg'] ?></span>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer; font-weight:bold;">×</button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert-toast" style="background:var(--danger);">
            <div style="display:flex; align-items:center; gap:8px;">
                <span>❌</span>
                <span><?= $_SESSION['error_msg'] ?></span>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer; font-weight:bold;">×</button>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <!-- TITLE HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Daftar Produk</h1>
            <p>Kelola inventaris stok dan informasi produk Anda.</p>
        </div>
        <a href="tambah-produk.php" class="btn btn-primary">
            <span>+</span> Tambah Produk
        </a>
    </div>

    <!-- FILTER & TABS -->
    <div class="filter-container">
        <div class="category-tabs">
            <a href="?kategori=Semua&search=<?= urlencode($search) ?>&status_stok=<?= urlencode($status_stok) ?>" class="category-tab <?= $kategori === 'Semua' ? 'active' : '' ?>">Semua</a>
            <?php 
            $defaultCategories = ['Sembako', 'Snack', 'Minuman'];
            $mergedCategories = array_unique(array_merge($defaultCategories, $kategoriList));
            foreach ($mergedCategories as $cat): 
                if (empty($cat)) continue;
            ?>
                <a href="?kategori=<?= urlencode($cat) ?>&search=<?= urlencode($search) ?>&status_stok=<?= urlencode($status_stok) ?>" class="category-tab <?= $kategori === $cat ? 'active' : '' ?>"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="filter-dropdowns">
            <form action="" method="GET" id="filterForm" style="display:flex; gap:12px;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
                
                <select name="status_stok" onchange="document.getElementById('filterForm').submit()" style="padding:8px 12px; border-radius:20px; border:1px solid var(--gray-200); font-size:0.85rem; outline:none; background:white; font-weight:500;">
                    <option value="Semua" <?= $status_stok === 'Semua' ? 'selected' : '' ?>>Status Stok: Semua</option>
                    <option value="Stok Aman" <?= $status_stok === 'Stok Aman' ? 'selected' : '' ?>>Stok Aman</option>
                    <option value="Stok Menipis" <?= $status_stok === 'Stok Menipis' ? 'selected' : '' ?>>Stok Menipis</option>
                    <option value="Habis" <?= $status_stok === 'Habis' ? 'selected' : '' ?>>Habis</option>
                </select>
            </form>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produkList)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding:48px; color:var(--gray-500);">
                                <div style="font-size:3rem; margin-bottom:12px;">📦</div>
                                <h3>Belum ada produk</h3>
                                <p>Silakan tambahkan produk baru untuk mulai berbisnis.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produkList as $p): 
                            // Hitung status stok
                            if ($p['stok'] <= 0) {
                                $statusBadge = '<span class="badge badge-danger">Habis</span>';
                            } elseif ($p['stok'] <= $p['min_stok']) {
                                $statusBadge = '<span class="badge badge-warning">Stok Menipis</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-success">Stok Aman</span>';
                            }
                            
                            $imagePath = $p['foto'] && file_exists($p['foto']) ? $p['foto'] : '';
                            $imageUrl = $imagePath ? '../' . $p['foto'] : 'https://placehold.co/100x100?text=Produk';
                        ?>
                            <tr>
                                <td>
                                    <div class="product-info-cell">
                                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="product-img">
                                        <div>
                                            <div style="font-weight:600; color:var(--gray-900);"><?= htmlspecialchars($p['nama']) ?></div>
                                            <div class="product-sku">SKU: <?= htmlspecialchars($p['sku']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['kategori']) ?></td>
                                <td style="font-weight:600; color:var(--gray-900);"><?= formatRupiah($p['harga']) ?></td>
                                <td style="font-weight:500;"><?= $p['stok'] ?> Pcs</td>
                                <td><?= $statusBadge ?></td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <a href="edit-produk.php?id=<?= $p['id'] ?>" class="btn-action" title="Edit Produk">
                                            ✏️
                                        </a>
                                        <a href="?delete=<?= $p['id'] ?>" class="btn-action btn-action-delete" title="Hapus Produk" onclick="return confirm('Apakah Anda yakin ingin menghapus produk <?= htmlspecialchars(addslashes($p['nama'])) ?>?')">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION SECTION -->
        <?php if ($totalPages > 1): ?>
            <div style="padding:16px 24px;" class="pagination">
                <div style="font-size:0.875rem; color:var(--gray-500);">
                    Menampilkan <?= $offset + 1 ?>-<?= min($offset + $limit, $totalRows) ?> dari <?= $totalRows ?> produk
                </div>
                <div class="pagination-buttons">
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status_stok=<?= urlencode($status_stok) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>" style="<?= $page <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">‹</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status_stok=<?= urlencode($status_stok) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status_stok=<?= urlencode($status_stok) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" style="<?= $page >= $totalPages ? 'pointer-events:none; opacity:0.5;' : '' ?>">›</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php tampilkanFooter(); ?>

</div>

</body>
</html>
