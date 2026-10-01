<?php
/**
 * Parameter Audit Logger
 * Call this function in save/edit scripts to log changes
 */

function logParameterChange($conn, $tableName, $recordId, $action, $fieldChanged = null, $oldValue = null, $newValue = null) {
    $scriptName = basename($_SERVER['SCRIPT_FILENAME'] ?? 'unknown');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
    
    $sql = "INSERT INTO parameter_audit_log 
            (table_name, record_id, action, field_changed, old_value, new_value, script_name, ip_address, user_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sisissssi", 
        $tableName, 
        $recordId, 
        $action, 
        $fieldChanged, 
        $oldValue, 
        $newValue, 
        $scriptName, 
        $ipAddress, 
        $userId
    );
    
    return $stmt->execute();
}

// Usage examples:
// logParameterChange($conn, 'testparameters', 45, 'INSERT', null, null, 'pH 7.0');
// logParameterChange($conn, 'testparameters', 45, 'UPDATE', 'ParameterName', 'pH 7.0', 'pH 6.5');
// logParameterChange($conn, 'testparameters', 45, 'DELETE', null, 'pH 7.0', null);
?>