<?php
/**
 * Expand recurring activities into Tasks when next_run is due.
 * Run via CLI: php expand_recurring.php
 */
if (php_sapi_name() !== 'cli') {
    echo "Run this script from the command line.\n";
    exit(1);
}

$PathPrefix = '../';
require $PathPrefix . 'includes/session.inc';
require $PathPrefix . 'includes/SQL_CommonFunctions.inc';

$now = new DateTime('now');

$SQL = "
SELECT ra.*, t.Taskname, t.Status, t.Priority, t.TaskOwner, t.taskdetails
FROM RecurringActivities ra
JOIN Tasks t ON t.pkey = ra.task_id
WHERE ra.next_run <= NOW()
";
$Result = DB_query($SQL, $db, '', '', false, false);
if ($Result === false) {
    fwrite(STDERR, "DB error reading RecurringActivities\n");
    exit(1);
}

$spawned = 0;
$removed = 0;

while ($row = DB_fetch_array($Result)) {
    $interval = null;
    try {
        switch ($row['unit']) {
            case 'hour':
                $interval = new DateInterval('PT' . (int)$row['every_n'] . 'H');
                break;
            case 'week':
                $interval = new DateInterval('P' . (int)$row['every_n'] . 'W');
                break;
            case 'month':
                $interval = new DateInterval('P' . (int)$row['every_n'] . 'M');
                break;
            case 'day':
            default:
                $interval = new DateInterval('P' . (int)$row['every_n'] . 'D');
                break;
        }
        $nextRunDt = new DateTime($row['next_run']);
        $dueDate = $nextRunDt->format('Y-m-d');

        // Insert new task instance
        $SQLInsert = sprintf(
            "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated) VALUES ('%s','%d','%d','%s','%s','%s',NOW())",
            DB_escape_string($row['Taskname']),
            (int)$row['Status'],
            (int)$row['Priority'],
            DB_escape_string($row['TaskOwner']),
            DB_escape_string($dueDate),
            DB_escape_string($row['taskdetails'])
        );
        DB_query($SQLInsert, $db, '', '', false, false);
        $spawned++;

        // Compute next run
        $nextRunDt->add($interval);
        $nextRunStr = $nextRunDt->format('Y-m-d H:i:s');

        $deleteRec = false;
        if (!empty($row['until_date']) && $nextRunDt->format('Y-m-d') > $row['until_date']) {
            $deleteRec = true;
        }
        $maxCount = is_null($row['max_count']) ? null : (int)$row['max_count'];
        if ($maxCount !== null) {
            $maxCount = $maxCount - 1;
            if ($maxCount <= 0) {
                $deleteRec = true;
            }
        }

        if ($deleteRec) {
            DB_query("DELETE FROM RecurringActivities WHERE id=" . (int)$row['id'], $db);
            $removed++;
        } else {
            $updateSQL = sprintf(
                "UPDATE RecurringActivities SET next_run='%s'%s WHERE id=%d",
                DB_escape_string($nextRunStr),
                $maxCount !== null ? ", max_count=" . (int)$maxCount : '',
                (int)$row['id']
            );
            DB_query($updateSQL, $db);
        }
    } catch (Exception $e) {
        fwrite(STDERR, "Error processing recurring id {$row['id']}: {$e->getMessage()}\n");
    }
}

echo "Spawned: $spawned, Removed: $removed\n";
