<?php
include('includes/session.inc');
$Title = _('Upcoming Activities');
include('includes/header.inc');

$wwwusers = array();
$result = DB_query("SELECT userid,realname FROM www_users WHERE `blocked`=0", $db);
while ($row = DB_fetch_array($result)) {
    $user = trim($row['userid']);
    $wwwusers[$user] = $row['realname'];
}

$NewContacts = array();
$result = DB_query("SELECT `Company`,`pkey` FROM `NewContacts`", $db);
while ($row = DB_fetch_array($result)) {
    $pkey = (int)$row['pkey'];
    $NewContacts[$pkey] = $row['Company'];
}

function getFollowupActivities($status) {
    global $db;

    $activities = array();
    $SQL = sprintf(
        "SELECT `pkey`,`ActivityOwner`,`Activityname`,`fromdue`,`todue`,`Contact`,`Status`,
        `valueofbusiness`,`taskdetails`,`createdby`,`createdon`,`lastactivity`,
        DATEDIFF(`fromdue`,NOW()) as Future
        FROM `NewActivity`
        WHERE `Status`=%f
        ORDER BY `pkey` DESC",
        $status
    );

    $result = DB_query($SQL, $db);
    while ($myrow = DB_fetch_array($result)) {
        $activities[] = $myrow;
    }

    return $activities;
}

function followupDueMeta($futureDays) {
    if ($futureDays < 0) {
        return array('class' => 'kanban-overdue', 'label' => _('Overdue'));
    }
    if ($futureDays == 0) {
        return array('class' => 'kanban-today', 'label' => _('Today'));
    }
    return array('class' => 'kanban-upcoming', 'label' => _('Upcoming'));
}

function renderFollowupCard($value) {
    global $NewContacts, $wwwusers;

    $contactKey = (int)$value['Contact'];
    $contact = isset($NewContacts[$contactKey]) ? $NewContacts[$contactKey] : '';
    $salesKey = trim($value['ActivityOwner']);
    $salesperson = isset($wwwusers[$salesKey]) ? $wwwusers[$salesKey] : $salesKey;
    $businessValue = $value['valueofbusiness'] === '' ? _('Not yet Known') : number_format((float)$value['valueofbusiness']);
    $meta = followupDueMeta((int)$value['Future']);

    echo '<article class="kanban-card ' . $meta['class'] . '">';
    echo '<header class="kanban-card-header">';
    echo '<h4>' . htmlspecialchars($value['Activityname'], ENT_QUOTES, 'UTF-8') . '</h4>';
    echo '<span class="kanban-badge">' . htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') . '</span>';
    echo '</header>';
    echo '<p class="kanban-card-line"><strong>' . _('Start') . ':</strong> ' . htmlspecialchars(ConvertSQLDate($value['fromdue']), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="kanban-card-line"><strong>' . _('End') . ':</strong> ' . htmlspecialchars(ConvertSQLDate($value['todue']), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="kanban-card-line"><strong>' . _('Contact') . ':</strong> ' . htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="kanban-card-line"><strong>' . _('Business') . ':</strong> ' . htmlspecialchars($businessValue, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="kanban-card-line"><strong>' . _('Sales Person') . ':</strong> ' . htmlspecialchars($salesperson, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p class="kanban-card-details">' . htmlspecialchars($value['taskdetails'], ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</article>';
}
?>

<style>
.kanban-board {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding: 8px 2px 12px;
}

.kanban-column {
    min-width: 290px;
    max-width: 330px;
    background: #f8fafc;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    padding: 10px;
}

.kanban-column h3 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 700;
}

.kanban-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.kanban-card {
    background: #ffffff;
    border: 1px solid #dbe2ea;
    border-left: 4px solid #64748b;
    border-radius: 8px;
    padding: 10px;
}

.kanban-overdue { border-left-color: #dc2626; }
.kanban-today { border-left-color: #ca8a04; }
.kanban-upcoming { border-left-color: #16a34a; }

.kanban-card-header {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}

.kanban-card-header h4 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
}

.kanban-badge {
    font-size: 11px;
    background: #e2e8f0;
    color: #0f172a;
    border-radius: 999px;
    padding: 2px 8px;
    white-space: nowrap;
}

.kanban-card-line {
    margin: 0 0 6px;
    font-size: 12px;
}

.kanban-card-details {
    margin: 0;
    font-size: 12px;
    color: #334155;
}

.kanban-empty {
    margin: 0;
    font-size: 12px;
    color: #64748b;
}
</style>

<div><p class="good"><?php echo _('Upcoming Activities'); ?></p></div>

<section class="kanban-board">
    <?php
    $columns = array(
        array('status' => 1, 'label' => $CRMArray[1]),
        array('status' => 2, 'label' => $CRMArray[2]),
        array('status' => 3, 'label' => $CRMArray[3]),
        array('status' => 4, 'label' => $CRMArray[4])
    );

    foreach ($columns as $column) {
        $items = getFollowupActivities($column['status']);
        echo '<div class="kanban-column">';
        echo '<h3>' . htmlspecialchars($column['label'], ENT_QUOTES, 'UTF-8') . ' (' . count($items) . ')</h3>';
        echo '<div class="kanban-cards">';
        if (empty($items)) {
            echo '<p class="kanban-empty">' . _('No Data') . '</p>';
        } else {
            foreach ($items as $item) {
                renderFollowupCard($item);
            }
        }
        echo '</div>';
        echo '</div>';
    }
    ?>
</section>

<?php include('includes/footer.inc'); ?>
