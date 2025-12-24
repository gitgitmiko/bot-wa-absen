#!/bin/bash
# Script Instalasi Bot WhatsApp untuk Armbian ARM64

echo "=========================================="
echo "Instalasi Bot WhatsApp - Armbian ARM64"
echo "=========================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Silakan jalankan sebagai root (sudo)"
    exit 1
fi

# 1. Upgrade Node.js ke 18.x
echo "📦 Mengupgrade Node.js ke versi 18.x..."
if ! command -v node &> /dev/null || [ "$(node -v | cut -d'v' -f2 | cut -d'.' -f1)" -lt 18 ]; then
    echo "Node.js versi lama terdeteksi, mengupgrade..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
else
    echo "✅ Node.js sudah versi 18+"
fi

# Verify Node.js version
NODE_VERSION=$(node -v)
echo "Node.js version: $NODE_VERSION"

# 2. Install Chromium untuk ARM64
echo "📦 Menginstall Chromium untuk ARM64..."
apt-get update
apt-get install -y chromium chromium-browser || {
    echo "⚠️  Chromium tidak tersedia di repo, mencoba alternatif..."
    apt-get install -y chromium-browser
}

# 3. Install dependencies sistem untuk Chromium
echo "📦 Menginstall dependencies sistem untuk Chromium..."
apt-get install -y \
    libnss3 \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libxss1 \
    libgtk-3-0

# 4. Cari path Chromium
echo "🔍 Mencari path Chromium..."
CHROMIUM_PATH=""
if [ -f "/usr/bin/chromium" ]; then
    CHROMIUM_PATH="/usr/bin/chromium"
elif [ -f "/usr/bin/chromium-browser" ]; then
    CHROMIUM_PATH="/usr/bin/chromium-browser"
else
    echo "⚠️  Chromium tidak ditemukan, silakan install manual"
    exit 1
fi

echo "✅ Chromium ditemukan di: $CHROMIUM_PATH"

# 5. Install npm dependencies
echo "📦 Menginstall npm dependencies..."
cd "$(dirname "$0")/bot" || exit 1

# Set environment variables
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
export PUPPETEER_EXECUTABLE_PATH="$CHROMIUM_PATH"

# Install dependencies
npm install

echo ""
echo "=========================================="
echo "✅ Instalasi selesai!"
echo "=========================================="
echo ""
echo "Chromium path: $CHROMIUM_PATH"
echo "Node.js version: $NODE_VERSION"
echo ""
echo "Langkah selanjutnya:"
echo "1. Edit bot/whatsappBot.js - pastikan executablePath sudah benar"
echo "2. Set BOT_API_URL environment variable atau edit di whatsappBot.js"
echo "3. Jalankan bot: cd bot && node whatsappBot.js"
echo "4. Atau buat systemd service (lihat README_PHP.md)"
echo ""

