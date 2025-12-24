# Solusi Error: Database Connection Failed

## Masalah
Error "Database connection failed" terjadi ketika PHP tidak bisa connect ke PostgreSQL.

## Langkah Troubleshooting

### 1. Test Koneksi Database

Akses file test di browser:
```
http://gitgitmiko.my.id/test-db.php
```

File ini akan menampilkan:
- Status koneksi database
- Versi PostgreSQL
- Tables yang ada
- Error detail jika gagal

### 2. Cek Konfigurasi Database

Edit file `config/database.php` dan pastikan:

```php
$this->host = '192.168.18.21';  // IP server PostgreSQL
$this->port = '5432';
$this->dbname = 'absensidb';
$this->user = 'postgres';
$this->password = 'admin';  // Ganti dengan password Anda
```

### 3. Cek PostgreSQL Berjalan

```bash
# Cek status PostgreSQL
systemctl status postgresql

# Atau
service postgresql status

# Jika tidak berjalan, start:
systemctl start postgresql
```

### 4. Test Koneksi dari Command Line

```bash
# Test koneksi langsung
psql -U postgres -h 192.168.18.21 -d absensidb

# Jika berhasil, berarti koneksi OK
# Jika error, cek:
# - PostgreSQL listening di IP tersebut
# - Firewall tidak memblokir port 5432
```

### 5. Cek PostgreSQL Listening

```bash
# Cek apakah PostgreSQL listening di IP yang benar
netstat -tlnp | grep 5432

# Atau
ss -tlnp | grep 5432

# Harus muncul: 0.0.0.0:5432 atau 192.168.18.21:5432
```

### 6. Cek PostgreSQL Config (postgresql.conf)

```bash
# Edit config
nano /etc/postgresql/*/main/postgresql.conf

# Pastikan:
listen_addresses = '*'  # atau '192.168.18.21'

# Restart PostgreSQL
systemctl restart postgresql
```

### 7. Cek PostgreSQL Access (pg_hba.conf)

```bash
# Edit access config
nano /etc/postgresql/*/main/pg_hba.conf

# Tambahkan atau pastikan ada:
host    all             all             0.0.0.0/0               md5
# atau
host    all             all             192.168.18.0/24         md5

# Reload config
systemctl reload postgresql
```

### 8. Cek PHP Extension

```bash
# Cek apakah extension terinstall
php -m | grep pgsql

# Harus muncul: pgsql dan pdo_pgsql

# Jika tidak ada, install:
apt-get install php-pgsql php7.4-pgsql  # atau php8.0-pgsql
systemctl restart php-fpm
```

### 9. Cek Firewall

```bash
# Cek firewall rules
iptables -L -n | grep 5432

# Atau jika pakai ufw:
ufw status | grep 5432

# Jika port 5432 diblokir, buka:
ufw allow 5432/tcp
```

### 10. Cek Database dan User

```bash
# Login ke PostgreSQL
psql -U postgres

# Cek database
\l

# Harus ada database 'absensidb'

# Jika tidak ada, buat:
CREATE DATABASE absensidb;

# Cek user
\du

# Jika perlu, buat user baru:
CREATE USER postgres WITH PASSWORD 'admin';
GRANT ALL PRIVILEGES ON DATABASE absensidb TO postgres;
```

### 11. Import Schema

```bash
# Pastikan schema sudah diimport
psql -U postgres -d absensidb -f /www/wwwroot/landingpage.test/database/schema.sql

# Atau via phpPgAdmin di aapanel
```

## Solusi Cepat

Jika semua sudah dicek, coba:

1. **Restart services:**
```bash
systemctl restart postgresql
systemctl restart php-fpm
systemctl restart apache2  # atau nginx
```

2. **Test lagi:**
   - Akses: `http://gitgitmiko.my.id/test-db.php`
   - Cek apakah koneksi berhasil

3. **Cek error log:**
```bash
# PHP error log
tail -f /www/server/php/var/log/php-fpm.log

# Apache error log
tail -f /www/wwwlogs/error.log

# PostgreSQL log
tail -f /var/log/postgresql/postgresql-*.log
```

## Konfigurasi yang Benar

Pastikan di `config/database.php`:

```php
$this->host = '192.168.18.21';  // IP server PostgreSQL (bisa localhost jika sama server)
$this->port = '5432';
$this->dbname = 'absensidb';
$this->user = 'postgres';
$this->password = 'admin';  // Password PostgreSQL Anda
```

## Catatan Penting

- Jika PostgreSQL di server yang sama, gunakan `localhost` atau `127.0.0.1`
- Jika PostgreSQL di server berbeda, pastikan firewall membolehkan koneksi
- Pastikan user PostgreSQL punya akses ke database `absensidb`
- Pastikan extension `pdo_pgsql` sudah terinstall di PHP

