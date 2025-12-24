<?php
require_once __DIR__ . '/../config/database.php';

class WhatsAppGroup {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Cek apakah group ID diizinkan (aktif)
    public function isGroupAllowed($groupId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM whatsapp_groups 
                 WHERE group_id = :group_id AND is_active = TRUE"
            );
            $stmt->execute(['group_id' => $groupId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error isGroupAllowed: " . $e->getMessage());
            return false;
        }
    }

    // Get semua group IDs yang aktif
    public function getAllActiveGroupIds() {
        try {
            $stmt = $this->db->query(
                "SELECT group_id FROM whatsapp_groups 
                 WHERE is_active = TRUE 
                 ORDER BY created_at ASC"
            );
            $results = $stmt->fetchAll();
            return array_column($results, 'group_id');
        } catch (PDOException $e) {
            error_log("Error getAllActiveGroupIds: " . $e->getMessage());
            return [];
        }
    }

    // Get semua groups dengan detail
    public function getAllGroups() {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM whatsapp_groups 
                 ORDER BY created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getAllGroups: " . $e->getMessage());
            return [];
        }
    }

    // Get group by ID
    public function getGroupById($groupId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM whatsapp_groups WHERE group_id = :group_id"
            );
            $stmt->execute(['group_id' => $groupId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getGroupById: " . $e->getMessage());
            return null;
        }
    }

    // Tambah group baru
    public function addGroup($groupId, $groupName = null, $description = null) {
        try {
            // Cek apakah sudah ada
            $existing = $this->getGroupById($groupId);
            if ($existing) {
                return ['success' => false, 'message' => 'Group ID sudah terdaftar'];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO whatsapp_groups (group_id, group_name, description, is_active) 
                 VALUES (:group_id, :group_name, :description, TRUE) 
                 RETURNING *"
            );
            $stmt->execute([
                'group_id' => $groupId,
                'group_name' => $groupName,
                'description' => $description
            ]);

            return ['success' => true, 'group' => $stmt->fetch()];
        } catch (PDOException $e) {
            error_log("Error addGroup: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambahkan group: ' . $e->getMessage()];
        }
    }

    // Update group
    public function updateGroup($groupId, $data) {
        try {
            $updates = [];
            $params = ['group_id' => $groupId];

            if (isset($data['group_name'])) {
                $updates[] = 'group_name = :group_name';
                $params['group_name'] = $data['group_name'];
            }
            if (isset($data['description'])) {
                $updates[] = 'description = :description';
                $params['description'] = $data['description'];
            }
            if (isset($data['is_active'])) {
                $updates[] = 'is_active = :is_active';
                $params['is_active'] = $data['is_active'] ? 'TRUE' : 'FALSE';
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'Tidak ada data yang diupdate'];
            }

            $sql = "UPDATE whatsapp_groups SET " . implode(', ', $updates) . 
                   ", updated_at = CURRENT_TIMESTAMP WHERE group_id = :group_id RETURNING *";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'group' => $stmt->fetch()];
        } catch (PDOException $e) {
            error_log("Error updateGroup: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengupdate group: ' . $e->getMessage()];
        }
    }

    // Hapus group (soft delete dengan set is_active = false)
    public function deleteGroup($groupId) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE whatsapp_groups 
                 SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
                 WHERE group_id = :group_id 
                 RETURNING *"
            );
            $stmt->execute(['group_id' => $groupId]);
            
            return ['success' => true, 'group' => $stmt->fetch()];
        } catch (PDOException $e) {
            error_log("Error deleteGroup: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus group: ' . $e->getMessage()];
        }
    }
}

