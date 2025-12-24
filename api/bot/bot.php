<?php
require_once __DIR__ . '/../../config/bootstrap.php';

/**
 * Bot WhatsApp Handler
 * File ini menerima webhook dari bot WhatsApp (Node.js)
 */

require_once __DIR__ . '/../../models/Absensi.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Command.php';
require_once __DIR__ . '/../../utils/LocationValidator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fungsi untuk normalize phone number
function normalizePhoneNumber($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Log original untuk debugging
    $original = $phone;
    
    // Hapus suffix WhatsApp (@c.us, @s.whatsapp.net, dll)
    $phone = preg_replace('/@.*$/', '', $phone);
    
    // Hapus karakter non-digit kecuali +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // Hapus + di awal jika ada
    $phone = ltrim($phone, '+');
    
    // Trim
    $phone = trim($phone);
    
    // Validasi: nomor telepon Indonesia biasanya 10-13 digit (dengan/tanpa kode negara)
    // Format: 08xxxxxxxxxx (11 digit) atau 628xxxxxxxxxx (13 digit)
    
    // Jika lebih dari 15 digit, kemungkinan besar bukan nomor telepon valid
    // Coba beberapa strategi:
    if (strlen($phone) > 15) {
        error_log("Warning: Phone number terlalu panjang: $original -> $phone (" . strlen($phone) . " digits)");
        
        // Strategi 1: Cari pola nomor Indonesia (dimulai dengan 08 atau 62)
        if (preg_match('/(62|08)\d{9,11}/', $phone, $matches)) {
            $phone = $matches[0];
            error_log("Found Indonesian pattern: $phone");
        }
        // Strategi 2: Ambil 13 digit terakhir (untuk nomor dengan kode negara)
        elseif (strlen($phone) > 13) {
            $phone = substr($phone, -13);
            error_log("Using last 13 digits: $phone");
        }
        // Strategi 3: Ambil 11 digit terakhir (untuk nomor lokal)
        else {
            $phone = substr($phone, -11);
            error_log("Using last 11 digits: $phone");
        }
    }
    
    // Validasi panjang minimum
    if (strlen($phone) < 10) {
        error_log("Warning: Phone number terlalu pendek: $original -> $phone (" . strlen($phone) . " digits)");
        // Return original jika terlalu pendek (mungkin ada karakter yang perlu dipertahankan)
        return $original;
    }
    
    // Limit panjang maksimal
    $phone = substr($phone, 0, 50);
    
    error_log("Phone normalized: $original -> $phone");
    
    return $phone;
}

// Handle incoming webhook dari bot WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $phoneNumber = normalizePhoneNumber($input['phone_number'] ?? '');
    $waName = $input['wa_name'] ?? '';
    $message = $input['message'] ?? '';
    $location = $input['location'] ?? null;
    
    if (!$phoneNumber || !$message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'phone_number dan message diperlukan']);
        exit();
    }
    
    try {
        $absensi = new Absensi();
        $user = new User();
        $commandModel = new Command();
        
        // Parse message untuk absensi
        $messageLower = strtolower(trim($message));
        
        // Extract command (ambil kata pertama sebelum spasi)
        $commandParts = explode(' ', $messageLower, 2);
        $commandText = $commandParts[0];
        
        // Handle command /commands untuk menampilkan semua command
        if ($commandText === '/commands') {
            $allCommands = $commandModel->getAllCommands(true);
            
            if (empty($allCommands)) {
                $response = "📋 Daftar Command\n\n";
                $response .= "Belum ada command yang tersedia.";
            } else {
                $response = "📋 *Daftar Command yang Tersedia*\n\n";
                
                foreach ($allCommands as $index => $cmd) {
                    $response .= ($index + 1) . ". *" . $cmd['command'] . "*\n";
                }
                
                $response .= "\n━━━━━━━━━━━━━━━━━━━━\n";
                $response .= "💡 Ketik command di atas untuk menggunakan.";
            }
            
            echo json_encode([
                'success' => true,
                'message' => $response
            ]);
            exit();
        }
        
        // Cek apakah command dimulai dengan /wfh atau /wfo (untuk absensi)
        if (strpos($messageLower, '/wfh') === 0 || strpos($messageLower, '/wfo') === 0) {
            // Cek apakah sudah absen hari ini
            $absenToday = $absensi->checkAbsenToday($phoneNumber);
            if ($absenToday) {
                $waktu = date('d F Y H:i', strtotime($absenToday['waktu_absen']));
                echo json_encode([
                    'success' => false,
                    'message' => "⚠️ Anda sudah absen hari ini!\n" .
                                "Tipe: {$absenToday['type']}" . 
                                ($absenToday['lantai'] ? " - Lantai {$absenToday['lantai']}" : '') . "\n" .
                                "Waktu: {$waktu}",
                    'data' => $absenToday
                ]);
                exit();
            }
            
            // Parse absensi type
            $type = null;
            $lantai = null;
            
            if (strpos($messageLower, '/wfo') === 0) {
                $type = 'WFO';
                // Extract lantai - format: /wfo 21 atau /wfo21
                if (preg_match('/\/wfo\s*(\d+)/i', $message, $matches)) {
                    $lantai = $matches[1];
                } else {
                    // Jika tidak ada lantai, tetap set type WFO
                    $type = 'WFO';
                }
                
                // VALIDASI LOKASI untuk WFO
                if (!$location || !isset($location['latitude']) || !isset($location['longitude'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => "❌ Absensi WFO memerlukan lokasi!\n\n" .
                                    "Silakan kirim lokasi Anda bersama dengan pesan absen.\n" .
                                    "Cara:\n" .
                                    "1. Ketik: /wfo [nomor]\n" .
                                    "2. Kirim lokasi Anda (tap icon 📍 di WhatsApp)\n\n" .
                                    "Lokasi harus dalam radius 300 meter dari kantor."
                    ]);
                    exit();
                }
                
                // Validasi lokasi
                $locationValidation = LocationValidator::validateLocation(
                    $location['latitude'],
                    $location['longitude']
                );
                
                if (!$locationValidation['valid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => "❌ " . $locationValidation['message'] . "\n\n" .
                                    "Koordinat kantor: " . 
                                    LocationConfig::OFFICE_LATITUDE . ", " . 
                                    LocationConfig::OFFICE_LONGITUDE . "\n" .
                                    "Koordinat Anda: " . 
                                    $location['latitude'] . ", " . 
                                    $location['longitude'] . "\n\n" .
                                    "Silakan pastikan Anda berada di lokasi kantor."
                    ]);
                    exit();
                }
                
            } elseif (strpos($messageLower, '/wfh') === 0) {
                $type = 'WFH';
                // WFH tidak perlu validasi lokasi
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "❌ Format absensi salah!\n\n" .
                                "Format yang benar:\n" .
                                "• `/wfo [nomor]` - untuk Work From Office\n" .
                                "• `/wfh` - untuk Work From Home\n\n" .
                                "Contoh:\n" .
                                "• `/wfo 21`\n" .
                                "• `/wfo 5`\n" .
                                "• `/wfh`"
                ]);
                exit();
            }
            
            // Get or create user
            $userData = $user->getOrCreateUser($phoneNumber, $waName);
            
            // Create absensi
            $absensiData = $absensi->createAbsensi(
                $userData['id'],
                $phoneNumber,
                $type,
                $lantai,
                $location
            );
            
            // Format response
            $waktu = date('d F Y H:i', strtotime($absensiData['waktu_absen']));
            $response = "✅ Absensi berhasil!\n\n";
            $response .= "Nama: {$waName}\n";
            $response .= "Tipe: {$type}\n";
            if ($lantai) {
                $response .= "Lantai: {$lantai}\n";
            }
            
            // Tampilkan info lokasi untuk WFO
            if ($type === 'WFO' && $location) {
                if (isset($location['address']) && !empty($location['address'])) {
                    $response .= "Lokasi: {$location['address']}\n";
                }
                if (isset($location['latitude']) && isset($location['longitude'])) {
                    $locationValidation = LocationValidator::validateLocation(
                        $location['latitude'],
                        $location['longitude']
                    );
                    $response .= "Jarak dari kantor: " . LocationValidator::formatDistance($locationValidation['distance']) . "\n";
                }
            } elseif ($location && isset($location['address'])) {
                $response .= "Lokasi: {$location['address']}\n";
            }
            
            $response .= "Waktu: {$waktu}";
            
            echo json_encode([
                'success' => true,
                'message' => $response,
                'data' => $absensiData
            ]);
        } else {
            // Cek apakah command ada di database
            $commandData = $commandModel->getCommandByCommand($commandText);
            
            if ($commandData && $commandData['is_active']) {
                // Command ditemukan di database, kirim deskripsi
                $response = "ℹ️ " . $commandData['description'];
                echo json_encode([
                    'success' => true,
                    'message' => $response
                ]);
            } else {
                // Command tidak ditemukan
                // Ambil semua command aktif untuk ditampilkan sebagai bantuan
                $allCommands = $commandModel->getAllCommands(true);
                $commandList = "❌ Command tidak dikenali.\n\n";
                $commandList .= "📋 Daftar Command yang tersedia:\n\n";
                
                foreach ($allCommands as $cmd) {
                    $commandList .= "• " . $cmd['command'] . "\n";
                    if (!empty($cmd['description'])) {
                        $commandList .= "  " . $cmd['description'] . "\n";
                    }
                    $commandList .= "\n";
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $commandList
                ]);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        $errorMessage = $e->getMessage();
        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        
        // Log error untuk debugging
        error_log("Bot API Error: " . $errorMessage);
        error_log("Error in file: " . $errorFile . " on line " . $errorLine);
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Log input data untuk debugging
        error_log("Input data: " . json_encode([
            'phone_number' => $phoneNumber,
            'wa_name' => $waName,
            'message' => $message
        ]));
        
        // Jika error database, berikan pesan yang lebih user-friendly
        if (strpos($errorMessage, 'Database connection') !== false) {
            $errorMessage = "Database connection failed. Silakan cek konfigurasi database.";
        }
        
        // Untuk development, tampilkan error detail (hapus di production)
        $showDetails = true; // Set false di production
        
        echo json_encode([
            'success' => false,
            'error' => $showDetails ? $errorMessage : 'Internal server error',
            'message' => $showDetails 
                ? "❌ Error: " . $errorMessage 
                : '❌ Terjadi kesalahan saat memproses absensi. Silakan hubungi administrator.',
            'debug' => $showDetails ? [
                'file' => $errorFile,
                'line' => $errorLine
            ] : null
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan']);
}

