<?php
/**
 * Standalone HTML preview for the proposed COA header layout.
 *
 * PHP 7.4 compatible. Preview only: this file does not modify the production
 * COA generators or connect to the LIMS/ERP database.
 *
 * Run from the repository root:
 *   php -S 127.0.0.1:8080 -t blockchain
 * Then open:
 *   http://127.0.0.1:8080/coa_header_preview.php
 */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$frames = [
    [
        'title' => 'Company Details',
        'fields' => [
            ['Company Name', "record['company_name']"],
            ['Address', "record['address']"],
            ['Address 2', "record['address1']"],
            ['Address 3', "record['address2']"],
            ['Address 4', "record['address3']"],
            ['Email', "record['email']"],
            ['Telephone', "record['telephone']"],
        ],
    ],
    [
        'title' => 'Customer Details',
        'fields' => [
            ['Customer', "customerData['customer']"],
            ['Company', "customerData['company']"],
            ['Postal Code', "customerData['postcode']"],
            ['City', "customerData['city']"],
            ['Telephone', "customerData['phone']"],
            ['Email', "customerData['email']"],
        ],
    ],
    [
        'title' => 'SAMPLE HEADER',
        'fields' => [
            ['Sample Batch No', "record['DocumentNo']"],
            ['Sample ID', '$realsampleid'],
            ['Sampling Date', "record['SamplingDate']"],
            ['Sampled By', "record['SampledBy']"],
            ['Sampling Method', "record['SamplingMethod']"],
            ['Sample Source', "record['ExternalSample']"],
            ['Sample Name', "record['sample_name']"],
        ],
    ],
    [
        'title' => 'SAMPLE HEADER',
        'fields' => [
            ['Date Received', "record['Date']"],
            ['Date Test Started', '$testreceived'],
            ['Date Test Ended', '$testended'],
            ['Sample Method', "record['sample_method']"],
            ['B/No', "record['BatchNo']"],
            ['B/size', "record['BatchSize']"],
            ['Date of Man', "record['ManufactureDate']"],
            ['Date of Exp', "record['ExpDate']"],
            ['Chilled Exp', "record['chilled_date_of_expiry']"],
            ['Frozen Exp', "record['frozen_date_of_expiry']"],
            ['SKU', "record['SKU']"],
            ['Condition', "record['condition_of_sample']"],
        ],
    ],
];

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>COA Header Layout Preview</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 22px; background: #edf0f3; color: #202b36; font-family: Arial, Helvetica, sans-serif; }
    .page { width: 100%; max-width: 794px; min-height: 1123px; margin: 0 auto; padding: 24px; background: #fff; border: 1px solid #b9c8d5; border-radius: 28px 9px 28px 9px; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    .letterhead { display: flex; justify-content: space-between; align-items: flex-start; min-height: 82px; padding: 2px 4px 14px; }
    .asset-slot { display: flex; align-items: center; justify-content: center; border: 1px dashed #9aa8b5; color: #667684; font-size: 11px; }
    .logo-slot { width: 150px; height: 46px; }
    .qr-slot { width: 58px; height: 58px; }
    .frames { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 9px; }
    .frame { min-width: 0; border: 1px solid #b6c4d1; padding: 10px; }
    .frame-title { margin: 0 0 9px; padding-bottom: 6px; border-bottom: 1px solid #d7dfe6; font-size: 11px; font-weight: 700; letter-spacing: .25px; text-transform: uppercase; }
    .fields { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 8px 10px; }
    .field { min-width: 0; overflow-wrap: anywhere; }
    .label { margin-bottom: 3px; font-size: 10px; font-weight: 700; }
    .binding { color: #657586; font-size: 10px; }
    .report-body { min-height: 285px; margin-top: 12px; padding: 12px; border: 1px solid #b6c4d1; }
    .report-body h2 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; }
    .report-body p { color: #657586; font-size: 11px; line-height: 1.5; }
    .sample-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .sample-table th, .sample-table td { padding: 8px; border: 1px solid #bac6d1; text-align: left; }
    .sample-table th { background: #eef2f5; }
    .issue-date { margin-top: 18px; text-align: right; font-size: 10px; }
    .notice { max-width: 794px; margin: 12px auto 0; color: #465565; font-size: 12px; line-height: 1.5; }
    @media (max-width: 600px) {
        body { padding: 8px; }
        .page { padding: 12px; }
        .frames { grid-template-columns: 1fr; }
    }
    @media print {
        body { padding: 0; background: #fff; }
        .page { max-width: none; box-shadow: none; }
        .notice { display: none; }
    }
</style>
</head>
<body>
<main class="page">
    <header class="letterhead">
        <div class="asset-slot logo-slot">Existing lab logo asset</div>
        <div class="asset-slot qr-slot">Existing QR asset</div>
    </header>

    <section class="frames" aria-label="COA header frames">
        <?php foreach ($frames as $frame): ?>
            <section class="frame">
                <h2 class="frame-title"><?php echo h($frame['title']); ?></h2>
                <div class="fields">
                    <?php foreach ($frame['fields'] as $field): ?>
                        <div class="field">
                            <div class="label"><?php echo h($field[0]); ?></div>
                            <div class="binding"><?php echo h($field[1]); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </section>

    <section class="report-body">
        <h2>Existing report body — unchanged</h2>
        <p>This is a layout boundary marker, not a replacement for the production report body. The existing report table, result rows, limits, methods, remarks, approval/signature content and footer are intentionally not reproduced or modified by this standalone header preview.</p>
        <table class="sample-table" aria-label="Existing report body placeholder">
            <thead>
                <tr>
                    <th>Existing report columns</th>
                    <th>Existing report columns</th>
                    <th>Existing report columns</th>
                    <th>Existing report columns</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="4">Existing result table remains in the production COA script.</td></tr>
            </tbody>
        </table>
        <div class="issue-date">Report Issue Date: final sample approval timestamp (source to be verified before implementation)</div>
    </section>
</main>
<p class="notice"><strong>Preview only.</strong> This file displays field bindings from the current COA code; it does not connect to the database, render real sample data, or alter the production COA scripts. The logo and QR areas are placeholders for the existing assets.</p>
</body>
</html>
