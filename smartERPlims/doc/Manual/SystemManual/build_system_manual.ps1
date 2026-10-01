$ErrorActionPreference = 'Stop'
$root = 'd:\inetpub\wwwroot\smartERPmysql.2.2'
$menuFile = Join-Path $root 'includes\MainMenuLinksArray.php'
$outDir = Join-Path $root 'doc\Manual\SystemManual'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$content = Get-Content -Raw $menuFile

function Parse-PhpArrayItems([string]$text){
    $items = @()
    foreach($x in [regex]::Matches($text, "'((?:[^'\\]|\\.)*)'", [System.Text.RegularExpressions.RegexOptions]::Singleline)){
        $v = $x.Groups[1].Value
        $v = $v -replace "\\'", "'"
        $v = $v -replace "\\n", " "
        $v = $v -replace "\\r", " "
        $v = $v -replace "\s+", " "
        $items += $v.Trim()
    }
    return $items
}

$menuMap = @{}
$pattern = '\$MenuItems\[''([^'']+)''\]\[''(Transactions|Reports|Maintenance)''\]\[''Caption''\]\s*=\s*array\((.*?)\);'
$modules = [regex]::Matches($content, $pattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)
foreach($m in $modules){
    $module = $m.Groups[1].Value
    $section = $m.Groups[2].Value
    $caps = Parse-PhpArrayItems $m.Groups[3].Value

    $uPattern = '\$MenuItems\[''' + [regex]::Escape($module) + '''\]\[''' + $section + '''\]\[''URL''\]\s*=\s*array\((.*?)\);'
    $um = [regex]::Match($content, $uPattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if(-not $um.Success){ continue }
    $urls = Parse-PhpArrayItems $um.Groups[1].Value

    if(-not $menuMap.ContainsKey($module)){ $menuMap[$module] = @{} }
    $menuMap[$module][$section] = @()
    for($i=0; $i -lt [Math]::Min($caps.Count,$urls.Count); $i++){
        $menuMap[$module][$section] += [pscustomobject]@{ Caption = $caps[$i]; Url = $urls[$i] }
    }
}

$fieldPurposeMap = @{
    'date'='Transaction date used for posting, reporting, and period assignment.'
    'docdate'='Document issue date.'
    'documentno'='Primary document reference number for this transaction.'
    'manualdocumentno'='Manual external invoice/document number used when manual numbering is enabled.'
    'reference'='External or business reference for traceability.'
    'customerid'='Customer account code for receivables and sales.'
    'customername'='Customer display name for verification and print outputs.'
    'vendorid'='Supplier account code for payables and purchasing.'
    'vendorname'='Supplier display name for verification and print outputs.'
    'currencycode'='Transaction currency code used to value amounts.'
    'salespersoncode'='Sales representative assigned for accountability and commissions.'
    'bank_code'='Bank account used for receipts, payments, or reconciliation.'
    'comments'='Narration and internal explanation stored with posting.'
    'qty'='Quantity entered for stock or sales line processing.'
    'quantity'='Quantity value used in calculation and posting.'
    'unitprice'='Unit sales/purchase price used for amount calculation.'
    'cost'='Cost value used in purchasing or inventory valuation contexts.'
    'amount'='Monetary value posted to ledger/subledger lines.'
    'vatamount'='Tax component for the transaction line.'
    'grossamount'='Total amount including tax and applicable charges.'
    'dimensionone'='Primary analytical dimension for reporting and control.'
    'dimensiontwo'='Secondary analytical dimension for reporting and control.'
    'formid'='Security token to validate genuine form submission.'
}

function Get-FieldPurpose([string]$name,[string]$label){
    $k = $name.ToLower()
    if($fieldPurposeMap.ContainsKey($k)){ return $fieldPurposeMap[$k] }
    if($k -like '*id'){ return 'Identifier used to select and link master records for this document.' }
    if($k -like '*date*'){ return 'Date value controlling transaction timing, reporting windows, or period logic.' }
    if($k -like '*amount*'){ return 'Amount value used in calculation, posting, and reporting.' }
    if($k -like '*code*'){ return 'Classification or master-data code used for posting and validation.' }
    if($k -like '*name*'){ return 'Display name shown to users for record confirmation.' }
    if($k -like '*qty*' -or $k -like '*quantity*'){ return 'Quantity value used for stock movement and pricing computation.' }
    if($k -like '*rate*'){ return 'Rate input used in tax, exchange, or valuation calculations.' }
    if($k -like '*status*'){ return 'Lifecycle status field that controls workflow progression.' }
    if($label){ return 'Operational input field for "' + $label + '" in this business document.' }
    return 'Operational input field used by this document workflow.'
}

function Extract-Fields([string]$filePath){
    $raw = Get-Content -Raw $filePath
    $fields = @()

    $ctrlMatches = [regex]::Matches($raw, '<(input|select|textarea)\b[^>]*\bname\s*=\s*"([^"]+)"[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    foreach($m in $ctrlMatches){
        $name = $m.Groups[2].Value.Trim()
        if([string]::IsNullOrWhiteSpace($name)){ continue }
        if($name -notmatch '^[A-Za-z_][A-Za-z0-9_]*(\[[A-Za-z0-9_]*\])?$'){ continue }
        $idx = $m.Index
        $start = [Math]::Max(0, $idx - 320)
        $len = [Math]::Min(640, $raw.Length - $start)
        $win = $raw.Substring($start,$len)

        $label = ''
        $lm = [regex]::Match($win, '<td[^>]*>\s*([^<]{1,80})\s*</td>\s*<td[^>]*>[^\r\n]{0,240}\bname\s*=\s*"' + [regex]::Escape($name) + '"', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if($lm.Success){ $label = ($lm.Groups[1].Value -replace '\s+',' ').Trim(' :`t`r`n') }
        if(-not $label){
            $lm2 = [regex]::Match($win, '<label[^>]*>\s*([^<]{1,80})\s*</label>[^\r\n]{0,240}\bname\s*=\s*"' + [regex]::Escape($name) + '"', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
            if($lm2.Success){ $label = ($lm2.Groups[1].Value -replace '\s+',' ').Trim(' :`t`r`n') }
        }
        if($label.Contains('$_') -or $label.Contains('_(') -or $label.Contains('''.')){ $label = '' }
        $fields += [pscustomobject]@{ Name=$name; Label=$label; Purpose=(Get-FieldPurpose $name $label) }
    }

    $dedup = $fields | Group-Object Name | ForEach-Object {
        $sample = $_.Group | Select-Object -First 1
        $labelObj = $_.Group | Where-Object { $_.Label -ne '' } | Select-Object -First 1
        $label = if($labelObj){$labelObj.Label}else{$sample.Label}
        [pscustomobject]@{ Name=$sample.Name; Label=$label; Purpose=$sample.Purpose }
    }
    return $dedup
}

$allEntries = @()
foreach($module in $menuMap.Keys | Sort-Object){
    foreach($section in @('Transactions','Reports','Maintenance')){
        if(-not $menuMap[$module].ContainsKey($section)){ continue }
        foreach($it in $menuMap[$module][$section]){
            $u = ([string]$it.Url).Trim()
            if($u.StartsWith('/')){ $u = $u.Substring(1) }
            $page = ($u -split '\?')[0]
            $abs = Join-Path $root $page
            $exists = Test-Path $abs
            $fields = @()
            if($exists -and $page.ToLower().EndsWith('.php')){ $fields = Extract-Fields $abs }
            $allEntries += [pscustomobject]@{ Module=$module; Section=$section; Menu=$it.Caption; Page=$page; Exists=$exists; Fields=$fields }
        }
    }
}

$detail = Join-Path $outDir '06_Field_Reference_All_Menu_Items.html'
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Field Reference by Menu Item</title>')
[void]$sb.AppendLine('<style>body{font-family:Segoe UI,Arial,sans-serif;margin:0;background:#f4f6f8;color:#1f2933}header{background:#1f3a56;color:#fff;padding:16px 22px}main{max-width:1200px;margin:20px auto;background:#fff;padding:24px;border:1px solid #d9e2ec}h1,h2,h3,h4{color:#102a43}.back a{color:#243b53;text-decoration:none}table{border-collapse:collapse;width:100%;margin:10px 0}th,td{border:1px solid #cbd2d9;padding:7px;vertical-align:top}th{background:#f0f4f8;text-align:left}.meta{color:#486581;font-size:13px}.warn{background:#fffbea;border:1px solid #f7d070;padding:8px}</style></head><body>')
[void]$sb.AppendLine('<header><h1>Field Reference by Menu Item</h1></header><main>')
[void]$sb.AppendLine('<div class="back"><a href="../SystemManual.html">Back to Table of Contents</a></div>')
[void]$sb.AppendLine('<p class="warn">This reference uses business menu names and describes each detected field function for each menu-linked document.</p>')

foreach($module in ($allEntries | Select-Object -ExpandProperty Module -Unique)){
    [void]$sb.AppendLine("<h2>" + [System.Net.WebUtility]::HtmlEncode($module) + "</h2>")
    foreach($section in @('Transactions','Reports','Maintenance')){
        $secItems = $allEntries | Where-Object { $_.Module -eq $module -and $_.Section -eq $section }
        if(-not $secItems){ continue }
        [void]$sb.AppendLine("<h3>" + [System.Net.WebUtility]::HtmlEncode($section) + "</h3>")
        foreach($e in $secItems){
            $anchor = ($e.Module + '_' + $e.Section + '_' + $e.Menu) -replace '[^A-Za-z0-9]+','_'
            $menuPath = $e.Module + ' -> ' + $e.Section + ' -> ' + $e.Menu
            [void]$sb.AppendLine("<h4 id='" + $anchor + "'>" + [System.Net.WebUtility]::HtmlEncode($e.Menu) + "</h4>")
            [void]$sb.AppendLine("<p class='meta'>Menu Path: " + [System.Net.WebUtility]::HtmlEncode($menuPath) + "</p>")
            if(-not $e.Exists){ [void]$sb.AppendLine("<p class='warn'>Target document is not available in current workspace.</p>"); continue }
            if(-not $e.Fields -or $e.Fields.Count -eq 0){ [void]$sb.AppendLine("<p class='warn'>No form fields detected for this document page.</p>"); continue }
            [void]$sb.AppendLine('<table><thead><tr><th>Field Name</th><th>Displayed Label</th><th>Field Function (Business Use)</th></tr></thead><tbody>')
            foreach($f in $e.Fields){
                [void]$sb.AppendLine('<tr><td>' + [System.Net.WebUtility]::HtmlEncode($f.Name) + '</td><td>' + [System.Net.WebUtility]::HtmlEncode($f.Label) + '</td><td>' + [System.Net.WebUtility]::HtmlEncode($f.Purpose) + '</td></tr>')
            }
            [void]$sb.AppendLine('</tbody></table>')
        }
    }
}
[void]$sb.AppendLine('</main></body></html>')
Set-Content -Path $detail -Value $sb.ToString() -Encoding UTF8

$tocPath = Join-Path $root 'doc\Manual\SystemManual.html'
$toc = New-Object System.Text.StringBuilder
[void]$toc.AppendLine('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SmartERP System Manual - Table of Contents</title>')
[void]$toc.AppendLine('<style>body{font-family:Segoe UI,Arial,sans-serif;margin:0;background:#f4f6f8;color:#1f2933}header{background:#1f3a56;color:#fff;padding:18px 24px}main{max-width:980px;margin:20px auto;background:#fff;padding:24px;border:1px solid #d9e2ec}.toc{background:#f0f4f8;border:1px solid #cbd2d9;padding:14px}.toc a{color:#243b53;text-decoration:none}.toc li{margin:6px 0}.toc input{width:100%;box-sizing:border-box;padding:8px;border:1px solid #9fb3c8;margin-bottom:10px}.hidden{display:none}.muted{color:#627d98;font-size:0.9em;margin-bottom:8px}</style></head><body>')
[void]$toc.AppendLine('<header><h1>SmartERP System Manual</h1><div>Table of Contents by Menu Item</div></header><main><div class="toc">')
[void]$toc.AppendLine('<p class="muted">Search and open any business document topic.</p><input type="search" id="tocSearch" placeholder="Search module, menu, or topic...">')
[void]$toc.AppendLine('<ol>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/01_Architecture_and_Runtime.html">Architecture and Runtime Model</a></li>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/02_Functional_Modules.html">Functional Modules and Capabilities</a></li>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/03_End_to_End_Flows.html">End-to-End Business Flows</a></li>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/04_Security_and_Administration.html">Security and Administration</a></li>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/05_Controls_and_Operating_Standards.html">Controls and Operating Standards</a></li>')
[void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/06_Field_Reference_All_Menu_Items.html">Field Reference for All Menu Items</a></li>')

foreach($e in ($allEntries | Sort-Object Module,Section,Menu)){
    $anchor = ($e.Module + '_' + $e.Section + '_' + $e.Menu) -replace '[^A-Za-z0-9]+','_'
    $line = [System.Net.WebUtility]::HtmlEncode($e.Module + ' -> ' + $e.Section + ' -> ' + $e.Menu)
    [void]$toc.AppendLine('<li class="toc-item"><a href="SystemManual/06_Field_Reference_All_Menu_Items.html#' + $anchor + '">' + $line + '</a></li>')
}
[void]$toc.AppendLine('</ol></div></main>')
[void]$toc.AppendLine('<script>(function(){var i=document.getElementById("tocSearch"),x=document.querySelectorAll(".toc-item");if(!i)return;i.addEventListener("input",function(){var q=i.value.toLowerCase().trim();for(var k=0;k<x.length;k++){var t=x[k].innerText.toLowerCase();x[k].classList.toggle("hidden", q && t.indexOf(q)===-1);}});})();</script>')
[void]$toc.AppendLine('</body></html>')
Set-Content -Path $tocPath -Value $toc.ToString() -Encoding UTF8


