<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole(['owner', 'kasir']);

// AJAX Detail Transaksi Handler
if (isset($_GET['detail'])) {
    header('Content-Type: application/json');
    $trx_id = (int)$_GET['detail'];
    
    // Ambil detail transaksi
    $stmt = $pdo->prepare("
        SELECT dt.qty, dt.harga_satuan, p.nama, p.sku
        FROM detail_transaksi dt
        JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
    ");
    $stmt->execute([$trx_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($items);
    exit;
}

// Batal Transaksi Handler
if (isset($_GET['cancel_trx'])) {
    $trx_id = (int)$_GET['cancel_trx'];
    
    try {
        $pdo->beginTransaction();
        
        // Cek status transaksi saat ini
        $stmt = $pdo->prepare("SELECT status, kode FROM transaksi WHERE id = ?");
        $stmt->execute([$trx_id]);
        $trx = $stmt->fetch();
        
        if ($trx && $trx['status'] === 'berhasil') {
            // Ambil semua item dalam transaksi
            $stmtItems = $pdo->prepare("SELECT produk_id, qty FROM detail_transaksi WHERE transaksi_id = ?");
            $stmtItems->execute([$trx_id]);
            $items = $stmtItems->fetchAll();
            
            // Kembalikan stok produk
            $stmtRestoreStok = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
            foreach ($items as $item) {
                $stmtRestoreStok->execute([$item['qty'], $item['produk_id']]);
            }
            
            // Ubah status transaksi jadi batal
            $stmtUpdateStatus = $pdo->prepare("UPDATE transaksi SET status = 'batal' WHERE id = ?");
            $stmtUpdateStatus->execute([$trx_id]);
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Transaksi #" . $trx['kode'] . " berhasil dibatalkan dan stok dikembalikan!";
        } else {
            throw new Exception("Transaksi sudah dibatalkan atau tidak ditemukan.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Gagal membatalkan transaksi: " . $e->getMessage();
    }
    
    header("Location: transaksi.php");
    exit;
}

// Filter & Pencarian
$search = trim($_GET['search'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$sql = "SELECT * FROM transaksi WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND kode LIKE ?";
    $params[] = "%$search%";
}

if ($tanggal !== '') {
    $sql .= " AND DATE(created_at) = ?";
    $params[] = $tanggal;
}

// Pagination
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total
$countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch data
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksiList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .filter-inputs {
            display: flex;
            gap: 12px;
            align-items: center;
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
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">PENA-UMKM</div>
    <div class="logo-sub">Merchant Admin</div>

    <nav>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="dashboard.php">🏠 Dashboard</a>
        <?php endif; ?>
        <a href="../kasir/kasir.php">🖥️ Kasir</a>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="kelola-produk.php">📦 Kelola Produk</a>
        <?php endif; ?>
        <a href="transaksi.php" class="active" style="background:#3B82F6; color:white;">🧾 Transaksi</a>
        <?php if ($_SESSION['role'] === 'owner'): ?>
            <a href="laporan.php">📊 Laporan</a>
        <a href="dompet.php">💳 Dompet Toko</a>
            <a href="kelola-user.php">👥 Kelola User</a>
        <?php endif; ?>
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
            <input type="text" name="search" placeholder="Cari Kode Transaksi..." value="<?= htmlspecialchars($search) ?>" style="border:none; outline:none; font-size:0.875rem; width:100%;">
            <?php if ($tanggal): ?>
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
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

    <!-- TOAST ALERTS -->
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
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Riwayat Transaksi</h1>
        <p>Pantau semua transaksi penjualan merchant dan statusnya.</p>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <form action="" method="GET" id="dateFilterForm" class="filter-inputs">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <div style="display:flex; align-items:center; gap:8px; background:white; padding:6px 12px; border-radius:20px; border:1px solid var(--gray-200); font-size:0.85rem; font-weight:500;">
                <span>📅</span>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" onchange="document.getElementById('dateFilterForm').submit()" style="border:none; outline:none; font-family:'Inter',sans-serif; font-size:0.85rem; cursor:pointer;">
            </div>
            <?php if ($tanggal): ?>
                <a href="transaksi.php?search=<?= urlencode($search) ?>" style="font-size:0.85rem; color:var(--danger); text-decoration:none; font-weight:500;">Hapus Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Kode Transaksi</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Total Nominal</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksiList)): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding:48px; color:var(--gray-500);">
                                <div style="font-size:3rem; margin-bottom:12px;">🧾</div>
                                <h3>Tidak ada riwayat transaksi</h3>
                                <p>Transaksi yang Anda buat di kasir akan muncul di sini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksiList as $trx): 
                            $statusBadge = $trx['status'] === 'berhasil' 
                                ? '<span class="badge badge-success">✓ Berhasil</span>' 
                                : '<span class="badge badge-danger">✗ Batal</span>';
                        ?>
                            <tr style="cursor:pointer;" onclick="openDetailModal(<?= $trx['id'] ?>, '<?= $trx['kode'] ?>', '<?= date('d M Y, H:i', strtotime($trx['created_at'])) ?>', '<?= $trx['status'] ?>', <?= $trx['total'] ?>)">
                                <td><span class="trx-code">#<?= htmlspecialchars($trx['kode']) ?></span></td>
                                <td><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?> WIB</td>
                                <td><?= $statusBadge ?></td>
                                <td style="font-weight:600; color:var(--gray-900);"><?= formatRupiah($trx['total']) ?></td>
                                <td style="text-align:right;" onclick="event.stopPropagation()">
                                    <button class="btn btn-outline" style="padding:6px 12px; font-size:0.75rem;" onclick="openDetailModal(<?= $trx['id'] ?>, '<?= $trx['kode'] ?>', '<?= date('d M Y, H:i', strtotime($trx['created_at'])) ?>', '<?= $trx['status'] ?>', <?= $trx['total'] ?>)">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <div style="padding:16px 24px;" class="pagination">
                <div style="font-size:0.875rem; color:var(--gray-500);">
                    Menampilkan <?= $offset + 1 ?>-<?= min($offset + $limit, $totalRows) ?> dari <?= $totalRows ?> transaksi
                </div>
                <div class="pagination-buttons">
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&tanggal=<?= urlencode($tanggal) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>" style="<?= $page <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">‹</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&tanggal=<?= urlencode($tanggal) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&tanggal=<?= urlencode($tanggal) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" style="<?= $page >= $totalPages ? 'pointer-events:none; opacity:0.5;' : '' ?>">›</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php tampilkanFooter(); ?>

</div>

<!-- DETAIL MODAL -->
<div class="modal-overlay" id="detailModal" style="display:none;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3>Detail Transaksi</h3>
            <button onclick="closeDetailModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid var(--gray-100); padding-bottom:12px;">
                <div>
                    <div style="font-size:0.75rem; color:var(--gray-500);">Kode Transaksi</div>
                    <div id="modalTrxCode" style="font-weight:700; font-size:1.1rem; color:var(--primary);">#PN-XXXXX</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.75rem; color:var(--gray-500);">Waktu Transaksi</div>
                    <div id="modalTrxTime" style="font-weight:600; font-size:0.875rem;">-</div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <div style="font-size:0.8rem; font-weight:600; color:var(--gray-700); margin-bottom:8px;">Item yang Dibeli</div>
                <div id="modalItemList" style="display:flex; flex-direction:column; gap:10px; max-height:200px; overflow-y:auto;">
                    <!-- dynamic detail items -->
                </div>
            </div>

            <div style="background:var(--gray-50); border-radius:12px; padding:16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <span style="font-size:0.8rem; color:var(--gray-500);">Status: </span>
                    <span id="modalTrxStatus">-</span>
                </div>
                <div>
                    <span style="font-size:0.8rem; color:var(--gray-500);">Total: </span>
                    <span id="modalTrxTotal" style="font-weight:700; font-size:1.2rem; color:var(--primary);">Rp 0</span>
                </div>
            </div>

            <div id="cancelTrxSection" style="display:none;">
                <a href="#" id="btnCancelTrx" class="btn btn-danger btn-full" onclick="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini? Stok produk akan dikembalikan.')">
                    Batalkan Transaksi (Refund)
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function openDetailModal(id, code, time, status, total) {
        document.getElementById('modalTrxCode').innerText = '#' + code;
        document.getElementById('modalTrxTime').innerText = time + ' WIB';
        document.getElementById('modalTrxTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        
        const statusEl = document.getElementById('modalTrxStatus');
        if (status === 'berhasil') {
            statusEl.innerHTML = '<span class="badge badge-success">Berhasil</span>';
            document.getElementById('cancelTrxSection').style.display = 'block';
            document.getElementById('btnCancelTrx').setAttribute('href', 'transaksi.php?cancel_trx=' + id);
        } else {
            statusEl.innerHTML = '<span class="badge badge-danger">Batal</span>';
            document.getElementById('cancelTrxSection').style.display = 'none';
        }

        // Fetch detail items
        const itemListContainer = document.getElementById('modalItemList');
        itemListContainer.innerHTML = '<div class="text-center" style="padding:12px; color:var(--gray-500);">Memuat data item...</div>';
        
        fetch('transaksi.php?detail=' + id)
        .then(response => response.json())
        .then(items => {
            itemListContainer.innerHTML = '';
            items.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'flex-between';
                itemEl.style.fontSize = '0.85rem';
                itemEl.innerHTML = `
                    <span>
                        <div style="font-weight:600; color:var(--gray-900);">${item.nama}</div>
                        <div style="font-size:0.75rem; color:var(--gray-500);">${item.qty} Pcs x Rp ${new Intl.NumberFormat('id-ID').format(item.harga_satuan)}</div>
                    </span>
                    <span style="font-weight:600;">Rp ${new Intl.NumberFormat('id-ID').format(item.qty * item.harga_satuan)}</span>
                `;
                itemListContainer.appendChild(itemEl);
            });
        })
        .catch(err => {
            itemListContainer.innerHTML = '<div class="text-center" style="padding:12px; color:var(--danger);">Gagal memuat data item.</div>';
        });

        document.getElementById('detailModal').style.display = 'flex';
    }

    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
</script>

</body>
</html>
