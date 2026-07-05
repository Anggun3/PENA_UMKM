<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode'] ?? '';
    $status = $_POST['status'] ?? '';
    $signature = $_POST['signature'] ?? '';
    
    // Validate signature
    $expected_signature = md5($kode . $status . 'PENA_SECRET_KEY');
    
    if ($signature !== $expected_signature) {
        die("Error: Signature verification failed. Deteksi kecurangan pembayaran!");
    }
    
    try {
        $pdo->beginTransaction();
        
        // Ambil info pencairan sebelum diupdate
        $stmtInfo = $pdo->prepare("SELECT nominal, bank FROM pencairan WHERE kode = ?");
        $stmtInfo->execute([$kode]);
        $payout = $stmtInfo->fetch();
        
        // Update status pencairan
        $stmt = $pdo->prepare("UPDATE pencairan SET status = ? WHERE kode = ?");
        $stmt->execute([$status, $kode]);
        
        $pdo->commit();
        
        // Pemicu Notifikasi Pencairan Sukses/Gagal
        if ($payout) {
            $nominal = $payout['nominal'];
            $bank = $payout['bank'];
            if ($status === 'berhasil') {
                tambahNotifikasi('pencairan_sukses', 'Uang Berhasil Cair', "Penarikan dana sebesar " . formatRupiah($nominal) . " ke rekening " . $bank . " (#" . $kode . ") telah berhasil ditransfer.");
            } else {
                tambahNotifikasi('pencairan_gagal', 'Pencairan Dana Gagal', "Penarikan dana sebesar " . formatRupiah($nominal) . " ke rekening " . $bank . " (#" . $kode . ") gagal diproses.");
            }
        }
        
        // Redirect back with success message
        session_start();
        $_SESSION['success_msg'] = "Webhook Callback Pencairan #" . $kode . " diterima! Status: " . strtoupper($status);
        header("Location: ../admin/dompet.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../admin/dompet.php");
    exit;
}
