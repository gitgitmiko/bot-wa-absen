# Solusi Masalah Node.js dan Puppeteer di Armbian ARM64

## Masalah yang Ditemukan:
1. Node.js versi terlalu lama (v12.22.9) - butuh >= 18.0.0
2. Chromium tidak tersedia untuk ARM64
3. Package deprecated

## Solusi 1: Upgrade Node.js (REKOMENDASI)

### Install Node.js 18.x di Armbian:

```bash
# Hapus Node.js lama (jika perlu)
# apt-get remove nodejs npm

# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Verify
node -v  # Harus >= 18.0.0
npm -v
```

### Install Chromium untuk ARM64:

```bash
# Install Chromium
apt-get update
apt-get install -y chromium chromium-browser

# Atau jika tidak tersedia, install chromium-browser
apt-get install -y chromium-browser

# Verify
chromium --version
```

### Install Dependencies Bot:

```bash
cd /www/wwwroot/landingpage.test/bot

# Set environment variable untuk skip download Chromium
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Install dependencies
npm install
```

## Solusi 2: Gunakan Versi Lama whatsapp-web.js (Alternatif)

Jika tidak bisa upgrade Node.js, gunakan versi lama whatsapp-web.js yang support Node 12:

```bash
cd /www/wwwroot/landingpage.test/bot

# Edit package.json, ganti versi whatsapp-web.js
# whatsapp-web.js@1.19.5 support Node 12
```

Tapi ini TIDAK DIREKOMENDASIKAN karena versi lama mungkin ada bug/security issue.

## Solusi 3: Install Chromium Manual untuk ARM64

```bash
# Install dependencies untuk Chromium
apt-get update
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
    libasound2

# Install Chromium
apt-get install -y chromium chromium-browser

# Set executable path
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
```

## Setup Bot dengan Chromium Manual

Setelah install Chromium, edit `bot/whatsappBot.js`:

```javascript
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

class WhatsAppBot {
    constructor() {
        this.client = new Client({
            authStrategy: new LocalAuth({
                dataPath: './.wwebjs_auth'
            }),
            puppeteer: {
                headless: true,
                executablePath: '/usr/bin/chromium',  // Path ke Chromium
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu',
                    '--disable-software-rasterizer'
                ]
            }
        });
        // ... rest of code
    }
}
```

## Langkah Instalasi Lengkap (REKOMENDASI):

```bash
# 1. Upgrade Node.js ke 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# 2. Install Chromium
apt-get update
apt-get install -y chromium chromium-browser

# 3. Install dependencies sistem untuk Chromium
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
    libasound2

# 4. Masuk ke folder bot
cd /www/wwwroot/landingpage.test/bot

# 5. Set environment variable
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# 6. Install npm dependencies
npm install

# 7. Test bot
node whatsappBot.js
```

## Troubleshooting

### Jika Chromium tidak ditemukan:
```bash
# Cari lokasi Chromium
which chromium
which chromium-browser

# Atau
find /usr -name chromium* 2>/dev/null
```

### Jika masih error, gunakan Chromium dari snap:
```bash
apt-get install -y snapd
snap install chromium
export PUPPETEER_EXECUTABLE_PATH=/snap/bin/chromium
```

### Cek versi Node.js:
```bash
node -v
# Harus >= 18.0.0
```

### Jika Node.js masih versi lama setelah install:
```bash
# Hapus cache npm
npm cache clean --force

# Hapus node_modules dan package-lock.json
rm -rf node_modules package-lock.json

# Install ulang
npm install
```

