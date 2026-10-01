-- Add labid column to stockmaster for mapping with blockchain baseparameters
ALTER TABLE stockmaster ADD COLUMN labid VARCHAR(50) DEFAULT NULL AFTER isstock;

CREATE INDEX idx_stockmaster_labid ON stockmaster(labid);

-- Add partperunit column if not exists (maps from baseparameters.ParameterName)
-- ALTER TABLE stockmaster ADD COLUMN partperunit DECIMAL(10,2) DEFAULT 1 AFTER averagestock;