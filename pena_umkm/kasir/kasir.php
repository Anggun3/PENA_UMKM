<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole(['owner', 'kasir']);

// AJAX Checkout Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['checkout'])) {
    header('Content-Type: application/json');
    
    // Check if store is open
    $status_toko = $_SESSION['status_toko'] ?? 'buka';
    if ($status_toko !== 'buka') {
        echo json_encode(['success' => false, 'error' => 'Toko sedang tutup. Tidak dapat melakukan transaksi saat ini.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['items'])) {
        echo json_encode(['success' => false, 'error' => 'Keranjang kosong atau data tidak valid.']);
        exit;
    }
    
    $total = (int)$input['total'];
    $metode = $input['metode_pembayaran']; // 'tunai' atau 'qris'
    $items = $input['items'];
    
    try {
        $pdo->beginTransaction();
        
        // Generate kode transaksi
        $kode = generateKodeTransaksi();
        
        // QRIS: status pending awal. Tunai: langsung berhasil
        $status = ($metode === 'qris') ? 'pending' : 'berhasil';
        
        // Insert ke transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (kode, total, metode, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$kode, $total, $metode, $status]);
        $transaksi_id = $pdo->lastInsertId();
        
        // Insert detail transaksi
        $stmtDetail = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga_satuan) VALUES (?, ?, ?, ?)");
        $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        
        foreach ($items as $item) {
            $produk_id = (int)$item['produk_id'];
            $qty = (int)$item['qty'];
            $harga_satuan = (int)$item['harga_satuan'];
            
            // Cek stok produk
            $stmtCek = $pdo->prepare("SELECT stok, nama FROM produk WHERE id = ?");
            $stmtCek->execute([$produk_id]);
            $prod = $stmtCek->fetch();
            
            if (!$prod || $prod['stok'] < $qty) {
                throw new Exception("Stok untuk produk '" . ($prod['nama'] ?? 'Unknown') . "' tidak mencukupi.");
            }
            
            // Insert detail
            $stmtDetail->execute([$transaksi_id, $produk_id, $qty, $harga_satuan]);
            
            // Potong stok HANYA jika pembayaran Tunai (karena Tunai langsung berhasil)
            // Untuk QRIS, stok baru dipotong di callback.php ketika pembayaran terverifikasi
            if ($metode === 'tunai') {
                $stmtUpdateStok->execute([$qty, $produk_id]);
            }
        }
        
        $pdo->commit();
        
        // Pemicu Notifikasi Uang Masuk Tunai & Stok Menipis
        if ($metode === 'tunai') {
            tambahNotifikasi('transaksi_masuk', 'Uang Masuk (Tunai)', "Transaksi Tunai #" . $kode . " sukses diterima sebesar " . formatRupiah($total) . ".");
            
            foreach ($items as $item) {
                $produk_id = (int)$item['produk_id'];
                $stmtCekStok = $pdo->prepare("SELECT nama, stok, min_stok FROM produk WHERE id = ?");
                $stmtCekStok->execute([$produk_id]);
                $prodStok = $stmtCekStok->fetch();
                if ($prodStok && $prodStok['stok'] <= $prodStok['min_stok']) {
                    tambahNotifikasi('stok_rendah', 'Stok Produk Menipis', "Stok '" . $prodStok['nama'] . "' tersisa " . $prodStok['stok'] . " Pcs (Batas minimum: " . $prodStok['min_stok'] . " Pcs).");
                }
            }
        }
        
        // Hitung signature valid untuk testing simulasi QRIS di client
        $private_key = 'PENA_SECRET_KEY';
        $signature = md5($kode . $total . $private_key);
        
        echo json_encode([
            'success' => true, 
            'code' => $kode, 
            'id' => $transaksi_id,
            'total' => $total,
            'signature' => $signature
        ]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Ambil produk untuk katalog
$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? 'Semua');

$sql = "SELECT * FROM produk WHERE stok > 0";
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

$sql .= " ORDER BY nama ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

// Ambil kategori unik
$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM produk WHERE stok > 0")->fetchAll(PDO::FETCH_COLUMN) ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #F8FAFC;
        }
        .kasir-layout {
            display: grid;
            grid-template-columns: 260px 1fr 380px;
            min-height: 100vh;
        }
        .kasir-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            height: 100vh;
        }
        .cart-panel {
            background: var(--white);
            border-left: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            right: 0;
            top: 0;
        }
        .category-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            margin-bottom: 20px;
            padding-bottom: 8px;
        }
        .category-scroll::-webkit-scrollbar {
            height: 4px;
        }
        .category-scroll::-webkit-scrollbar-thumb {
            background: var(--gray-200);
            border-radius: 4px;
        }
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            overflow-y: auto;
            flex: 1;
            padding-bottom: 24px;
        }
        .product-card {
            background: var(--white);
            border-radius: 16px;
            padding: 16px;
            border: 1px solid rgba(229, 231, 235, 0.5);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s ease;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0px 8px 24px rgba(0, 108, 71, 0.08), 0px 4px 12px rgba(0, 0, 0, 0.03);
            border-color: rgba(0, 108, 71, 0.15);
        }
        .product-card .stock-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--success-light);
            color: #065F46;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .product-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 12px;
            background: var(--gray-100);
        }
        .product-card .title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
            min-height: 36px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .price {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .cart-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-100);
            padding-bottom: 12px;
        }
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 20px;
        }
        .qty-btn {
            background: none;
            border: none;
            font-weight: bold;
            font-size: 1rem;
            color: var(--gray-700);
            cursor: pointer;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-summary {
            padding: 20px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        .payment-modal-container {
            display: flex;
            min-height: 400px;
        }
        .payment-left {
            flex: 1.2;
            background: var(--gray-50);
            padding: 24px;
            border-right: 1px solid var(--gray-200);
        }
        .payment-right {
            flex: 1.5;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }
        .method-btn {
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .method-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        .method-btn:active {
            transform: scale(0.97);
        }
        .method-btn.active {
            border-color: var(--primary);
            background: #8BE4BD;
            color: var(--primary);
        }
        .shortcut-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 12px;
            margin-bottom: 16px;
        }
        .shortcut-btn {
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 8px 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }
        .shortcut-btn:hover {
            background: var(--gray-200);
        }
        .shortcut-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .receipt-preview {
            background: white;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 16px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8rem;
            color: #333;
            max-width: 280px;
            margin: 0 auto;
        }
        .simulation-box {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 12px;
            padding: 12px;
            margin-top: 12px;
            text-align: left;
        }
        .simulation-box h4 {
            color: #D97706;
            font-size: 0.8rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body>

<div class="kasir-layout">

    <!-- SIDEBAR -->
    <div class="sidebar" style="position:relative; height:100vh;">
        <div class="logo">PENA-UMKM</div>
        <div class="logo-sub">Merchant Premium Plan</div>

        <nav>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="../admin/dashboard.php">🏠 Dashboard</a>
            <?php endif; ?>
            <a href="kasir.php" class="active-kasir" style="background:#E6F4EA; color:#006C47; font-weight:600;">🖥️ Kasir</a>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="../admin/kelola-produk.php">📦 Kelola Produk</a>
            <?php endif; ?>
            <a href="../admin/transaksi.php">🧾 Transaksi</a>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="../admin/laporan.php">📊 Laporan</a>
        <a href="../admin/dompet.php">💳 Dompet Toko</a>
                <a href="../admin/kelola-user.php">👥 Kelola User</a>
            <?php endif; ?>
            <a href="../admin/pengaturan.php">⚙️ Pengaturan</a>
        </nav>

        <div style="margin-top:auto;">
            <a href="../logout.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:10px; text-decoration:none; color:var(--danger); font-size:0.9rem; font-weight:500;">
                🚪 Keluar
            </a>
        </div>
    </div>

    <!-- CENTER CATALOG -->
    <div class="kasir-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:16px;">
            <form action="" method="GET" style="display:flex; align-items:center; background:var(--white); border:1px solid var(--gray-200); border-radius:30px; padding:8px 16px; width:100%; max-width:480px; box-shadow:var(--shadow-sm);">
                <span style="color:var(--gray-500); margin-right:8px;">🔍</span>
                <input type="text" name="search" placeholder="Cari produk atau SKU..." value="<?= htmlspecialchars($search) ?>" style="border:none; outline:none; font-size:0.875rem; width:100%;">
                <?php if ($kategori !== 'Semua'): ?>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
                <?php endif; ?>
            </form>
            
            <div style="display:flex; align-items:center; gap:16px;">
                <?php if (($_SESSION['status_toko'] ?? 'buka') === 'buka'): ?>
                    <span style="background:var(--success-light); color:#065F46; padding:6px 12px; border-radius:20px; font-size:0.8rem; font-weight:600; border:1px solid #A7F3D0;">🟢 Toko Buka</span>
                <?php else: ?>
                    <span style="background:var(--danger-light); color:#991B1B; padding:6px 12px; border-radius:20px; font-size:0.8rem; font-weight:600; border:1px solid #FCA5A5;">🔴 Toko Tutup</span>
                <?php endif; ?>
                <?php tampilkanNotifikasi(); ?>
                <a href="../admin/pengaturan.php" class="topbar-profile" style="padding-bottom:0; border:none; margin:0; text-decoration:none; color:inherit; cursor:pointer;" title="Buka Pengaturan Akun">
                    <div class="topbar-avatar" style="width:36px; height:36px;">
                        <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
                    </div>
                </a>
            </div>
        </div>

        <div class="category-scroll">
            <a href="?kategori=Semua&search=<?= urlencode($search) ?>" class="category-tab <?= $kategori === 'Semua' ? 'active' : '' ?>">Semua</a>
            <?php foreach ($kategoriList as $cat): ?>
                <a href="?kategori=<?= urlencode($cat) ?>&search=<?= urlencode($search) ?>" class="category-tab <?= $kategori === $cat ? 'active' : '' ?>"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="catalog-grid">
            <?php if (empty($produkList)): ?>
                <div style="grid-column:1/-1; text-align:center; padding:48px; color:var(--gray-500);">
                    <div style="font-size:3rem; margin-bottom:12px;">🔍</div>
                    <h3>Produk tidak ditemukan</h3>
                    <p>Coba kata kunci lain atau periksa stok produk di menu Kelola Produk.</p>
                </div>
            <?php else: ?>
                <?php foreach ($produkList as $p): 
                    $imagePath = $p['foto'] && file_exists($p['foto']) ? $p['foto'] : '';
                    $imageUrl = $imagePath ? '../' . $p['foto'] : 'https://placehold.co/150x120?text=' . urlencode($p['nama']);
                ?>
                    <div class="product-card">
                        <span class="stock-tag"><?= $p['stok'] ?> Stok</span>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
                        <div class="title"><?= htmlspecialchars($p['nama']) ?></div>
                        <div class="price"><?= formatRupiah($p['harga']) ?></div>
                        <button class="btn btn-primary" onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nama'])) ?>', <?= $p['harga'] ?>, <?= $p['stok'] ?>)" style="margin-top:auto; font-size:0.8rem; padding:8px 12px;">
                            + Tambah
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT CART PANEL -->
    <div class="cart-panel">
        <div class="cart-header">
            <h3 style="display:flex; align-items:center; gap:8px;">
                🛒 Pesanan Aktif
            </h3>
            <a href="#" onclick="clearCart()" style="color:var(--danger); text-decoration:none; font-size:0.8rem; font-weight:500;">Hapus Semua</a>
        </div>

        <div class="cart-items" id="cartItemsContainer">
            <div style="text-align:center; margin:auto; color:var(--gray-500);">
                <div style="font-size:3rem; margin-bottom:12px;">🛒</div>
                <p>Belum ada pesanan</p>
            </div>
        </div>

        <div class="cart-summary">
            <?php if (($_SESSION['status_toko'] ?? 'buka') !== 'buka'): ?>
                <div style="background:var(--danger-light); color:#991B1B; border:1px solid #FCA5A5; border-radius:8px; padding:10px 12px; margin-bottom:12px; font-size:0.8rem; font-weight:500; text-align:center;">
                    ⚠️ Toko Tutup. Transaksi dinonaktifkan.
                </div>
            <?php endif; ?>
            <div class="flex-between" style="margin-bottom:8px;">
                <span style="font-size:0.85rem; color:var(--gray-500);">Subtotal</span>
                <span id="summarySubtotal" style="font-weight:600; font-size:0.9rem;">Rp 0</span>
            </div>
            <div class="flex-between" style="margin-bottom:12px; display:none;" id="taxRowSummary">
                <span style="font-size:0.85rem; color:var(--gray-500);">Pajak (11%)</span>
                <span id="summaryPajak" style="font-weight:600; font-size:0.9rem;">Rp 0</span>
            </div>
            <div class="flex-between" style="margin-bottom:20px; border-top:1px dashed var(--gray-200); padding-top:12px;">
                <span style="font-size:0.95rem; font-weight:700;">Total</span>
                <span id="summaryTotal" style="font-weight:700; font-size:1.15rem; color:var(--primary);">Rp 0</span>
            </div>
            
            <button class="btn btn-primary btn-full" id="btnCheckout" onclick="openPaymentModal()" disabled style="padding:14px; font-weight:600; <?= ($_SESSION['status_toko'] ?? 'buka') !== 'buka' ? 'background:#D1D5DB; border-color:#D1D5DB; cursor:not-allowed;' : '' ?>">
                <?= ($_SESSION['status_toko'] ?? 'buka') !== 'buka' ? 'Toko Tutup (Kunci Pembayaran)' : 'Proses Pembayaran →' ?>
            </button>
        </div>
    </div>

</div>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="paymentModal" style="display:none;">
    <div class="modal-content" style="max-width:800px; border-radius:16px;">
        <div class="modal-header">
            <h3>Proses Transaksi</h3>
            <button onclick="closePaymentModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        
        <div class="payment-modal-container">
            <!-- LEFT PANEL -->
            <div class="payment-left">
                <h4 style="margin-bottom:16px;">Ringkasan Pesanan</h4>
                <div id="modalOrderItems" style="display:flex; flex-direction:column; gap:12px; max-height:220px; overflow-y:auto; margin-bottom:16px; border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
                </div>
                <div class="flex-between" style="margin-bottom:6px; font-size:0.85rem; color:var(--gray-500);">
                    <span>Subtotal</span>
                    <span id="modalSubtotal">Rp 0</span>
                </div>
                <div class="flex-between" style="margin-bottom:12px; font-size:0.85rem; color:var(--gray-500); display:none;" id="taxRowModal">
                    <span>Pajak (11%)</span>
                    <span id="modalPajak">Rp 0</span>
                </div>
                <div class="flex-between" style="border-top:1px dashed var(--gray-300); padding-top:12px;">
                    <span style="font-weight:700;">Total Tagihan</span>
                    <span id="modalTotal" style="font-weight:700; color:var(--primary); font-size:1.15rem;">Rp 0</span>
                </div>
            </div>
            
            <!-- RIGHT PANEL: Selection -->
            <div class="payment-right" id="paymentMethodScreen">
                <h3 style="margin-bottom:20px;">Metode Pembayaran</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:32px;">
                    <div class="method-btn" id="methodTunai" onclick="selectPaymentMethod('tunai')">
                        <span style="font-size:2rem;">💵</span>
                        <span>Tunai (Cash)</span>
                    </div>
                    <div class="method-btn" id="methodQris" onclick="selectPaymentMethod('qris')">
                        <span style="font-size:2rem;">📲</span>
                        <span>QRIS</span>
                    </div>
                </div>
                <button class="btn btn-outline btn-full" onclick="closePaymentModal()" style="margin-top:auto;">Batal</button>
            </div>
            
            <!-- SCREEN: CASH FORM -->
            <div class="payment-right" id="cashScreen" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="display:flex; align-items:center; gap:8px;">
                        <span onclick="goBackToMethods()" style="cursor:pointer; font-size:1.2rem;">←</span> Metode Pembayaran
                    </h3>
                    <span class="badge badge-success">Tunai (Cash)</span>
                </div>
                
                <div class="input-group">
                    <label>Uang Diterima</label>
                    <div style="display:flex; align-items:center; border:2px solid var(--primary); border-radius:8px; overflow:hidden; background:white;">
                        <span style="padding:12px 16px; background:var(--gray-50); font-weight:600; font-size:1.2rem; color:var(--gray-700); border-right:1px solid var(--gray-200);">Rp</span>
                        <input type="number" id="cashInput" style="border:none; width:100%; border-radius:0; box-shadow:none; font-size:1.5rem; font-weight:700; padding:10px;" oninput="calculateChange()">
                    </div>
                </div>
                
                <div class="shortcut-grid" id="nominalShortcuts">
                </div>
                
                <div style="background:var(--gray-100); border-radius:12px; padding:16px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:600; font-size:0.90rem;">Kembalian</span>
                    <span id="cashChange" style="font-weight:700; font-size:1.3rem; color:var(--primary);">Rp 0</span>
                </div>
                
                <button class="btn btn-primary btn-full" id="btnSubmitCash" onclick="submitTransaction('tunai')" style="padding:14px; font-weight:600; font-size:0.95rem;">
                    Sudah Terima Uang →
                </button>
            </div>
            
            <!-- SCREEN: QRIS DISPLAY -->
            <div class="payment-right" id="qrisScreen" style="display:none; text-align:center;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; text-align:left;">
                    <h3 style="display:flex; align-items:center; gap:8px;">
                        <span onclick="goBackToMethods()" style="cursor:pointer; font-size:1.2rem;">←</span> Metode Pembayaran
                    </h3>
                    <span class="badge badge-info">QRIS</span>
                </div>
                
                <div style="border:1px solid var(--gray-200); border-radius:16px; padding:16px; background:white; display:inline-block; margin:0 auto 8px auto; box-shadow:var(--shadow-sm);">
                    <img id="qrisQrCode" src="https://placehold.co/180x180?text=Generating..." alt="QRIS Code" style="width:160px; height:160px; display:block;">
                    <div style="margin-top:8px; font-weight:700; color:#1B6B4A; letter-spacing:0.05em; font-size:0.8rem;">QRIS DINAMIS (KF-01)</div>
                </div>
                
                <div style="font-size:0.8rem; color:var(--gray-500); margin-bottom:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <span style="display:inline-block; width:8px; height:8px; background:var(--warning); border-radius:50%; animation: pulse 1s infinite;"></span>
                    <span id="qrisStatusText">Menunggu pembayaran gateway...</span>
                </div>

                <!-- SIMULATOR PANEL FOR TESTING WEBHOOK (KF-02 & KF-10) -->
                <div class="simulation-box">
                    <h4>🛠️ Gateway Webhook Simulator</h4>
                    <p style="font-size:0.75rem; color:var(--gray-500); margin-bottom:10px; line-height:1.3;">
                        Gunakan tombol di bawah untuk menyimulasikan notifikasi pembayaran dari Payment Gateway ke server toko Anda.
                    </p>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-success" onclick="simulateWebhook(true)" style="padding:6px 12px; font-size:0.75rem; flex:1; font-weight:600;">
                            ✅ Bayar Sukses (Webhook Valid)
                        </button>
                        <button class="btn btn-danger" onclick="simulateWebhook(false)" style="padding:6px 12px; font-size:0.75rem; flex:1; font-weight:600;">
                            ❌ Bayar Palsu (Webhook Invalid)
                        </button>
                    </div>
                </div>
                
                <a href="#" onclick="cancelQrisTransaction()" style="color:var(--danger); text-decoration:none; font-size:0.8rem; font-weight:500; display:block; margin-top:12px;">Batalkan Transaksi</a>
            </div>

        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal" style="display:none;">
    <div class="modal-content" style="max-width:760px; border-radius:16px;">
        <div class="payment-modal-container" style="min-height:460px;">
            <div class="payment-left" style="background:white; text-align:center; padding:32px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                <div style="width:64px; height:64px; background:var(--success-light); color:var(--success); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin-bottom:16px;">
                    ✓
                </div>
                <h2 style="font-size:1.6rem; margin-bottom:8px;">Pembayaran Berhasil!</h2>
                <p style="margin-bottom:32px; font-size:0.85rem;">Transaksi telah diverifikasi dan tercatat dalam sistem.</p>
                
                <div style="width:100%; border:1px solid var(--gray-100); border-radius:12px; margin-bottom:32px;">
                    <div class="flex-between" style="padding:12px 16px; border-bottom:1px solid var(--gray-100); font-size:0.85rem;">
                        <span style="color:var(--gray-500);">Total Pembayaran</span>
                        <span id="successTotal" style="font-weight:700; color:var(--primary);">Rp 0</span>
                    </div>
                    <div class="flex-between" style="padding:12px 16px; border-bottom:1px solid var(--gray-100); font-size:0.85rem;">
                        <span style="color:var(--gray-500);">Metode Pembayaran</span>
                        <span id="successMetode" style="font-weight:600; text-transform:capitalize;">-</span>
                    </div>
                    <div class="flex-between" style="padding:12px 16px; font-size:0.85rem;">
                        <span style="color:var(--gray-500);">ID Transaksi</span>
                        <span id="successCode" style="font-weight:600; color:var(--gray-900);">-</span>
                    </div>
                </div>
                
                <div style="display:flex; gap:12px; width:100%;">
                    <button class="btn btn-primary" onclick="window.print()" style="flex:1; padding:12px;">Cetak Struk</button>
                    <button class="btn btn-outline" onclick="resetKasir()" style="flex:1; padding:12px;">Transaksi Baru</button>
                </div>
            </div>

            <div class="payment-right" style="background:var(--gray-50); padding:32px; display:flex; justify-content:center; align-items:center;">
                <div style="text-align:center; width:100%;">
                    <span style="font-size:0.75rem; font-weight:700; letter-spacing:0.1em; color:var(--gray-500); display:block; margin-bottom:16px;">PRATINJAU STRUK DIGITAL</span>
                    <div class="receipt-preview" id="receiptPreviewContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const isTokoBuka = <?= (($_SESSION['status_toko'] ?? 'buka') === 'buka') ? 'true' : 'false' ?>;
    const pajakStatus = <?= json_encode($_SESSION['pajak_status'] ?? 'nonaktif') ?>;
    let cart = [];
    let selectedMethod = '';
    let pollingInterval = null;
    
    // Transaksi data cache untuk testing QRIS
    let currentTrx = null;

    function addToCart(id, nama, harga, stokMaks) {
        if (!isTokoBuka) {
            alert('Toko sedang tutup. Tidak dapat melakukan transaksi saat ini.');
            return;
        }
        let existing = cart.find(item => item.produk_id === id);
        if (existing) {
            if (existing.qty < stokMaks) {
                existing.qty++;
            } else {
                alert("Stok produk tidak mencukupi untuk ditambahkan lagi.");
            }
        } else {
            cart.push({
                produk_id: id,
                nama: nama,
                harga_satuan: harga,
                qty: 1,
                stok_maks: stokMaks
            });
        }
        updateCartUI();
    }

    function changeQty(id, delta) {
        let item = cart.find(i => i.produk_id === id);
        if (!item) return;
        
        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(i => i.produk_id !== id);
        } else if (item.qty > item.stok_maks) {
            item.qty = item.stok_maks;
            alert("Jumlah pesanan melebihi stok yang tersedia.");
        }
        updateCartUI();
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.produk_id !== id);
        updateCartUI();
    }

    function clearCart() {
        cart = [];
        updateCartUI();
    }

    function formatRupiahJS(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(number);
    }

    function getTotals() {
        let subtotal = cart.reduce((sum, item) => sum + (item.harga_satuan * item.qty), 0);
        let pajak = (pajakStatus === 'aktif') ? Math.round(subtotal * 0.11) : 0;
        let total = subtotal + pajak;
        return { subtotal, pajak, total };
    }

    function updateCartUI() {
        const container = document.getElementById('cartItemsContainer');
        const btnCheckout = document.getElementById('btnCheckout');
        
        if (cart.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; margin:auto; color:var(--gray-500);">
                    <div style="font-size:3rem; margin-bottom:12px;">🛒</div>
                    <p>Belum ada pesanan</p>
                </div>
            `;
            btnCheckout.disabled = true;
            document.getElementById('summarySubtotal').innerText = 'Rp 0';
            document.getElementById('summaryPajak').innerText = 'Rp 0';
            document.getElementById('summaryTotal').innerText = 'Rp 0';
            return;
        }
        
        btnCheckout.disabled = !isTokoBuka;
        container.innerHTML = '';
        
        cart.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'cart-item';
            itemEl.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nama}</div>
                    <div class="cart-item-price">${formatRupiahJS(item.harga_satuan)} x ${item.qty}</div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="changeQty(${item.produk_id}, -1)">-</button>
                        <span style="font-size:0.85rem; font-weight:600; min-width:16px; text-align:center;">${item.qty}</span>
                        <button class="qty-btn" onclick="changeQty(${item.produk_id}, 1)">+</button>
                    </div>
                    <button onclick="removeFromCart(${item.produk_id})" style="background:none; border:none; cursor:pointer; color:var(--danger); font-size:1.1rem;">🗑️</button>
                </div>
            `;
            container.appendChild(itemEl);
        });
        
        const { subtotal, pajak, total } = getTotals();
        document.getElementById('summarySubtotal').innerText = formatRupiahJS(subtotal);
        document.getElementById('summaryPajak').innerText = formatRupiahJS(pajak);
        document.getElementById('summaryTotal').innerText = formatRupiahJS(total);
        document.getElementById('taxRowSummary').style.display = (pajakStatus === 'aktif') ? 'flex' : 'none';
    }

    function openPaymentModal() {
        if (!isTokoBuka) {
            alert('Toko sedang tutup. Tidak dapat melakukan transaksi saat ini.');
            return;
        }
        const { subtotal, pajak, total } = getTotals();
        
        document.getElementById('modalSubtotal').innerText = formatRupiahJS(subtotal);
        document.getElementById('modalPajak').innerText = formatRupiahJS(pajak);
        document.getElementById('modalTotal').innerText = formatRupiahJS(total);
        document.getElementById('taxRowModal').style.display = (pajakStatus === 'aktif') ? 'flex' : 'none';
        
        const listContainer = document.getElementById('modalOrderItems');
        listContainer.innerHTML = '';
        cart.forEach(item => {
            const row = document.createElement('div');
            row.className = 'flex-between';
            row.style.fontSize = '0.85rem';
            row.innerHTML = `
                <span>${item.nama} <span style="color:var(--gray-500)">x${item.qty}</span></span>
                <span style="font-weight:500;">${formatRupiahJS(item.harga_satuan * item.qty)}</span>
            `;
            listContainer.appendChild(row);
        });
        
        document.getElementById('paymentMethodScreen').style.display = 'flex';
        document.getElementById('cashScreen').style.display = 'none';
        document.getElementById('qrisScreen').style.display = 'none';
        
        document.getElementById('methodTunai').classList.remove('active');
        document.getElementById('methodQris').classList.remove('active');
        selectedMethod = '';
        currentTrx = null;
        
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        
        document.getElementById('paymentModal').style.display = 'flex';
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    function selectPaymentMethod(method) {
        selectedMethod = method;
        document.getElementById('methodTunai').classList.remove('active');
        document.getElementById('methodQris').classList.remove('active');
        
        if (method === 'tunai') {
            document.getElementById('methodTunai').classList.add('active');
            document.getElementById('paymentMethodScreen').style.display = 'none';
            document.getElementById('cashScreen').style.display = 'flex';
            
            const { total } = getTotals();
            document.getElementById('cashInput').value = total;
            calculateChange();
            generateNominalShortcuts(total);
        } else if (method === 'qris') {
            document.getElementById('methodQris').classList.add('active');
            
            // Lakukan checkout QRIS (membuat transaksi 'pending' di DB)
            initiateQrisCheckout();
        }
    }

    function initiateQrisCheckout() {
        const { total } = getTotals();
        const data = {
            total: total,
            metode_pembayaran: 'qris',
            items: cart.map(item => ({
                produk_id: item.produk_id,
                qty: item.qty,
                harga_satuan: item.harga_satuan
            }))
        };
        
        document.getElementById('qrisStatusText').innerText = "Membuat invoice pembayaran...";
        document.getElementById('paymentMethodScreen').style.display = 'none';
        document.getElementById('qrisScreen').style.display = 'flex';
        
        fetch('kasir.php?checkout=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                currentTrx = res; // Simpan data transaksi (kode, total, signature)
                
                // KF-01: Generate QRIS Dinamis berdasarkan nominal total belanja
                const qrData = `qris_pena_umkm_${res.code}_${res.total}`;
                document.getElementById('qrisQrCode').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;
                document.getElementById('qrisStatusText').innerText = "Menunggu pembayaran gateway...";
                
                // Mulai polling status verifikasi real-time (KF-02)
                startPollingStatus(res.code);
            } else {
                alert("Gagal membuat transaksi: " + res.error);
                goBackToMethods();
            }
        })
        .catch(err => {
            console.error(err);
            alert("Kesalahan jaringan.");
            goBackToMethods();
        });
    }

    function startPollingStatus(code) {
        if (pollingInterval) clearInterval(pollingInterval);
        
        pollingInterval = setInterval(() => {
            fetch(`../api/check-status.php?code=${code}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.status === 'berhasil') {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    closePaymentModal();
                    showSuccessModal(code, 'qris');
                } else if (res.success && res.status === 'batal') {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    alert("Transaksi ini dibatalkan.");
                    goBackToMethods();
                }
            })
            .catch(err => console.error("Polling error:", err));
        }, 2000); // Poll every 2 seconds
    }

    // KF-02 & KF-10 Webhook Simulator
    function simulateWebhook(isValid) {
        if (!currentTrx) {
            alert("Sesi transaksi tidak aktif.");
            return;
        }
        
        const payload = {
            kode: currentTrx.code,
            total: currentTrx.total,
            signature: isValid ? currentTrx.signature : 'wrong_fake_signature_999'
        };
        
        fetch('../api/callback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.error) });
            }
            return response.json();
        })
        .then(res => {
            alert(res.message);
        })
        .catch(err => {
            // Deteksi Bukti Palsu terpicu (KF-10)
            alert("Payment Gateway Refused: " + err.message);
        });
    }

    function cancelQrisTransaction() {
        if (currentTrx && currentTrx.id) {
            if (confirm("Batalkan transaksi pending ini?")) {
                fetch(`transaksi.php?cancel_trx=${currentTrx.id}`)
                .then(() => {
                    closePaymentModal();
                    alert("Transaksi dibatalkan.");
                });
            }
        } else {
            goBackToMethods();
        }
    }

    function goBackToMethods() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        document.getElementById('cashScreen').style.display = 'none';
        document.getElementById('qrisScreen').style.display = 'none';
        document.getElementById('paymentMethodScreen').style.display = 'flex';
    }

    function generateNominalShortcuts(total) {
        const shortcuts = document.getElementById('nominalShortcuts');
        shortcuts.innerHTML = '';
        
        const btnPas = document.createElement('button');
        btnPas.className = 'shortcut-btn active';
        btnPas.innerText = 'Uang Pas';
        btnPas.onclick = () => selectShortcut(total, btnPas);
        shortcuts.appendChild(btnPas);
        
        const nominalOptions = getShortcutNominals(total);
        nominalOptions.forEach(nom => {
            const btn = document.createElement('button');
            btn.className = 'shortcut-btn';
            btn.innerText = formatRupiahJS(nom).replace('Rp ', '');
            btn.onclick = () => selectShortcut(nom, btn);
            shortcuts.appendChild(btn);
        });
    }

    function getShortcutNominals(total) {
        let list = [];
        let nextTens = Math.ceil(total / 10000) * 10000;
        let nextFifty = Math.ceil(total / 50000) * 50000;
        let nextHundred = Math.ceil(total / 100000) * 100000;
        
        if (nextTens > total && !list.includes(nextTens)) list.push(nextTens);
        if (nextFifty > total && !list.includes(nextFifty)) list.push(nextFifty);
        if (nextHundred > total && !list.includes(nextHundred)) list.push(nextHundred);
        
        if (list.length < 3) {
            let addValue = total + 20000;
            if (!list.includes(addValue)) list.push(addValue);
        }
        return list.slice(0, 3).sort((a,b) => a - b);
    }

    function selectShortcut(val, button) {
        document.querySelectorAll('.shortcut-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        document.getElementById('cashInput').value = val;
        calculateChange();
    }

    function calculateChange() {
        const inputVal = parseInt(document.getElementById('cashInput').value) || 0;
        const { total } = getTotals();
        const change = inputVal - total;
        
        const changeEl = document.getElementById('cashChange');
        if (change >= 0) {
            changeEl.innerText = formatRupiahJS(change);
            changeEl.style.color = 'var(--primary)';
            document.getElementById('btnSubmitCash').disabled = false;
        } else {
            changeEl.innerText = 'Uang kurang!';
            changeEl.style.color = 'var(--danger)';
            document.getElementById('btnSubmitCash').disabled = true;
        }
    }

    function submitTransaction(method) {
        const { total } = getTotals();
        const data = {
            total: total,
            metode_pembayaran: method,
            items: cart.map(item => ({
                produk_id: item.produk_id,
                qty: item.qty,
                harga_satuan: item.harga_satuan
            }))
        };
        
        document.getElementById('btnSubmitCash').disabled = true;
        
        fetch('kasir.php?checkout=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                closePaymentModal();
                showSuccessModal(res.code, method);
            } else {
                alert("Transaksi Gagal: " + res.error);
                document.getElementById('btnSubmitCash').disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Kesalahan jaringan.");
            document.getElementById('btnSubmitCash').disabled = false;
        });
    }

    function showSuccessModal(code, method) {
        const { subtotal, pajak, total } = getTotals();
        
        document.getElementById('successTotal').innerText = formatRupiahJS(total);
        document.getElementById('successMetode').innerText = method === 'tunai' ? 'Tunai (Cash)' : 'QRIS';
        document.getElementById('successCode').innerText = '#' + code;
        
        const receiptContainer = document.getElementById('receiptPreviewContainer');
        let receiptHtml = `
            <div style="font-weight:700; text-transform:uppercase; margin-bottom:4px; font-size:0.95rem;">${<?= json_encode($_SESSION['nama_toko'] ?? 'Toko Saya') ?>}</div>
            <div style="font-size:0.75rem; color:#666; margin-bottom:8px;">Merchant POS Receipt<br>Waktu: ${new Date().toLocaleString('id-ID')}</div>
            <div style="border-bottom:1px dashed #ccc; margin:8px 0;"></div>
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.75rem;">
        `;
        
        cart.forEach(item => {
            receiptHtml += `
                <tr>
                    <td colspan="2" style="padding:2px 0;">${item.nama}</td>
                </tr>
                <tr style="color:#555;">
                    <td style="padding:2px 0 6px 0;">${item.qty}x ${formatRupiahJS(item.harga_satuan).replace('Rp ', '')}</td>
                    <td style="text-align:right; padding:2px 0 6px 0;">${formatRupiahJS(item.harga_satuan * item.qty).replace('Rp ', '')}</td>
                </tr>
            `;
        });
        
        let taxRowHtml = '';
        if (pajakStatus === 'aktif') {
            taxRowHtml = `
                <tr>
                    <td style="padding:2px 0; color:#555;">Pajak (11%)</td>
                    <td style="text-align:right; padding:2px 0;">${formatRupiahJS(pajak).replace('Rp ', '')}</td>
                </tr>
            `;
        }
        
        receiptHtml += `
            </table>
            <div style="border-bottom:1px dashed #ccc; margin:8px 0;"></div>
            <table style="width:100%; text-align:left; font-size:0.75rem; border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0; color:#555;">Subtotal</td>
                    <td style="text-align:right; padding:2px 0;">${formatRupiahJS(subtotal).replace('Rp ', '')}</td>
                </tr>
                ${taxRowHtml}
                <tr style="font-weight:bold; font-size:0.85rem;">
                    <td style="padding:8px 0 2px 0;">TOTAL</td>
                    <td style="text-align:right; padding:8px 0 2px 0;">${formatRupiahJS(total).replace('Rp ', '')}</td>
                </tr>
            </table>
            <div style="border-bottom:1px dashed #ccc; margin:8px 0;"></div>
            <div style="font-size:0.7rem; color:#666; margin-top:8px;">
                TERIMA KASIH ATAS KUNJUNGAN ANDA<br>
                <span style="font-style:italic; font-size:0.65rem;">Powered by PENA-UMKM</span>
            </div>
        `;
        receiptContainer.innerHTML = receiptHtml;
        document.getElementById('successModal').style.display = 'flex';
    }

    function resetKasir() {
        document.getElementById('successModal').style.display = 'none';
        clearCart();
        window.location.reload();
    }
</script>

</body>
</html>
