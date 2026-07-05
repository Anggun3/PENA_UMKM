<?php

// Perhitungan BASE_URL secara dinamis
if (!defined('BASE_URL')) {
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $base = rtrim($script_dir, '/');
    if (preg_match('/\/(admin|kasir|api|info)$/', $base)) {
        $base = dirname($base);
    }
    define('BASE_URL', rtrim(str_replace('\\', '/', $base), '/') . '/');
}
// Format harga ke Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Generate kode transaksi otomatis
function generateKodeTransaksi() {
    return 'PN-' . strtoupper(substr(uniqid(), -5));
}

// Cek apakah user sudah login
function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Cek role user (bisa menerima string tunggal atau array beberapa role)
function cekRole($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Jika tidak punya akses, redirect sesuai role yang dimiliki
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'kasir') {
            header('Location: ' . BASE_URL . 'kasir/kasir.php');
        } else {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        }
        exit;
    }
}

// Menampilkan footer copyright di halaman admin/dashboard
function tampilkanFooter() {
    echo '
    <div style="margin-top: 48px; border-top: 1px solid var(--gray-100); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--gray-500); flex-wrap: wrap; gap: 8px;">
        <div>&copy; 2026 <strong>PENA-UMKM</strong>. Kelompok 7 RPL C from Universitas Muhammadiyah Malang.</div>
        <div>Made with ❤️ by <strong>Kelompok 7 RPL C</strong></div>
    </div>';
}

// Mengirimkan email (melalui mail() PHP dan menyimpan salinan HTML di folder emails/)
function kirimEmail($to, $subject, $messageHtml) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: PENA-UMKM <noreply@pena-umkm.com>\r\n";
    @mail($to, $subject, $messageHtml, $headers);

    // Simpan salinan ke file HTML lokal agar bisa dicek di XAMPP secara offline
    $dir = __DIR__ . '/../emails';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $filename = $dir . '/email_' . time() . '_' . rand(1000, 9999) . '.html';
    
    $fullHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { font-family: \'Inter\', sans-serif; background: #F4F7FC; padding: 24px; color: #1F2937; margin:0; }
            .container { max-width: 600px; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #E5E7EB; overflow: hidden; margin: 0 auto; }
            .header { background: #006C47; padding: 24px; text-align: center; color: white; }
            .header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.025em; }
            .body { padding: 32px 24px; line-height: 1.6; }
            .footer { padding: 20px; background: #F9FAFB; border-top: 1px solid #E5E7EB; text-align: center; font-size: 0.8rem; color: #6B7280; }
            .btn { display: inline-block; padding: 12px 24px; background: #006C47; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 16px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>PENA-UMKM</h1>
            </div>
            <div class="body">
                ' . $messageHtml . '
            </div>
            <div class="footer">
                &copy; 2026 PENA-UMKM. Hak Cipta Dilindungi Undang-Undang.<br>
                Email ini dikirim ke ' . htmlspecialchars($to) . '
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($filename, $fullHtml);
}

// Menambahkan notifikasi baru di database dan mengirim email ke owner
function tambahNotifikasi($tipe, $judul, $pesan) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifikasi (tipe, judul, pesan) VALUES (?, ?, ?)");
        $stmt->execute([$tipe, $judul, $pesan]);
    } catch (PDOException $e) {
        // Table not exist
        return;
    }
    
    // Kirim notifikasi email HANYA ke email Owner/Admin
    $emailOwner = 'zahrazeta@webmail.umm.ac.id';
    $subject = '[PENA-UMKM] Notifikasi Baru: ' . $judul;
    
    $icon = '📢';
    if ($tipe === 'stok_rendah') $icon = '⚠️';
    elseif ($tipe === 'transaksi_masuk') $icon = '💰';
    elseif ($tipe === 'pencairan_sukses') $icon = '✅';
    elseif ($tipe === 'pencairan_gagal') $icon = '❌';
    
    $messageHtml = '
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
        <span style="font-size: 2.5rem;">' . $icon . '</span>
        <div>
            <h2 style="margin:0; font-size:1.25rem; color:#1F2937;">' . htmlspecialchars($judul) . '</h2>
            <p style="margin:4px 0 0 0; font-size:0.9rem; color:#6B7280;">Waktu: ' . date('d M Y, H:i') . ' WIB</p>
        </div>
    </div>
    <div style="background:#F9FAFB; padding:20px; border-radius:12px; border:1px solid #E5E7EB; margin-bottom:24px;">
        <p style="margin:0; font-size:0.95rem; color:#374151; font-weight:500; line-height:1.5;">
            ' . nl2br(htmlspecialchars($pesan)) . '
        </p>
    </div>
    <div style="text-align:center;">
        <a href="http://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'admin/dashboard.php" class="btn" style="color:white;">Buka Dashboard PENA-UMKM</a>
    </div>';
    
    kirimEmail($emailOwner, $subject, $messageHtml);
}

// Menampilkan dropdown notifikasi lonceng
function tampilkanNotifikasi() {
    global $pdo;
    $count = 0;
    $notifs = [];
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM notifikasi WHERE is_read = 0")->fetchColumn() ?? 0;
        $notifs = $pdo->query("SELECT * FROM notifikasi ORDER BY created_at DESC LIMIT 5")->fetchAll() ?? [];
    } catch (PDOException $e) {
        // Fail silent
    }
    
    $badgeStyle = $count > 0 ? '' : 'display:none;';
    
    echo '
    <div class="notif-dropdown-wrapper" style="position: relative; display: flex; align-items: center; margin-right: 12px; user-select: none;">
        <div id="notif-bell-btn" style="font-size:1.25rem; cursor:pointer; color:var(--gray-500); position: relative; padding: 6px; transition: color 0.2s;" title="Notifikasi" onmouseover="this.style.color=\'var(--primary)\'" onmouseout="this.style.color=\'var(--gray-500)\'">
            🔔
            <span id="notif-badge-count" style="' . $badgeStyle . ' position: absolute; top: 0; right: 0; background: var(--danger); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; font-weight: bold; border: 2px solid white; transform: translate(25%, -25%);">' . $count . '</span>
        </div>
        
        <div id="notif-dropdown-content" style="display:none; position: absolute; right: 0; top: 40px; width: 340px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); border: 1px solid var(--gray-200); z-index: 999; overflow: hidden; animation: slideUp 0.2s ease-out;">
            <div style="padding: 12px 16px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50);">
                <span style="font-weight: 700; font-size: 0.85rem; color: var(--gray-900);">Notifikasi Terbaru</span>
                <a href="?read_all_notif=1" style="font-size: 0.75rem; color: var(--primary); text-decoration: none; font-weight: 600;">Tandai Dibaca</a>
            </div>
            <div style="max-height: 280px; overflow-y: auto;">';
    
    if (empty($notifs)) {
        echo '
        <div style="padding: 32px 16px; text-align: center; color: var(--gray-500); font-size: 0.85rem;">
            <div style="font-size: 2rem; margin-bottom: 8px;">📭</div>
            Tidak ada notifikasi baru
        </div>';
    } else {
        foreach ($notifs as $n) {
            $bg = $n['is_read'] == 0 ? 'background: #F0FDF4; border-left: 3px solid var(--primary);' : 'background: white;';
            $icon = '📢';
            if ($n['tipe'] === 'stok_rendah') $icon = '⚠️';
            elseif ($n['tipe'] === 'transaksi_masuk') $icon = '💰';
            elseif ($n['tipe'] === 'pencairan_sukses') $icon = '✅';
            elseif ($n['tipe'] === 'pencairan_gagal') $icon = '❌';
            
            echo '
            <div style="' . $bg . ' padding: 12px 16px; border-bottom: 1px solid var(--gray-100); display: flex; gap: 12px; font-size: 0.825rem; transition: background 0.2s; text-align: left;">
                <span style="font-size: 1.2rem; margin-top: 2px;">' . $icon . '</span>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--gray-900); margin-bottom: 2px;">' . htmlspecialchars($n['judul']) . '</div>
                    <div style="color: var(--gray-600); line-height: 1.4; margin-bottom: 4px;">' . htmlspecialchars($n['pesan']) . '</div>
                    <div style="font-size: 0.7rem; color: var(--gray-400);">' . date('d M Y, H:i', strtotime($n['created_at'])) . ' WIB</div>
                </div>
            </div>';
        }
    }
    
    echo '
            </div>
            <div style="padding: 10px 16px; text-align: center; border-top: 1px solid var(--gray-100); background: var(--gray-50); display: flex; justify-content: space-between; align-items: center;">
                <a href="?clear_all_notif=1" style="font-size: 0.75rem; color: var(--danger); text-decoration: none; font-weight: 500;">Hapus Riwayat</a>
                <span style="font-size: 0.75rem; color: var(--gray-400);">Maksimal 5 notif</span>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const bell = document.getElementById("notif-bell-btn");
            const dropdown = document.getElementById("notif-dropdown-content");
            if (bell && dropdown) {
                bell.addEventListener("click", function(e) {
                    e.stopPropagation();
                    const isVisible = dropdown.style.display === "block";
                    dropdown.style.display = isVisible ? "none" : "block";
                });
                document.addEventListener("click", function(e) {
                    if (!dropdown.contains(e.target) && e.target !== bell) {
                        dropdown.style.display = "none";
                    }
                });
            }
        });
    </script>';
}

// Proses aksi notifikasi secara global jika pdo tersedia
if (isset($pdo)) {
    if (isset($_GET['read_all_notif']) && $_GET['read_all_notif'] == 1) {
        if (isset($_SESSION['user_id'])) {
            $pdo->query("UPDATE notifikasi SET is_read = 1");
        }
        $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: " . $clean_url);
        exit;
    }

    if (isset($_GET['clear_all_notif']) && $_GET['clear_all_notif'] == 1) {
        if (isset($_SESSION['user_id'])) {
            $pdo->query("DELETE FROM notifikasi");
        }
        $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: " . $clean_url);
        exit;
    }
}
?>
