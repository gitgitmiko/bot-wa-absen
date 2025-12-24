/**
 * Konfigurasi Bot WhatsApp
 */

module.exports = {
    // Group ID yang diizinkan untuk menggunakan bot
    // Format: ["120363422758876589@g.us", "group_id_lain@g.us"]
    // Kosongkan array [] untuk mengizinkan semua grup
    // Untuk mendapatkan Group ID, lihat log bot saat menerima pesan dari grup
    allowedGroupIds: [
        "120363422758876589@g.us"
        // Tambahkan group ID di sini
    ],
    
    // Log semua group ID yang mengirim pesan (untuk debugging)
    // Set true untuk melihat group ID di log
    logAllGroupIds: true
};

