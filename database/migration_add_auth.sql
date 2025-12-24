-- Migration: Tambahkan kolom password dan isAdmin di table users
-- Tambahkan table commands untuk menyimpan command dan deskripsinya

-- Tambahkan kolom password dan isAdmin di table users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password VARCHAR(255),
ADD COLUMN IF NOT EXISTS isAdmin BOOLEAN DEFAULT FALSE;

-- Buat table commands untuk menyimpan command dan deskripsinya
CREATE TABLE IF NOT EXISTS commands (
    id SERIAL PRIMARY KEY,
    command VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default commands untuk absensi (tetap hardcoded di bot)
-- Command lainnya bisa ditambahkan melalui admin panel
INSERT INTO commands (command, description, is_active) VALUES
    ('/wfh', 'Work From Home - Absensi untuk bekerja dari rumah', TRUE),
    ('/wfo', 'Work From Office - Absensi untuk bekerja di kantor (format: /wfo [nomor lantai])', TRUE)
ON CONFLICT (command) DO NOTHING;

-- Index untuk mempercepat query
CREATE INDEX IF NOT EXISTS idx_commands_command ON commands(command);
CREATE INDEX IF NOT EXISTS idx_commands_is_active ON commands(is_active);

