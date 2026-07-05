-- SQL Schema untuk database pena_umkm

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'kasir') NOT NULL DEFAULT 'owner',
  `nama_toko` VARCHAR(100) NOT NULL DEFAULT 'Toko Saya',
  `status_toko` ENUM('buka', 'tutup') NOT NULL DEFAULT 'buka',
  `pajak_status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'nonaktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `produk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `kategori` VARCHAR(50) NOT NULL DEFAULT 'Lainnya',
  `deskripsi` TEXT DEFAULT NULL,
  `harga_beli` INT NOT NULL DEFAULT 0,
  `harga` INT NOT NULL DEFAULT 0, -- Harga Jual
  `stok` INT NOT NULL DEFAULT 0,
  `min_stok` INT NOT NULL DEFAULT 0,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `total` INT NOT NULL DEFAULT 0,
  `metode` ENUM('tunai', 'qris') NOT NULL DEFAULT 'tunai',
  `status` ENUM('berhasil', 'batal') NOT NULL DEFAULT 'berhasil',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `detail_transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaksi_id` INT NOT NULL,
  `produk_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `harga_satuan` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pencairan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(30) NOT NULL UNIQUE,
  `nominal` INT NOT NULL DEFAULT 0,
  `bank` VARCHAR(50) NOT NULL,
  `no_rekening` VARCHAR(50) NOT NULL,
  `nama_rekening` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'berhasil', 'gagal') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
