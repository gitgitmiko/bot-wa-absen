<?php
/**
 * API untuk mendapatkan tanggal server
 * Digunakan untuk sinkronisasi timezone antara client dan server
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set header JSON di awal
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error in date.php: $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'Terjadi kesalahan saat memproses request'
    ]);
    exit();
});

try {
    require_once __DIR__ . '/../config/bootstrap.php';
    
    // Return tanggal server dalam format Y-m-d
    $serverDate = date('Y-m-d');
    $serverDateTime = date('Y-m-d H:i:s');
    $timezone = date_default_timezone_get();
    
    echo json_encode([
        'success' => true,
        'date' => $serverDate,
        'datetime' => $serverDateTime,
        'timezone' => $timezone,
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    error_log("Error in date.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Terjadi kesalahan saat memproses request'
    ]);
}

