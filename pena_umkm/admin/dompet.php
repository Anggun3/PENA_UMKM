<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner'); // Hanya Owner yang bisa mencairkan uang

$error = '';
$success = '';

// 1. Hitung Saldo Digital (QRIS yang berhasil)
$totalQris = $pdo->query("SELECT SUM(total) FROM transaksi WHERE status='berhasil' AND metode='qris'")->fetchColumn() ?? 0;

// 2. Hitung Saldo yang sudah dicairkan (Status berhasil)
$totalDicairkan = $pdo->query("SELECT SUM(nominal) FROM pencairan WHERE status='berhasil'")->fetchColumn() ?? 0;

// 3. Hitung Saldo yang sedang ditarik (Status pending)
$totalPending = $pdo->query("SELECT SUM(nominal) FROM pencairan WHERE status='pending'")->fetchColumn() ?? 0;

// 4. Saldo Tersedia
$saldoTersedia = $totalQris - $totalDicairkan - $totalPending;

// Tarik Saldo Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarik_saldo'])) {
    $nominal       = (int)$_POST['nominal'];
    $bank          = trim($_POST['bank']);
    $no_rekening   = trim($_POST['no_rekening']);
    $nama_rekening = trim($_POST['nama_rekening']);
    
    if ($nominal < 10000) {
        $error = "Minimal pencairan dana adalah Rp 10.000.";
    } elseif ($nominal > $saldoTersedia) {
        $error = "Nominal pencairan melebihi Saldo Tersedia Anda.";
    } elseif (empty($bank) || empty($no_rekening) || empty($nama_rekening)) {
        $error = "Silakan lengkapi semua kolom detail bank tujuan.";
    } else {
        try {
            $kode = "WD-" . time() . rand(10, 99);
            $stmt = $pdo->prepare("INSERT INTO pencairan (kode, nominal, bank, no_rekening, nama_rekening, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$kode, $nominal, $bank, $no_rekening, $nama_rekening]);
            
            $_SESSION['success_msg'] = "Permintaan pencairan sebesar " . formatRupiah($nominal) . " berhasil diajukan. Status: PENDING.";
            header("Location: dompet.php");
            exit;
        } catch (Exception $e) {
            $error = "Gagal mengajukan pencairan: " . $e->getMessage();
        }
    }
}

// Ambil Riwayat Pencairan
$stmt = $pdo->query("SELECT * FROM pencairan ORDER BY created_at DESC");
$riwayatPencairan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dompet Toko - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .wallet-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card-wallet {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .simulation-box-payout {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 12px;
            padding: 16px;
            margin-top: 24px;
        }
        .simulation-box-payout h4 {
            color: #D97706;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
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
        <a href="laporan.php">📊 Laporan</a>
        <a href="dompet.php" class="active" style="background:#3B82F6; color:white;">💳 Dompet Toko</a>
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
            <h2 style="font-size:1.2rem; font-weight:600;">Dompet Merchant & Pencairan</h2>
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
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Dompet Toko</h1>
        <p>Cairkan pendapatan digital (transaksi QRIS) Anda langsung ke rekening bank pribadi Anda.</p>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div style="background:var(--success-light); color:#065F46; padding:12px 16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem; font-weight:500;">
            ✅ <?= $_SESSION['success_msg'] ?>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:var(--danger-light); color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem; font-weight:500;">
            ⚠️ <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="wallet-layout">
        
        <!-- LEFT PANEL: Stats & History -->
        <div>
            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card-wallet" style="border-left: 4px solid var(--primary);">
                    <div style="font-size:0.8rem; color:var(--gray-500); font-weight:600; text-transform:uppercase;">Saldo Tersedia (Siap Cair)</div>
                    <div style="font-size:1.6rem; font-weight:700; color:var(--primary); margin-top:8px;"><?= formatRupiah($saldoTersedia) ?></div>
                    <div style="font-size:0.75rem; color:var(--gray-400); margin-top:4px;">Berasal dari pembayaran QRIS non-tunai</div>
                </div>
                
                <div class="stat-card-wallet" style="border-left: 4px solid #F59E0B;">
                    <div style="font-size:0.8rem; color:var(--gray-500); font-weight:600; text-transform:uppercase;">Sedang Diproses (Pending)</div>
                    <div style="font-size:1.6rem; font-weight:700; color:#D97706; margin-top:8px;"><?= formatRupiah($totalPending) ?></div>
                    <div style="font-size:0.75rem; color:var(--gray-400); margin-top:4px;">Sedang ditransfer ke rekening Anda</div>
                </div>

                <div class="stat-card-wallet" style="border-left: 4px solid #3B82F6;">
                    <div style="font-size:0.8rem; color:var(--gray-500); font-weight:600; text-transform:uppercase;">Total Berhasil Cair</div>
                    <div style="font-size:1.4rem; font-weight:700; color:#2563EB; margin-top:8px;"><?= formatRupiah($totalDicairkan) ?></div>
                </div>

                <div class="stat-card-wallet" style="border-left: 4px solid var(--gray-400);">
                    <div style="font-size:0.8rem; color:var(--gray-500); font-weight:600; text-transform:uppercase;">Total Pendapatan QRIS</div>
                    <div style="font-size:1.4rem; font-weight:700; color:var(--gray-800); margin-top:8px;"><?= formatRupiah($totalQris) ?></div>
                </div>
            </div>

            <!-- TABLE Penarikan -->
            <div class="card" style="padding:0; overflow:hidden;">
                <div style="padding:20px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="font-size:1rem; font-weight:600;">Riwayat Pencairan Dana</h3>
                    <span style="font-size:0.75rem; color:var(--gray-500);">Menampilkan semua pengajuan transfer keluar</span>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Kode Request</th>
                                <th>Bank Tujuan</th>
                                <th>Nominal</th>
                                <th>Status Transfer</th>
                                <th>Tanggal Pengajuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($riwayatPencairan)): ?>
                                <tr>
                                    <td colspan="5" class="text-center" style="padding:40px; color:var(--gray-500);">
                                        <div style="font-size:2rem; margin-bottom:8px;">💸</div>
                                        <p>Belum ada pengajuan pencairan dana.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayatPencairan as $payout): 
                                    if ($payout['status'] === 'berhasil') {
                                        $statusBadge = '<span class="badge badge-success">✓ Berhasil Cair</span>';
                                    } elseif ($payout['status'] === 'gagal') {
                                        $statusBadge = '<span class="badge badge-danger">✗ Gagal / Ditolak</span>';
                                    } else {
                                        $statusBadge = '<span class="badge badge-warning">⚡ Sedang Diproses</span>';
                                    }
                                ?>
                                    <tr>
                                        <td><span class="trx-code">#<?= htmlspecialchars($payout['kode']) ?></span></td>
                                        <td>
                                            <div style="font-weight:600; color:var(--gray-900);"><?= htmlspecialchars($payout['bank']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--gray-500);"><?= htmlspecialchars($payout['no_rekening']) ?> a.n <?= htmlspecialchars($payout['nama_rekening']) ?></div>
                                        </td>
                                        <td style="font-weight:600; color:var(--gray-900);"><?= formatRupiah($payout['nominal']) ?></td>
                                        <td><?= $statusBadge ?></td>
                                        <td><?= date('d M Y, H:i', strtotime($payout['created_at'])) ?> WIB</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Withdraw Form & Simulator -->
        <div>
            <!-- FORM -->
            <div class="card" style="margin-bottom: 24px;">
                <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                    <span>💸</span> Cairkan Dana Toko
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="tarik_saldo" value="1">
                    
                    <div class="input-group">
                        <label>Pilih Bank Tujuan</label>
                        <select name="bank" required>
                            <option value="BCA">BCA (Bank Central Asia)</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
                            <option value="BNI">BNI (Bank Negara Indonesia)</option>
                            <option value="Danamon">Bank Danamon</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Nomor Rekening</label>
                        <input type="text" name="no_rekening" placeholder="Masukkan nomor rekening bank" required pattern="[0-9]+">
                    </div>

                    <div class="input-group">
                        <label>Nama Pemilik Rekening</label>
                        <input type="text" name="nama_rekening" placeholder="Nama sesuai buku tabungan" required>
                    </div>

                    <div class="input-group">
                        <label>Nominal Penarikan (Rp)</label>
                        <input type="number" name="nominal" min="10000" max="<?= $saldoTersedia ?>" placeholder="Min. Rp 10.000" required>
                        <small style="color:var(--gray-500); font-size:0.75rem; display:block; margin-top:4px;">Saldo maksimal yang dapat ditarik: <?= formatRupiah($saldoTersedia) ?></small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full" style="margin-top:16px; padding:12px;" <?= $saldoTersedia < 10000 ? 'disabled style="background:#D1D5DB; border-color:#D1D5DB; cursor:not-allowed;"' : '' ?>>
                        Ajukan Transfer Keluar
                    </button>
                </form>
            </div>

            <!-- SIMULATOR -->
            <div class="simulation-box-payout">
                <h4><span>⚙️</span> Simulator Webhook Pencairan</h4>
                <p style="font-size:0.75rem; color:#856404; margin-bottom:12px; line-height:1.4;">
                    Gunakan panel ini untuk mensimulasikan notifikasi callback transfer (webhook) sukses/gagal dari payment gateway.
                </p>
                
                <?php 
                $pendingPayouts = array_filter($riwayatPencairan, function($p) { return $p['status'] === 'pending'; });
                if (empty($pendingPayouts)): 
                ?>
                    <div style="font-size:0.75rem; color:#856404; font-style:italic; text-align:center; padding:12px; background:rgba(255,255,255,0.5); border-radius:6px;">
                        Tidak ada permintaan penarikan dengan status pending untuk disimulasikan.
                    </div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($pendingPayouts as $payout): ?>
                            <div style="background:white; border-radius:8px; padding:10px; border:1px solid #F59E0B;">
                                <div style="font-size:0.75rem; font-weight:600; color:var(--gray-800); margin-bottom:4px;">
                                    #<?= htmlspecialchars($payout['kode']) ?> - <?= formatRupiah($payout['nominal']) ?>
                                </div>
                                <div style="font-size:0.7rem; color:var(--gray-500); margin-bottom:8px;">
                                    Ke: <?= htmlspecialchars($payout['bank']) ?> (<?= htmlspecialchars($payout['no_rekening']) ?>)
                                </div>
                                <div style="display:flex; gap:6px;">
                                    <form action="../api/callback-disbursement.php" method="POST" style="flex:1;">
                                        <input type="hidden" name="kode" value="<?= htmlspecialchars($payout['kode']) ?>">
                                        <input type="hidden" name="status" value="berhasil">
                                        <input type="hidden" name="signature" value="<?= md5($payout['kode'] . 'berhasil' . 'PENA_SECRET_KEY') ?>">
                                        <button type="submit" class="btn btn-primary" style="font-size:0.65rem; padding:6px; width:100%;">✓ Transfer Sukses</button>
                                    </form>
                                    <form action="../api/callback-disbursement.php" method="POST" style="flex:1;">
                                        <input type="hidden" name="kode" value="<?= htmlspecialchars($payout['kode']) ?>">
                                        <input type="hidden" name="status" value="gagal">
                                        <input type="hidden" name="signature" value="<?= md5($payout['kode'] . 'gagal' . 'PENA_SECRET_KEY') ?>">
                                        <button type="submit" class="btn btn-outline" style="font-size:0.65rem; padding:5px; width:100%; border-color:var(--danger); color:var(--danger);">✗ Transfer Gagal</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php tampilkanFooter(); ?>

</div>

</body>
</html>
