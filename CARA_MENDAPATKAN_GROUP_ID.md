# Cara Mendapatkan Group ID WhatsApp

## Metode 1: Melalui Log Bot (Paling Mudah)

1. **Pastikan bot sudah berjalan** dan terhubung ke WhatsApp
2. **Kirim pesan apapun di grup** yang ingin Anda tambahkan ke whitelist
3. **Cek log bot** dengan command:
   ```bash
   journalctl -u whatsappbot -f
   ```
4. **Cari log yang menampilkan:**
   ```
   📱 Group ID: 120363422758876589@g.us | Group Name: Nama Grup
   ```
5. **Salin Group ID** yang muncul (format: `120363422758876589@g.us`)
6. **Tambahkan ke file** `config/bot.js` di array `allowedGroupIds`

## Metode 2: Melalui WhatsApp Web (Manual)

1. Buka WhatsApp Web di browser
2. Buka grup yang diinginkan
3. Lihat URL di address bar, formatnya seperti:
   ```
   https://web.whatsapp.com/send?phone=6281234567890&text=&type=phone_number&app_absent=0
   ```
4. Group ID biasanya terlihat di console browser (F12) saat membuka grup

## Metode 3: Menggunakan Bot Command (Jika sudah ada)

Jika bot sudah memiliki command untuk menampilkan group ID, gunakan command tersebut.

## Cara Menambahkan Group ID ke Whitelist

1. **Buka file** `config/bot.js`
2. **Tambahkan Group ID** ke array `allowedGroupIds`:
   ```javascript
   allowedGroupIds: [
       "120363422758876589@g.us",  // Grup 1
       "120363422758876590@g.us"   // Grup 2 (tambahkan di sini)
   ]
   ```
3. **Restart bot** untuk menerapkan perubahan:
   ```bash
   sudo systemctl restart whatsappbot
   ```

## Catatan Penting

- **Format Group ID**: Selalu diakhiri dengan `@g.us`
- **Case Sensitive**: Group ID case-sensitive, salin persis seperti yang muncul di log
- **Kosongkan Array**: Jika `allowedGroupIds` dikosongkan `[]`, bot akan berfungsi di semua grup
- **Logging**: Set `logAllGroupIds: true` di `config/bot.js` untuk melihat semua Group ID yang mengirim pesan

## Troubleshooting

### Bot tidak merespon di grup tertentu
1. Cek apakah Group ID sudah ditambahkan ke `allowedGroupIds`
2. Pastikan Group ID yang digunakan benar (cek di log)
3. Restart bot setelah mengubah konfigurasi

### Tidak melihat Group ID di log
1. Pastikan `logAllGroupIds: true` di `config/bot.js`
2. Pastikan bot sudah terhubung dan menerima pesan dari grup
3. Cek log dengan `journalctl -u whatsappbot -f`

