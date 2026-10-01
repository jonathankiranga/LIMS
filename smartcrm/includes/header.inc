<?php
if (!isset($RootPath)){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $Title . '</title>
<meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1"> 
<link rel="shortcut icon" href="'. $RootPath.'/comerp.ico" /><link rel="icon" href="' . $RootPath.'/comerp.ico" /> 
<link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="'.$RootPath.'/css/homepage.css" rel="stylesheet" type="text/css"/>
<link href="'.$RootPath.'/css/smart-dialog.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src = "'.$RootPath.'/javascripts/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src = "'.$RootPath.'/javascripts/bootstrap.min.js"></script>
<script type="text/javascript" src = "'.$RootPath.'/javascripts/SmartDialog.js"></script>
<script type="text/javascript" src = "'.$RootPath.'/javascripts/MiscFunctions.js"></script> 
';

$inIframeMode = isset($inIframe) && $inIframe;
?>

<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
.modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
.accept { color:#21ba45; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.accept:hover, .accept:focus { color: black; text-decoration: none; cursor: pointer; }
.image-item { display: inline-block; margin: 10px; cursor: pointer; }
.image-item.selected { border: 5px solid blue; }

.toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
.toast { min-width: 280px; padding: 15px 20px; margin-bottom: 10px; border-radius: 6px; color: white; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s ease-out; display: flex; justify-content: space-between; align-items: center; }
.toast.success { background: #27ae60; }
.toast.error { background: #e74c3c; }
.toast.warning { background: #f39c12; }
.toast.info { background: #3498db; }
.toast-close { background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 15px; }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
.toast.hiding { animation: slideOut 0.3s ease-out forwards; }
</style>

<?php
$inIframeMode = isset($inIframe) && $inIframe;

if (!$inIframeMode) {
    echo '<div style="padding:0;float:left;">';
}
?>

<?php if (!$inIframeMode): ?>
<div class="toast-container" id="toastContainer"></div>
<?php endif; ?>

<script>
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    if (!container) return;
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<span>' + message + '</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);
    setTimeout(function() {
        toast.classList.add('hiding');
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

function toastSuccess(msg) { showToast(msg, 'success'); }
function toastError(msg) { showToast(msg, 'error'); }
function toastWarning(msg) { showToast(msg, 'warning'); }
function toastInfo(msg) { showToast(msg, 'info'); }
</script>
