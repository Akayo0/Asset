# Aplikasi Pendataan Asset Master (PHP Native + MySQL PDO)

Aplikasi web CRUD pengelolaan data asset kendaraan menggunakan PHP Native, MySQL (PDO Prepared Statements), dan Bootstrap 5 CDN.

## 🚀 Fitur Utama
- **Autentikasi & Session**: Login menggunakan `password_verify()` dan algoritma BCRYPT, proteksi session di semua halaman dashboard, dan fitur logout aman.
- **CRUD Lengkap**:
  - **Create**: Tambah asset baru dengan upload gambar unik (`time()_namafile`) dan datalist `no_lambung` dinamis (843 s/d 8164).
  - **Read**: Menampilkan tabel data asset lengkap dengan thumbnail gambar 80px dan total asset.
  - **Update**: Edit data teks dan opsi ganti foto (otomatis menghapus foto lama via `unlink()`).
  - **Delete**: Hapus data dari database sekaligus menghapus file fisik gambar di folder `uploads/` via `unlink()`.
- **Fitur Pencarian**: Pencarian cepat berdasarkan `sdt` atau `no_lambung` menggunakan SQL `LIKE` dengan PDO Parameter Binding + tombol Reset.
- **Keamanan**: Full PDO Prepared Statements (anti SQL Injection) dan `htmlspecialchars()` (anti XSS).
- **Desain Responsif**: Antarmuka bersih, rapi, dan modern dengan Bootstrap 5 dan Bootstrap Icons.

---

## 📁 Struktur Direktori
```text
asset_master/
├── uploads/              # Direktori penyimpanan foto yang di-upload
├── db_master.sql         # Script SQL pembuatan database & tabel default
├── koneksi.php           # Inisialisasi PDO MySQL & session
├── login.php             # Form login & verifikasi credential
├── logout.php            # Skrip destroy session
├── index.php             # Dashboard utama, pencarian & tabel data
├── input.php             # Form tambah data asset baru & upload gambar
├── edit.php              # Form edit data & update gambar
└── hapus.php             # Skrip hapus data & unlink foto fisik
```

---

## ⚙️ Petunjuk Instalasi & Menjalankan

### 1. Import Database
1. Buka **phpMyAdmin** atau MySQL CLI.
2. Buat database atau langsung import file `asset_master/db_master.sql`.
3. Database `db_master` dan 2 tabel (`users`, `assets`) akan otomatis terbuat.
4. Akun default telah dibuat:
   - **Username**: `admin`
   - **Password**: `password`

### 2. Konfigurasi Koneksi (Jika Diperlukan)
Sesuaikan kredensial MySQL di file `asset_master/koneksi.php`:
```php
$host    = 'localhost';
$db      = 'db_master';
$user    = 'root';
$pass    = ''; // Sesuaikan jika ada password mysql
```

### 3. Menjalankan Aplikasi
Bisa dijalankan lewat XAMPP (pindahkan folder ke `htdocs`) atau langsung menggunakan PHP Built-in Server:
```bash
cd asset_master
php -S localhost:8000
```
Buka browser dan akses: `http://localhost:8000/login.php`
