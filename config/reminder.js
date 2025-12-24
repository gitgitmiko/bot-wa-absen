/**
 * Konfigurasi Reminder
 */

module.exports = {
    // Waktu reminder (WIB - UTC+7)
    // Format: { hour: 10, minute: 59 }
    schedule: {
        hour: 10,
        minute: 59,
        timezone: 'Asia/Jakarta'
    },
    
    // Group ID yang akan menerima reminder
    // Format: "120363422758876589@g.us" atau array untuk multiple groups
    // Untuk mendapatkan Group ID, cek log bot saat bot menerima pesan dari group
    groupIds: [
        "120363422758876589@g.us"
        // Tambahkan group ID di sini
    ],
    
    // Pesan reminder
    message: `Reminder!!! Daily Stand Up Meeting!!

https://us06web.zoom.us/j/82115129933

Meeting ID: 821 1512 9933
Passcode: SYSDBA2025

3.6 Squad 4

Ditunggu sampai 11:10 yawww`
};

