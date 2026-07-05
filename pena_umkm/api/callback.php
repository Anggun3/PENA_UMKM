<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

header('Content-Type: application/json');

// Menerima input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['kode']) || !isset($input['total']) || empty($input['signature'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Data callback tidak lengkap atau tidak valid.']);
    exit;
}

$kode = trim($input['kode']);
$total = (int)$input['total'];
$signature = $input['signature'];

// Verifikasi Signature (Kunci Rahasia antara Gateway & Sistem)
// Aturan: md5(kode + total + private_key)
$private_key = 'PENA_SECRET_KEY';
$expected_signature = md5($kode . $total . $private_key);

// Proteksi KF-10: Deteksi Bukti Bayar Palsu
if ($signature !== $expected_signature) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Deteksi Bukti Palsu! Signature pembayaran tidak cocok dengan payment gateway.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Cari transaksi pending
    $stmt = $pdo->prepare("SELECT id, status FROM transaksi WHERE kode = ?");
    $stmt->execute([$kode]);
    $trx = $stmt->fetch();
    
    if (!$trx) {
        throw new Exception("Transaksi dengan kode #" . $kode . " tidak ditemukan.");
    }
    
    if ($trx['status'] === 'berhasil') {
        // Transaksi sudah diverifikasi sebelumnya (idempotent)
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi sudah berstatus berhasil sebelumnya.']);
        exit;
    }
    
    if ($trx['status'] === 'batal') {
        throw new Exception("Transaksi sudah dibatalkan sebelumnya.");
    }
    
    // Ubah status transaksi menjadi berhasil
    $stmtUpdate = $pdo->prepare("UPDATE transaksi SET status = 'berhasil' WHERE id = ?");
    $stmtUpdate->execute([$trx['id']]);
    
    // Pemotongan Stok Otomatis (KF-04)
    // Ambil item-item dalam transaksi
    $stmtItems = $pdo->prepare("SELECT produk_id, qty FROM detail_transaksi WHERE transaksi_id = ?");
    $stmtItems->execute([$trx['id']]);
    $items = $stmtItems->fetchAll();
    
    $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
    
    foreach ($items as $item) {
        $produk_id = (int)$item['produk_id'];
        $qty = (int)$item['qty'];
        
        // Cek stok apakah cukup
        $stmtCek = $pdo->prepare("SELECT stok, nama FROM produk WHERE id = ?");
        $stmtCek->execute([$produk_id]);
        $prod = $stmtCek->fetch();
        
        if (!$prod || $prod['stok'] < $qty) {
            throw new Exception("Stok untuk '" . ($prod['nama'] ?? 'Unknown') . "' tidak mencukupi saat verifikasi callback.");
        }
        
        // Potong stok
        $stmtUpdateStok->execute([$qty, $produk_id]);
    }
    
    $pdo->commit();
    
    // Pemicu Notifikasi Uang Masuk QRIS & Stok Menipis
    tambahNotifikasi('transaksi_masuk', 'Uang Masuk (QRIS)', "Transaksi QRIS #" . $kode . " sukses diterima sebesar " . formatRupiah($total) . ".");
    
    foreach ($items as $item) {
        $produk_id = (int)$item['produk_id'];
        $stmtCekStok = $pdo->prepare("SELECT nama, stok, min_stok FROM produk WHERE id = ?");
        $stmtCekStok->execute([$produk_id]);
        $prodStok = $stmtCekStok->fetch();
        if ($prodStok && $prodStok['stok'] <= $prodStok['min_stok']) {
            tambahNotifikasi('stok_rendah', 'Stok Produk Menipis', "Stok '" . $prodStok['nama'] . "' tersisa " . $prodStok['stok'] . " Pcs (Batas minimum: " . $prodStok['min_stok'] . " Pcs).");
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pembayaran berhasil diverifikasi secara Real-Time. Stok dipotong otomatis.'
    ]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>
