<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner');

$format = $_GET['format'] ?? 'excel';
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Nama Bulan
$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulanTeks = $namaBulan[$bulan] ?? $bulan;

// Ambil data transaksi periode ini
$stmt = $pdo->prepare("
    SELECT kode, created_at, status, total 
    FROM transaksi 
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    ORDER BY created_at ASC
");
$stmt->execute([$bulan, $tahun]);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'excel') {
    // Ekspor Excel (CSV format)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Laporan_Penjualan_' . $bulanTeks . '_' . $tahun . '.csv"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Print UTF-8 BOM for Excel double click compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header CSV
    fputcsv($output, ['LAPORAN PENJUALAN ' . strtoupper($_SESSION['nama_toko'] ?? 'Toko Saya')]);
    fputcsv($output, ['Periode:', $bulanTeks . ' ' . $tahun]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Kode Transaksi', 'Waktu Transaksi', 'Status', 'Nominal Total']);
    
    $no = 1;
    $totalOmset = 0;
    foreach ($transaksi as $row) {
        $nominal = (int)$row['total'];
        if ($row['status'] === 'berhasil') {
            $totalOmset += $nominal;
        }
        
        fputcsv($output, [
            $no++,
            '#' . $row['kode'],
            date('d M Y H:i', strtotime($row['created_at'])) . ' WIB',
            ucfirst($row['status']),
            'Rp ' . number_format($nominal, 0, ',', '.')
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['', '', '', 'TOTAL OMSET BERHASIL:', 'Rp ' . number_format($totalOmset, 0, ',', '.')]);
    
    fclose($output);
    exit;
} elseif ($format === 'pdf') {
    // Ekspor PDF (Print Layout)
    // Ambil metrik ringkasan
    $stmtStats = $pdo->prepare("
        SELECT SUM(total) as omset, COUNT(*) as jml_trx 
        FROM transaksi 
        WHERE status='berhasil' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    ");
    $stmtStats->execute([$bulan, $tahun]);
    $stats = $stmtStats->fetch();
    $omset = $stats['omset'] ?? 0;
    $jmlTrx = $stats['jml_trx'] ?? 0;
    
    $stmtStatsBatal = $pdo->prepare("
        SELECT COUNT(*) 
        FROM transaksi 
        WHERE status='batal' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    ");
    $stmtStatsBatal->execute([$bulan, $tahun]);
    $jmlBatal = $stmtStatsBatal->fetchColumn() ?? 0;
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak Laporan Penjualan - Periode <?= $bulanTeks ?> <?= $tahun ?></title>
        <style>
            body {
                font-family: 'Inter', Arial, sans-serif;
                color: #333;
                padding: 30px;
                line-height: 1.5;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 1.8rem;
                color: #006C47;
            }
            .header p {
                margin: 5px 0 0 0;
                color: #666;
            }
            .meta-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                font-size: 0.9rem;
            }
            .metrics-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                margin-bottom: 30px;
            }
            .metric-card {
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 16px;
                background: #f9f9f9;
            }
            .metric-card .title {
                font-size: 0.8rem;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .metric-card .value {
                font-size: 1.3rem;
                font-weight: bold;
                color: #006C47;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            table th {
                background: #f2f2f2;
                border-bottom: 2px solid #ccc;
                padding: 10px;
                text-align: left;
                font-size: 0.85rem;
                text-transform: uppercase;
            }
            table td {
                padding: 10px;
                border-bottom: 1px solid #eee;
                font-size: 0.85rem;
            }
            .total-row {
                font-weight: bold;
                background: #f9f9f9;
            }
            @media print {
                body {
                    padding: 0;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #006C47; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                🖨️ Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left: 8px;">
                Tutup Halaman
            </button>
        </div>

        <div class="header">
            <h1><?= htmlspecialchars($_SESSION['nama_toko'] ?? 'Toko Saya') ?></h1>
            <p>Laporan Penjualan Rekap Transaksi Merchant</p>
        </div>

        <div class="meta-info">
            <div>
                <strong>Periode Laporan:</strong> <?= $bulanTeks ?> <?= $tahun ?>
            </div>
            <div>
                <strong>Waktu Cetak:</strong> <?= date('d M Y H:i') ?> WIB
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="title">Total Omset Selesai</div>
                <div class="value"><?= formatRupiah($omset) ?></div>
            </div>
            <div class="metric-card">
                <div class="title">Total Transaksi Selesai</div>
                <div class="value"><?= $jmlTrx ?> Transaksi</div>
            </div>
            <div class="metric-card">
                <div class="title">Transaksi Dibatalkan</div>
                <div class="value" style="color: #c00;"><?= $jmlBatal ?> Transaksi</div>
            </div>
        </div>

        <h3>Rincian Transaksi Penjualan</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Kode Transaksi</th>
                    <th>Waktu Transaksi</th>
                    <th>Status</th>
                    <th style="text-align: right;">Nominal Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksi)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">Tidak ada data transaksi pada periode ini.</td>
                    </tr>
                <?php else: 
                    $no = 1;
                    foreach ($transaksi as $row): 
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="font-weight: bold; color: #006C47;">#<?= htmlspecialchars($row['kode']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?> WIB</td>
                        <td>
                            <span style="color: <?= $row['status'] === 'berhasil' ? '#006C47' : '#c00' ?>; font-weight: bold;">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: bold;"><?= formatRupiah($row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;">TOTAL OMSET BERHASIL:</td>
                        <td style="text-align: right; color: #006C47; font-size: 1rem;"><?= formatRupiah($omset) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <script>
            // Auto trigger print dialog when loaded
            window.onload = function() {
                // Delay slightly to ensure fonts/layout are ready
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
