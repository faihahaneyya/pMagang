# Sistem Informasi Inventaris Sewa Beli — PT Banyan Koalindo Lestari

Aplikasi manajemen inventaris kantor dan pelacakan kontrak sewa beli berbasis QR Code dan Barcode Code 128. Dibangun dengan arsitektur **terpisah**:
- **Backend:** Laravel 12 (REST API, generator barcode/QR vektor, ekspor PDF/Excel, Blade label print) di folder `backend/`.
- **Frontend:** React 19 + Vite (Single Page Application modern, dashboard statistik, CRUD inventaris & kontrak, scan publik aset) di folder `frontend/`.

Pengguna dan administrator cukup mengakses **satu alamat web** (Single Web Address) tanpa perlu berpindah-pindah port.

---

## 📋 Persyaratan Sistem

- **PHP:** Versi 8.2 atau lebih baru (dengan ekstensi `pdo_mysql`, `gd`, `openssl`, `mbstring`, `bcmath`, `fileinfo`)
- **Composer:** Versi 2.x
- **Node.js:** Versi 18.x atau lebih baru & **npm**
- **Database:** MySQL / MariaDB (Database default: `inventaris_sewa_beli`)
- **Web Server (Produksi):** Nginx (direkomendasikan) atau Apache / Laragon

---

## 🚀 1. Cara Menjalankan Development (Satu Perintah dari Root)

Untuk memudahkan pengembangan, Anda **tidak perlu** membuka dua terminal terpisah. Cukup jalankan satu perintah dari folder root `C:\pMagang`:

```powershell
# Dari direktori root C:\pMagang:
npm run dev
```

Perintah ini akan secara otomatis menjalankan:
1. **Backend Laravel:** `php artisan serve` pada `http://localhost:8000` (hanya diakses via internal proxy).
2. **Frontend React + Vite:** `vite --host` pada port `5173`.

### 🌐 Akses Aplikasi
Buka browser dan buka alamat frontend:
- **Alamat Aplikasi:** [http://localhost:5173](http://localhost:5173)
- Halaman Publik Scan Aset: `http://localhost:5173/aset/{kode_inventaris}`
- Cetak Label Satuan: `http://localhost:5173/admin/barcode/{kode_inventaris}/print`
- Cetak Label Massal: `http://localhost:5173/admin/barcode/print-all`

Semua request `/api`, `/storage`, dan `/admin/barcode` secara otomatis diteruskan oleh Vite Proxy ke backend Laravel di port 8000.

---

## 📱 2. Cara Tes Scan QR dari Smartphone (HP di Wi-Fi Lokal)

Agar QR Code pada label inventaris dapat dipindai langsung dari kamera HP dan membuka halaman informasi aset, ikuti langkah berikut:

### Langkah A: Cari IP Lokal Laptop Anda
Buka PowerShell atau Command Prompt pada laptop, lalu ketik:
```powershell
ipconfig
```
Cari bagian **IPv4 Address** pada adapter Wi-Fi Anda, misalnya: `192.168.1.116`.

### Langkah B: Perbarui `FRONTEND_URL` di Backend
Buka berkas `backend/.env` dan ubah `FRONTEND_URL` menggunakan IP laptop Anda:
```env
FRONTEND_URL=http://192.168.1.116:5173
```
Setelah mengubah `.env`, bersihkan cache konfigurasi Laravel:
```powershell
cd C:\pMagang\backend
php artisan config:clear
```
*(Langkah ini memastikan setiap QR Code yang dibuat mengodekan URL yang dapat dijangkau oleh perangkat lain di jaringan Wi-Fi, bukan `localhost`).*

### Langkah C: Izinkan Port 5173 di Windows Firewall
Agar smartphone dapat mengakses port Vite (5173) di laptop Anda:
1. Buka PowerShell sebagai **Administrator**.
2. Jalankan perintah pembuatan aturan masuk (Inbound Rule):
   ```powershell
   New-NetFirewallRule -DisplayName "Vite Dev Server (Port 5173)" -Direction Inbound -LocalPort 5173 -Protocol TCP -Action Allow
   ```
   *(Atau secara manual izinkan aplikasi `Node.js` pada menu Windows Defender Firewall).*

### Langkah D: Pindai Label dengan Smartphone
1. Pastikan smartphone dan laptop terhubung ke **jaringan Wi-Fi yang sama**.
2. Jalankan aplikasi dengan `npm run dev` di root.
3. Buka halaman cetak label di laptop (`http://localhost:5173/admin/barcode/INV-IT-001/print`).
4. Arahkan kamera smartphone ke QR Code yang tampil di layar atau label yang dicetak.
5. Smartphone akan otomatis membuka URL `http://192.168.1.116:5173/aset/INV-IT-001` dan menampilkan rincian spesifikasi aset kantor PT Banyan Koalindo Lestari secara responsif.

---

## 🔒 3. Pembuatan Akun Administrator Pertama

Jika database tabel `users` masih kosong, buat akun administrator pertama melalui terminal:

```powershell
cd C:\pMagang\backend
php artisan make:admin
```
Masukkan nama lengkap, alamat email, dan kata sandi Anda secara interaktif (input password aman dan tersembunyi).

---

## 🚢 4. Langkah Deploy ke Server Produksi (Single-Origin)

Pada server produksi, seluruh frontend dan backend disajikan di bawah satu domain (misalnya `https://inventaris.bkl.co.id`) menggunakan reverse proxy web server.

### Tahap 1: Build Aset Frontend React
Jalankan perintah build dari root:
```bash
npm run build:frontend
```
Hasil build statis akan tersimpan di folder `frontend/dist/`.

### Tahap 2: Persiapan Backend Laravel
Masuk ke folder `backend/` pada server produksi:
```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Pastikan pengaturan `.env` produksi:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://inventaris.bkl.co.id
FRONTEND_URL=https://inventaris.bkl.co.id
```

### Tahap 3: Konfigurasi Web Server

#### A. Menggunakan Nginx (LEMP Stack)
Salin berkas `nginx.conf.example` ke konfigurasi Nginx Anda (`/etc/nginx/sites-available/inventaris.conf`):
- `root` diarahkan ke berkas statis React: `/var/www/pMagang/frontend/dist`
- Blok `location ^~ /api` dan `location ^~ /admin/barcode` diteruskan ke `backend/public/index.php` via FastCGI.
- Blok `location ^~ /storage/` melayani file unggahan foto barang langsung.
- Blok `location /` mengarahkan semua rute SPA ke `index.html`.
- **Penting:** Pastikan socket PHP-FPM disesuaikan dengan versi PHP di server Anda (misal `unix:/run/php/php8.2-fpm.sock` atau `php8.3-fpm.sock`).

#### B. Menggunakan Apache / Laragon / XAMPP
Gunakan template virtual host pada `apache.conf.example`:
- `DocumentRoot` diarahkan ke `frontend/dist`.
- Gunakan direktif `Alias /api` dan `Alias /admin/barcode` ke folder `backend/public`.
- Gunakan direktif `Alias /storage` ke folder `backend/storage/app/public`.
- Aturan `RewriteCond %{REQUEST_URI} !^/(api|storage|admin/barcode)` pada DocumentRoot memastikan panggilan backend tidak jatuh ke SPA `index.html`.

---

## 💾 5. Panduan Backup Database & Berkas Upload Sebelum Deployment

Sebelum melakukan deployment, migrasi database, atau pemeliharaan server produksi, **wajib** melakukan pencadangan (backup) data dan aset:

### A. Pencadangan Database MySQL
Jalankan utilitas `mysqldump` dari terminal server:
```bash
# Backup terkompresi dengan stempel waktu:
mysqldump -u [username_db] -p inventaris_sewa_beli | gzip > backup_inventaris_$(date +%Y%m%d_%H%M%S).sql.gz
```
*Prosedur pemulihan (restore) database bila diperlukan:*
```bash
gunzip < backup_inventaris_YYYYMMDD_HHMMSS.sql.gz | mysql -u [username_db] -p inventaris_sewa_beli
```

### B. Pencadangan Berkas Foto Fisik Inventaris
Arsipkan seluruh berkas foto aset yang diunggah ke folder `storage`:
```bash
tar -czvf backup_photos_$(date +%Y%m%d_%H%M%S).tar.gz -C backend/storage/app/public .
```
*Prosedur pemulihan berkas foto:*
```bash
tar -xzvf backup_photos_YYYYMMDD_HHMMSS.tar.gz -C backend/storage/app/public/
```

### C. Checklist Hak Akses Berkas di Server (Permissions)
Pastikan user web server (`www-data` / `nginx` / `apache`) memiliki izin tulis pada direktori cache dan penyimpanan:
```bash
sudo chown -R www-data:www-data /var/www/pMagang/backend/storage /var/www/pMagang/backend/bootstrap/cache
sudo chmod -R 775 /var/www/pMagang/backend/storage /var/www/pMagang/backend/bootstrap/cache
```

---

## 🧪 6. Menjalankan Pengujian (Testing)

Untuk memastikan seluruh modul backend berfungsi dengan baik dan bebas dari regresi:

```powershell
# Jalankan seluruh test suite backend Laravel:
cd C:\pMagang\backend
php artisan test
```

Untuk menguji build frontend:
```powershell
# Dari direktori root C:\pMagang:
npm run build:frontend
```

---

## 👥 Hak Cipta & Pengembang
**PT Banyan Koalindo Lestari** — Sistem Informasi Inventaris Sewa Beli.
