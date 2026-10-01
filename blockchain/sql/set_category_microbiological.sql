-- ============================================================
-- Set Category = 'microbiological' for organism/pathogen/indicator
-- parameters that were incorrectly defaulted to 'chemical' when
-- the column was added via ALTER TABLE with DEFAULT 'chemical'.
-- ============================================================
-- Run this once AFTER confirming the list is correct.

-- Step 1: UPDATE baseparameters
UPDATE baseparameters SET Category = 'microbiological' WHERE ParameterID IN (
   12, 13, 15, 16, 17, 18, 19, 39, 40, 41,
   42, 43, 89, 90, 91, 92, 93, 101, 104, 106,
   107, 109, 112, 113, 114, 119, 120, 155, 157, 179,
   180, 183, 196, 197, 198, 199, 200, 201, 202, 203,
   204, 205, 285, 286, 287, 288, 289, 292, 324, 326,
   337, 340, 342, 384, 390, 392, 395, 404, 417, 418,
   419, 420, 421, 427, 467, 472, 473, 474, 488, 490
);

-- Step 2: Sync Category down to testparameters rows linked via BaseID
UPDATE testparameters tp
  JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
  SET tp.Category = bp.Category
  WHERE bp.Category = 'microbiological';

-- ============================================================
-- OPTIONAL: Mycotoxins (often placed under microbiology in LIMS)
-- Uncomment if your lab categorises these as microbiology:
-- ============================================================
-- UPDATE baseparameters SET Category = 'microbiological' WHERE ParameterID IN (
--   2, 138, 139, 293, 405, 444, 445, 466, 489
-- );
-- UPDATE testparameters tp
--   JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
--   SET tp.Category = bp.Category
--   WHERE bp.ParameterID IN (2, 138, 139, 293, 405, 444, 445, 466, 489);

-- ============================================================
-- To verify after running:
--   SELECT Category, COUNT(*) FROM baseparameters GROUP BY Category;
--   SELECT Category, COUNT(*) FROM testparameters GROUP BY Category;
-- ============================================================
