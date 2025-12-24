-- Database Schema untuk Bot Absensi WhatsApp

-- Table untuk menyimpan data user
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255),
    wa_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table untuk menyimpan data absensi
CREATE TABLE IF NOT EXISTS absensi (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    phone_number VARCHAR(20) NOT NULL,
    type VARCHAR(10) NOT NULL CHECK (type IN ('WFO', 'WFH', 'WFA')),
    lantai VARCHAR(50),
    location_latitude DECIMAL(10, 8),
    location_longitude DECIMAL(11, 8),
    location_address TEXT,
    waktu_absen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk mempercepat query
CREATE INDEX IF NOT EXISTS idx_absensi_user_id ON absensi(user_id);
CREATE INDEX IF NOT EXISTS idx_absensi_phone_number ON absensi(phone_number);
CREATE INDEX IF NOT EXISTS idx_absensi_waktu_absen ON absensi(waktu_absen);
CREATE INDEX IF NOT EXISTS idx_absensi_type ON absensi(type);

-- View untuk laporan absensi harian
CREATE OR REPLACE VIEW v_absensi_harian AS
SELECT 
    a.id,
    u.name,
    u.phone_number,
    a.type,
    a.lantai,
    a.location_address,
    a.waktu_absen,
    DATE(a.waktu_absen) as tanggal
FROM absensi a
LEFT JOIN users u ON a.user_id = u.id
ORDER BY a.waktu_absen DESC;

