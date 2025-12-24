# Bot WhatsApp Absensi - PHP Version

Bot WhatsApp untuk absensi karyawan dengan format:
- `absen wfo lantai [nomor]` - untuk Work From Office
- `absen wfh` - untuk Work From Home

**Dibuat khusus untuk aapanel dengan Apache Web Server**

## Fitur

✅ Bot WhatsApp untuk absensi di group  
✅ Dashboard web untuk monitoring  
✅ Menyimpan data user dari kontak WhatsApp  
✅ Menyimpan lokasi absensi (jika dikirim)  
✅ Database PostgreSQL  
✅ Export data ke CSV  
✅ Kompatibel dengan aapanel/Apache  

## Persyaratan

- PHP 7.4 atau lebih baru (dengan ekstensi PDO PostgreSQL)
- PostgreSQL
- Apache Web Server (aapanel)
- Node.js (untuk bot WhatsApp - opsional, bisa dijalankan terpisah)

## Instalasi di aapanel

### 1. Setup Database

1. Login ke aapanel
2. Buka **Database** → **PostgreSQL**
3. Buat database baru: `absensidb`
4. Buka **phpPgAdmin** atau gunakan terminal:
```bash
psql -U postgres -d absensidb -f /path/to/database/schema.sql
```

### 2. Upload Files

1. Upload semua file project ke folder website di aapanel
   - Biasanya di: `/www/wwwroot/your-domain.com/`
   - Atau buat folder baru: `/www/wwwroot/bot-absensi/`

2. Set permissions:
```bash
chmod 755 -R /www/wwwroot/bot-absensi/
chmod 644 /www/wwwroot/bot-absensi/.htaccess
```

### 3. Konfigurasi Database

Edit file `config/database.php` atau buat file `.env` (jika menggunakan library dotenv):

```php
// Atau set di aapanel → Website → PHP Settings → Environment Variables
DB_HOST=192.168.18.21
DB_PORT=5432
DB_NAME=absensidb
DB_USER=postgres
DB_PASSWORD=admin
```

### 4. Install PHP Extensions

Di aapanel:
1. Buka **App Store** → **PHP** → Pilih versi PHP Anda
2. Install extension: `pdo_pgsql` dan `pgsql`

Atau via terminal:
```bash
# Untuk PHP 7.4
apt-get install php7.4-pgsql

# Untuk PHP 8.0
apt-get install php8.0-pgsql

# Restart PHP-FPM
systemctl restart php-fpm-74  # atau php-fpm-80
```

### 5. Setup Bot WhatsApp (Node.js)

Bot WhatsApp menggunakan Node.js yang berjalan terpisah:

1. Install Node.js di server (jika belum ada):
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs
```

2. Masuk ke folder bot:
```bash
cd /www/wwwroot/bot-absensi/bot
npm install
```

3. Edit konfigurasi di `bot/whatsappBot.js`:
   - Set `BOT_API_URL` ke URL PHP API Anda
   - Contoh: `http://your-domain.com/api/bot/bot.php`

4. Jalankan bot sebagai service:
```bash
# Buat file service
nano /etc/systemd/system/wa-bot.service
```

Isi file service:
```ini
[Unit]
Description=WhatsApp Bot Absensi
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/www/wwwroot/bot-absensi/bot
ExecStart=/usr/bin/node whatsappBot.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Aktifkan dan jalankan:
```bash
systemctl daemon-reload
systemctl enable wa-bot
systemctl start wa-bot
systemctl status wa-bot
```

### 6. Setup Website di aapanel

1. Buka **Website** → **Add Site**
2. Domain: `your-domain.com` atau `bot-absensi.local`
3. Document Root: `/www/wwwroot/bot-absensi`
4. PHP Version: Pilih PHP 7.4 atau lebih baru
5. Enable **Rewrite** (untuk .htaccess)

### 7. Test Aplikasi

1. Akses dashboard: `http://your-domain.com`
2. Scan QR Code bot WhatsApp (cek log: `journalctl -u wa-bot -f`)
3. Test absensi di group WhatsApp

## Struktur Project

```
bot-absensi/
├── api/
│   ├── absensi.php          # API untuk absensi
│   ├── users.php            # API untuk users
│   └── bot/
│       └── bot.php          # Webhook handler untuk bot
├── assets/
│   ├── style.css            # Dashboard styling
│   └── script.js            # Dashboard JavaScript
├── bot/
│   └── whatsappBot.js       # Bot WhatsApp (Node.js)
├── config/
│   └── database.php         # Database configuration
├── models/
│   ├── Absensi.php          # Absensi model
│   └── User.php             # User model
├── database/
│   └── schema.sql           # Database schema
├── index.php                # Dashboard HTML
├── .htaccess                # Apache configuration
└── README_PHP.md            # Dokumentasi
```

## Cara Menggunakan Bot

### Di WhatsApp Group:

1. **Absen WFO (Work From Office):**
   ```
   absen wfo lantai 5
   ```
   atau
   ```
   absen wfo dilantai 3
   ```

2. **Absen WFH (Work From Home):**
   ```
   absen wfh
   ```

3. **Absen dengan Lokasi (opsional):**
   - Kirim lokasi di WhatsApp bersama dengan pesan absen
   - Bot akan menyimpan koordinat lokasi

### Dashboard Web:

- Akses `http://your-domain.com`
- Lihat statistik absensi
- Filter berdasarkan tanggal
- Export data ke CSV

## API Endpoints

- `GET /api/absensi.php?action=today` - Get absensi hari ini
- `GET /api/absensi.php?action=range&startDate=YYYY-MM-DD&endDate=YYYY-MM-DD` - Get absensi berdasarkan range tanggal
- `GET /api/absensi.php?action=user&phoneNumber=xxx&limit=30` - Get absensi by user
- `GET /api/absensi.php?action=statistics&startDate=YYYY-MM-DD&endDate=YYYY-MM-DD` - Get statistics
- `GET /api/users.php` - Get semua users
- `POST /api/bot/bot.php` - Webhook untuk bot WhatsApp

## Troubleshooting

1. **Database connection error:**
   - Pastikan PostgreSQL berjalan: `systemctl status postgresql`
   - Cek konfigurasi di `config/database.php`
   - Pastikan extension `pdo_pgsql` sudah terinstall

2. **Bot tidak merespon:**
   - Cek status service: `systemctl status wa-bot`
   - Cek log: `journalctl -u wa-bot -f`
   - Pastikan URL API di `whatsappBot.js` benar
   - Pastikan bot sudah scan QR Code

3. **Permission denied:**
   - Set permission folder: `chmod 755 -R /www/wwwroot/bot-absensi/`
   - Set ownership: `chown -R www:www /www/wwwroot/bot-absensi/`

4. **.htaccess tidak bekerja:**
   - Pastikan mod_rewrite enabled di Apache
   - Di aapanel: **Website** → **Settings** → Enable **Rewrite**

5. **CORS error di dashboard:**
   - Pastikan `.htaccess` sudah di-upload
   - Cek header CORS di `api/absensi.php` dan `api/users.php`

## Catatan

- Bot hanya merespon pesan dari group WhatsApp
- User otomatis dibuat dari kontak WhatsApp saat pertama kali absen
- Bot akan menolak absensi ganda dalam satu hari
- Lokasi absensi opsional (jika dikirim via WhatsApp location)
- Bot WhatsApp berjalan sebagai service terpisah (Node.js)
- Dashboard dan API menggunakan PHP (kompatibel dengan aapanel)

## License

ISC

