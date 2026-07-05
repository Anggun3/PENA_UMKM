# PENA-UMKM 🏪

**PENA-UMKM** adalah platform Point of Sale (POS) & Manajemen Merchant modern, responsif, dan dinamis yang dirancang khusus untuk memenuhi kebutuhan digitalisasi usaha mikro, kecil, dan menengah (UMKM) di Indonesia. Platform ini dirancang untuk menyederhanakan pengelolaan produk, transaksi kasir, keuangan toko, dan analisis laporan penjualan dalam satu antarmuka premium.

---

## ✨ Fitur Utama

1. **Registrasi Dua Langkah (2-Step Registration)**
   * **Langkah 1**: Mengisi Informasi Utama Toko (Nama Toko & Alamat).
   * **Langkah 2**: Mengisi Kredensial Akun Pengguna (Nama Lengkap, Email, Password, & Konfirmasi).
2. **Pengaturan Pajak Dinamis (PPN 11% Toggle)**
   * Pemilik toko dapat mengaktifkan atau menonaktifkan pengenaan pajak PPN 11% secara instan melalui menu Pengaturan.
   * Sangat membantu bagi UMKM mikro yang ingin melakukan transaksi bersih tanpa potongan pajak.
3. **Status Operasional Toko (Buka/Tutup)**
   * Status toko tersinkronisasi secara real-time di seluruh sesi pengguna (Owner & Kasir).
   * Saat status toko **TUTUP**, sistem kasir secara otomatis mengunci fitur belanja (tidak bisa menambah item ke keranjang atau checkout) demi keamanan operasional.
4. **Dompet Merchant & Webhook Pencairan Mandiri**
   * Pencairan dana (disbursement) dihitung secara dinamis hanya dari transaksi digital non-tunai (QRIS / E-Wallet).
   * Simulator webhook terintegrasi menggunakan MD5 signature keamanan (`PENA_SECRET_KEY`) untuk memproses simulasi transfer sukses atau gagal secara instan.
5. **Navigasi Profil Cepat (Clickable Topbar)**
   * Klik pada foto profil atau nama pengguna di bagian topbar untuk langsung diarahkan ke halaman **Pengaturan Akun**.
6. **Sistem Notifikasi Terpadu & Email Alerts (Sandbox)**
   * Notifikasi web real-time (stok menipis, pembayaran QRIS sukses, status pencairan dana) pada ikon lonceng topbar.
   * Notifikasi email simulasi yang disimpan sebagai file HTML interaktif di folder `emails/`.
7. **Desain Visual Premium**
   * Menggunakan tipografi modern (Font Inter), skema warna profesional, efek glassmorphism, dan micro-interaction transisi hover yang halus.

---

## 📁 Struktur Direktori Proyek

Untuk menjaga kerapian dan keteraturan modular, berkas aplikasi ini dibagi menjadi beberapa direktori utama:

* **`admin/`**: Dasbor dan panel manajemen internal merchant (Kelola Produk, Transaksi, Laporan, Dompet Toko, Kelola Karyawan/Kasir, dan Pengaturan).
* **`kasir/`**: Halaman operasional khusus untuk transaksi POS kasir toko (`kasir.php`).
* **`api/`**: Endpoint webhook pemrosesan transaksi QRIS, webhook pencairan dana, dan pengecekan status real-time.
* **`info/`**: Halaman informasi hukum dan bantuan publik (Kebijakan Privasi, Ketentuan Layanan, Pusat Bantuan, dan Hubungi Kami).
* **`lib/`**: File inti koneksi database (`db.php`) dan pustaka fungsi helper global (`myfunc.php`).
* **`css/`**: Stylesheet global custom (`style.css`).
* **`uploads/`**: Direktori penyimpanan file gambar/foto produk yang diunggah.
* **`emails/`**: Direktori sandbox tempat salinan email notifikasi HTML disimpan secara lokal.

---

## 🛠️ Spesifikasi Teknologi

* **Backend**: PHP 8.x
* **Database**: MySQL / MariaDB (melalui PDO)
* **Frontend**: HTML5, Vanilla CSS3 (Custom Variables & Grids), Modern JavaScript (Fetch API, DOM Manipulation)
* **Keamanan**: MD5 Webhook Signature Verification, Session Control, Role-Based Access Control (RBAC)

---

## 📦 Panduan Instalasi & Konfigurasi

### 1. Prasyarat
* Pasang **XAMPP** atau web server lokal yang mendukung PHP 8.x dan MySQL.
* Pastikan port Apache dan MySQL berjalan di XAMPP Control Panel Anda.

### 2. Pemasangan Aplikasi
1. Salin atau clone repositori ini ke dalam direktori root server lokal Anda:
   * Windows: `C:\xampp\htdocs\pena_umkm\`
2. Jalankan MySQL, kemudian masuk ke phpMyAdmin (`http://localhost/phpmyadmin/`).
3. Buat database baru bernama `pena_umkm`.
4. Impor file skema database `database.sql` yang terletak di direktori utama proyek ke dalam database baru tersebut.

### 3. Konfigurasi Koneksi Database
Jika Anda menggunakan kredensial database selain default, Anda dapat menyesuaikannya di file `lib/db.php`:
```php
$host = 'localhost';
$db   = 'pena_umkm';
$user = 'root'; // Username database Anda
$pass = '';     // Password database Anda
```

### 4. Menjalankan Aplikasi
* Buka browser internet Anda dan akses URL: [http://localhost/pena_umkm](http://localhost/pena_umkm)

---

## 🔑 Kredensial Akun Default

Untuk masuk ke aplikasi tanpa mendaftar terlebih dahulu, Anda dapat menggunakan akun demo berikut:

* **Role Owner (Pemilik Toko)**
  * **Email**: `zahrazeta@webmail.umm.ac.id`
  * **Password**: `password`
* **Role Kasir**
  * Buat akun kasir baru melalui menu **Kelola User** di dashboard Owner.

---

## 👥 Dikembangkan Oleh

Platform ini dikembangkan oleh **Kelompok 7 RPL C from Universitas Muhammadiyah Malang** sebagai wujud kontribusi digitalisasi dan modernisasi pelaku usaha UMKM di Indonesia agar berdaya saing, cepat, dan transparan.

* **Lisensi & Hak Cipta**: Copyright &copy; 2026 **PENA-UMKM**. Made with ❤️ by Kelompok 7 RPL C.
# PENA_UMKM
