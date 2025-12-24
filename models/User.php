<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Normalize phone number
    private function normalizePhoneNumber($phone) {
        if (empty($phone)) {
            return '';
        }
        
        // Hapus suffix WhatsApp (@c.us, @s.whatsapp.net, dll)
        $phone = preg_replace('/@.*$/', '', $phone);
        
        // Hapus karakter non-digit kecuali +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Hapus + di awal jika ada
        $phone = ltrim($phone, '+');
        
        // Trim dan limit panjang
        $phone = substr(trim($phone), 0, 50);
        
        return $phone;
    }

    // Get atau create user berdasarkan phone number
    public function getOrCreateUser($phoneNumber, $waName = null) {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            if (empty($phoneNumber)) {
                throw new Exception('Phone number tidak valid');
            }
            
            // Cari user berdasarkan phone number
            $stmt = $this->db->prepare("SELECT * FROM users WHERE phone_number = :phone_number");
            $stmt->execute(['phone_number' => $phoneNumber]);
            $user = $stmt->fetch();

            if ($user) {
                // Update WA name jika ada
                if ($waName) {
                    $stmt = $this->db->prepare(
                        "UPDATE users SET wa_name = :wa_name, updated_at = CURRENT_TIMESTAMP 
                         WHERE phone_number = :phone_number"
                    );
                    $stmt->execute([
                        'wa_name' => $waName,
                        'phone_number' => $phoneNumber
                    ]);
                    $user['wa_name'] = $waName;
                }
                return $user;
            }

            // Create user baru
            $stmt = $this->db->prepare(
                "INSERT INTO users (phone_number, wa_name, name) 
                 VALUES (:phone_number, :wa_name, :name) RETURNING *"
            );
            $stmt->execute([
                'phone_number' => $phoneNumber,
                'wa_name' => $waName ?: $phoneNumber,
                'name' => $waName ?: $phoneNumber
            ]);

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getOrCreateUser: " . $e->getMessage());
            throw $e;
        }
    }

    // Get all users
    public function getAllUsers() {
        try {
            $stmt = $this->db->query("SELECT * FROM users ORDER BY name ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getAllUsers: " . $e->getMessage());
            throw $e;
        }
    }

    // Get user by phone number
    public function getUserByPhone($phoneNumber) {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            $stmt = $this->db->prepare("SELECT * FROM users WHERE phone_number = :phone_number");
            $stmt->execute(['phone_number' => $phoneNumber]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getUserByPhone: " . $e->getMessage());
            throw $e;
        }
    }

    // Get user by wa_name
    public function getUserByWaName($waName) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE wa_name = :wa_name");
            $stmt->execute(['wa_name' => $waName]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getUserByWaName: " . $e->getMessage());
            throw $e;
        }
    }

    // Login user dengan wa_name dan password
    public function login($waName, $password) {
        try {
            $user = $this->getUserByWaName($waName);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Username tidak ditemukan'];
            }

            // Jika user belum punya password, return flag untuk reset password
            if (empty($user['password'])) {
                return ['success' => false, 'needs_reset' => true, 'message' => 'User belum memiliki password. Silakan reset password terlebih dahulu.'];
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Password salah'];
            }

            // Hapus password dari response
            unset($user['password']);
            
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            error_log("Error login: " . $e->getMessage());
            throw $e;
        }
    }

    // Registrasi user baru
    public function register($waName, $password, $phoneNumber = null) {
        try {
            // Cek apakah wa_name sudah ada
            $existingUser = $this->getUserByWaName($waName);
            if ($existingUser) {
                return ['success' => false, 'message' => 'Username sudah terdaftar'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt = $this->db->prepare(
                "INSERT INTO users (wa_name, password, name, phone_number) 
                 VALUES (:wa_name, :password, :name, :phone_number) RETURNING id, wa_name, name, phone_number, isAdmin, created_at"
            );
            $stmt->execute([
                'wa_name' => $waName,
                'password' => $hashedPassword,
                'name' => $waName,
                'phone_number' => $phoneNumber
            ]);

            $user = $stmt->fetch();
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            error_log("Error register: " . $e->getMessage());
            throw $e;
        }
    }

    // Reset password untuk user yang sudah ada
    public function resetPassword($waName, $newPassword) {
        try {
            $user = $this->getUserByWaName($waName);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Username tidak ditemukan'];
            }

            // Hash password baru
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            $stmt = $this->db->prepare(
                "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP 
                 WHERE wa_name = :wa_name RETURNING id, wa_name, name, phone_number, isAdmin"
            );
            $stmt->execute([
                'wa_name' => $waName,
                'password' => $hashedPassword
            ]);

            $user = $stmt->fetch();
            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            error_log("Error resetPassword: " . $e->getMessage());
            throw $e;
        }
    }

    // Update password (untuk user yang sudah login)
    public function updatePassword($userId, $oldPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }

            // Verify old password
            if (!password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Password lama salah'];
            }

            // Hash password baru
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            $stmt = $this->db->prepare(
                "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id"
            );
            $stmt->execute([
                'id' => $userId,
                'password' => $hashedPassword
            ]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error updatePassword: " . $e->getMessage());
            throw $e;
        }
    }

    // Update user
    public function updateUser($userId, $data) {
        try {
            $updates = [];
            $params = ['id' => $userId];

            if (isset($data['name'])) {
                $updates[] = 'name = :name';
                $params['name'] = $data['name'];
            }
            if (isset($data['wa_name'])) {
                // Cek apakah wa_name sudah digunakan oleh user lain
                $existing = $this->getUserByWaName($data['wa_name']);
                if ($existing && $existing['id'] != $userId) {
                    return ['success' => false, 'message' => 'WA Name sudah digunakan'];
                }
                $updates[] = 'wa_name = :wa_name';
                $params['wa_name'] = $data['wa_name'];
            }
            if (isset($data['phone_number'])) {
                $phoneNumber = $this->normalizePhoneNumber($data['phone_number']);
                if (!empty($phoneNumber)) {
                    // Cek apakah phone_number sudah digunakan oleh user lain
                    $existing = $this->getUserByPhone($phoneNumber);
                    if ($existing && $existing['id'] != $userId) {
                        return ['success' => false, 'message' => 'Nomor HP sudah digunakan'];
                    }
                    $updates[] = 'phone_number = :phone_number';
                    $params['phone_number'] = $phoneNumber;
                }
            }
            if (isset($data['isAdmin'])) {
                $updates[] = 'isAdmin = :isAdmin';
                $params['isAdmin'] = $data['isAdmin'] ? 'TRUE' : 'FALSE';
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'Tidak ada data yang diupdate'];
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . 
                   ", updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING id, wa_name, name, phone_number, isAdmin, created_at";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'user' => $stmt->fetch()];
        } catch (PDOException $e) {
            error_log("Error updateUser: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengupdate user: ' . $e->getMessage()];
        }
    }

    // Delete user (soft delete atau hard delete)
    public function deleteUser($userId, $hardDelete = false) {
        try {
            if ($hardDelete) {
                // Hard delete
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
            } else {
                // Soft delete - set phone_number dan wa_name ke null atau mark as deleted
                // Untuk sekarang kita hard delete saja karena tidak ada kolom deleted_at
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error deleteUser: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus user: ' . $e->getMessage()];
        }
    }

    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id, wa_name, name, phone_number, isAdmin, created_at, updated_at FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getUserById: " . $e->getMessage());
            return null;
        }
    }
}

