<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

$user = new User();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'login':
                $waName = trim($input['wa_name'] ?? '');
                $password = $input['password'] ?? '';

                if (empty($waName) || empty($password)) {
                    throw new Exception('Username dan password diperlukan');
                }

                $result = $user->login($waName, $password);

                if ($result['success']) {
                    // Set session
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['wa_name'] = $result['user']['wa_name'];
                    $_SESSION['isAdmin'] = $result['user']['isadmin'] ?? false;
                    $_SESSION['name'] = $result['user']['name'] ?? $result['user']['wa_name'];

                    echo json_encode([
                        'success' => true,
                        'message' => 'Login berhasil',
                        'user' => $result['user']
                    ]);
                } else {
                    echo json_encode($result);
                }
                break;

            case 'register':
                $waName = trim($input['wa_name'] ?? '');
                $password = $input['password'] ?? '';
                $phoneNumber = $input['phone_number'] ?? null;

                if (empty($waName) || empty($password)) {
                    throw new Exception('Username dan password diperlukan');
                }

                if (strlen($password) < 6) {
                    throw new Exception('Password minimal 6 karakter');
                }

                $result = $user->register($waName, $password, $phoneNumber);

                if ($result['success']) {
                    // Set session
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['wa_name'] = $result['user']['wa_name'];
                    $_SESSION['isAdmin'] = $result['user']['isadmin'] ?? false;
                    $_SESSION['name'] = $result['user']['name'] ?? $result['user']['wa_name'];

                    echo json_encode([
                        'success' => true,
                        'message' => 'Registrasi berhasil',
                        'user' => $result['user']
                    ]);
                } else {
                    echo json_encode($result);
                }
                break;

            case 'reset_password':
                $waName = trim($input['wa_name'] ?? '');
                $newPassword = $input['new_password'] ?? '';

                if (empty($waName) || empty($newPassword)) {
                    throw new Exception('Username dan password baru diperlukan');
                }

                if (strlen($newPassword) < 6) {
                    throw new Exception('Password minimal 6 karakter');
                }

                // Cek apakah user ada
                $existingUser = $user->getUserByWaName($waName);
                if (!$existingUser) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Username tidak ditemukan'
                    ]);
                    exit();
                }

                $result = $user->resetPassword($waName, $newPassword);

                if ($result['success']) {
                    // Set session
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['wa_name'] = $result['user']['wa_name'];
                    $_SESSION['isAdmin'] = $result['user']['isadmin'] ?? false;
                    $_SESSION['name'] = $result['user']['name'] ?? $result['user']['wa_name'];

                    echo json_encode([
                        'success' => true,
                        'message' => 'Password berhasil direset',
                        'user' => $result['user']
                    ]);
                } else {
                    echo json_encode($result);
                }
                break;

            case 'logout':
                session_destroy();
                echo json_encode([
                    'success' => true,
                    'message' => 'Logout berhasil'
                ]);
                break;

            case 'check_session':
                if (isset($_SESSION['user_id'])) {
                    echo json_encode([
                        'success' => true,
                        'user' => [
                            'id' => $_SESSION['user_id'],
                            'wa_name' => $_SESSION['wa_name'],
                            'name' => $_SESSION['name'],
                            'isAdmin' => $_SESSION['isAdmin'] ?? false
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Session tidak ditemukan'
                    ]);
                }
                break;

            default:
                throw new Exception('Action tidak valid');
        }
    } else {
        throw new Exception('Method tidak diizinkan');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

