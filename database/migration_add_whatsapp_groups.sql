-- Migration: Tambah tabel untuk menyimpan WhatsApp Group IDs
-- Created: 2025

-- Table untuk menyimpan WhatsApp Group IDs
CREATE TABLE IF NOT EXISTS whatsapp_groups (
    id SERIAL PRIMARY KEY,
    group_id VARCHAR(255) UNIQUE NOT NULL,
    group_name VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk mempercepat query
CREATE INDEX IF NOT EXISTS idx_whatsapp_groups_group_id ON whatsapp_groups(group_id);
CREATE INDEX IF NOT EXISTS idx_whatsapp_groups_is_active ON whatsapp_groups(is_active);

-- Insert group ID yang sudah ada (dari config/bot.js)
INSERT INTO whatsapp_groups (group_id, group_name, is_active, description)
VALUES ('120363422758876589@g.us', 'Default Group', TRUE, 'Group ID dari konfigurasi awal')
ON CONFLICT (group_id) DO NOTHING;

-- Update updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_whatsapp_groups_updated_at 
    BEFORE UPDATE ON whatsapp_groups 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

