<?php
include('includes/session.inc');
$Title = _('Leave Notifications');
include('includes/header.inc');
require_once('Mailer/CustomMailerclass.php');

if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    DB_query("DELETE FROM prlleavenotifications WHERE notification_id = $id", $db);
    $_SESSION['msg'] = _('Notification deleted successfully');
    header('Location: prlleavenotifications.php');
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'resend' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM prlleavenotifications WHERE notification_id = $id";
    $result = DB_query($sql, $db);
    $notif = DB_fetch_array($result);
    
    if($notif) {
        $mymailer = new MyMailer();
        if($mymailer->sendmail($notif['recipient_email'], $notif['subject'], $notif['message'])) {
            DB_query("UPDATE prlleavenotifications SET status = 'SENT', sent_at = NOW() WHERE notification_id = $id", $db);
            $_SESSION['msg'] = _('Email sent successfully');
        } else {
            DB_query("UPDATE prlleavenotifications SET status = 'FAILED', error_message = 'Mail delivery failed' WHERE notification_id = $id", $db);
            $_SESSION['error'] = _('Failed to send email');
        }
    }
    header('Location: prlleavenotifications.php');
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where = "1=1";
if($status_filter) {
    $where .= " AND status = '$status_filter'";
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bell"></i> <?php echo _('Leave Notifications'); ?></h3>
                </div>
                <div class="panel-body">
                    <?php if(isset($_SESSION['msg'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    
                    <div class="row" style="margin-bottom:15px;">
                        <div class="col-md-6">
                            <form method="get" class="form-inline">
                                <label><?php echo _('Filter by Status:'); ?></label>
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?php echo _('All'); ?></option>
                                    <option value="QUEUED" <?php if($status_filter=='QUEUED') echo 'selected'; ?>><?php echo _('Queued'); ?></option>
                                    <option value="SENT" <?php if($status_filter=='SENT') echo 'selected'; ?>><?php echo _('Sent'); ?></option>
                                    <option value="FAILED" <?php if($status_filter=='FAILED') echo 'selected'; ?>><?php echo _('Failed'); ?></option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="prlleavenotifications.php?process=1" class="btn btn-success">
                                <i class="fa fa-play"></i> <?php echo _('Process Queue'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php
                    if(isset($_GET['process'])) {
                        echo '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Processing notifications...</div>';
                        flush();
                        
                        $sql = "SELECT * FROM prlleavenotifications WHERE status = 'QUEUED' ORDER BY created_at LIMIT 100";
                        $result = DB_query($sql, $db);
                        $count = 0;
                        
                        $mymailer = new MyMailer();
                        while($notif = DB_fetch_array($result)) {
                            if(!empty($notif['recipient_email']) && $mymailer->sendmail($notif['recipient_email'], $notif['subject'], $notif['message'])) {
                                DB_query("UPDATE prlleavenotifications SET status = 'SENT', sent_at = NOW() WHERE notification_id = " . $notif['notification_id'], $db);
                                $count++;
                            } else {
                                DB_query("UPDATE prlleavenotifications SET status = 'FAILED', error_message = 'Mail delivery failed' WHERE notification_id = " . $notif['notification_id'], $db);
                            }
                        }
                        echo '<div class="alert alert-success">Processed ' . $count . ' notifications.</div>';
                    }
                    ?>
                    
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><?php echo _('ID'); ?></th>
                                <th><?php echo _('Type'); ?></th>
                                <th><?php echo _('Recipient'); ?></th>
                                <th><?php echo _('Subject'); ?></th>
                                <th><?php echo _('Status'); ?></th>
                                <th><?php echo _('Created'); ?></th>
                                <th><?php echo _('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM prlleavenotifications WHERE $where ORDER BY created_at DESC LIMIT 100";
                            $result = DB_query($sql, $db);
                            
                            if(DB_num_rows($result) == 0) {
                                echo '<tr><td colspan="7" class="text-center">' . _('No notifications found') . '</td></tr>';
                            }
                            
                            while($row = DB_fetch_array($result)) {
                                $status_class = '';
                                $status_icon = '';
                                switch($row['status']) {
                                    case 'QUEUED': $status_class = 'warning'; $status_icon = 'clock-o'; break;
                                    case 'SENT': $status_class = 'success'; $status_icon = 'check'; break;
                                    case 'FAILED': $status_class = 'danger'; $status_icon = 'times'; break;
                                    case 'READ': $status_class = 'info'; $status_icon = 'eye'; break;
                                }
                                
                                echo '<tr>';
                                echo '<td>' . $row['notification_id'] . '</td>';
                                echo '<td><span class="label label-default">' . _($row['notification_type']) . '</span></td>';
                                echo '<td>' . $row['recipient_email'] . '</td>';
                                echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
                                echo '<td><span class="label label-' . $status_class . '"><i class="fa fa-' . $status_icon . '"></i> ' . _($row['status']) . '</span></td>';
                                echo '<td>' . ConvertSQLDate($row['created_at']) . '</td>';
                                echo '<td>';
                                if($row['status'] == 'QUEUED') {
                                    echo '<a href="?action=resend&id=' . $row['notification_id'] . '" class="btn btn-xs btn-success" title="Send Now"><i class="fa fa-paper-plane"></i></a> ';
                                }
                                echo '<a href="?action=view&id=' . $row['notification_id'] . '" class="btn btn-xs btn-info" title="View"><i class="fa fa-eye"></i></a> ';
                                echo '<a href="?action=delete&id=' . $row['notification_id'] . '" class="btn btn-xs btn-danger" onclick="return confirm(\'Delete this notification?\')" title="Delete"><i class="fa fa-trash"></i></a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM prlleavenotifications WHERE notification_id = $id";
    $result = DB_query($sql, $db);
    $notif = DB_fetch_array($result);
    
    if($notif) {
        DB_query("UPDATE prlleavenotifications SET status = 'READ', read_at = NOW() WHERE notification_id = $id", $db);
        ?>
        <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><?php echo _('Notification Details'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <tr><th><?php echo _('Notification ID'); ?>:</th><td><?php echo $notif['notification_id']; ?></td></tr>
                            <tr><th><?php echo _('Type'); ?>:</th><td><?php echo $notif['notification_type']; ?></td></tr>
                            <tr><th><?php echo _('Recipient'); ?>:</th><td><?php echo $notif['recipient_email']; ?></td></tr>
                            <tr><th><?php echo _('Subject'); ?>:</th><td><?php echo htmlspecialchars($notif['subject']); ?></td></tr>
                            <tr><th><?php echo _('Status'); ?>:</th><td><?php echo $notif['status']; ?></td></tr>
                            <tr><th><?php echo _('Created'); ?>:</th><td><?php echo $notif['created_at']; ?></td></tr>
                            <tr><th><?php echo _('Sent'); ?>:</th><td><?php echo $notif['sent_at'] ?: '-'; ?></td></tr>
                            <?php if($notif['error_message']): ?>
                            <tr><th><?php echo _('Error'); ?>:</th><td class="text-danger"><?php echo htmlspecialchars($notif['error_message']); ?></td></tr>
                            <?php endif; ?>
                        </table>
                        <hr>
                        <h4><?php echo _('Message'); ?>:</h4>
                        <div class="well">
                            <?php echo $notif['message']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>$('#viewModal').modal('show');</script>
        <?php
    }
}
?>

<?php include('includes/footer.inc'); ?>
