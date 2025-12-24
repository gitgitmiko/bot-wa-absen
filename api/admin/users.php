<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session dan cek admin
session_start();

if (!isset($_SESSION['user_id']) || !($_SESSION['isAdmin'] ?? false)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Akses ditolak. Hanya admin yang dapat mengakses.'
    ]);
    exit();
}

$user = new User();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Get all users (tanpa password)
            $users = $user->getAllUsers();
            // Remove password dari response
            foreach ($users as &$u) {
                unset($u['password']);
            }
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'POST':
            // Create user baru
            $input = json_decode(file_get_contents('php://input'), true);
            $waName = trim($input['wa_name'] ?? '');
            $password = $input['password'] ?? '';
            $phoneNumber = $input['phone_number'] ?? null;
            $name = trim($input['name'] ?? $waName);
            $isAdmin = isset($input['isAdmin']) ? (bool)$input['isAdmin'] : false;

            if (empty($waName)) {
                throw new Exception('WA Name tidak boleh kosong');
            }

            if (empty($password)) {
                throw new Exception('Password tidak boleh kosong');
            }

            if (strlen($password) < 6) {
                throw new Exception('Password minimal 6 karakter');
            }

            $result = $user->register($waName, $password, $phoneNumber);
            
            if ($result['success']) {
                // Update name dan isAdmin jika ada
                if ($name !== $waName || $isAdmin) {
                    $updateData = [];
                    if ($name !== $waName) {
                        $updateData['name'] = $name;
                    }
                    if ($isAdmin) {
                        $updateData['isAdmin'] = true;
                    }
                    $user->updateUser($result['user']['id'], $updateData);
                    $result['user'] = $user->getUserById($result['user']['id']);
                }
                
                unset($result['user']['password']);
                echo json_encode($result);
            } else {
                echo json_encode($result);
                http_response_code(400);
            }
            break;

        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['id'] ?? null;

            if (!$userId) {
                throw new Exception('User ID diperlukan');
            }

            $updateData = [];
            if (isset($input['name'])) {
                $updateData['name'] = trim($input['name']);
            }
            if (isset($input['wa_name'])) {
                $updateData['wa_name'] = trim($input['wa_name']);
            }
            if (isset($input['phone_number'])) {
                $updateData['phone_number'] = $input['phone_number'];
            }
            if (isset($input['isAdmin'])) {
                $updateData['isAdmin'] = (bool)$input['isAdmin'];
            }

            if (empty($updateData)) {
                throw new Exception('Tidak ada data yang diupdate');
            }

            $result = $user->updateUser($userId, $updateData);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                echo json_encode($result);
                http_response_code(400);
            }
            break;

        case 'DELETE':
            // Delete user
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['id'] ?? null;

            if (!$userId) {
                throw new Exception('User ID diperlukan');
            }

            // Prevent delete current user
            if ($userId == $_SESSION['user_id']) {
                throw new Exception('Tidak dapat menghapus user yang sedang login');
            }

            $result = $user->deleteUser($userId);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                echo json_encode($result);
                http_response_code(400);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Method tidak didukung'
            ]);
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    error_log("Error in admin/users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    http_response_code(400);
}

