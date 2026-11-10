-- Convert legacy RFQ unit codes to inventory configuration roles.
-- Unambiguous old values are migrated automatically. Rows where the same code
-- represented both stockmaster.units and stockmaster.subunits are left unchanged
-- because their original quotation basis cannot be reconstructed safely.

UPDATE rfq_lines rl
INNER JOIN stockmaster sm
    ON sm.itemcode COLLATE utf8mb4_unicode_ci = rl.itemcode COLLATE utf8mb4_unicode_ci
SET rl.requested_pack = CASE
    WHEN rl.requested_pack = sm.units
         AND (sm.subunits IS NULL OR sm.subunits = '' OR rl.requested_pack <> sm.subunits)
        THEN 'units'
    WHEN rl.requested_pack = sm.subunits
         AND (sm.units IS NULL OR sm.units = '' OR rl.requested_pack <> sm.units)
        THEN 'subunits'
    ELSE rl.requested_pack
END
WHERE rl.requested_pack NOT IN ('units','subunits');

-- Review any remaining legacy values. These are ambiguous or no longer mapped.
SELECT rl.id, rl.rfq_id, rl.itemcode, rl.requested_pack,
       sm.units AS current_full_unit, sm.subunits AS current_loose_unit
FROM rfq_lines rl
LEFT JOIN stockmaster sm
    ON sm.itemcode COLLATE utf8mb4_unicode_ci = rl.itemcode COLLATE utf8mb4_unicode_ci
WHERE rl.requested_pack NOT IN ('units','subunits')
   OR rl.requested_pack IS NULL;
