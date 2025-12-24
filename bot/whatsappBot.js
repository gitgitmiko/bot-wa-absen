const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const http = require('http');
const https = require('https');
const cron = require('node-cron');
const reminderConfig = require('../config/reminder');
const botConfig = require('../config/bot');

// Konfigurasi
// Ganti dengan URL website Anda di aapanel
const BOT_API_URL = process.env.BOT_API_URL || 'http://gitgitmiko.my.id/api/bot/bot.php';
const GROUPS_API_URL = process.env.GROUPS_API_URL || 'http://gitgitmiko.my.id/api/bot/groups.php';

class WhatsAppBot {
    constructor() {
        // Deteksi path Chromium untuk ARM64
        const chromiumPath = process.env.PUPPETEER_EXECUTABLE_PATH || 
                           '/usr/bin/chromium' || 
                           '/usr/bin/chromium-browser';

        this.client = new Client({
            authStrategy: new LocalAuth({
                dataPath: './.wwebjs_auth'
            }),
            puppeteer: {
                headless: true,
                executablePath: chromiumPath, // Path ke Chromium untuk ARM64
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

        // Map untuk menyimpan pending absensi (menunggu lokasi)
        // Format: { phoneNumber: { message: 'absen wfo lantai 21', waName: '...', timestamp: ... } }
        this.pendingAbsensi = new Map();

        // Cache untuk allowed group IDs dari database
        this.allowedGroupIds = [];
        this.lastGroupIdsFetch = 0;
        this.groupIdsCacheTTL = 5 * 60 * 1000; // 5 menit

        this.setupEventHandlers();
        this.setupReminder();
        
        // Load group IDs saat startup
        this.loadAllowedGroupIds();
    }

    // Load allowed group IDs dari database
    async loadAllowedGroupIds() {
        try {
            const response = await this.httpRequest(`${GROUPS_API_URL}?active_only=true`, 'GET');
            const data = JSON.parse(response);
            
            if (data.success && Array.isArray(data.data)) {
                this.allowedGroupIds = data.data;
                this.lastGroupIdsFetch = Date.now();
                console.log(`✅ Loaded ${this.allowedGroupIds.length} allowed group IDs from database`);
                if (this.allowedGroupIds.length > 0) {
                    console.log('   Groups:', this.allowedGroupIds.join(', '));
                }
            } else {
                console.warn('⚠️ Failed to load group IDs from database, using fallback');
                // Fallback ke config jika database error
                this.allowedGroupIds = botConfig.allowedGroupIds || [];
            }
        } catch (error) {
            console.error('❌ Error loading group IDs from database:', error.message);
            // Fallback ke config jika API error
            this.allowedGroupIds = botConfig.allowedGroupIds || [];
        }
    }

    // Cek apakah group ID diizinkan (dengan auto-refresh cache)
    async isGroupAllowed(groupId) {
        // Refresh cache jika sudah expired
        if (Date.now() - this.lastGroupIdsFetch > this.groupIdsCacheTTL) {
            await this.loadAllowedGroupIds();
        }

        // Jika tidak ada group IDs di cache, cek langsung ke API
        if (this.allowedGroupIds.length === 0) {
            try {
                const response = await this.httpRequest(`${GROUPS_API_URL}?group_id=${encodeURIComponent(groupId)}`, 'GET');
                const data = JSON.parse(response);
                return data.success && data.allowed === true;
            } catch (error) {
                console.error('❌ Error checking group ID:', error.message);
                // Fallback: jika database error, gunakan config
                return botConfig.allowedGroupIds && botConfig.allowedGroupIds.length > 0 
                    ? botConfig.allowedGroupIds.includes(groupId)
                    : true; // Jika config juga kosong, izinkan semua
            }
        }

        // Cek dari cache
        return this.allowedGroupIds.includes(groupId);
    }

    setupEventHandlers() {
        // QR Code untuk scan
        this.client.on('qr', (qr) => {
            console.log('📱 Scan QR Code ini dengan WhatsApp:');
            qrcode.generate(qr, { small: true });
        });

        // Bot siap
        this.client.on('ready', () => {
            console.log('✅ Bot WhatsApp siap digunakan!');
        });

        // Handle pesan masuk
        this.client.on('message', async (message) => {
            await this.handleMessage(message);
        });

        // Handle error
        this.client.on('disconnected', (reason) => {
            console.log('❌ Bot terputus:', reason);
        });

        this.client.on('auth_failure', (msg) => {
            console.error('❌ Authentication failed:', msg);
        });
    }

    async handleMessage(message) {
        try {
            // Hanya handle pesan dari group
            const chat = await message.getChat();
            if (!chat.isGroup) {
                return; // Skip pesan dari chat personal
            }

            const body = message.body ? message.body.trim() : '';
            
            // Cek apakah pesan adalah command (dimulai dengan /)
            const isCommand = body.startsWith('/');
            
            // Cek apakah grup diizinkan
            const groupId = chat.id._serialized || chat.id;
            const groupName = chat.name || 'Unknown';
            
            // Log group ID hanya jika ada command dan logging enabled
            if (isCommand && botConfig.logAllGroupIds) {
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.log('📱 Group Info:');
                console.log('   ID:', groupId);
                console.log('   Name:', groupName);
                console.log('   Command:', body);
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            }
            
            // Cek whitelist grup dari database
            const isAllowed = await this.isGroupAllowed(groupId);
            
            if (!isAllowed) {
                // Cek apakah ada group IDs yang dikonfigurasi (jika tidak ada, izinkan semua)
                if (this.allowedGroupIds.length > 0) {
                    // Grup tidak diizinkan, skip pesan
                    if (isCommand && botConfig.logAllGroupIds) {
                        console.log('⚠️ Grup TIDAK diizinkan - Bot tidak akan merespon');
                        console.log('   Untuk menambahkan, tambahkan Group ID ke database melalui web admin');
                    }
                    return;
                }
                // Jika tidak ada group IDs di database, izinkan semua (backward compatibility)
            } else {
                if (isCommand && botConfig.logAllGroupIds) {
                    console.log('✅ Grup diizinkan - Bot akan merespon');
                }
            }
            
            // CEK LOKASI DULU (sebelum ambil kontak info untuk efisiensi)
            // Deteksi lokasi: hasLocation atau type === 'location'
            const isLocationMessage = message.hasLocation || message.type === 'location';
            
            if (isLocationMessage) {
                // Ambil info kontak dengan cepat untuk matching pending
                let quickPhoneNumber = '';
                try {
                    if (message.author) {
                        quickPhoneNumber = message.author.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '').replace('@g.us', '');
                    } else if (message.from) {
                        quickPhoneNumber = message.from.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '').replace('@g.us', '');
                    }
                } catch (e) {
                    // Silent error
                }
                
                // Cari pending absensi
                let foundPending = null;
                let pendingKey = null;
                
                // Coba dengan quickPhoneNumber
                if (quickPhoneNumber && this.pendingAbsensi.has(quickPhoneNumber)) {
                    foundPending = this.pendingAbsensi.get(quickPhoneNumber);
                    pendingKey = quickPhoneNumber;
                } else {
                    // Cari di semua pending (untuk handle phoneNumber yang berbeda)
                    for (const [key, pending] of this.pendingAbsensi.entries()) {
                        const now = Date.now();
                        if (now - pending.timestamp < 5 * 60 * 1000) {
                            // Gunakan pending terbaru yang masih valid
                            if (!foundPending || pending.timestamp > foundPending.timestamp) {
                                foundPending = pending;
                                pendingKey = key;
                            }
                        }
                    }
                }
                
                if (foundPending) {
                    const now = Date.now();
                    if (now - foundPending.timestamp < 5 * 60 * 1000) {
                        // Ambil data lokasi
                        let locationData = null;
                        try {
                            if (message.location) {
                                locationData = message.location;
                            } else {
                                // Coba ambil dengan method getLocation()
                                locationData = await message.getLocation();
                            }
                        } catch (e) {
                            await message.reply('❌ Tidak bisa membaca data lokasi. Silakan coba kirim lokasi lagi.');
                            return;
                        }
                        
                        if (!locationData || !locationData.latitude || !locationData.longitude) {
                            await message.reply('❌ Data lokasi tidak valid. Silakan kirim lokasi lagi.');
                            return;
                        }
                        
                        // Ambil info kontak lengkap
                        let phoneNumber = quickPhoneNumber;
                        let waName = foundPending.waName || 'Unknown';
                        
                        // Proses absensi dengan lokasi
                        await this.processAbsensi(message, foundPending.message, phoneNumber, waName, true);
                        
                        // Hapus pending
                        if (pendingKey) {
                            this.pendingAbsensi.delete(pendingKey);
                        }
                        return;
                    } else {
                        if (pendingKey) {
                            this.pendingAbsensi.delete(pendingKey);
                        }
                        await message.reply('⏰ Permintaan absensi sudah kadaluarsa. Silakan ketik "/wfo [nomor]" lagi.');
                        return;
                    }
                } else {
                    await message.reply('📍 Lokasi diterima, tapi tidak ada permintaan absensi.\n\nSilakan ketik: /wfo [nomor]');
                    return;
                }
            }
            
            // Ambil informasi kontak - PRIORITAS: getContact() untuk nomor yang akurat
            let phoneNumber = '';
            let waName = '';
            
            try {
                // PRIORITAS 1: Coba ambil dari getContact() dulu (paling akurat)
                try {
                    const contact = await message.getContact();
                    
                    // Gunakan contact.number jika tersedia (ini yang paling akurat)
                    if (contact.number) {
                        phoneNumber = contact.number.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                    }
                    
                    // Ambil nama dari contact
                    waName = contact.pushname || contact.name || contact.number || '';
                } catch (contactError) {
                    // Silent error, use fallback
                    
                    // PRIORITAS 2: Fallback ke message data
                    // Untuk group chat, gunakan message.author
                    if (chat.isGroup) {
                        if (message.author) {
                            phoneNumber = message.author.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                        } else if (message.from) {
                            phoneNumber = message.from.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                        }
                    } else {
                        // Untuk chat personal
                        if (message.from) {
                            phoneNumber = message.from.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                        }
                    }
                    
                    // Ambil nama dari notifyName
                    waName = message.notifyName || message._data?.notifyName || phoneNumber || 'Unknown';
                }
                
            } catch (error) {
                // Fallback terakhir
                if (chat.isGroup && message.author) {
                    phoneNumber = message.author.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                } else if (message.from) {
                    phoneNumber = message.from.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                }
                waName = message.notifyName || phoneNumber || 'Unknown';
            }

            // Pastikan phoneNumber tidak kosong
            if (!phoneNumber) {
                if (chat.isGroup && message.author) {
                    phoneNumber = message.author.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                } else if (message.from) {
                    phoneNumber = message.from.replace('@c.us', '').replace('@s.whatsapp.net', '').replace('@lid', '');
                }
            }

            // Parse command - hanya log jika command terdeteksi
            const bodyLower = body.toLowerCase().trim();
            
            // Cek apakah command dimulai dengan /
            if (bodyLower.startsWith('/')) {
                console.log('📨 Command received:', bodyLower, 'from:', waName);
                
                if (bodyLower.startsWith('/wfh') || bodyLower.startsWith('/wfo') || bodyLower.startsWith('/wfa')) {
                    await this.processAbsensi(message, body, phoneNumber, waName);
                } else {
                    // Command lain, kirim ke PHP API untuk cek di database
                    await this.processOtherCommand(message, body, phoneNumber, waName);
                }
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    }

    async processAbsensi(message, body, phoneNumber, waName, hasLocationFromPending = false) {
        try {
            const bodyLower = (body || '').toLowerCase().trim();
            // Parse jenis absensi
            const isWFO = bodyLower.startsWith('/wfo') || bodyLower.includes(' wfo');
            const isWFH = bodyLower.startsWith('/wfh');
            const isWFA = bodyLower.startsWith('/wfa');
            
            // Get/Capture location
            let location = null;

            // Special handling for WFA: location is the text following the command
            if (isWFA) {
                const parts = body.trim().split(/\s+/);
                const wfaLocationText = parts.slice(1).join(' ').trim();
                if (!wfaLocationText) {
                    await message.reply('❌ Format /wfa salah. Gunakan: /wfa [lokasi]\nContoh: /wfa PLN UIT JBT');
                    return;
                }
                location = { address: wfaLocationText };
            }

            const isLocationMessage = message.hasLocation || message.type === 'location';
            
            if (isLocationMessage) {
                // Coba ambil lokasi dari berbagai cara
                let locationData = null;
                
                if (message.location) {
                    locationData = message.location;
                } else if (message._data && message._data.location) {
                    locationData = message._data.location;
                } else {
                    // Coba akses langsung dari message
                    try {
                        locationData = await message.getLocation();
                    } catch (e) {
                        console.warn('⚠️ Tidak bisa ambil location data:', e.message);
                    }
                }
                
                if (locationData) {
                    location = {
                        latitude: locationData.latitude,
                        longitude: locationData.longitude,
                        address: locationData.description || locationData.name || null
                    };
                }
            } else if (isWFO && !hasLocationFromPending) {
                // Jika WFO tapi tidak ada lokasi, simpan sebagai pending dan minta kirim lokasi
                const pendingData = {
                    message: body,
                    waName: waName,
                    phoneNumber: phoneNumber, // Simpan juga phoneNumber untuk matching
                    timestamp: Date.now()
                };
                
                this.pendingAbsensi.set(phoneNumber, pendingData);
                
                await message.reply(
                    '📍 Absensi WFO memerlukan lokasi!\n\n' +
                    'Silakan kirim lokasi Anda sekarang:\n' +
                    '1. Tap icon 📍 di WhatsApp\n' +
                    '2. Pilih lokasi Anda\n' +
                    '3. Kirim lokasi\n\n' +
                    'Lokasi harus dalam radius 300 meter dari kantor.\n' +
                    '⏰ Anda punya 5 menit untuk mengirim lokasi.'
                );
                return;
            } else if (isWFO && !location) {
                // Jika masih tidak ada lokasi setelah pending, error
                await message.reply(
                    '❌ Lokasi tidak ditemukan. Silakan ketik "/wfo [nomor]" dan kirim lokasi Anda.'
                );
                return;
            }

            // Kirim ke PHP API
            const response = await this.sendToPHPAPI({
                phone_number: phoneNumber,
                wa_name: waName,
                message: body,
                location: location
            });

            // Kirim response ke WhatsApp
            if (response.success) {
                await message.reply(response.message || '✅ Absensi berhasil!');
            } else {
                await message.reply(response.message || response.error || '❌ Terjadi kesalahan');
            }
        } catch (error) {
            console.error('❌ Error processAbsensi:', error.message);
            try {
                await message.reply('❌ Terjadi kesalahan saat memproses absensi. Silakan coba lagi.');
            } catch (replyError) {
                // Silent error
            }
        }
    }

    async processOtherCommand(message, body, phoneNumber, waName) {
        try {
            // Kirim ke PHP API untuk cek command di database
            const response = await this.sendToPHPAPI({
                phone_number: phoneNumber,
                wa_name: waName,
                message: body,
                location: null
            });

            // Kirim response ke WhatsApp
            if (response.success) {
                await message.reply(response.message || '✅ Command berhasil diproses!');
            } else {
                await message.reply(response.message || response.error || '❌ Command tidak dikenali');
            }
        } catch (error) {
            console.error('❌ Error processOtherCommand:', error.message);
            try {
                await message.reply('❌ Terjadi kesalahan saat memproses command. Silakan coba lagi.');
            } catch (replyError) {
                // Silent error
            }
        }
    }

    async sendToPHPAPI(data) {
        return new Promise((resolve, reject) => {
            const postData = JSON.stringify(data);
            
            const url = new URL(BOT_API_URL);
            const options = {
                hostname: url.hostname,
                port: url.port || 80,
                path: url.pathname,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Content-Length': Buffer.byteLength(postData)
                }
            };

            const req = http.request(options, (res) => {
                let responseData = '';
                
                res.on('data', (chunk) => {
                    responseData += chunk;
                });
                
                res.on('end', () => {
                    try {
                        const json = JSON.parse(responseData);
                        resolve(json);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                });
            });

            req.on('error', (error) => {
                reject(error);
            });

            req.write(postData);
            req.end();
        });
    }

    async initialize() {
        try {
            await this.client.initialize();
        } catch (error) {
            console.error('Error initializing bot:', error);
            throw error;
        }
    }

    async destroy() {
        try {
            await this.client.destroy();
        } catch (error) {
            console.error('Error destroying bot:', error);
        }
    }

    setupReminder() {
        // Setup reminder scheduler
        const { hour, minute, timezone } = reminderConfig.schedule;
        
        // Convert WIB (UTC+7) ke UTC untuk cron
        // WIB 10:59 = UTC 03:59
        const utcHour = (hour - 7 + 24) % 24;
        
        // Cron expression: minute hour * * * (setiap hari)
        // Format: minute hour day month weekday
        const cronExpression = `${minute} ${utcHour} * * *`;
        
        console.log(`⏰ Reminder scheduler setup: ${hour}:${minute} WIB (${utcHour}:${minute} UTC)`);
        console.log(`📅 Cron expression: ${cronExpression}`);
        
        // Schedule reminder
        cron.schedule(cronExpression, async () => {
            console.log('🔔 Reminder triggered!');
            await this.sendReminder();
        }, {
            timezone: 'UTC' // Cron menggunakan UTC
        });
        
        console.log('✅ Reminder scheduler aktif!');
    }

    async sendReminder() {
        try {
            const { message } = reminderConfig;
            
            // Ambil group IDs dari database
            let groupIds = [];
            try {
                const response = await this.httpRequest(`${GROUPS_API_URL}?active_only=true`, 'GET');
                const data = JSON.parse(response);
                
                if (data.success && Array.isArray(data.data)) {
                    groupIds = data.data;
                } else {
                    // Fallback ke config jika database error
                    groupIds = reminderConfig.groupIds || [];
                }
            } catch (error) {
                console.warn('⚠️ Error loading group IDs from database, using fallback:', error.message);
                // Fallback ke config jika API error
                groupIds = reminderConfig.groupIds || [];
            }
            
            if (!groupIds || groupIds.length === 0) {
                console.warn('⚠️ Tidak ada group ID yang dikonfigurasi untuk reminder');
                return;
            }
            
            // Pastikan bot sudah ready
            if (!this.client.info) {
                console.warn('⚠️ Bot belum ready, skip reminder');
                return;
            }
            
            // Kirim reminder ke semua group
            for (const groupId of groupIds) {
                try {
                    await this.client.sendMessage(groupId, message);
                    console.log(`✅ Reminder terkirim ke group: ${groupId}`);
                } catch (error) {
                    console.error(`❌ Error mengirim reminder ke group ${groupId}:`, error.message);
                }
            }
        } catch (error) {
            console.error('❌ Error dalam sendReminder:', error);
        }
    }

    // Helper function untuk HTTP request (karena Node.js tidak punya fetch built-in)
    httpRequest(url, method = 'GET', data = null) {
        return new Promise((resolve, reject) => {
            const urlObj = new URL(url);
            const isHttps = urlObj.protocol === 'https:';
            const httpModule = isHttps ? https : http;
            
            const options = {
                hostname: urlObj.hostname,
                port: urlObj.port || (isHttps ? 443 : 80),
                path: urlObj.pathname + urlObj.search,
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (data) {
                const postData = JSON.stringify(data);
                options.headers['Content-Length'] = Buffer.byteLength(postData);
            }

            const req = httpModule.request(options, (res) => {
                let responseData = '';
                
                res.on('data', (chunk) => {
                    responseData += chunk;
                });
                
                res.on('end', () => {
                    resolve(responseData);
                });
            });

            req.on('error', (error) => {
                reject(error);
            });

            if (data) {
                req.write(JSON.stringify(data));
            }
            
            req.end();
        });
    }
}

// Jalankan bot jika file ini dijalankan langsung
if (require.main === module) {
    const bot = new WhatsAppBot();
    bot.initialize().catch(console.error);

    // Graceful shutdown
    process.on('SIGINT', async () => {
        console.log('\n🛑 Shutting down...');
        await bot.destroy();
        process.exit(0);
    });

    process.on('SIGTERM', async () => {
        console.log('\n🛑 Shutting down...');
        await bot.destroy();
        process.exit(0);
    });
}

module.exports = WhatsAppBot;
