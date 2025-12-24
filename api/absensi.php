<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../models/Absensi.php';
require_once __DIR__ . '/../models/User.php';

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$absensi = new Absensi();
$user = new User();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'today':
            if ($method === 'GET') {
                $data = $absensi->getAbsensiToday();
                echo json_encode(['success' => true, 'data' => $data]);
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
            }
            break;

        default:
            throw new Exception('Action tidak valid');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

