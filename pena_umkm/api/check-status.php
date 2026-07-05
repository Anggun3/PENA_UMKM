<?php
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_GET['code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parameter code tidak ditemukan.']);
    exit;
}

$kode = trim($_GET['code']);

try {
    $stmt = $pdo->prepare("SELECT status FROM transaksi WHERE kode = ?");
    $stmt->execute([$kode]);
    $status = $stmt->fetchColumn();
    
    if (!$status) {
        echo json_encode(['success' => false, 'error' => 'Transaksi tidak ditemukan.']);
        exit;
    }
    
    echo json_encode(['success' => true, 'status' => $status]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>
