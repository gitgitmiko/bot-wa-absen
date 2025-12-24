#!/bin/bash
# Script Instalasi Bot WA Absensi untuk aapanel

echo "=========================================="
echo "Instalasi Bot WhatsApp Absensi"
echo "=========================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Silakan jalankan sebagai root (sudo)"
    exit 1
fi

# Install PHP PostgreSQL extension
echo "📦 Menginstall PHP PostgreSQL extension..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)

if [ -f "/etc/apt/sources.list.d/ppa-ondrej-php.list" ]; then
    apt-get update
    apt-get install -y php${PHP_VERSION}-pgsql
else
    echo "⚠️  PHP version tidak terdeteksi, silakan install manual:"
    echo "   apt-get install php7.4-pgsql  # atau php8.0-pgsql"
fi

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 -R .
chmod 644 .htaccess
chown -R www:www .

# Create .wwebjs_auth directory for bot
echo "📁 Membuat direktori untuk bot..."
mkdir -p bot/.wwebjs_auth
chmod 755 bot/.wwebjs_auth

# Install Node.js dependencies for bot
if command -v node &> /dev/null; then
    echo "📦 Menginstall Node.js dependencies..."
    cd bot
    npm install
    cd ..
else
    echo "⚠️  Node.js tidak terdeteksi. Install Node.js terlebih dahulu:"
    echo "   curl -fsSL https://deb.nodesource.com/setup_18.x | bash -"
    echo "   apt-get install -y nodejs"
fi

echo ""
echo "=========================================="
echo "✅ Instalasi selesai!"
echo "=========================================="
echo ""
echo "Langkah selanjutnya:"
echo "1. Setup database PostgreSQL (jalankan database/schema.sql)"
echo "2. Edit config/database.php dengan kredensial database Anda"
echo "3. Setup website di aapanel"
echo "4. Jalankan bot: cd bot && node whatsappBot.js"
echo "5. Atau buat systemd service (lihat README_PHP.md)"
echo ""

