-- Add Foreign Key constraint for baseparameters -> testparameters
-- baseparameters is "one" and testparameters is "many"

-- First check existing foreign keys
-- Then add the constraint if not exists

ALTER TABLE testparameters 
ADD CONSTRAINT testparameters_ibfk_2 
FOREIGN KEY (BaseID) REFERENCES baseparameters(ParameterID) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Also check ParameterMatrix should reference baseparameters if it's meant to be a child
-- Check if there's a similar structure needed for ParameterMatrix
-- Let's also check/fix ParameterMatrix if it exists

-- Add this to parameterIntegrityTest for better checks:
-- Verify foreign key integrity is enforced from the database side
SELECT 
    'Checking Foreign Key Constraint' as check_type,
    COUNT(*) as issues
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.TABLE_CONSTRAINTS c 
    ON k.CONSTRAINT_NAME = c.CONSTRAINT_NAME 
    AND k.TABLE_SCHEMA = c.TABLE_SCHEMA
WHERE c.TABLE_NAME = 'testparameters' 
    AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND k.COLUMN_NAME = 'BaseID';