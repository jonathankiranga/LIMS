SELECT s.id AS sample_id, s.document_no, s.customer_name, stp.test_parameter, stp.value, stp.units, stp.mrl, stp.above_mrl, stp.test_date
FROM samples s
LEFT JOIN sample_test_parameters stp ON s.id = stp.sample_id;

SELECT s.document_no, stp.test_parameter, bl.current_hash, bl.previous_hash, bl.digital_signature
FROM blockchain_ledger bl
INNER JOIN sample_test_parameters stp ON bl.sample_test_id = stp.id
INNER JOIN samples s ON stp.sample_id = s.id
WHERE s.id = 1;


SELECT 
    tp.test_parameter, tp.value, tp.units, tp.test_date, tp.method, tp.analyst, tp.equipment_id, tp.remarks,
    bl.current_hash, bl.previous_hash
FROM test_parameters tp
JOIN blockchain_ledger bl ON tp.id = bl.sample_test_id
WHERE tp.sample_id = 1;
