# Cara Menjalankan Project di XAMPP

## Langkah 1: Copy Project ke htdocs

### Opsi A: Menggunakan Script (Otomatis)
Jalankan file `setup-xampp.bat` dengan double-click. Script akan:
- Membuat folder `bot-wa-real` di `C:\xampp\htdocs\`
- Copy semua file project ke folder tersebut

### Opsi B: Manual Copy
1. Buka folder XAMPP: `C:\xampp\htdocs\`
2. Buat folder baru: `bot-wa-real`
3. Copy semua file project ke folder tersebut

## Langkah 2: Aktifkan Extension PostgreSQL di PHP

1. **Buka file php.ini:**
   - Lokasi: `C:\xampp\php\php.ini`
   - Buka dengan Notepad++ atau text editor lain

2. **Cari dan aktifkan extension PostgreSQL:**
   - Tekan `Ctrl+F` untuk mencari
   - Cari: `;extension=pdo_pgsql`
   - Hapus tanda `;` di depan menjadi: `extension=pdo_pgsql`
   - Cari juga: `;extension=pgsql`
   - Hapus tanda `;` di depan menjadi: `extension=pgsql`

3. **Simpan file php.ini**

4. **Restart Apache:**
   - Buka XAMPP Control Panel
   - Klik **Stop** pada Apache
   - Klik **Start** pada Apache lagi

## Langkah 3: Verifikasi Extension

1. Buat file `test-extension.php` di folder project:
```php
<?php
if (extension_loaded('pdo_pgsql')) {
    echo "✅ Extension pdo_pgsql TERINSTALL";
} else {
    echo "❌ Extension pdo_pgsql TIDAK TERINSTALL";
}

if (extension_loaded('pgsql')) {
    echo "<br>✅ Extension pgsql TERINSTALL";
} else {
    echo "<br>❌ Extension pgsql TIDAK TERINSTALL";
}

echo "<br><br>PDO Drivers: ";
print_r(PDO::getAvailableDrivers());
?>
```

2. Akses di browser: `http://localhost/bot-wa-real/test-extension.php`

3. Pastikan muncul "✅ TERINSTALL" untuk kedua extension

## Langkah 4: Setup Database PostgreSQL

**PENTING:** Project ini menggunakan **PostgreSQL**, bukan MySQL!

### Jika PostgreSQL sudah terinstall:
1. Pastikan PostgreSQL service running
2. Cek konfigurasi di `config/database.php`:
   - Host: `192.168.18.21` (atau `localhost` jika di komputer yang sama)
   - Port: `5432`
   - Database: `absensidb`
   - User: `admin`
   - Password: `admindb`

### Jika PostgreSQL belum terinstall:
1. Download PostgreSQL: https://www.postgresql.org/download/windows/
2. Install PostgreSQL
3. Buat database `absensidb`
4. Import schema dari `database/schema.sql`

## Langkah 5: Akses Web

1. **Pastikan Apache running** di XAMPP Control Panel

2. **Buka browser** dan akses:
   ```
   http://localhost/bot-wa-real/login.php
   ```

3. **Halaman login akan muncul**

4. **Jika belum punya akun:**
   - Klik tab "Daftar"
   - Isi form registrasi
   - Login dengan akun yang baru dibuat

## Troubleshooting

### Error: "could not find driver"
- Pastikan extension PostgreSQL sudah aktif di `php.ini`
- Restart Apache setelah mengubah `php.ini`

### Error: "Database connection failed"
- Pastikan PostgreSQL service running
- Cek konfigurasi database di `config/database.php`
- Test koneksi dengan file `check-php-extensions.php`

### Error: 404 Not Found
- Pastikan folder project ada di `C:\xampp\htdocs\bot-wa-real\`
- Pastikan Apache running
- Cek URL: `http://localhost/bot-wa-real/login.php` (perhatikan huruf besar/kecil)

### Extension tidak muncul setelah diaktifkan
- Pastikan file DLL ada di `C:\xampp\php\ext\`
- Jika tidak ada, download dari: https://windows.php.net/downloads/pecl/releases/
- Restart Apache

## Struktur Folder di htdocs

Setelah setup, struktur folder akan seperti ini:
```
C:\xampp\htdocs\
└── bot-wa-real\
    ├── api\
    ├── assets\
    ├── bot\
    ├── config\
    ├── database\
    ├── models\
    ├── utils\
    ├── index.php
    ├── login.php
    └── ...
```

## Catatan Penting

1. **PostgreSQL vs MySQL:**
   - Project ini menggunakan **PostgreSQL**
   - XAMPP default menggunakan MySQL
   - Anda perlu install PostgreSQL terpisah

2. **Port Conflict:**
   - Apache default: port 80
   - PostgreSQL default: port 5432
   - Pastikan tidak ada konflik port

3. **Extension PHP:**
   - XAMPP biasanya sudah include extension PostgreSQL
   - Tapi perlu diaktifkan manual di `php.ini`

4. **Database Remote:**
   - Jika database PostgreSQL di server lain (192.168.18.21)
   - Pastikan firewall tidak memblokir port 5432
   - Pastikan PostgreSQL mengizinkan remote connection

