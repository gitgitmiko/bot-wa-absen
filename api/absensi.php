<?php
// Set error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output, hanya di log

// Set header JSON di awal untuk memastikan semua output adalah JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handler untuk memastikan semua error mengembalikan JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error in absensi.php: $errstr in $errfile on line $errline");
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
    require_once __DIR__ . '/../models/Absensi.php';
    require_once __DIR__ . '/../models/User.php';
    
    $absensi = new Absensi();
    $user = new User();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Parameter action diperlukan');
    }
    
    switch ($action) {
        case 'today':
            if ($method === 'GET') {
                $data = $absensi->getAbsensiToday();
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Method tidak diizinkan untuk action today');
            }
            break;

        case 'range':
            if ($method === 'GET') {
                $startDate = $_GET['startDate'] ?? date('Y-m-d');
                $endDate = $_GET['endDate'] ?? date('Y-m-d');
                
                // Log untuk debugging
                error_log("API range called: startDate=$startDate, endDate=$endDate");
                
                $data = $absensi->getAbsensiByDateRange($startDate, $endDate);
                
                // Log hasil
                error_log("API range result count: " . count($data));
                
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Method tidak diizinkan untuk action range');
            }
            break;

        case 'user':
            if ($method === 'GET') {
                $phoneNumber = $_GET['phoneNumber'] ?? '';
                $limit = $_GET['limit'] ?? 30;
                if ($phoneNumber) {
                    $data = $absensi->getAbsensiByUser($phoneNumber, $limit);
                    echo json_encode(['success' => true, 'data' => $data]);
                } else {
                    throw new Exception('phoneNumber diperlukan');
                }
            } else {
                throw new Exception('Method tidak diizinkan untuk action user');
            }
            break;

        case 'statistics':
            if ($method === 'GET') {
                $startDate = $_GET['startDate'] ?? date('Y-m-d');
                $endDate = $_GET['endDate'] ?? date('Y-m-d');
                
                // Log untuk debugging
                error_log("API statistics called: startDate=$startDate, endDate=$endDate");
                
                $data = $absensi->getStatistics($startDate, $endDate);
                
                // Log hasil
                error_log("API statistics result: " . json_encode($data));
                
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Method tidak diizinkan untuk action statistics');
            }
            break;

        case 'create':
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $phoneNumber = $input['phone_number'] ?? '';
                $waName = $input['wa_name'] ?? '';
                $type = $input['type'] ?? '';
                $lantai = $input['lantai'] ?? null;
                $location = $input['location'] ?? null;

                if (!$phoneNumber || !$type) {
                    throw new Exception('phone_number dan type diperlukan');
                }

                // Get or create user
                $userData = $user->getOrCreateUser($phoneNumber, $waName);

                // Check if already absen today
                $absenToday = $absensi->checkAbsenToday($phoneNumber);
                if ($absenToday) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Anda sudah absen hari ini',
                        'data' => $absenToday
                    ]);
                    exit();
                }

                // Create absensi
                $data = $absensi->createAbsensi(
                    $userData['id'],
                    $phoneNumber,
                    $type,
                    $lantai,
                    $location
                );

                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception('Method tidak diizinkan untuk action create');
            }
            break;

        default:
            throw new Exception('Action tidak valid: ' . ($action ?: 'kosong'));
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => 'Terjadi kesalahan saat mengakses database'
    ]);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => $e->getMessage()
    ]);
}
