<?php
require_once __DIR__ . '/../config/database.php';

class Command {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Get all commands
    public function getAllCommands($activeOnly = false) {
        try {
            if ($activeOnly) {
                $stmt = $this->db->query("SELECT * FROM commands WHERE is_active = TRUE ORDER BY command ASC");
            } else {
                $stmt = $this->db->query("SELECT * FROM commands ORDER BY command ASC");
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getAllCommands: " . $e->getMessage());
            throw $e;
        }
    }

    // Get command by command string
    public function getCommandByCommand($command) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM commands WHERE command = :command");
            $stmt->execute(['command' => $command]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getCommandByCommand: " . $e->getMessage());
            throw $e;
        }
    }

    // Get command by ID
    public function getCommandById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM commands WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getCommandById: " . $e->getMessage());
            throw $e;
        }
    }

    // Create command
    public function createCommand($command, $description, $isActive = true) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO commands (command, description, is_active) 
                 VALUES (:command, :description, :is_active) RETURNING *"
            );
            $stmt->execute([
                'command' => $command,
                'description' => $description,
                'is_active' => $isActive
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error createCommand: " . $e->getMessage());
            throw $e;
        }
    }

    // Update command
    public function updateCommand($id, $command, $description, $isActive) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE commands 
                 SET command = :command, description = :description, is_active = :is_active, 
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id RETURNING *"
            );
            $stmt->execute([
                'id' => $id,
                'command' => $command,
                'description' => $description,
                'is_active' => $isActive
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updateCommand: " . $e->getMessage());
            throw $e;
        }
    }

    // Delete command
    public function deleteCommand($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM commands WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleteCommand: " . $e->getMessage());
            throw $e;
        }
    }
}

