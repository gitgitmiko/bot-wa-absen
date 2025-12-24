<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../models/User.php';

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$user = new User();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = $user->getAllUsers();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        throw new Exception('Method tidak diizinkan');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

