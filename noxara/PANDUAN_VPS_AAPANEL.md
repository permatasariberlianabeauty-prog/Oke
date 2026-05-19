# 🚀 Panduan Deployment NOXARA di VPS dengan aaPanel

> **Platform:** VPS Linux (Ubuntu 20.04/22.04 / CentOS 7/8)  
> **Panel:** aaPanel (aapanel.com)  
> **PHP:** 8.2+  
> **Database:** MySQL 8.0 / MariaDB 10.6+  

---

## 📋 Prasyarat

Sebelum memulai, pastikan Anda memiliki:

- [ ] VPS dengan minimal **2 GB RAM**, **20 GB SSD**
- [ ] aaPanel sudah terinstall dan bisa diakses
- [ ] Domain sudah diarahkan ke IP VPS (DNS A record)
- [ ] PHP 8.2 sudah diinstall di aaPanel
- [ ] MySQL 8.0 / MariaDB terinstall
- [ ] Nginx terinstall (direkomendasikan vs Apache)
- [ ] File NOXARA (zip/tar.gz) sudah tersedia

---

## 📁 Langkah 1: Upload dan Ekstrak File

### Via aaPanel File Manager:

1. Login ke aaPanel: `http://IP-VPS:PORT/PANEL_TOKEN`
2. Klik **File Manager** di menu kiri
3. Navigasi ke `/www/wwwroot/`
4. Klik **Upload** → pilih file zip NOXARA
5. Setelah upload selesai, klik kanan file zip → **Extract**
6. Rename folder hasil ekstrak menjadi `noxara.page` (atau domain Anda)

### Via SSH/SCP (lebih cepat untuk file besar):

```bash
# Upload via SCP dari komputer lokal
scp noxara.zip root@IP-VPS:/www/wwwroot/

# SSH ke VPS
ssh root@IP-VPS

# Ekstrak
cd /www/wwwroot/
unzip noxara.zip -d noxara.page

# Set permissions
chown -R www:www /www/wwwroot/noxara.page
find /www/wwwroot/noxara.page -type d -exec chmod 755 {} \;
find /www/wwwroot/noxara.page -type f -exec chmod 644 {} \;

# Folder yang perlu writable
chmod -R 775 /www/wwwroot/noxara.page/uploads
chmod -R 775 /www/wwwroot/noxara.page/logs
chmod -R 775 /www/wwwroot/noxara.page/backups
chmod -R 775 /www/wwwroot/noxara.page/config
```

---

## 🗄️ Langkah 2: Buat Database di aaPanel

1. Di aaPanel, klik **Database** → **MySQL**
2. Klik tombol **Add Database**
3. Isi form:
   - **Database Name:** `noxara_db`
   - **Username:** `noxara_user`
   - **Password:** buat password kuat (catat!)
   - **Character Set:** `utf8mb4`
4. Klik **Submit**

> ⚠️ Catat nama database, username, dan password — akan dipakai di langkah berikutnya.

---

## 📥 Langkah 3: Import Database (opsional — installer bisa melakukan ini)

Jika ingin import manual:

1. Di aaPanel → **Database** → klik **PhpMyAdmin** atau **Import**
2. Pilih database `noxara_db`
3. Import file: `/www/wwwroot/noxara.page/database/dashboard.sql`
4. Tunggu hingga proses selesai
5. Verifikasi: pastikan tabel-tabel berhasil dibuat

Atau via SSH:

```bash
mysql -u noxara_user -p noxara_db < /www/wwwroot/noxara.page/database/dashboard.sql
```

---

## ⚙️ Langkah 4: Konfigurasi PHP 8.2 di aaPanel

### Install PHP 8.2 (jika belum):

1. aaPanel → **App Store** → cari **PHP 8.2**
2. Klik **Install**
3. Tunggu proses instalasi selesai

### Install ekstensi PHP yang diperlukan:

1. aaPanel → **App Store** → **PHP 8.2** → **Settings**
2. Klik tab **Install Extensions**
3. Install ekstensi berikut (jika belum ada):
   - `mysqli`
   - `pdo_mysql`
   - `gd`
   - `openssl`
   - `mbstring`
   - `zip`
   - `fileinfo`
   - `curl`

### Sesuaikan PHP settings:

1. PHP 8.2 → **Settings** → **Configuration**
2. Ubah nilai berikut:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 120
   memory_limit = 256M
   date.timezone = Asia/Jakarta
   ```
3. Klik **Save**

---

## 🌐 Langkah 5: Buat Website di aaPanel

1. aaPanel → **Website** → **Add Site**
2. Isi form:
   - **Domain:** `noxara.page` (dan `www.noxara.page` sebagai alias)
   - **Root Directory:** `/www/wwwroot/noxara.page`
   - **PHP Version:** `PHP 8.2`
   - **Database:** `noxara_db` (pilih yang sudah dibuat)
3. Klik **Submit**

---

## 📝 Langkah 6: Pasang Konfigurasi Nginx

### Opsi A: Gunakan nginx.conf dari proyek:

1. aaPanel → **Website** → klik nama domain → **Config**
2. Tab **Nginx Configuration**
3. Ganti isi dengan konten dari file `nginx.conf` di proyek
4. **Penting:** Sesuaikan path:
   - `root` → `/www/wwwroot/noxara.page`
   - `fastcgi_pass` → sesuaikan socket PHP-FPM versi yang digunakan:
     - PHP 8.2: `unix:/tmp/php-cgi-82.sock`
   - `server_name` → domain Anda
5. Klik **Save**

### Opsi B: Edit via SSH:

```bash
# Cek path config nginx aaPanel
cat /www/server/panel/vhost/nginx/noxara.page.conf

# Edit
nano /www/server/panel/vhost/nginx/noxara.page.conf
```

---

## 🔒 Langkah 7: Pasang SSL (HTTPS)

1. aaPanel → **Website** → nama domain → **SSL**
2. Tab **Let's Encrypt**
3. Centang domain: `noxara.page` dan `www.noxara.page`
4. Klik **Apply**
5. Setelah berhasil, aktifkan **Force HTTPS**

Atau gunakan Cloudflare untuk SSL gratis.

---

## 🔄 Langkah 8: Restart Nginx & PHP-FPM

```bash
# Restart Nginx
/etc/init.d/nginx restart
# atau
nginx -s reload

# Restart PHP-FPM 8.2
/etc/init.d/php-fpm-82 restart
```

Atau via aaPanel → klik **Restart** pada Nginx dan PHP-FPM di halaman utama.

---

## ⏰ Langkah 9: Setup Cron Jobs di aaPanel

1. aaPanel → **Cron**
2. Klik **Add Cron Job**
3. Tambahkan cron jobs berikut:

| Nama              | Tipe      | Waktu            | Script |
|-------------------|-----------|------------------|--------|
| Daily Profit      | Shell Script | Setiap hari 00:05 | `php /www/wwwroot/noxara.page/cron/daily_profit.php` |
| Check Packages    | Shell Script | Setiap jam       | `php /www/wwwroot/noxara.page/cron/check_packages.php` |
| Reset Missions    | Shell Script | Setiap hari 00:01 | `php /www/wwwroot/noxara.page/cron/reset_missions.php` |
| Auto Backup       | Shell Script | Setiap hari 03:00 | `php /www/wwwroot/noxara.page/cron/backup.php` |

Atau tambahkan manual di crontab:

```bash
crontab -e

# Tambahkan:
5 0 * * * php /www/wwwroot/noxara.page/cron/daily_profit.php >> /www/wwwroot/noxara.page/logs/cron_profit.log 2>&1
0 * * * * php /www/wwwroot/noxara.page/cron/check_packages.php >> /www/wwwroot/noxara.page/logs/cron_packages.log 2>&1
1 0 * * * php /www/wwwroot/noxara.page/cron/reset_missions.php >> /www/wwwroot/noxara.page/logs/cron_missions.log 2>&1
0 3 * * * php /www/wwwroot/noxara.page/cron/backup.php >> /www/wwwroot/noxara.page/logs/cron_backup.log 2>&1
```

---

## 🧙 Langkah 10: Jalankan Install Wizard

1. Buka browser: `https://noxara.page/install/`
2. **Step 1 - Requirements:** Pastikan semua ✓ hijau
3. **Step 2 - Database:** Masukkan kredensial database yang sudah dibuat
4. Klik **Test Koneksi** — pastikan berhasil
5. **Step 3 - Import SQL:** Klik tombol import
6. **Step 4 - Admin:** Buat akun admin pertama
7. **Step 5 - Finalize:** Klik Selesaikan Instalasi

---

## 👑 Langkah 11: Login ke Admin Panel

1. Buka: `https://noxara.page/admin/login.php`
2. Gunakan username dan password yang dibuat di Step 4 wizard
3. Login dan eksplorasi fitur admin

---

## 🔐 Langkah 12: Ubah Password Admin SEGERA

1. Di admin panel → **Settings** → **Security**
2. Ubah password menjadi password yang kuat dan unik
3. Aktifkan 2FA jika tersedia
4. Simpan kredensial di password manager

> ⚠️ **WAJIB:** Jangan gunakan password default! Ubah segera setelah login pertama.

---

## 🗑️ Langkah 13: Amankan/Hapus Folder Install

Setelah instalasi berhasil, blokir akses ke folder install:

### Opsi A: Hapus folder install
```bash
rm -rf /www/wwwroot/noxara.page/install/
```

### Opsi B: Blokir via Nginx
Tambahkan di nginx config (sudah ada komentar di `nginx.conf`):
```nginx
location ^~ /install/ {
    deny all;
    return 403;
}
```

### Opsi C: Biarkan .installed ada
File `install/.installed` sudah mengunci wizard secara otomatis. Tapi menghapus folder lebih aman.

---

## 🔧 Troubleshooting Umum

### Error 500 / Halaman Putih
```bash
# Cek error log PHP
tail -f /www/wwwroot/noxara.page/logs/php_errors.log

# Cek error log Nginx
tail -f /www/wwwlogs/noxara.page.error.log

# Set APP_ENV ke development sementara di config.php
define('APP_ENV', 'development');
```

### Permission denied pada upload
```bash
chown -R www:www /www/wwwroot/noxara.page/uploads
chmod -R 775 /www/wwwroot/noxara.page/uploads
```

### Database connection failed
- Cek kredensial di `config/config.php`
- Pastikan user MySQL punya hak akses ke database

### Cron tidak berjalan
```bash
# Cek apakah cron service berjalan
systemctl status cron
# atau
service crond status

# Test manual
php /www/wwwroot/noxara.page/cron/daily_profit.php
```

---

## 📞 Bantuan

Jika mengalami masalah, cek:
- Log PHP: `logs/php_errors.log`
- Log Nginx: `/www/wwwlogs/noxara.page.error.log`
- Admin cron status: `https://noxara.page/admin/cron_status.php`

---

*Panduan ini dibuat untuk NOXARA v1.0.0 pada aaPanel. Disesuaikan jika versi aaPanel atau PHP berbeda.*
