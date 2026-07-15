# 🏪 Sistem Informasi Manajemen Usaha Rumahan (ERP)

[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue.svg)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/database-PostgreSQL-blue.svg)](https://www.postgresql.org/)
[![Bootstrap](https://img.shields.io/badge/frontend-Bootstrap%205-purple.svg)](https://getbootstrap.com/)
[![Academic Project](https://img.shields.io/badge/course-Basis%20Data%20P3-green.svg)](#)

Aplikasi web **Sistem Informasi Manajemen Usaha Rumahan** adalah sebuah sistem berbasis web (ERP sederhana) yang dirancang untuk mengelola seluruh rantai operasional usaha manufaktur rumahan/industri kecil. Sistem ini mencakup pencatatan data master, pengelolaan bahan baku, proses produksi, hingga transaksi pembelian bahan baku dan penjualan produk jadi ke pelanggan perorangan maupun bisnis.

Proyek ini dikembangkan sebagai tugas besar praktikum mata kuliah **Basis Data (P3)**.

---

## 👥 Anggota Kelompok 10
Sistem ini dirancang dan dibangun oleh:
1. **Aditya Cahyo Nugroho** (NIM: `M0403241109`)
2. **Dhikral Baihaqi** (NIM: `M0403241168`)
3. **Keyla Nurul Arafah** (NIM: `M0403241059`)
4. **Raihanah Azka Zhafira** (NIM: `M0403241016`)

---

## 🌟 Fitur Utama

Sistem ini memiliki berbagai modul fungsional yang saling terintegrasi:

### 1. 📊 Dashboard Owner
Halaman utama yang menyajikan ringkasan kinerja keuangan dan operasional usaha secara real-time:
*   **Keuangan:** Total Pemasukan (Penjualan), Total Pengeluaran (Pembelian), dan Laba Bersih bulanan (dengan filter bulan & tahun).
*   **Notifikasi Stok:** Peringatan otomatis untuk stok bahan baku dan produk jadi yang kritis (stok $\le 20$).
*   **Analitik Sederhana:** Daftar produk terlaris dan pelanggan dengan total belanja terbesar (*Pelanggan Sultan*).

### 2. 💸 Transaksi & Produksi
*   **Transaksi Pembelian:** Pencatatan pengadaan bahan baku dari pemasok (supplier), lengkap dengan kalkulasi total harga beli.
*   **Sesi Produksi:** Pencatatan proses manufaktur yang mengubah bahan baku menjadi produk jadi siap jual.
*   **Transaksi Penjualan:** Pencatatan pesanan produk jadi oleh pelanggan dengan kalkulasi otomatis total pendapatan.

### 3. 📦 Manajemen Stok
*   **Bahan Baku:** Daftar persediaan bahan baku beserta kuantitas (stok) dan satuan ukurnya.
*   **Produk Jadi:** Daftar produk akhir yang siap dipasarkan beserta kuantitas stoknya.

### 4. 🗂️ Data Master
*   **Data Pemasok (Supplier):** Informasi pemasok untuk kebutuhan restock bahan baku.
*   **Data Karyawan:** Manajemen data staf/karyawan yang mengelola operasional.
*   **Data Pelanggan:** Informasi pelanggan yang terbagi menjadi tipe **Perorangan** dan **Bisnis** (pelanggan korporat/toko).

---

## 🛠️ Teknologi & Arsitektur

*   **Bahasa Pemrograman:** PHP (Native menggunakan PDO)
*   **Basis Data:** PostgreSQL (driver `pgsql`)
*   **Antarmuka (Frontend):** 
    *   Bootstrap 5 (Responsive Layout & Components)
    *   Bootstrap Icons
    *   Google Fonts (Inter)
    *   Vanilla CSS (`assets/css/style.css` untuk kustomisasi tema)

---

## 🚀 Panduan Instalasi & Konfigurasi

### 1. Prasyarat Sistem
Pastikan perangkat Anda sudah terinstal:
*   Web Server (seperti Apache / Laragon / XAMPP) yang mendukung PHP $\ge 8.0$.
*   PostgreSQL Database Server.
*   Ekstensi PHP PDO PostgreSQL (`pdo_pgsql`) diaktifkan di file `php.ini`.

### 2. Kloning Repositori
Kloning repositori ini ke direktori web root Anda (misalnya `htdocs` atau `www`):
```bash
git clone https://github.com/username/Basis-Data-Project.git usaharumahan
```

### 3. Konfigurasi Database
1. Buat database baru di PostgreSQL dengan nama `manajemenusaharumahan`.
2. Impor struktur tabel dan data awal dari file database SQL Anda (jika ada).
3. Sesuaikan konfigurasi koneksi database di file `config/db.php`:
   ```php
   $host = "localhost";
   $port = "5432";
   $dbname = "manajemenusaharumahan";
   $user = "postgres";      // Sesuaikan username PostgreSQL Anda
   $password = "password";  // Sesuaikan password PostgreSQL Anda
   ```

### 4. Menjalankan Aplikasi
*   Akses aplikasi melalui browser di URL: `http://localhost/usaharumahan` (atau sesuaikan dengan konfigurasi `$base_url` di `config/db.php`).

---

## 📝 Catatan Tambahan (Komentar Kode)
Untuk mempermudah pemahaman alur sistem dan fungsi-fungsi logika di dalam program, kami telah menyertakan komentar penjelasan pada setiap blok kode penting. 

> [!NOTE]
> Beberapa komentar di dalam kode ditulis dalam bahasa yang santai/non-formal karena awalnya digunakan untuk koordinasi internal tim kami selama proses pengerjaan yang cepat. Kami mohon maaf jika terdapat penulisan komentar yang kurang formal.