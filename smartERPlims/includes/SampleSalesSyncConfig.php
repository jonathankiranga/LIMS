<?php

/*
 * Sample registration -> Sales Order sync config.
 * Edit this file to control mapping from blockchain LIMS data into smartERP sales docs.
 */
return [
    'source' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'lims_encrpted',
        'username' => 'root',
        'password' => 'mysqlpassword',
        'charset' => 'utf8mb4',
        // Optional: pull credentials from blockchain app config.
        'blockchain_config_path' => 'E:\\limsISO\\blockchain\\include\\config.php'
    ],
    'sync' => [
        'batch_size' => 100,
        'sales_document_type' => 1,
        'sales_systype_id' => 1,
        'erp_user_id' => 'admin',
        'default_currency' => 'KES',
        'default_salesperson' => '',
        'default_locationcode' => '',
        'default_postinggroup' => 'DEFAULT',
        'default_vat_inclusive' => 0,
        'default_uom' => 'PCS',
        'default_unit_price' => 0.0,
        // Optional fallback stock item code to use when a parameter has no explicit mapping.
        'default_itemcode' => '',
        // Map blockchain test_results.ParameterID -> smartERP stockmaster.itemcode
        'parameter_item_map' => [
            // 101 => 'LAB-TEST-PH',
            // 102 => 'LAB-TEST-MOIST',
        ],
        // Optional per-parameter price override.
        'parameter_price_map' => [
            // 101 => 1500.00,
            // 102 => 2000.00,
        ],
    ],
];

