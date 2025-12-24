<?php
require_once __DIR__ . '/../config/database.php';

class Absensi {
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

    // Create absensi
    public function createAbsensi($userId, $phoneNumber, $type, $lantai = null, $location = null) {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            if (empty($phoneNumber)) {
                throw new Exception('Phone number tidak valid');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO absensi (user_id, phone_number, type, lantai, location_latitude, location_longitude, location_address)
                 VALUES (:user_id, :phone_number, :type, :lantai, :latitude, :longitude, :address) RETURNING *"
            );
            $stmt->execute([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'type' => $type,
                'lantai' => $lantai,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'address' => $location['address'] ?? null
            ]);

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error createAbsensi: " . $e->getMessage());
            throw $e;
        }
    }

    // Get absensi by date range
    public function getAbsensiByDateRange($startDate, $endDate) {
        try {
            // Log untuk debugging
            error_log("getAbsensiByDateRange called with: startDate=$startDate, endDate=$endDate");
            
            $stmt = $this->db->prepare(
                "SELECT a.*, u.name, u.wa_name, u.phone_number as user_phone
                 FROM absensi a
                 LEFT JOIN users u ON a.user_id = u.id
                 WHERE DATE(a.waktu_absen) BETWEEN :start_date AND :end_date
                 ORDER BY a.waktu_absen DESC"
            );
            $stmt->execute([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $result = $stmt->fetchAll();
            
            // Log hasil
            error_log("getAbsensiByDateRange returned " . count($result) . " records");
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getAbsensiByDateRange: " . $e->getMessage());
            throw $e;
        }
    }

    // Get absensi today
    public function getAbsensiToday() {
        try {
            $stmt = $this->db->query(
                "SELECT a.*, u.name, u.wa_name, u.phone_number as user_phone
                 FROM absensi a
                 LEFT JOIN users u ON a.user_id = u.id
                 WHERE DATE(a.waktu_absen) = CURRENT_DATE
                 ORDER BY a.waktu_absen DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getAbsensiToday: " . $e->getMessage());
            throw $e;
        }
    }

    // Get absensi by user
    public function getAbsensiByUser($phoneNumber, $limit = 30) {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            $stmt = $this->db->prepare(
                "SELECT a.*, u.name, u.wa_name
                 FROM absensi a
                 LEFT JOIN users u ON a.user_id = u.id
                 WHERE a.phone_number = :phone_number
                 ORDER BY a.waktu_absen DESC
                 LIMIT :limit"
            );
            $stmt->bindValue(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getAbsensiByUser: " . $e->getMessage());
            throw $e;
        }
    }

    // Get statistics
    public function getStatistics($startDate, $endDate) {
        try {
            // Log untuk debugging
            error_log("getStatistics called with: startDate=$startDate, endDate=$endDate");
            
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_absen,
                    COUNT(DISTINCT user_id) as total_user,
                    COUNT(CASE WHEN type = 'WFO' THEN 1 END) as total_wfo,
                    COUNT(CASE WHEN type = 'WFH' THEN 1 END) as total_wfh
                 FROM absensi
                 WHERE DATE(waktu_absen) BETWEEN :start_date AND :end_date"
            );
            $stmt->execute([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $result = $stmt->fetch();
            
            // Log hasil
            error_log("getStatistics returned: " . json_encode($result));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getStatistics: " . $e->getMessage());
            throw $e;
        }
    }

    // Check if user already absen today
    public function checkAbsenToday($phoneNumber) {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            if (empty($phoneNumber)) {
                return null;
            }
            
            $stmt = $this->db->prepare(
                "SELECT * FROM absensi 
                 WHERE phone_number = :phone_number 
                 AND DATE(waktu_absen) = CURRENT_DATE
                 ORDER BY waktu_absen DESC
                 LIMIT 1"
            );
            $stmt->execute(['phone_number' => $phoneNumber]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error checkAbsenToday: " . $e->getMessage());
            throw $e;
        }
    }
}

