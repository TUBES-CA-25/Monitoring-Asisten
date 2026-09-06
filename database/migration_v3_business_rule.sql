
-- ICLABS V3 additive migration
-- Apply manually after backup.
ALTER TABLE users ADD COLUMN IF NOT EXISTS status_account VARCHAR(20) DEFAULT 'ACTIVE';
