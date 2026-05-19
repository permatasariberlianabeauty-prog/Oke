# 🌐 Panduan Deployment NOXARA di cPanel / Shared Hosting

> **Panel:** cPanel (Hostinger, Niagahoster, IDCloudHost, dsb.)  
> **PHP:** 8.2+ (via cPanel PHP Selector / MultiPHP)  
> **Database:** MySQL 8.0+  
> **Web Server:** Apache dengan .htaccess  

---

## 📋 Prasyarat

Sebelum memulai, pastikan:

- [ ] Akses cPanel aktif (username + password)
- [ ] Hosting mendukung **PHP 8.2+**
- [ ] Kuota disk cukup (minimal 500 MB)
- [ ] Domain sudah aktif dan mengarah ke server
- [ ] File NOXARA (zip) sudah tersedia di komputer lokal

> ⚠️ **Penting:** Shared hosting mungkin memiliki batasan eksekusi cron, memory, dan upload. Pastikan paket hosting Anda memadai untuk aplikasi ini.

---

## 📁 Langkah 1: Upload File via cPanel File Manager

### Metode A: Via File Manager cPanel

1. Login ke cPanel Anda
2. Klik **File Manager**
3. Navigasi ke `public_html/` (atau subdomain folder jika menggunakan subdomain)
4. Klik **Upload** (pojok atas)
5. Pilih file `noxara.zip` dari komputer Anda
6. Tunggu upload selesai
7. Kembali ke File Manager, klik kanan file zip → **Extract**
8. Jika file terekstrak ke subfolder, pindahkan isinya ke `public_html/`

### Metode B: Via FTP (FileZilla)

1. Buka FileZilla
2. Masukkan kredensial FTP dari cPanel:
   - Host: `ftp.domain-anda.com`
   - Username: `ftp-username`
   - Password: `ftp-password`
   - Port: `21`
3. Upload seluruh folder NOXARA ke `public_html/`

### Pastikan struktur folder:
```
public_html/
├── index.php
├── manifest.json
├── service-worker.js
├── .htaccess
├── admin/
├── api/
├── assets/
├── auth/
├── config/
├── cron/
├── database/
├── includes/
├── install/
├── pages/
└── uploads/
```

---

## 📂 Langkah 2: Set Permissions File

Di cPanel File Manager:

1. Pilih folder `uploads/` → klik kanan → **Change Permissions** → `775`
2. Lakukan hal sama untuk:
   - `logs/` → `775`
   - `backups/` → `775`
   - `config/` → `755` (bisa ditulis saat install)

Via SSH (jika tersedia):
```bash
chmod -R 775 public_html/uploads
chmod -R 775 public_html/logs
chmod -R 775 public_html/backups
chmod 755 public_html/config
```

---

## 🗄️ Langkah 3: Buat Database MySQL di cPanel

1. cPanel → **MySQL Databases**
2. Di bagian **Create New Database:**
   - Nama: `noxara_db` (cPanel akan otomatis menambahkan prefix username, misal: `cpusr_noxara_db`)
   - Klik **Create Database**
3. Di bagian **MySQL Users** → **Add New User:**
   - Username: `noxara_usr` (akan jadi `cpusr_noxara_usr`)
   - Password: buat yang kuat (gunakan generator)
   - Klik **Create User**
4. Di bagian **Add User To Database:**
   - User: pilih user yang baru dibuat
   - Database: pilih database yang baru dibuat
   - Klik **Add**
   - Centang **All Privileges** → **Make Changes**

> ⚠️ **Catat** nama database dan username yang **lengkap dengan prefix**! Contoh: `cpusr_noxara_db` dan `cpusr_noxara_usr`

---

## 📥 Langkah 4: Import Database

### Via phpMyAdmin (cPanel):

1. cPanel → **phpMyAdmin**
2. Pilih database `cpusr_noxara_db` di panel kiri
3. Klik tab **Import**
4. Klik **Choose File** → pilih `database/dashboard.sql` dari komputer
5. Format: `SQL`
6. Klik **Go / Import**
7. Tunggu hingga muncul pesan sukses

### Via cPanel MySQL Import:

1. cPanel → **MySQL Databases** → **Database Backups**
2. Pilih database → **Restore from backup**
3. Upload file `.sql`

---

## ⚙️ Langkah 5: Set PHP 8.2 di cPanel

### Via MultiPHP Manager:

1. cPanel → **Software** → **MultiPHP Manager**
2. Centang domain/folder `public_html`
3. Pilih PHP versi: **PHP 8.2**
4. Klik **Apply**

### Via PHP Selector (CloudLinux/Softaculous hosting):

1. cPanel → **Software** → **Select PHP Version**
2. Pilih **PHP 8.2**
3. Pastikan ekstensi berikut aktif:
   - ✅ `mysqli`
   - ✅ `pdo_mysql`
   - ✅ `gd`
   - ✅ `openssl`
   - ✅ `mbstring`
   - ✅ `zip`
   - ✅ `fileinfo`
   - ✅ `curl`
4. Klik **Set as current**

### Sesuaikan PHP.ini via cPanel:

1. cPanel → **Software** → **MultiPHP INI Editor**
2. Pilih folder `public_html`
3. Ubah nilai:
   ```
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 120
   memory_limit = 256M
   ```
4. Klik **Apply**

---

## 🔑 Langkah 6: Konfigurasi .htaccess

File `.htaccess` sudah disertakan di proyek. Pastikan file ada di `public_html/` dan mod_rewrite aktif.

Jika situs tidak bisa diakses setelah upload, coba tambahkan di atas file `.htaccess`:

```apache
Options +FollowSymLinks
```

Untuk subdirectory (misal `public_html/noxara/`), ubah `RewriteBase`:
```apache
RewriteBase /noxara/
```

---

## 🧙 Langkah 7: Jalankan Install Wizard

1. Buka browser: `https://domain-anda.com/install/`
2. **Step 1 - Persyaratan:** Pastikan semua ✓ hijau
   - Jika ada ✗ merah, perbaiki dulu (lihat langkah 5)
3. **Step 2 - Database:** Isi dengan kredensial database:
   - DB Host: `localhost`
   - DB Port: `3306`
   - DB Name: `cpusr_noxara_db` ← gunakan nama lengkap dengan prefix!
   - DB User: `cpusr_noxara_usr` ← gunakan username lengkap!
   - DB Pass: password yang dibuat
   - Base URL: `https://domain-anda.com`
4. Klik **Test Koneksi** — harus berhasil
5. **Step 3 - Import SQL:** Klik tombol import
6. **Step 4 - Admin:** Buat akun superadmin
7. **Step 5 - Finalize:** Selesaikan instalasi

---

## 👑 Langkah 8: Login ke Admin Panel

1. Buka: `https://domain-anda.com/admin/login.php`
2. Login dengan username dan password dari Step 4 wizard
3. Lakukan konfigurasi awal:
   - Setting nama platform
   - Upload logo
   - Konfigurasi payment method
   - Atur VIP levels

---

## 🔐 Langkah 9: Ubah Password Admin SEGERA

1. Admin Panel → profil admin → **Ganti Password**
2. Gunakan password minimal 12 karakter dengan kombinasi huruf, angka, dan simbol
3. Aktifkan notifikasi email untuk login admin

> ⚠️ **WAJIB:** Jangan simpan password default!

---

## ⏰ Langkah 10: Setup Cron Jobs di cPanel

1. cPanel → **Advanced** → **Cron Jobs**
2. **Recommended time setting:** Gunakan *Once Per Day (0 0 * * *)* sebagai dasar
3. Tambahkan cron jobs berikut:

| Common Settings | Command |
|----------------|---------|
| Once Per Day (00:05) | `php /home/cpusr/public_html/cron/daily_profit.php` |
| Once Per Hour | `php /home/cpusr/public_html/cron/check_packages.php` |
| Once Per Day (00:01) | `php /home/cpusr/public_html/cron/reset_missions.php` |
| Once Per Day (03:00) | `php /home/cpusr/public_html/cron/backup.php` |

> ⚠️ Ganti `/home/cpusr/` dengan path home account cPanel Anda yang sebenarnya!

Cara mengetahui path lengkap:
1. cPanel → File Manager → perhatikan path di address bar
2. Biasanya: `/home/username/public_html/`

Contoh cron command lengkap:
```
/usr/local/bin/php /home/myusername/public_html/cron/daily_profit.php > /dev/null 2>&1
```

---

## 🗑️ Langkah 11: Amankan Folder Install

Setelah instalasi berhasil:

### Opsi A: Rename folder install
```
install/ → _install_bak/
```
(via File Manager cPanel — rename folder)

### Opsi B: Blokir via .htaccess
Tambahkan di `public_html/.htaccess`:
```apache
# Block install directory
RewriteRule ^install/ - [F,L]
```

### Opsi C: Hapus folder install
Di File Manager → pilih folder `install/` → Delete

---

## 🔍 Langkah 12: Verifikasi Instalasi

Cek semua fitur berfungsi:

- [ ] Landing page tampil dengan benar
- [ ] Registrasi akun baru berhasil
- [ ] Login berhasil
- [ ] Dashboard tampil data
- [ ] Deposit form bisa diakses
- [ ] Admin panel bisa login
- [ ] Cron berjalan (cek di admin cron status)

---

## 🛠️ Troubleshooting cPanel

### Error 500 Internal Server Error

1. Cek error log: cPanel → **Error Logs**
2. Atau: File Manager → `logs/php_errors.log`
3. Solusi umum:
   - PHP belum 8.2 → ganti di MultiPHP Manager
   - mod_rewrite tidak aktif → hubungi hosting support
   - Permission folder config → set ke 755

### "Database connection failed"

- Pastikan nama DB dan username **lengkap dengan prefix** cPanel
- Cek: DB Host harus `localhost` (bukan IP)
- Verifikasi user sudah diberikan akses ke database

### File upload tidak bisa

```
# Di .htaccess atau php.ini.user:
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

### Halaman menampilkan kode PHP

- PHP belum aktif/tidak didukung di hosting
- Cek versi PHP di MultiPHP Manager
- Pastikan file berekstensi `.php` bukan `.php.txt`

### Cron tidak berjalan

- Gunakan path PHP absolut: `/usr/local/bin/php` atau `/usr/bin/php`
- Cari path PHP yang benar: di cPanel terminal: `which php`
- Beberapa hosting murah tidak mendukung cron → pertimbangkan upgrade paket

### URL tidak bisa diakses (404)

- Pastikan file `.htaccess` ada dan tidak kosong
- Mod_rewrite harus aktif (hubungi hosting support untuk mengaktifkan)
- Cek RewriteBase sesuai dengan lokasi instalasi

---

## 📊 Perbandingan Hosting yang Direkomendasikan

| Hosting | PHP 8.2 | Cron | SSH | Rekomendasi |
|---------|---------|------|-----|-------------|
| Niagahoster Business | ✅ | ✅ | ✅ | ⭐⭐⭐⭐ |
| Hostinger Premium | ✅ | ✅ | ✅ | ⭐⭐⭐⭐ |
| IDCloudHost | ✅ | ✅ | ✅ | ⭐⭐⭐⭐ |
| DomaiNesia | ✅ | ✅ | Limited | ⭐⭐⭐ |

> 💡 **Rekomendasi:** Untuk performa terbaik, gunakan **VPS + aaPanel** (lihat panduan VPS). Shared hosting cocok untuk testing atau traffic rendah.

---

## 📞 Bantuan Teknis

Jika masih mengalami masalah:

1. Cek error log di: `logs/php_errors.log`
2. Aktifkan debug mode sementara di `config/config.php`:
   ```php
   define('APP_ENV', 'development');
   ```
3. Cek admin panel: `https://domain/admin/cron_status.php`

---

*Panduan ini dibuat untuk NOXARA v1.0.0. Diperbarui sesuai perkembangan platform.*
