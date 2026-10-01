<?php
if (function_exists('opcache_invalidate')) opcache_invalidate(__FILE__, true);
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');
$Title = _('Price List');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (isset($_GET['export_prices'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="price_list.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['stockcode','description','price','tat']);
    $rs = DB_query("SELECT s.itemcode, s.descrip,
                           (SELECT price FROM PriceList WHERE customerCode='' AND stockcode=s.itemcode ORDER BY quantity LIMIT 1) AS price,
                           (SELECT tat FROM PriceList WHERE customerCode='' AND stockcode=s.itemcode ORDER BY quantity LIMIT 1) AS tat
                    FROM stockmaster s
                    WHERE s.isstock_1=1
                    ORDER BY s.descrip", $db);
    while ($r = DB_fetch_row($rs)) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

include('includes/header.inc');

if (isset($_POST['import_prices']) && isset($_FILES['prices_file'])) {
    $fh = fopen($_FILES['prices_file']['tmp_name'], 'r');
    $headers = fgetcsv($fh);
    $required = ['stockcode','price','tat'];
    $miss = array_diff($required, $headers);
    if (!empty($miss)) {
        prnMsg(_('Missing columns: ') . implode(', ', $miss), 'error');
    } else {
        $idx_sc = array_search('stockcode', $headers);
        $idx_pr = array_search('price', $headers);
        $idx_tat = array_search('tat', $headers);
        $updated = 0; $inserted = 0;
        DB_Txn_Begin($db);
        while (($row = fgetcsv($fh)) !== false) {
            $stockcode = trim($row[$idx_sc] ?? '');
            $price     = (float)($row[$idx_pr] ?? 0);
            $tat       = (int)($row[$idx_tat] ?? 0);
            if ($stockcode === '') { continue; }
            $esc_sc = $db->real_escape_string($stockcode);
            $chk = DB_query("SELECT COUNT(*) FROM PriceList WHERE customerCode='' AND stockcode='$esc_sc'", $db);
            $cnt = DB_fetch_row($chk);
            if ($cnt[0] > 0) {
                DB_query("UPDATE PriceList SET price=$price, tat=$tat WHERE customerCode='' AND stockcode='$esc_sc'", $db);
                $updated++;
            } else {
                DB_query("INSERT INTO PriceList (customerCode,stockcode,price,tat) VALUES ('','$esc_sc',$price,$tat)", $db);
                $inserted++;
            }
        }
        fclose($fh);
        if (DB_error_no($db) == 0) {
            DB_Txn_Commit($db);
            prnMsg(sprintf(_('Price list updated: %d updated, %d inserted'), $updated, $inserted), 'info');
        } else {
            DB_Txn_Rollback($db);
            prnMsg(_('Database error during import'), 'error');
        }
    }
}

$pge = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$ajaxUrl = $RootPath . '/Ajax/price_list_ajax.php';

$plistCatOpts = '<option value="">' . _('All Sample Standards') . '</option>';
$plTsOpts = '';
$plTsRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid LIKE 'TS%' ORDER BY categorydescription", $db);
while ($plTs = DB_fetch_array($plTsRes)) {
    $plTsOpts .= sprintf('<option value="%s">%s</option>',
        htmlspecialchars($plTs['categoryid'], ENT_QUOTES),
        htmlspecialchars($plTs['categorydescription'] ?? $plTs['categoryid'], ENT_QUOTES));
}
if ($plTsOpts !== '') {
    $plistCatOpts .= '<optgroup label="' . _('Sample Standards') . '">' . $plTsOpts . '</optgroup>';
}
$plCatOpts = '';
$plCatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid NOT LIKE 'TS%' ORDER BY categorydescription", $db);
while ($plCat = DB_fetch_array($plCatRes)) {
    $plCatOpts .= sprintf('<option value="%s">%s</option>',
        htmlspecialchars($plCat['categoryid'], ENT_QUOTES),
        htmlspecialchars($plCat['categorydescription'] ?? $plCat['categoryid'], ENT_QUOTES));
}
if ($plCatOpts !== '') {
    $plistCatOpts .= '<optgroup label="' . _('Categories') . '">' . $plCatOpts . '</optgroup>';
}

// Unit suggestions: the unit table only holds a few rows while stockmaster
// legitimately uses values such as 'PCS' and 'mg/kg', so offer both.
$plUnits = array();
$plUnitRes = DB_query("SELECT DISTINCT units FROM stockmaster WHERE units IS NOT NULL AND units <> '' AND units <> '0'", $db);
while ($plUnit = DB_fetch_array($plUnitRes)) {
    $plUnits[trim($plUnit['units'])] = trim($plUnit['units']);
}
$plUnitRes2 = DB_query("SELECT code FROM unit", $db);
while ($plUnit = DB_fetch_array($plUnitRes2)) {
    $plUnitCode = trim($plUnit['code']);
    if ($plUnitCode !== '') {
        $plUnits[$plUnitCode] = $plUnitCode;
    }
}
ksort($plUnits);
$plUnitList = '';
foreach ($plUnits as $plUnitCode) {
    $plUnitList .= '<option value="' . htmlspecialchars($plUnitCode, ENT_QUOTES) . '"></option>';
}
?>
<style>
.pl-toolbar { margin-bottom: 10px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.pl-btn { padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; background: #2563eb; color: #fff; }
.pl-btn:hover { background: #1d4ed8; }
.pl-btn:disabled { background: #cbd5e1; color: #64748b; cursor: not-allowed; }
.pl-select { padding: 5px 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; }

/* status bar: slim, non-alert for info; alerts are reserved for problems */
.pl-status { display: none; margin: 0 0 10px; padding: 6px 12px; font-size: 13px; border-radius: 4px; border-left: 4px solid #16a34a; background: #f0fdf4; color: #166534; }
.pl-status.pl-info { display: block; }
.pl-status.pl-busy { display: block; border-left-color: #ca8a04; background: #fefce8; color: #854d0e; }
.pl-status.pl-error { display: block; border-left-color: #dc2626; background: #fef2f2; color: #991b1b; }

.pl-wrap { border: 1px solid #d1d5db; border-radius: 6px; overflow: auto; max-height: 70vh; background: #fff; }
table.pl-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.pl-table th, table.pl-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; text-align: left; }
table.pl-table thead th { position: sticky; top: 0; z-index: 2; background: #f8fafc; border-bottom: 2px solid #cbd5e1; font-size: 12px; color: #475569; }
table.pl-table tr.pl-grp th { background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 13px; padding: 6px 8px; border-bottom: 1px solid #dbeafe; cursor: pointer; }
table.pl-table tr.pl-grp:hover th { background: #dbeafe; }
.pl-caret { display: inline-block; width: 12px; font-size: 11px; }
.pl-grp-code { color: #64748b; font-weight: 600; margin-left: 6px; }
.pl-grp-count { color: #64748b; font-weight: 400; margin-left: 6px; font-size: 12px; }
tr.pl-item.hidden { display: none; }
tr.pl-item:hover td { background: #f8fafc; }
tr.pl-item.pl-bundle td { background: #f8fafc; font-weight: 600; }
tr.pl-item.pl-bundle:hover td { background: #f1f5f9; }
tr.pl-item.pl-dirty td { background: #fffbeb; }
.pl-code { white-space: nowrap; color: #475569; font-size: 12px; }
.pl-desc { max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pl-price, .pl-tat { width: 88px; padding: 3px 6px; border: 1px solid #e5e7eb; border-radius: 3px; font-size: 12px; text-align: right; }
.pl-unit { width: 84px; padding: 3px 6px; border: 1px solid #e5e7eb; border-radius: 3px; font-size: 12px; }
.pl-price:focus, .pl-tat:focus, .pl-unit:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,0.15); }
.pl-qty { text-align: center; color: #64748b; font-size: 12px; white-space: nowrap; }
.pl-act { white-space: nowrap; text-align: right; }
.pl-act button { padding: 2px 7px; border: none; border-radius: 3px; cursor: pointer; font-size: 11px; color: #fff; }
.pl-save { background: #16a34a; }
.pl-save:hover { background: #15803d; }
.pl-save.pl-add-btn { background: #2563eb; }
.pl-calc { background: #ca8a04; }
.pl-calc:hover { background: #a16207; }
.pl-del { background: #dc2626; }
.pl-del:hover { background: #b91c1c; }
.pl-pager { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 10px; font-size: 13px; color: #475569; }
.pl-pager .pl-btn { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.pl-pager .pl-btn:hover:not(:disabled) { background: #e2e8f0; }
.pl-empty { padding: 20px; text-align: center; color: #64748b; font-size: 13px; }
#addModal, #calcModal { display: none; }
.modal { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 1000; display: none; }
.modal-content { border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.25); overflow: hidden; padding: 0; border: none; }
</style>

<div class="centre">
  <div class="pl-toolbar">
    <a href="<?php echo $pge; ?>?export_prices=1" class="pl-btn"><?php echo _('Download CSV'); ?></a>
    <form method="post" enctype="multipart/form-data" action="<?php echo $pge; ?>" style="display:inline">
      <input type="file" name="prices_file" accept=".csv" required style="display:inline-block">
      <input type="submit" name="import_prices" value="<?php echo _('Import CSV'); ?>" class="pl-btn">
    </form>
    <select id="plFilter" class="pl-select"><?php echo $plistCatOpts; ?></select>
    <label class="pl-select" for="plPageSize"><?php echo _('Rows'); ?>
      <select id="plPageSize">
        <option value="10">10</option>
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </label>
    <span id="plScope" class="pl-grp-count"></span>
  </div>

  <div id="plStatus" class="pl-status"></div>

  <div class="pl-wrap">
    <table class="pl-table">
      <thead>
        <tr>
          <th style="width:110px"><?php echo _('Item Code'); ?></th>
          <th><?php echo _('Product Name'); ?></th>
          <th style="width:100px"><?php echo _('Unit Price'); ?></th>
          <th style="width:70px"><?php echo _('TAT'); ?></th>
          <th style="width:95px"><?php echo _('Unit'); ?></th>
          <th style="width:60px"><?php echo _('Qty'); ?></th>
          <th style="width:170px"></th>
        </tr>
      </thead>
      <tbody id="plBody"></tbody>
    </table>
    <datalist id="plUnitList"><?php echo $plUnitList; ?></datalist>
  </div>

  <div class="pl-pager">
    <button type="button" class="pl-btn" id="plFirst"><?php echo _('First'); ?></button>
    <button type="button" class="pl-btn" id="plPrev"><?php echo _('Prev'); ?></button>
    <span id="plPageInfo"></span>
    <button type="button" class="pl-btn" id="plNext"><?php echo _('Next'); ?></button>
    <button type="button" class="pl-btn" id="plLast"><?php echo _('Last'); ?></button>
  </div>
</div>

<!-- Calculator Modal (full-viewport iframe) -->
<div id="calcModal" class="modal">
  <div class="modal-content" style="position:relative;width:90%;height:90%;border-radius:8px">
    <span class="pl-del" onclick="plCloseCalc()" style="position:absolute;right:10px;top:6px;z-index:10;font-size:20px;line-height:1;padding:4px 10px">&times;</span>
    <iframe id="calcFrame" style="width:100%;height:100%;border:none;border-radius:4px"></iframe>
  </div>
</div>

<script>
var plAjax = <?php echo json_encode($ajaxUrl); ?>;
var plState = { page: 1, pageSize: 25, group: '' };
var plCollapsed = {};

function plStatus(msg, type) {
    var el = document.getElementById('plStatus');
    el.className = 'pl-status pl-' + (type || 'info');
    el.textContent = msg;
    if (type === 'ok') {
        window.clearTimeout(plStatus._t);
        plStatus._t = window.setTimeout(function() { el.className = 'pl-status'; }, 4000);
    }
}

function plEsc(v) {
    return String(v === null || v === undefined ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function plGet(params, onOk, method) {
    plStatus(<?php echo json_encode(_('Loading...')); ?>, 'busy');
    $.ajax({
        url: plAjax,
        method: method || 'GET',
        data: params,
        dataType: 'json',
        success: function(r) {
            if (r && r.status === 'ok') {
                plStatus('');
                onOk(r);
            } else {
                plStatus((r && r.message ? r.message : 'Request failed') + (r && r.code ? ' [' + r.code + ']' : ''), 'error');
            }
        },
        error: function(xhr) {
            var msg = 'Request failed (HTTP ' + xhr.status + ')';
            try { var j = JSON.parse(xhr.responseText); if (j.message) { msg = j.message; } } catch (e) {}
            plStatus(msg, 'error');
        }
    });
}

function plRender(r) {
    var html = '';
    var cur = null;
    var count = 0;
    for (var i = 0; i < r.rows.length; i++) {
        var d = r.rows[i];
        if (d.groupkey !== cur) {
            if (cur !== null) { count = 0; }
            cur = d.groupkey;
            var name = (d.groupname || '').replace(/^\s+|\s+$/g, '');
            var caret = plCollapsed[cur] ? '&#9656;' : '&#9662;';
            html += '<tr class="pl-grp" data-group="' + plEsc(cur) + '">'
                  + '<th colspan="7"><span class="pl-caret">' + caret + '</span>'
                  + plEsc(name) + '<span class="pl-grp-code">' + plEsc(cur) + '</span>'
                  + '<span class="pl-grp-count" data-cnt="' + plEsc(cur) + '"></span></th></tr>';
        }
        count++;
        var priced = d.id > 0;
        var pval = (d.price === null || d.price === undefined) ? '' : d.price;
        html += '<tr class="pl-item' + (d.is_bundle ? ' pl-bundle' : '') + (plCollapsed[cur] ? ' hidden' : '') + '"'
              + ' data-id="' + d.id + '" data-code="' + plEsc(d.stockcode) + '" data-group="' + plEsc(d.groupkey) + '">'
              + '<td class="pl-code">' + plEsc(d.stockcode) + '</td>'
              + '<td class="pl-desc" title="' + plEsc(d.descrip) + '">' + plEsc(d.descrip) + '</td>'
              + '<td><input type="number" class="pl-price" step="0.01" min="0" value="' + plEsc(pval) + '"></td>'
              + '<td><input type="number" class="pl-tat" min="0" step="1" value="' + plEsc(d.tat || 0) + '"></td>'
              + '<td><input type="text" class="pl-unit" list="plUnitList" value="' + plEsc(d.units_code) + '"></td>'
              + '<td class="pl-qty">' + plEsc(d.quantity) + '</td>'
              + '<td class="pl-act">'
              + '<button type="button" class="pl-save' + (priced ? '' : ' pl-add-btn') + '">' + (priced ? <?php echo json_encode(_('Save')); ?> : <?php echo json_encode(_('Add')); ?>) + '</button> '
              + '<button type="button" class="pl-calc">' + <?php echo json_encode(_('Calc')); ?> + '</button>'
              + (priced ? '<button type="button" class="pl-del">' + <?php echo json_encode(_('Delete')); ?> + '</button>' : '')
              + '</td></tr>';
    }
    if (r.rows.length === 0) {
        html += '<tr><td colspan="7" class="pl-empty">' + <?php echo json_encode(_('No prices found')); ?> + '</td></tr>';
    }
    document.getElementById('plBody').innerHTML = html;
    plCountGroups();

    var pages = r.pages || 1;
    document.getElementById('plPageInfo').textContent = <?php echo json_encode(_('Page')); ?> + ' ' + r.page + ' / ' + pages;
    document.getElementById('plFirst').disabled = (r.page <= 1);
    document.getElementById('plPrev').disabled = (r.page <= 1);
    document.getElementById('plNext').disabled = (r.page >= pages);
    document.getElementById('plLast').disabled = (r.page >= pages);
    var scope = document.getElementById('plScope');
    scope.textContent = r.scope === 'standard'
        ? <?php echo json_encode(_('One standard')); ?>
        : (<?php echo json_encode(_('Standards')); ?> + ': ' + r.total + ' · ' + <?php echo json_encode(_('Items')); ?> + ': ' + r.rows.length);
}

function plCountGroups() {
    var counts = {};
    var trs = document.querySelectorAll('#plBody tr.pl-item');
    for (var i = 0; i < trs.length; i++) {
        var g = trs[i].getAttribute('data-group');
        if (!plCollapsed[g]) { counts[g] = (counts[g] || 0) + 1; }
    }
    var spans = document.querySelectorAll('#plBody .pl-grp-count');
    for (var j = 0; j < spans.length; j++) {
        var key = spans[j].getAttribute('data-cnt');
        var n = counts[key] || 0;
        spans[j].textContent = '(' + n + ' ' + <?php echo json_encode(_('items')); ?> + ')';
    }
}

function plLoad() {
    plGet({ action: 'list_prices', page: plState.page, page_size: plState.pageSize, group: plState.group }, plRender);
}

function plNum(v, fallback) {
    var n = parseFloat(v);
    return isNaN(n) ? fallback : n;
}

function plSaveRow(btn) {
    var tr = btn.closest('tr.pl-item');
    var id = parseInt(tr.getAttribute('data-id'), 10) || 0;
    var price = plNum(tr.querySelector('.pl-price').value, NaN);
    var tat = Math.max(0, Math.round(plNum(tr.querySelector('.pl-tat').value, 0)));
    var unit = tr.querySelector('.pl-unit').value.trim();
    if (isNaN(price) || price < 0) {
        plStatus(<?php echo json_encode(_('Enter a valid price (0 or more).')); ?>, 'error');
        return;
    }
    var code = tr.getAttribute('data-code');
    var params;
    if (id > 0) {
        params = { action: 'save_price', id: id, price: price, tat: tat };
        if (unit !== '') { params.units_code = unit; }
    } else {
        params = { action: 'add_price', stockcode: code, price: price, tat: tat };
        if (unit !== '') { params.units_code = unit; }
    }
    plGet(params, function(r) {
        tr.classList.remove('pl-dirty');
        if (id > 0) {
            tr.setAttribute('data-id', r.id);
            var b = tr.querySelector('.pl-save');
            b.classList.remove('pl-add-btn');
            b.textContent = <?php echo json_encode(_('Save')); ?>;
            if (!tr.querySelector('.pl-del')) {
                var calc = tr.querySelector('.pl-calc');
                var del = document.createElement('button');
                del.type = 'button';
                del.className = 'pl-del';
                del.textContent = <?php echo json_encode(_('Delete')); ?>;
                tr.querySelector('.pl-act').appendChild(del);
            }
        }
        plStatus(code + ' ' + <?php echo json_encode(_('saved.')); ?>, 'ok');
        plCountGroups();
    }, 'POST');
}

function plDeleteRow(btn) {
    var tr = btn.closest('tr.pl-item');
    var id = parseInt(tr.getAttribute('data-id'), 10) || 0;
    var code = tr.getAttribute('data-code');
    if (id <= 0) { return; }
    if (!window.confirm(<?php echo json_encode(_('Delete this price?')); ?> + '\n' + code)) { return; }
    plGet({ action: 'delete_price', id: id }, function(r) {
        plStatus(r.message || <?php echo json_encode(_('Deleted.')); ?>, 'ok');
        plLoad();
    }, 'POST');
}

function plOpenCalc(code) {
    if (!code) { plStatus(<?php echo json_encode(_('No item code.')); ?>, 'error'); return; }
    document.getElementById('calcFrame').src = 'PricingCalculator.php?itemcode=' + encodeURIComponent(code);
    document.getElementById('calcModal').style.display = 'block';
}

function plCloseCalc() {
    document.getElementById('calcFrame').src = '';
    document.getElementById('calcModal').style.display = 'none';
}

$(document).ready(function() {
    // Row level events (delegated: the body is re-rendered on every load).
    $('#plBody').on('click', '.pl-save', function() { plSaveRow(this); });
    $('#plBody').on('click', '.pl-del', function() { plDeleteRow(this); });
    $('#plBody').on('click', '.pl-calc', function() {
        plOpenCalc(this.closest('tr.pl-item').getAttribute('data-code'));
    });
    $('#plBody').on('input', '.pl-price, .pl-tat, .pl-unit', function() {
        this.closest('tr.pl-item').classList.add('pl-dirty');
    });
    $('#plBody').on('keydown', '.pl-price, .pl-tat, .pl-unit', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            plSaveRow(this.closest('tr.pl-item').querySelector('.pl-save'));
        }
    });

    // Group collapse.
    $('#plBody').on('click', 'tr.pl-grp', function() {
        var g = this.getAttribute('data-group');
        plCollapsed[g] = !plCollapsed[g];
        var rows = document.querySelectorAll('#plBody tr.pl-item[data-group="' + g.replace(/"/g, '') + '"]');
        for (var i = 0; i < rows.length; i++) {
            rows[i].classList.toggle('hidden', !!plCollapsed[g]);
        }
        this.querySelector('.pl-caret').innerHTML = plCollapsed[g] ? '&#9656;' : '&#9662;';
        plCountGroups();
    });

    $('#plFilter').on('change', function() {
        plState.group = this.value;
        plState.page = 1;
        plCollapsed = {};
        plLoad();
    });
    $('#plPageSize').on('change', function() {
        plState.pageSize = parseInt(this.value, 10) || 25;
        plState.page = 1;
        plLoad();
    });
    $('#plFirst').on('click', function() { plState.page = 1; plLoad(); });
    $('#plPrev').on('click', function() { plState.page = Math.max(1, plState.page - 1); plLoad(); });
    $('#plNext').on('click', function() { plState.page = plState.page + 1; plLoad(); });
    $('#plLast').on('click', function() { plState.page = 999999; plLoad(); });

    $('#calcModal').on('click', function(e) { if (e.target === this) { plCloseCalc(); } });
    $(document).on('keydown', function(e) { if (e.key === 'Escape') { plCloseCalc(); } });
    window.addEventListener('message', function(e) {
        if (e.data && e.data.action === 'pricePublished') { plCloseCalc(); plLoad(); }
    });

    plLoad();
});
</script>

<?php
include('includes/footer.inc');
?>
