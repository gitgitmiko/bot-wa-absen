# Panduan Instalasi di aapanel (STB HG680P dengan Armbian)

## Persiapan

1. **Login ke aapanel** via browser: `http://ip-server:7800`
2. **Pastikan PostgreSQL sudah terinstall** di aapanel
3. **Pastikan PHP 7.4+ sudah terinstall** dengan extension `pdo_pgsql`

## Langkah Instalasi

### 1. Upload Files ke Server

**Via aapanel File Manager:**
- Buka **File** → **Upload**
- Upload semua file project ke folder website
- Biasanya di: `/www/wwwroot/your-domain.com/`

**Via SCP/SFTP:**
```bash
scp -r * user@ip-server:/www/wwwroot/bot-absensi/
```

**Via Git (jika ada):**
```bash
cd /www/wwwroot
git clone your-repo bot-absensi
cd bot-absensi
```

### 2. Setup Database PostgreSQL

**Via aapanel:**
1. Buka **Database** → **PostgreSQL**
2. Klik **Add Database**
3. Database Name: `absensidb`
4. User: `postgres` (atau buat user baru)
5. Password: `admin` (atau password Anda)
6. Klik **Submit**

**Import Schema:**
```bash
# Via terminal SSH
psql -U postgres -d absensidb -f /www/wwwroot/bot-absensi/database/schema.sql

# Atau via phpPgAdmin di aapanel
# Buka database absensidb → SQL → Paste isi schema.sql → Execute
```

### 3. Konfigurasi Database di PHP

Edit file `config/database.php`:
```php
$this->host = '192.168.18.21';  // IP server PostgreSQL
$this->port = '5432';
$this->dbname = 'absensidb';
$this->user = 'postgres';
$this->password = 'admin';  // Ganti dengan password Anda
```

**Atau via Environment Variables di aapanel:**
1. Buka **Website** → Pilih website → **Settings**
2. **PHP Settings** → **Environment Variables**
3. Tambahkan:
   - `DB_HOST` = `192.168.18.21`
   - `DB_PORT` = `5432`
   - `DB_NAME` = `absensidb`
   - `DB_USER` = `postgres`
   - `DB_PASSWORD` = `admin`

### 4. Install PHP Extension

**Via aapanel:**
1. Buka **App Store** → **PHP** → Pilih versi PHP (7.4 atau 8.0)
2. Klik **Settings** → **Install Extension**
3. Install: `pdo_pgsql` dan `pgsql`

**Via Terminal:**
```bash
# Deteksi versi PHP
php -v

# Install extension (contoh untuk PHP 7.4)
apt-get update
apt-get install php7.4-pgsql

# Restart PHP-FPM
systemctl restart php-fpm-74
```

### 5. Setup Website di aapanel

1. Buka **Website** → **Add Site**
2. **Domain**: Masukkan domain atau subdomain (contoh: `bot-absensi.local` atau `absensi.yourdomain.com`)
3. **Document Root**: `/www/wwwroot/bot-absensi` (atau folder project Anda)
4. **PHP Version**: Pilih PHP 7.4 atau lebih baru
5. **Enable Rewrite**: ✅ Centang (untuk .htaccess)
6. Klik **Submit**

### 6. Set Permissions

```bash
cd /www/wwwroot/bot-absensi
chmod 755 -R .
chmod 644 .htaccess
chown -R www:www .
```

### 7. Install Node.js (untuk Bot WhatsApp)

**Jika belum terinstall:**
```bash
# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Verify
node -v
npm -v
```

**Install dependencies bot:**
```bash
cd /www/wwwroot/bot-absensi/bot
npm install
```

### 8. Konfigurasi Bot WhatsApp

Edit file `bot/whatsappBot.js`:
```javascript
// Ganti dengan URL website Anda
const BOT_API_URL = process.env.BOT_API_URL || 'http://your-domain.com/api/bot/bot.php';
```

**Atau set environment variable:**
```bash
export BOT_API_URL=http://your-domain.com/api/bot/bot.php
```

### 9. Setup Bot sebagai Systemd Service

Buat file service:
```bash
nano /etc/systemd/system/wa-bot.service
```

Isi file:
```ini
[Unit]
Description=WhatsApp Bot Absensi
After=network.target postgresql.service

[Service]
Type=simple
User=root
WorkingDirectory=/www/wwwroot/bot-absensi/bot
Environment="BOT_API_URL=http://your-domain.com/api/bot/bot.php"
ExecStart=/usr/bin/node whatsappBot.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Aktifkan dan jalankan:**
```bash
systemctl daemon-reload
systemctl enable wa-bot
systemctl start wa-bot
systemctl status wa-bot
```

**Cek log:**
```bash
journalctl -u wa-bot -f
```

### 10. Test Aplikasi

1. **Akses Dashboard:**
   - Buka browser: `http://your-domain.com`
   - Dashboard harus muncul

2. **Test Bot:**
   - Cek log bot: `journalctl -u wa-bot -f`
   - Scan QR Code yang muncul di log
   - Test absensi di group WhatsApp:
     - `absen wfo lantai 5`
     - `absen wfh`

## Troubleshooting

### Database Connection Error
```bash
# Test koneksi PostgreSQL
psql -U postgres -h 192.168.18.21 -d absensidb

# Cek PostgreSQL running
systemctl status postgresql

# Cek PHP extension
php -m | grep pgsql
```

### Bot Tidak Merespon
```bash
# Cek status service
systemctl status wa-bot

# Cek log
journalctl -u wa-bot -n 50

# Test API manual
curl -X POST http://your-domain.com/api/bot/bot.php \
  -H "Content-Type: application/json" \
  -d '{"phone_number":"6281234567890","wa_name":"Test","message":"absen wfh"}'
```

### Permission Denied
```bash
chmod 755 -R /www/wwwroot/bot-absensi
chown -R www:www /www/wwwroot/bot-absensi
```

### .htaccess Tidak Bekerja
- Pastikan mod_rewrite enabled di Apache
- Di aapanel: **Website** → **Settings** → Enable **Rewrite**
- Atau edit Apache config: `a2enmod rewrite && systemctl restart apache2`

## Struktur File Final

```
/www/wwwroot/bot-absensi/
├── api/
│   ├── absensi.php
│   ├── users.php
│   └── bot/
│       └── bot.php
├── assets/
│   ├── style.css
│   └── script.js
├── bot/
│   ├── whatsappBot.js
│   └── .wwebjs_auth/
├── config/
│   └── database.php
├── models/
│   ├── Absensi.php
│   └── User.php
├── database/
│   └── schema.sql
├── index.php
├── .htaccess
└── README_PHP.md
```

## Catatan Penting

1. **Firewall**: Pastikan port 80/443 terbuka untuk akses web
2. **SSL**: Setup SSL certificate di aapanel untuk HTTPS
3. **Backup**: Backup database secara berkala
4. **Monitoring**: Monitor log bot dan website secara berkala

## Support

Jika ada masalah, cek:
- Log bot: `journalctl -u wa-bot -f`
- Log Apache: `/www/wwwlogs/`
- Log PHP: `/www/server/php/var/log/php-fpm.log`

