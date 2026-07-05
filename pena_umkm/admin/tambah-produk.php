<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/myfunc.php';

cekLogin();
cekRole('owner');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama']);
    $sku         = trim($_POST['sku']);
    $kategori    = trim($_POST['kategori']);
    $deskripsi   = trim($_POST['deskripsi']);
    $harga_beli  = (int)$_POST['harga_beli'];
    $harga       = (int)$_POST['harga']; // Harga Jual
    $stok        = (int)$_POST['stok'];
    $min_stok    = (int)$_POST['min_stok'];
    
    // Cek apakah SKU sudah ada
    $stmt = $pdo->prepare("SELECT id FROM produk WHERE sku = ?");
    $stmt->execute([$sku]);
    if ($stmt->fetch()) {
        $error = "SKU atau Kode Produk sudah digunakan oleh produk lain.";
    } else {
        $fotoPath = NULL;
        
        // Handle file upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileSize      = $_FILES['foto']['size'];
            $fileType      = $_FILES['foto']['type'];
            $fileNameCmps  = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (in_array($fileExtension, $allowedExtensions)) {
                if ($fileSize <= 5 * 1024 * 1024) { // 5MB max
                    // Buat folder uploads jika belum ada
                    if (!is_dir('../uploads')) {
                        mkdir('../uploads', 0777, true);
                    }
                    
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $dest_path = '../uploads/' . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $fotoPath = 'uploads/' . $newFileName;
                    } else {
                        $error = 'Terjadi kesalahan saat memindahkan file foto.';
                    }
                } else {
                    $error = 'Ukuran foto maksimal 5MB.';
                }
            } else {
                $error = 'Tipe file foto tidak diizinkan. Hanya JPG, JPEG, dan PNG.';
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO produk (nama, sku, kategori, deskripsi, harga_beli, harga, stok, min_stok, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $sku, $kategori, $deskripsi, $harga_beli, $harga, $stok, $min_stok, $fotoPath]);
            
            $_SESSION['success_msg'] = "Produk berhasil ditambahkan!";
            header("Location: kelola-produk.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - PENA-UMKM</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .breadcrumb {
            display: flex;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 12px;
        }
        .breadcrumb a {
            text-decoration: none;
            color: var(--gray-500);
        }
        .breadcrumb span {
            color: var(--gray-300);
        }
        .form-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 100px; /* Space for action bar */
        }
        .form-card {
            margin-bottom: 24px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .upload-dropzone {
            border: 2px dashed var(--gray-200);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            background: var(--gray-50);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .upload-dropzone:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .tips-list {
            list-style: none;
            padding: 0;
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        .tips-list li {
            margin-bottom: 8px;
            display: flex;
            align-items: start;
            gap: 8px;
        }
        .tips-list li::before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
        }
        .action-bar {
            position: fixed;
            bottom: 0;
            left: 260px; /* align with sidebar */
            right: 0;
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            padding: 16px 32px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            z-index: 10;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
        }
        #photo-preview {
            max-width: 100%;
            max-height: 180px;
            border-radius: 8px;
            margin-top: 12px;
            display: none;
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
        <div class="breadcrumb">
            <a href="kelola-produk.php">Inventory</a>
            <span>/</span>
            <a href="tambah-produk.php" style="font-weight:500; color:var(--gray-900);">Produk Baru</a>
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

    <!-- HEADER TITLE -->
    <div style="margin-bottom:24px;">
        <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:4px;">Detail Informasi Produk</h1>
        <p>Lengkapi informasi produk Anda dengan detail yang akurat untuk memudahkan pengelolaan stok dan pencatatan transaksi.</p>
    </div>

    <?php if ($error): ?>
        <div style="background:var(--danger-light); color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem; font-weight:500;">
            ⚠️ <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout">
            
            <!-- LEFT COLUMN: Inputs -->
            <div>
                <!-- Card 1: Informasi Produk -->
                <div class="card form-card">
                    <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <span>ℹ️</span> Informasi Produk
                    </h3>
                    
                    <div class="input-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama" placeholder="Contoh: Beras Premium 5kg" required>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <option value="Sembako">Sembako</option>
                                <option value="Snack">Snack</option>
                                <option value="Minuman">Minuman</option>
                                <option value="Bumbu Dapur">Bumbu Dapur</option>
                                <option value="Fresh Food">Fresh Food</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>SKU / Kode Produk</label>
                            <input type="text" name="sku" placeholder="Contoh: BR-001" required>
                        </div>
                    </div>

                    <div class="input-group" style="margin-bottom:0;">
                        <label>Deskripsi Produk</label>
                        <textarea name="deskripsi" placeholder="Jelaskan detail produk Anda di sini..." rows="4"></textarea>
                    </div>
                </div>

                <!-- Card 2: Detail Harga & Stok -->
                <div class="card form-card">
                    <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <span>💵</span> Detail Harga & Stok
                    </h3>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label>Harga Beli (Modal)</label>
                            <div style="display:flex; align-items:center; border:1px solid var(--gray-200); border-radius:8px; overflow:hidden; background:white;">
                                <span style="padding:10px 14px; background:var(--gray-50); font-weight:500; font-size:0.9rem; color:var(--gray-500); border-right:1px solid var(--gray-200);">Rp</span>
                                <input type="number" name="harga_beli" placeholder="0" min="0" required style="border:none; width:100%; border-radius:0; box-shadow:none;">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Harga Jual</label>
                            <div style="display:flex; align-items:center; border:1px solid var(--gray-200); border-radius:8px; overflow:hidden; background:white;">
                                <span style="padding:10px 14px; background:var(--gray-50); font-weight:500; font-size:0.9rem; color:var(--gray-500); border-right:1px solid var(--gray-200);">Rp</span>
                                <input type="number" name="harga" placeholder="0" min="0" required style="border:none; width:100%; border-radius:0; box-shadow:none;">
                            </div>
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:0;">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Stok Awal</label>
                            <input type="number" name="stok" placeholder="0" min="0" required>
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Minimum Stok Alert</label>
                            <input type="number" name="min_stok" placeholder="5" min="0" value="5" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Photo -->
            <div>
                <!-- Card 1: Foto Produk -->
                <div class="card form-card">
                    <h3 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <span>📷</span> Foto Produk
                    </h3>
                    
                    <div class="upload-dropzone">
                        <span style="font-size:2.5rem; display:block; margin-bottom:12px; color:var(--primary);">☁️</span>
                        <div style="font-weight:500; font-size:0.85rem; color:var(--gray-900); margin-bottom:4px;">Klik atau seret foto ke sini</div>
                        <p style="font-size:0.75rem; color:var(--gray-500);">Maksimal 5MB (JPG, PNG)</p>
                        <input type="file" name="foto" id="foto-input" accept="image/*">
                        
                        <img id="photo-preview" src="#" alt="Preview Foto">
                    </div>
                </div>

                <!-- Card 2: Tips Foto Bagus -->
                <div class="card form-card" style="background:#F0FDFA; border-color:rgba(13, 148, 136, 0.2);">
                    <h4 style="color:#0D9488; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        <span>💡</span> Tips Foto Bagus
                    </h4>
                    <ul class="tips-list">
                        <li>Gunakan pencahayaan terang dan alami untuk foto produk Anda.</li>
                        <li>Ambil foto dengan latar belakang polos agar fokus pada produk.</li>
                        <li>Pastikan detail tulisan kemasan produk terbaca dengan jelas.</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- STICKY ACTION BAR -->
        <div class="action-bar">
            <a href="kelola-produk.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Produk →</button>
    </form>

    <?php tampilkanFooter(); ?>
</div>

<script>
    // Preview image
    const fotoInput = document.getElementById('foto-input');
    const photoPreview = document.getElementById('photo-preview');

    fotoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.addEventListener('load', function() {
                photoPreview.setAttribute('src', this.result);
                photoPreview.style.display = 'block';
            });
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>
