<?php
// Lead Management Page
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'Lead Management';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');
include($PathPrefix . 'includes/crm_scope.php');

$Title = _('Lead Management');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);

$LeadStatusArray = array('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'converted', 'lost');
$LeadSourceArray = array('Website', 'Referral', 'Google', 'Facebook', 'LinkedIn', 'Trade Show', 'Cold Call', 'Email Campaign', 'Walk-in', 'Other');
?>
<link rel="stylesheet" href="kanban.css">
<style>
.crm-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.crm-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; align-items: center; }
.crm-filters select, .crm-filters input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
.crm-filters button { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
.btn-primary { background: #3498db; color: white; }
.btn-success { background: #27ae60; color: white; }
.btn-danger { background: #e74c3c; color: white; }
.btn-secondary { background: #95a5a6; color: white; }
.leads-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.leads-table th, .leads-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
.leads-table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
.leads-table tr:hover { background: #f8f9fa; }
.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-new { background: #e9ecef; color: #6c757d; }
.status-contacted { background: #e7f1ff; color: #0d6efd; }
.status-qualified { background: #e8e0ff; color: #6610f2; }
.status-proposal { background: #f0e8ff; color: #6f42c1; }
.status-negotiation { background: #ffe8f0; color: #d63384; }
.status-converted { background: #d4edda; color: #198754; }
.status-lost { background: #f8d7da; color: #dc3545; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal.show { display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.modal-header h3 { margin: 0; }
.close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c3e50; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.form-group textarea { min-height: 80px; resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.action-btns { display: flex; gap: 5px; }
.action-btns button { padding: 6px 10px; font-size: 12px; }
.search-box { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2><?php echo _('Lead Management'); ?></h2>
        <div style="display: flex; gap: 10px;">
            <button class="btn-primary" onclick="openImportModal()">Import from Excel</button>
            <button class="btn-success" onclick="openLeadModal()">+ New Lead</button>
        </div>
    </div>
    
    <div class="crm-filters">
        <input type="text" id="leadSearch" class="search-box" placeholder="Search leads..." onkeyup="loadLeads()">
        <select id="statusFilter" onchange="loadLeads()">
            <option value="">All Statuses</option>
            <?php foreach($LeadStatusArray as $s): ?>
            <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="sourceFilter" onchange="loadLeads()">
            <option value="">All Sources</option>
            <?php foreach($LeadSourceArray as $s): ?>
            <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select>
        <select id="assignedFilter" onchange="onScopeChange()">
            <?php echo crm_scope_options('My Leads', 'All Sales Reps'); ?>
        </select>
        <button class="btn-primary" onclick="loadLeads()">Search</button>
    </div>
    
    <table class="leads-table" id="leadsTable">
        <thead>
            <tr>
                <th>Company</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Source</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Score</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="leadsBody">
            <tr><td colspan="9" style="text-align:center;">Loading leads...</td></tr>
        </tbody>
    </table>
</div>

<!-- Lead Modal -->
<div class="modal" id="leadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="leadModalTitle">New Lead</h3>
            <button class="close-modal" onclick="closeLeadModal()">&times;</button>
        </div>
        <form id="leadForm">
            <input type="hidden" id="leadId" name="id" value="0">
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Company Name'); ?> *</label>
                    <input type="text" id="company_name" name="company_name" required>
                </div>
                <div class="form-group">
                    <label><?php echo _('Contact Name'); ?></label>
                    <input type="text" id="contact_name" name="contact_name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Email'); ?></label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label><?php echo _('Phone'); ?></label>
                    <input type="text" id="phone" name="phone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Mobile'); ?></label>
                    <input type="text" id="mobile" name="mobile">
                </div>
                <div class="form-group">
                    <label><?php echo _('Website'); ?></label>
                    <input type="text" id="website" name="website">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Industry'); ?></label>
                    <input type="text" id="industry" name="industry">
                </div>
                <div class="form-group">
                    <label><?php echo _('Source'); ?></label>
                    <select id="source" name="source">
                        <option value="">Select Source</option>
                        <?php foreach($LeadSourceArray as $s): ?>
                        <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Status'); ?></label>
                    <select id="status" name="status">
                        <?php foreach($LeadStatusArray as $s): ?>
                        <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo _('Lead Score'); ?></label>
                    <input type="number" id="lead_score" name="lead_score" min="0" max="100" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('Assigned To'); ?></label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo _('Next Follow-up'); ?></label>
                    <input type="date" id="next_followup" name="next_followup">
                </div>
            </div>
            <div class="form-group">
                <label><?php echo _('Address'); ?></label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo _('City'); ?></label>
                    <input type="text" id="city" name="city">
                </div>
                <div class="form-group">
                    <label><?php echo _('Country'); ?></label>
                    <input type="text" id="country" name="country">
                </div>
            </div>
            <div class="form-group">
                <label><?php echo _('PIN/VAT'); ?></label>
                <input type="text" id="pin_vat" name="pin_vat">
            </div>
            <div class="form-group">
                <label><?php echo _('Notes'); ?></label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeLeadModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Lead</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Leads Modal -->
<div class="modal" id="importModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Leads from Excel</h3>
            <button class="close-modal" onclick="closeImportModal()">&times;</button>
        </div>
        <form id="importForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Excel File (.xlsx, .xls) *</label>
                <input type="file" id="importFile" name="importFile" accept=".xlsx,.xls" required>
                <small style="color: #666;">Required columns: <strong>company_name</strong>. Optional: contact_name, email, phone, mobile, website, industry, source, status, lead_score, notes, address, city, country, pin_vat, next_followup</small>
                <br>
                <button type="button" class="btn-secondary" onclick="downloadImportTemplate()" style="margin-top: 5px;">Download Template</button>
            </div>
            <div class="form-group">
                <label>Default Source</label>
                <select id="importDefaultSource" name="default_source">
                    <option value="">Use from file or leave blank</option>
                    <?php foreach($LeadSourceArray as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Default Status</label>
                <select id="importDefaultStatus" name="default_status">
                    <option value="">Use from file or 'new'</option>
                    <?php foreach($LeadStatusArray as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="importAssignToMe" name="assign_to_me" checked> Assign imported leads to me
                </label>
            </div>
            <div class="form-group">
                <label>Skip header row</label>
                <input type="checkbox" id="importSkipHeader" name="skip_header" checked>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeImportModal()">Cancel</button>
                <button type="submit" class="btn-primary">Import Leads</button>
            </div>
            <div id="importProgress" style="display: none; margin-top: 15px;">
                <div style="background: #f0f0f0; border-radius: 4px; height: 20px; overflow: hidden;">
                    <div id="importProgressBar" style="background: #3498db; height: 100%; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p id="importProgressText" style="margin-top: 5px; font-size: 14px; color: #666;"></p>
            </div>
        </form>
    </div>
</div>

<script>
<?php echo crm_scope_js(); ?>

function onScopeChange() {
    setCrmScope(document.getElementById('assignedFilter').value, false);
    loadLeads();
}

function loadLeads() {
    const search = document.getElementById('leadSearch').value;
    const status = document.getElementById('statusFilter').value;
    const source = document.getElementById('sourceFilter').value;
    const assigned = document.getElementById('assignedFilter').value;
    
    fetch('api.php?action=listLeads&scope=' + encodeURIComponent(assigned) +
          '&search=' + encodeURIComponent(search) +
          '&status=' + status + '&source=' + source)
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('leadsBody');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No leads found</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(lead => `
            <tr>
                <td><strong>${lead.company_name || '-'}</strong></td>
                <td>${lead.contact_name || '-'}</td>
                <td>${lead.email || '-'}</td>
                <td>${lead.phone || '-'}</td>
                <td>${lead.source || '-'}</td>
                <td><span class="status-badge status-${lead.status}">${lead.status || 'new'}</span></td>
                <td>${lead.assigned_to || '-'}</td>
                <td>${lead.lead_score || 0}</td>
                <td class="action-btns">
                    <button class="btn-primary" onclick="editLead(${lead.id})">Edit</button>
                    <button class="btn-success" onclick="convertLeadBtn(${lead.id}, '${lead.company_name || ''}', '${lead.contact_name || ''}')">Convert</button>
                    <button class="btn-secondary" onclick="viewLead(${lead.id})">View</button>
                </td>
            </tr>
        `).join('');
    });
}

function loadUsers() {
    fetch('api.php?action=listUsers')
    .then(r => r.json())
    .then(res => {
        if (res.data) {
            const select = document.getElementById('assigned_to');
            res.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                select.appendChild(opt);
            });
        }
    });
}

function openLeadModal(id = 0) {
    document.getElementById('leadModal').classList.add('show');
    document.getElementById('leadModalTitle').textContent = id ? 'Edit Lead' : 'New Lead';
    document.getElementById('leadForm').reset();
    document.getElementById('leadId').value = id;
}

function closeLeadModal() {
    document.getElementById('leadModal').classList.remove('show');
}

function editLead(id) {
    fetch('api.php?action=getLead&id=' + id)
    .then(r => r.json())
    .then(res => {
        if (res.data) {
            const lead = res.data;
            openLeadModal(lead.id);
            Object.keys(lead).forEach(k => {
                const el = document.getElementById(k);
                if (el) el.value = lead[k] || '';
            });
        }
    });
}

function viewLead(id) {
    window.location.href = 'lead_details.php?id=' + id;
}

function deleteLead(id) {
    if (!confirm('Are you sure you want to delete this lead?')) return;
    const fd = new FormData();
    fd.append('action', 'deleteLead');
    fd.append('id', id);
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.ok) loadLeads();
    });
}

document.getElementById('leadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'saveLead');
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            closeLeadModal();
            loadLeads();
            toastSuccess('Lead saved successfully');
            
            if (document.getElementById('leadId').value === '0') {
                const company = document.getElementById('company_name').value;
                const contact = document.getElementById('contact_name').value;
                if (confirm('Lead created! Would you like to convert it to an Opportunity in the Pipeline?')) {
                    convertLeadToOpportunity(res.id || 0, company, contact);
                }
            }
        } else {
            toastError(res.error || 'Error saving lead');
        }
    });
});

function convertLeadBtn(leadId, companyName, contactName) {
    if (!leadId) {
        toastError('Invalid lead');
        return;
    }
    convertLeadToOpportunity(leadId, companyName, contactName);
}

function convertLeadToOpportunity(leadId, companyName, contactName) {
    if (!leadId || leadId === 0) {
        toastError('Invalid lead ID');
        return;
    }
    
    const opportunity_name = companyName || 'New Opportunity';
    const fd = new FormData();
    fd.append('action', 'saveOpportunity');
    fd.append('opportunity_name', opportunity_name);
    fd.append('lead_id', leadId);
    fd.append('company_name', companyName);
    fd.append('contact_name', contactName);
    fd.append('pipeline_stage_id', '1');
    fd.append('expected_value', '0');
    fd.append('probability', '10');
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            toastSuccess('Lead converted to Opportunity!');
            window.location.href = 'Opportunities.php';
        } else {
            toastError(res.error || 'Error converting lead');
        }
    })
    .catch(err => {
        toastError('Network error: ' + err.message);
    });
}

// ===== IMPORT FUNCTIONS =====
function openImportModal() {
    document.getElementById('importModal').classList.add('show');
    document.getElementById('importForm').reset();
    document.getElementById('importProgress').style.display = 'none';
    document.getElementById('importProgressBar').style.width = '0%';
}

function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
}

function downloadImportTemplate() {
    // Create a sample Excel file with headers
    const headers = [
        'company_name', 'contact_name', 'email', 'phone', 'mobile', 
        'website', 'industry', 'source', 'status', 'lead_score', 
        'notes', 'address', 'city', 'country', 'pin_vat', 'next_followup'
    ];
    
    const sampleRow = [
        'Acme Corporation', 'John Doe', 'john@acme.com', '+1-555-0100', '+1-555-0101',
        'https://acme.com', 'Manufacturing', 'Website', 'new', '50',
        'Interested in enterprise solution', '123 Main St', 'New York', 'USA', 'VAT123456', '2026-09-15'
    ];
    
    // Create CSV content (Excel can open CSV)
    let csvContent = headers.join(',') + '\n';
    csvContent += sampleRow.map(v => '"' + v.replace(/"/g, '""') + '"').join(',') + '\n';
    
    // Add a second sample row
    const sampleRow2 = [
        'Beta Industries', 'Jane Smith', 'jane@beta.com', '+1-555-0200', '+1-555-0201',
        'https://beta.com', 'Technology', 'Referral', 'contacted', '75',
        'Follow up next week', '456 Oak Ave', 'San Francisco', 'USA', 'VAT789012', '2026-09-20'
    ];
    csvContent += sampleRow2.map(v => '"' + v.replace(/"/g, '""') + '"').join(',') + '\n';
    
    // Download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'leads_import_template.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

document.addEventListener('DOMContentLoaded', function() {
    loadLeads();
    loadUsers();

    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('importFile');
        if (!fileInput.files.length) {
            toastError('Please select an Excel file');
            return;
        }
        
        const file = fileInput.files[0];
        
        const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
            toastError('Please select a valid Excel file (.xlsx or .xls)');
            return;
        }
        
        const fd = new FormData();
        fd.append('action', 'importLeads');
        fd.append('importFile', file);
        fd.append('default_source', document.getElementById('importDefaultSource').value);
        fd.append('default_status', document.getElementById('importDefaultStatus').value);
        fd.append('assign_to_me', document.getElementById('importAssignToMe').checked ? '1' : '0');
        fd.append('skip_header', document.getElementById('importSkipHeader').checked ? '1' : '0');
        
        const progressDiv = document.getElementById('importProgress');
        const progressBar = document.getElementById('importProgressBar');
        const progressText = document.getElementById('importProgressText');
        
        progressDiv.style.display = 'block';
        progressBar.style.width = '10%';
        progressText.textContent = 'Uploading...';
        
        fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            progressBar.style.width = '100%';
            if (res.ok) {
                progressText.textContent = `Import complete! ${res.imported} leads imported${res.errors ? ', ' + res.errors + ' errors' : ''}`;
                toastSuccess(`Successfully imported ${res.imported} leads`);
                closeImportModal();
                loadLeads();
            } else {
                progressText.textContent = 'Import failed: ' + (res.error || 'Unknown error');
                toastError(res.error || 'Import failed');
            }
        })
        .catch(err => {
            progressText.textContent = 'Network error: ' + err.message;
            toastError('Network error: ' + err.message);
        });
    });
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
