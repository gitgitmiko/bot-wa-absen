<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/bootstrap.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../models/WhatsAppGroup.php';

$groupModel = new WhatsAppGroup();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Cek apakah ada parameter group_id untuk validasi
            if (isset($_GET['group_id'])) {
                $groupId = $_GET['group_id'];
                $isAllowed = $groupModel->isGroupAllowed($groupId);
                
                echo json_encode([
                    'success' => true,
                    'group_id' => $groupId,
                    'allowed' => $isAllowed
                ]);
            } 
            // Get semua active group IDs (untuk bot)
            else if (isset($_GET['active_only']) && $_GET['active_only'] === 'true') {
                $groupIds = $groupModel->getAllActiveGroupIds();
                
                echo json_encode([
                    'success' => true,
                    'data' => $groupIds
                ]);
            }
            // Get semua groups dengan detail
            else {
                $groups = $groupModel->getAllGroups();
                
                echo json_encode([
                    'success' => true,
                    'data' => $groups
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['group_id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'group_id diperlukan'
                ]);
                http_response_code(400);
                exit();
            }

            $result = $groupModel->addGroup(
                $data['group_id'],
                $data['group_name'] ?? null,
                $data['description'] ?? null
            );

            if ($result['success']) {
                echo json_encode($result);
            } else {
                echo json_encode($result);
                http_response_code(400);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['group_id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'group_id diperlukan'
                ]);
                http_response_code(400);
                exit();
            }

            $result = $groupModel->updateGroup($data['group_id'], $data);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                echo json_encode($result);
                http_response_code(400);
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['group_id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'group_id diperlukan'
                ]);
                http_response_code(400);
                exit();
            }

            $result = $groupModel->deleteGroup($data['group_id']);

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
    error_log("Error in groups.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    http_response_code(500);
}

