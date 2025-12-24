<?php
/**
 * Location Validator untuk Absensi WFO
 * Menggunakan Haversine formula untuk menghitung jarak
 */

require_once __DIR__ . '/../config/location.php';

class LocationValidator {
    
    /**
     * Hitung jarak antara dua koordinat menggunakan Haversine formula
     * @param float $lat1 Latitude titik 1
     * @param float $lon1 Longitude titik 1
     * @param float $lat2 Latitude titik 2
     * @param float $lon2 Longitude titik 2
     * @return float Jarak dalam meter
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Radius bumi dalam meter
        $earthRadius = 6371000;
        
        // Convert derajat ke radian
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        // Haversine formula
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Jarak dalam meter
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    /**
     * Validasi apakah lokasi berada dalam radius yang ditentukan
     * @param float $latitude Latitude user
     * @param float $longitude Longitude user
     * @return array ['valid' => bool, 'distance' => float, 'message' => string]
     */
    public static function validateLocation($latitude, $longitude) {
        // Validasi input
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return [
                'valid' => false,
                'distance' => 0,
                'message' => 'Koordinat lokasi tidak valid'
            ];
        }
        
        // Hitung jarak dari kantor
        $distance = self::calculateDistance(
            LocationConfig::OFFICE_LATITUDE,
            LocationConfig::OFFICE_LONGITUDE,
            $latitude,
            $longitude
        );
        
        // Cek apakah dalam radius
        $isValid = $distance <= LocationConfig::RADIUS_METERS;
        
        $message = $isValid 
            ? "Lokasi valid (jarak: " . round($distance, 2) . " meter dari kantor)"
            : "Lokasi tidak valid! Anda berada " . round($distance, 2) . " meter dari kantor. Maksimal " . LocationConfig::RADIUS_METERS . " meter.";
        
        return [
            'valid' => $isValid,
            'distance' => round($distance, 2),
            'message' => $message,
            'office_lat' => LocationConfig::OFFICE_LATITUDE,
            'office_lon' => LocationConfig::OFFICE_LONGITUDE,
            'user_lat' => $latitude,
            'user_lon' => $longitude
        ];
    }
    
    /**
     * Format jarak untuk ditampilkan
     * @param float $distance Jarak dalam meter
     * @return string
     */
    public static function formatDistance($distance) {
        if ($distance < 1000) {
            return round($distance, 2) . " meter";
        } else {
            return round($distance / 1000, 2) . " km";
        }
    }
}

