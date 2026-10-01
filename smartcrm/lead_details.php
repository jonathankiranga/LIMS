<?php
// Lead Details Page
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'Lead Details';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');

$LeadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$Title = _('Lead Details');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);

$LeadStatusArray = array('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'converted', 'lost');
$LeadSourceArray = array('Website', 'Referral', 'Google', 'Facebook', 'LinkedIn', 'Trade Show', 'Cold Call', 'Email Campaign', 'Walk-in', 'Other');

$SQL = "SELECT * FROM crm_leads WHERE id = " . $LeadId;
$Result = DB_query($SQL, $db);
$lead = DB_fetch_array($Result);

if (!$lead) {
    echo '<div class="crm-container"><p>Lead not found.</p></div>';
    include($PathPrefix . 'includes/footer.inc');
    exit;
}
?>
<link rel="stylesheet" href="kanban.css">
<style>
.crm-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.crm-back { padding: 8px 16px; background: #6b4fd6; color: white; text-decoration: none; border-radius: 8px; }
.crm-back:hover { background: #5a3fc0; }
.lead-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.lead-card h3 { margin: 0 0 16px; color: #6b4fd6; border-bottom: 1px solid #e1dfe8; padding-bottom: 10px; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
.info-item { display: flex; flex-direction: column; }
.info-item label { font-size: 12px; color: #6f6c7a; font-weight: 600; margin-bottom: 4px; }
.info-item span { font-size: 14px; color: #2f2b3a; }
.status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; display: inline-block; }
.status-new { background: #e9ecef; color: #6c757d; }
.status-contacted { background: #e7f1ff; color: #0d6efd; }
.status-qualified { background: #e8e0ff; color: #6610f2; }
.status-proposal { background: #f0e8ff; color: #6f42c1; }
.status-negotiation { background: #ffe8f0; color: #d63384; }
.status-converted { background: #d4edda; color: #198754; }
.status-lost { background: #f8d7da; color: #dc3545; }
.btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
.btn-primary { background: #6b4fd6; color: white; }
.btn-secondary { background: #e1dfe8; color: #6f6c7a; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2><?php echo htmlspecialchars($lead['company_name'] ?: 'Lead Details'); ?></h2>
        <a href="Leads.php" class="crm-back">&larr; Back to Leads</a>
    </div>
    
    <div class="lead-card">
        <h3>Basic Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Company Name</label>
                <span><?php echo htmlspecialchars($lead['company_name'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Contact Name</label>
                <span><?php echo htmlspecialchars($lead['contact_name'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Email</label>
                <span><?php echo htmlspecialchars($lead['email'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Phone</label>
                <span><?php echo htmlspecialchars($lead['phone'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Mobile</label>
                <span><?php echo htmlspecialchars($lead['mobile'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Website</label>
                <span><?php echo htmlspecialchars($lead['website'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span class="status-badge status-<?php echo $lead['status']; ?>"><?php echo ucfirst($lead['status'] ?: 'new'); ?></span>
            </div>
            <div class="info-item">
                <label>Source</label>
                <span><?php echo htmlspecialchars($lead['source'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Assigned To</label>
                <span><?php echo htmlspecialchars($lead['assigned_to'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Lead Score</label>
                <span><?php echo (int)$lead['lead_score']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="lead-card">
        <h3>Additional Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Industry</label>
                <span><?php echo htmlspecialchars($lead['industry'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Source Details</label>
                <span><?php echo htmlspecialchars($lead['source_details'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>City</label>
                <span><?php echo htmlspecialchars($lead['city'] ?: '-'); ?></span>
            </div>
            <div class="info-item">
                <label>Created At</label>
                <span><?php echo $lead['created_at']; ?></span>
            </div>
            <div class="info-item">
                <label>Updated At</label>
                <span><?php echo $lead['updated_at']; ?></span>
            </div>
        </div>
    </div>
    
    <?php if ($lead['notes']): ?>
    <div class="lead-card">
        <h3>Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($lead['notes'])); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($lead['address']): ?>
    <div class="lead-card">
        <h3>Address</h3>
        <p><?php echo nl2br(htmlspecialchars($lead['address'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <button class="btn btn-primary" onclick="editLead(<?php echo $lead['id']; ?>); window.location.href='Leads.php';">Edit Lead</button>
    </div>
</div>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
