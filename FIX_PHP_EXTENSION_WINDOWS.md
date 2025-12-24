# Cara Mengaktifkan Extension PostgreSQL di PHP (Windows)

## Masalah
Error: `could not find driver` saat koneksi ke PostgreSQL

## Solusi

### Langkah 1: Cek Extension yang Terinstall

1. Akses file `check-php-extensions.php` di browser:
   ```
   http://localhost:8000/check-php-extensions.php
   ```

2. Atau jalankan di terminal:
   ```bash
   php -m | findstr pgsql
   ```

### Langkah 2: Aktifkan Extension di php.ini

1. **Cari file php.ini:**
   ```bash
   php --ini
   ```
   Akan muncul path seperti: `C:\php\php.ini`

2. **Buka file php.ini dengan text editor** (Notepad++, VS Code, dll)

3. **Cari baris berikut** (gunakan Ctrl+F):
   ```
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```

4. **Hapus tanda `;` di depan** untuk mengaktifkan:
   ```
   extension=pdo_pgsql
   extension=pgsql
   ```

5. **Simpan file php.ini**

### Langkah 3: Jika Extension File Tidak Ada

Jika file DLL tidak ada di folder `ext/`, Anda perlu:

#### Opsi A: Install via XAMPP (Paling Mudah)

1. Download XAMPP dari https://www.apachefriends.org/
2. Install XAMPP (sudah include PHP dengan extension PostgreSQL)
3. Aktifkan extension di `C:\xampp\php\php.ini`

#### Opsi B: Download DLL Manual

1. **Cek versi PHP Anda:**
   ```bash
   php -v
   ```

2. **Download DLL dari PECL:**
   - Kunjungi: https://windows.php.net/downloads/pecl/releases/
   - Pilih versi PHP Anda (misal: php_pdo_pgsql-1.0.2-8.2-ts-vs16-x64.zip)
   - Extract file DLL

3. **Copy DLL ke folder ext:**
   - Copy `php_pdo_pgsql.dll` ke `C:\php\ext\`
   - Copy `php_pgsql.dll` ke `C:\php\ext\`

4. **Install PostgreSQL Client Library:**
   - Download dari: https://www.postgresql.org/download/windows/
   - Install PostgreSQL (atau minimal client library)
   - Atau download libpq.dll dan copy ke folder PHP atau Windows\System32

### Langkah 4: Restart PHP Server

Setelah mengaktifkan extension:

1. **Hentikan PHP server** (Ctrl+C di terminal)
2. **Jalankan lagi:**
   ```bash
   php -S localhost:8000
   ```

3. **Cek lagi extension:**
   ```bash
   php -m | findstr pgsql
   ```
   
   Harus muncul:
   ```
   pdo_pgsql
   pgsql
   ```

### Langkah 5: Test Koneksi Database

1. Akses `http://localhost:8000/login.php`
2. Coba login atau daftar
3. Jika masih error, cek:
   - Apakah PostgreSQL server berjalan?
   - Apakah konfigurasi database di `config/database.php` benar?
   - Apakah firewall tidak memblokir port 5432?

## Troubleshooting

### Error: "Unable to load dynamic library"

**Penyebab:** DLL file tidak ditemukan atau tidak kompatibel

**Solusi:**
1. Pastikan DLL file ada di folder `ext/`
2. Pastikan versi DLL sesuai dengan versi PHP (TS/NTS, x64/x86)
3. Install PostgreSQL client library (libpq.dll)

### Error: "Class 'PDO' not found"

**Penyebab:** Extension PDO tidak aktif

**Solusi:**
1. Aktifkan `extension=pdo` di php.ini
2. Restart PHP server

### Error: "could not find driver"

**Penyebab:** Extension pdo_pgsql tidak aktif atau tidak terinstall

**Solusi:**
1. Pastikan `extension=pdo_pgsql` aktif di php.ini
2. Pastikan file `php_pdo_pgsql.dll` ada di folder `ext/`
3. Pastikan PostgreSQL client library terinstall

## Catatan

- **Thread Safe (TS) vs Non-Thread Safe (NTS):** Pastikan DLL sesuai dengan versi PHP Anda
- **x64 vs x86:** Pastikan DLL sesuai dengan arsitektur sistem (64-bit atau 32-bit)
- **PHP Version:** Pastikan DLL sesuai dengan versi PHP (7.4, 8.0, 8.1, 8.2, dll)

## Alternatif: Gunakan XAMPP

Jika kesulitan, gunakan XAMPP yang sudah include:
- PHP dengan extension lengkap
- Apache Web Server
- MySQL/PostgreSQL support

1. Install XAMPP
2. Copy project ke `C:\xampp\htdocs\bot-wa-real\`
3. Aktifkan extension di `C:\xampp\php\php.ini`
4. Start Apache dari XAMPP Control Panel
5. Akses: `http://localhost/bot-wa-real/login.php`

