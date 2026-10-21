-- ImaraLIMS reviewtestresults.php status repair
-- Date: 2026-10-21
-- Purpose:
--   1. Snapshot potentially mis-transitioned StatusID=4 rows that have no
--      approver recorded but do have a reviewer recorded.
--   2. Do NOT automatically move records back to StatusID=3. StatusID=4 may
--      represent genuinely approved results. Inspect the candidate rows first.
--
-- Run against lims_encrpted after taking a database backup.
-- Step A is safe to rerun: CREATE TABLE IF NOT EXISTS preserves the first snapshot.

CREATE TABLE IF NOT EXISTS reviewtestresults_status_repair_20261021 AS
SELECT
    tr.resultsID,
    tr.TestID,
    tr.HeaderID,
    tr.SampleID,
    tr.StandardID,
    tr.ParameterID,
    tr.StatusID AS original_StatusID,
    tr.approvedby AS original_approvedby,
    tr.reviewedby AS original_reviewedby,
    CURRENT_TIMESTAMP AS captured_at,
    CAST(0 AS UNSIGNED) AS repair_to_status3
FROM test_results tr
WHERE tr.StatusID = 4
  AND (tr.approvedby IS NULL OR CAST(tr.approvedby AS CHAR) = '')
  AND tr.reviewedby IS NOT NULL;

-- Step B: inspect candidates before approving any repair.
SELECT *
FROM reviewtestresults_status_repair_20261021
ORDER BY HeaderID, SampleID, resultsID;

-- Step C: ONLY after confirming a candidate was incorrectly advanced to
-- StatusID=4, set repair_to_status3=1 for its exact resultsID(s):
--
-- UPDATE reviewtestresults_status_repair_20261021
-- SET repair_to_status3 = 1
-- WHERE resultsID IN (/* verified resultsID values */);
--
-- Then execute the guarded correction below. It changes only explicitly
-- selected candidates that still match their captured status and approver
-- state. A row that has changed since the snapshot will not be modified.

START TRANSACTION;

UPDATE test_results tr
JOIN reviewtestresults_status_repair_20261021 c
  ON c.resultsID = tr.resultsID
SET tr.StatusID = 3
WHERE c.repair_to_status3 = 1
  AND tr.StatusID = c.original_StatusID
  AND tr.StatusID = 4
  AND (tr.approvedby IS NULL OR CAST(tr.approvedby AS CHAR) = '')
  AND tr.reviewedby IS NOT NULL;

SELECT ROW_COUNT() AS corrected_result_rows;

COMMIT;

-- Verification: pending review rows should now be visible to
-- reviewtestresults.php, which requests StatusID=3.
SELECT tr.HeaderID, tr.SampleID, COUNT(*) AS pending_review_results
FROM test_results tr
WHERE tr.StatusID = 3
GROUP BY tr.HeaderID, tr.SampleID
ORDER BY tr.HeaderID, tr.SampleID;
