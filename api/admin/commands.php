<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../models/Command.php';

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

$command = new Command();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
            $data = $command->getAllCommands($activeOnly);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $cmd = trim($input['command'] ?? '');
            $description = trim($input['description'] ?? '');
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;

            if (empty($cmd)) {
                throw new Exception('Command tidak boleh kosong');
            }

            // Validasi format command harus dimulai dengan /
            if (strpos($cmd, '/') !== 0) {
                throw new Exception('Command harus dimulai dengan /');
            }

            $data = $command->createCommand($cmd, $description, $isActive);
            echo json_encode(['success' => true, 'data' => $data, 'message' => 'Command berhasil ditambahkan']);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $cmd = trim($input['command'] ?? '');
            $description = trim($input['description'] ?? '');
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;

            if (empty($id)) {
                throw new Exception('ID command diperlukan');
            }

            if (empty($cmd)) {
                throw new Exception('Command tidak boleh kosong');
            }

            // Validasi format command harus dimulai dengan /
            if (strpos($cmd, '/') !== 0) {
                throw new Exception('Command harus dimulai dengan /');
            }

            $data = $command->updateCommand($id, $cmd, $description, $isActive);
            echo json_encode(['success' => true, 'data' => $data, 'message' => 'Command berhasil diupdate']);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;

            if (empty($id)) {
                throw new Exception('ID command diperlukan');
            }

            $success = $command->deleteCommand($id);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Command berhasil dihapus']);
            } else {
                throw new Exception('Command tidak ditemukan');
            }
            break;

        default:
            throw new Exception('Method tidak diizinkan');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

