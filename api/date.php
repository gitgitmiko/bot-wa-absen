<?php
/**
 * API untuk mendapatkan tanggal server
 * Digunakan untuk sinkronisasi timezone antara client dan server
 */

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

