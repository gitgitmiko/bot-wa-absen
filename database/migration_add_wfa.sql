-- Migration: Add WFA type and command
-- 1) Update check constraint on absensi.type to include 'WFA'
-- 2) Insert /wfa command into commands table

ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_type_check;
ALTER TABLE absensi ADD CONSTRAINT absensi_type_check CHECK (type IN ('WFO','WFH','WFA'));

-- Add /wfa command to commands table (if commands table exists)
INSERT INTO commands (command, description, is_active) VALUES
    ('/wfa', 'Work From Anywhere - Absensi dengan lokasi teks (format: /wfa [lokasi])', TRUE)
ON CONFLICT (command) DO NOTHING;
