<div id="blockchainAuditApp" class="audit-tool">
  <style>
    #blockchainAuditApp{--audit-ink:#18233b;--audit-muted:#69758c;--audit-line:#e6ebf4;--audit-blue:#4664f5;--audit-violet:#8657e8;--audit-teal:#13b8a6;color:var(--audit-ink);font-family:Inter,"Segoe UI",Arial,sans-serif;padding:18px;max-width:1600px;margin:0 auto}
    #blockchainAuditApp *{box-sizing:border-box}
    #blockchainAuditApp .audit-hero{position:relative;overflow:hidden;border-radius:20px;padding:25px 28px;color:#fff;background:linear-gradient(115deg,#172554 0%,#4338ca 54%,#8b5cf6 100%);box-shadow:0 14px 35px rgba(67,56,202,.22)}
    #blockchainAuditApp .audit-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,255,255,.18);border-radius:50%;right:8%;top:-115px;box-shadow:0 0 0 28px rgba(255,255,255,.045),0 0 0 58px rgba(255,255,255,.035)}
    #blockchainAuditApp .audit-hero-inner{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}
    #blockchainAuditApp .audit-eyebrow{font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;opacity:.78;margin-bottom:8px}
    #blockchainAuditApp .audit-title{font-size:clamp(23px,3vw,31px);font-weight:800;letter-spacing:-.7px;margin:0 0 8px;color:#fff}
    #blockchainAuditApp .audit-subtitle{margin:0;color:rgba(255,255,255,.8);font-size:13px}
    #blockchainAuditApp .audit-live{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);padding:9px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}
    #blockchainAuditApp .audit-dot{width:8px;height:8px;border-radius:50%;background:#5df2c1;box-shadow:0 0 0 4px rgba(93,242,193,.15)}
    #blockchainAuditApp .audit-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:16px 0}
    #blockchainAuditApp .audit-stat{border:1px solid var(--audit-line);border-radius:15px;padding:15px 17px;background:#fff;display:flex;align-items:center;gap:13px;min-width:0;box-shadow:0 4px 14px rgba(24,35,59,.035)}
    #blockchainAuditApp .audit-stat-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;font-size:17px;flex-shrink:0}
    #blockchainAuditApp .audit-stat:nth-child(1) .audit-stat-icon{color:#465cf5;background:#eef0ff}
    #blockchainAuditApp .audit-stat:nth-child(2) .audit-stat-icon{color:#0f9f8f;background:#e5fbf5}
    #blockchainAuditApp .audit-stat:nth-child(3) .audit-stat-icon{color:#8856db;background:#f3eaff}
    #blockchainAuditApp .audit-stat-label{font-size:11px;color:var(--audit-muted);font-weight:700;text-transform:uppercase;letter-spacing:.65px;margin-bottom:4px}
    #blockchainAuditApp .audit-stat-value{font-size:23px;line-height:1.1;font-weight:800;letter-spacing:-.5px}
    #blockchainAuditApp .audit-panel{border:1px solid var(--audit-line);border-radius:17px;background:#fff;overflow:hidden;box-shadow:0 5px 20px rgba(24,35,59,.045)}
    #blockchainAuditApp .audit-toolbar{padding:16px;display:flex;gap:11px;align-items:center;justify-content:space-between;flex-wrap:wrap;border-bottom:1px solid var(--audit-line)}
    #blockchainAuditApp .audit-search-wrap{position:relative;flex:1 1 300px;max-width:540px}
    #blockchainAuditApp .audit-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8792a8;pointer-events:none}
    #blockchainAuditApp .audit-search{width:100%;border:1px solid #dfe5f0;border-radius:10px;padding:11px 13px 11px 38px;font-size:13px;color:var(--audit-ink);outline:none;background:#fbfcff}
    #blockchainAuditApp .audit-search:focus{border-color:#8c9bff;box-shadow:0 0 0 3px rgba(70,100,245,.1)}
    #blockchainAuditApp .audit-btn{border:1px solid #dfe5f0;background:#fff;border-radius:10px;padding:10px 13px;font-size:12px;font-weight:750;color:#394761;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
    #blockchainAuditApp .audit-btn:hover{border-color:#aab5ff;color:#394be5;background:#f8f9ff}
    #blockchainAuditApp .audit-count{font-size:12px;color:var(--audit-muted);font-weight:650;white-space:nowrap}
    #blockchainAuditApp .audit-table-wrap{width:100%;overflow:auto}
    #blockchainAuditApp table{width:100%;min-width:1040px;border-collapse:separate;border-spacing:0;font-size:12px}
    #blockchainAuditApp thead th{padding:12px 14px;background:#f7f8fc;color:#6c7890;text-align:left;text-transform:uppercase;letter-spacing:.65px;font-size:10px;font-weight:800;border-bottom:1px solid var(--audit-line);white-space:nowrap}
    #blockchainAuditApp tbody td{padding:13px 14px;border-bottom:1px solid #edf0f6;vertical-align:top}
    #blockchainAuditApp tbody tr:last-child td{border-bottom:0}
    #blockchainAuditApp tbody tr:hover td{background:#fafbff}
    #blockchainAuditApp .audit-block-id{display:inline-flex;align-items:center;justify-content:center;min-width:45px;padding:6px 8px;border-radius:8px;background:#edf0ff;color:#465cf5;font-weight:800;font-variant-numeric:tabular-nums}
    #blockchainAuditApp .audit-time{white-space:nowrap;color:#596780}
    #blockchainAuditApp .audit-user{display:inline-flex;align-items:center;gap:7px;font-weight:700;white-space:nowrap}
    #blockchainAuditApp .audit-avatar{display:grid;place-items:center;width:27px;height:27px;border-radius:9px;background:#e5fbf5;color:#0d9488;font-weight:800}
    #blockchainAuditApp .audit-payload{max-width:360px;min-width:210px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.5;color:#394761}
    #blockchainAuditApp .audit-hash{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:10px;line-height:1.6;overflow-wrap:anywhere;max-width:190px;color:#52617c}
    #blockchainAuditApp .audit-copy{border:0;background:transparent;color:#5265e9;cursor:pointer;padding:3px 5px;margin-left:3px;border-radius:5px}
    #blockchainAuditApp .audit-copy:hover{background:#eef0ff}
    #blockchainAuditApp .audit-tag{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 8px;font-size:10px;font-weight:800;white-space:nowrap}
    #blockchainAuditApp .audit-tag-ok{background:#e4fbf2;color:#0d8c68}
    #blockchainAuditApp .audit-tag-warn{background:#fff3dc;color:#a96809}
    #blockchainAuditApp .audit-tag-error{background:#ffe9eb;color:#c03950}
    #blockchainAuditApp .audit-empty{padding:38px 16px;text-align:center;color:var(--audit-muted)}
    #blockchainAuditApp .audit-empty i{display:block;font-size:26px;margin-bottom:10px;color:#a5afc4}
    #blockchainAuditApp .audit-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:13px 16px;border-top:1px solid var(--audit-line);background:#fcfcfe}
    #blockchainAuditApp .audit-pagination{display:flex;gap:5px;flex-wrap:wrap}
    #blockchainAuditApp .audit-page{min-width:31px;height:31px;padding:0 9px;border:1px solid #dfe5f0;border-radius:8px;background:#fff;color:#5c6880;font-size:11px;font-weight:750;cursor:pointer}
    #blockchainAuditApp .audit-page.active{background:#4d5ff2;color:#fff;border-color:#4d5ff2}
    #blockchainAuditApp .audit-page:disabled{opacity:.4;cursor:not-allowed}
    #blockchainAuditApp .audit-note{font-size:11px;color:#8490a5;margin:13px 2px 0;line-height:1.5}
    #blockchainAuditApp .audit-toast{position:fixed;right:24px;bottom:24px;z-index:9999;background:#172554;color:#fff;padding:11px 15px;border-radius:10px;box-shadow:0 8px 25px #18233b30;font-size:12px;display:none}
    @media(max-width:720px){#blockchainAuditApp{padding:10px}#blockchainAuditApp .audit-hero{padding:20px}#blockchainAuditApp .audit-stats{grid-template-columns:1fr;gap:8px}#blockchainAuditApp .audit-stat{padding:12px 14px}#blockchainAuditApp .audit-toolbar{align-items:stretch}#blockchainAuditApp .audit-search-wrap{max-width:none}#blockchainAuditApp .audit-count{width:100%}}
  </style>

  <section class="audit-hero">
    <div class="audit-hero-inner">
      <div>
        <div class="audit-eyebrow">Integrity centre · LIMS</div>
        <h1 class="audit-title">Audit Trail Explorer</h1>
        <p class="audit-subtitle">Inspect ledger activity, review record integrity and trace signed events.</p>
      </div>
      <div class="audit-live"><span class="audit-dot"></span> Ledger records viewer</div>
    </div>
  </section>

  <section class="audit-stats" aria-label="Ledger summary">
    <div class="audit-stat"><div class="audit-stat-icon"><i class="fas fa-cubes"></i></div><div><div class="audit-stat-label">Records on this page</div><div class="audit-stat-value" id="auditPageCount">—</div></div></div>
    <div class="audit-stat"><div class="audit-stat-icon"><i class="fas fa-user-shield"></i></div><div><div class="audit-stat-label">Unique users on this page</div><div class="audit-stat-value" id="auditUserCount">—</div></div></div>
    <div class="audit-stat"><div class="audit-stat-icon"><i class="fas fa-fingerprint"></i></div><div><div class="audit-stat-label">Verified payloads</div><div class="audit-stat-value" id="auditDecryptedCount">—</div></div></div>
  </section>

  <section class="audit-panel">
    <div class="audit-toolbar">
      <div class="audit-search-wrap"><i class="fas fa-search audit-search-icon"></i><input id="auditSearch" class="audit-search" type="search" placeholder="Search this page by block, user, payload or hash…" autocomplete="off" aria-label="Search visible ledger records"></div>
      <button type="button" class="audit-btn" id="auditRefresh"><i class="fas fa-sync-alt"></i> Refresh ledger</button>
      
      <div class="audit-count" id="auditCountLabel">Loading ledger…</div>
    </div>
    <div class="audit-table-wrap">
      <table aria-label="Blockchain audit trail records">
        <thead><tr><th>Block</th><th>Timestamp</th><th>Actor</th><th>Payload / event data</th><th>Previous hash</th><th>Current hash</th><th>Signature</th></tr></thead>
        <tbody id="auditRows"><tr><td colspan="7"><div class="audit-empty"><i class="fas fa-circle-notch fa-spin"></i>Loading ledger records…</div></td></tr></tbody>
      </table>
    </div>
    <div class="audit-footer"><div class="audit-count" id="auditPageLabel">Connecting to ledger…</div><div class="audit-pagination" id="auditPagination"></div></div>
  </section>
  <p class="audit-note"><i class="fas fa-info-circle"></i> Search filters the records currently loaded on this page. Hashes and payloads are displayed as returned by the existing blockchain endpoint; this screen does not independently validate digital signatures or recalculate hashes.</p>
  <div id="auditToast" class="audit-toast" role="status" aria-live="polite"></div>
</div>

<script>
(function() {
  const root = document.getElementById('blockchainAuditApp');
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';
  const $ = (selector) => root.querySelector(selector);
  const state = { page: 1, totalPages: 1, blocks: [], request: 0 };

  function safeText(value) {
    return value === null || value === undefined || value === '' ? '—' : String(value);
  }
  function shortHash(value) {
    const text = safeText(value);
    return text.length > 25 ? text.slice(0, 12) + '…' + text.slice(-9) : text;
  }
  function showToast(message) {
    const toast = $('#auditToast');
    toast.textContent = message;
    toast.style.display = 'block';
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => { toast.style.display = 'none'; }, 1800);
  }
  function cell(text, className, title) {
    const td = document.createElement('td');
    if (className) td.className = className;
    td.textContent = safeText(text);
    if (title) td.title = title;
    return td;
  }
  function hashCell(value) {
    const td = document.createElement('td');
    const code = document.createElement('div');
    code.className = 'audit-hash';
    code.textContent = shortHash(value);
    code.title = safeText(value);
    td.appendChild(code);
    if (value && value !== '—') {
      const copy = document.createElement('button');
      copy.type = 'button';
      copy.className = 'audit-copy';
      copy.title = 'Copy full value';
      copy.setAttribute('aria-label', 'Copy full hash');
      copy.innerHTML = '<i class="far fa-copy"></i>';
      copy.addEventListener('click', function() {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(String(value)).then(() => showToast('Copied to clipboard')).catch(() => showToast('Could not copy this value'));
        } else {
          const field = document.createElement('textarea');
          field.value = String(value);
          field.style.position = 'fixed';
          field.style.opacity = '0';
          root.appendChild(field);
          field.select();
          try { document.execCommand('copy'); showToast('Copied to clipboard'); } catch (e) { showToast('Could not copy this value'); }
          field.remove();
        }
      });
      td.appendChild(copy);
    }
    return td;
  }
  function statusTag(block) {
    const value = String(block.decrypted_data || '');
    const ok = value !== '' && value !== 'Decryption failed' && value !== 'private key not found';
    const span = document.createElement('span');
    span.className = 'audit-tag ' + (ok ? 'audit-tag-ok' : 'audit-tag-warn');
    const icon = document.createElement('i');
    icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    span.appendChild(icon);
    span.appendChild(document.createTextNode(ok ? 'Payload decrypted' : value === 'Decryption failed' ? 'Decryption failed' : 'Key unavailable'));
    return span;
  }
  function renderRows() {
    const tbody = $('#auditRows');
    tbody.replaceChildren();
    const term = $('#auditSearch').value.trim().toLowerCase();
    const filtered = state.blocks.filter(block => [
      block.block_id, block.timestamp, block.userid, block.decrypted_data,
      block.previous_hash, block.current_hash, block.digital_signature
    ].some(value => String(value == null ? '' : value).toLowerCase().includes(term)));

    $('#auditPageCount').textContent = String(state.blocks.length);
    $('#auditUserCount').textContent = String(new Set(state.blocks.map(block => String(block.userid == null ? 'Unknown' : block.userid))).size);
    $('#auditDecryptedCount').textContent = String(state.blocks.filter(block => {
      const value = String(block.decrypted_data || '');
      return value !== '' && value !== 'Decryption failed' && value !== 'private key not found';
    }).length);
    $('#auditCountLabel').textContent = term ? filtered.length + ' matching · ' + state.blocks.length + ' loaded' : state.blocks.length + ' records loaded';

    if (!filtered.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 7;
      const empty = document.createElement('div');
      empty.className = 'audit-empty';
      const icon = document.createElement('i');
      icon.className = 'fas fa-search';
      empty.appendChild(icon);
      empty.appendChild(document.createTextNode(term ? 'No records match your search on this page.' : 'No blockchain records were returned.'));
      td.appendChild(empty);
      tr.appendChild(td);
      tbody.appendChild(tr);
      return;
    }

    filtered.forEach(block => {
      const tr = document.createElement('tr');
      const blockId = document.createElement('span');
      blockId.className = 'audit-block-id';
      blockId.textContent = '#' + safeText(block.block_id);
      const blockCell = document.createElement('td');
      blockCell.appendChild(blockId);
      tr.appendChild(blockCell);

      tr.appendChild(cell(block.timestamp, 'audit-time'));
      const userTd = document.createElement('td');
      const user = document.createElement('span');
      user.className = 'audit-user';
      const avatar = document.createElement('span');
      avatar.className = 'audit-avatar';
      const userId = safeText(block.userid);
      avatar.textContent = userId === '—' ? '?' : userId.slice(0, 2);
      user.appendChild(avatar);
      user.appendChild(document.createTextNode(userId === '—' ? 'Unknown user' : 'User ' + userId));
      userTd.appendChild(user);
      tr.appendChild(userTd);

      const payloadTd = document.createElement('td');
      const payload = document.createElement('div');
      payload.className = 'audit-payload';
      payload.textContent = safeText(block.decrypted_data);
      payload.title = safeText(block.decrypted_data);
      payloadTd.appendChild(payload);
      payloadTd.appendChild(document.createElement('div')).style.marginTop = '7px';
      payloadTd.lastChild.appendChild(statusTag(block));
      tr.appendChild(payloadTd);

      tr.appendChild(hashCell(block.previous_hash));
      tr.appendChild(hashCell(block.current_hash));
      const signatureTd = document.createElement('td');
      const signature = document.createElement('div');
      signature.className = 'audit-hash';
      signature.textContent = shortHash(block.digital_signature);
      signature.title = safeText(block.digital_signature);
      signatureTd.appendChild(signature);
      tr.appendChild(signatureTd);
      tbody.appendChild(tr);
    });
  }
  function renderPagination() {
    const nav = $('#auditPagination');
    nav.replaceChildren();
    const previous = document.createElement('button');
    previous.type = 'button'; previous.className = 'audit-page'; previous.textContent = '‹'; previous.title = 'Previous page';
    previous.disabled = state.page <= 1;
    previous.addEventListener('click', () => loadPage(state.page - 1));
    nav.appendChild(previous);

    const start = Math.max(1, state.page - 2);
    const end = Math.min(state.totalPages, start + 4);
    for (let page = start; page <= end; page++) {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'audit-page' + (page === state.page ? ' active' : '');
      button.textContent = String(page); button.disabled = page === state.page;
      button.addEventListener('click', () => loadPage(page));
      nav.appendChild(button);
    }
    const next = document.createElement('button');
    next.type = 'button'; next.className = 'audit-page'; next.textContent = '›'; next.title = 'Next page';
    next.disabled = state.page >= state.totalPages;
    next.addEventListener('click', () => loadPage(state.page + 1));
    nav.appendChild(next);
    $('#auditPageLabel').textContent = 'Page ' + state.page + ' of ' + state.totalPages;
  }
  function loadPage(page) {
    const requestId = ++state.request;
    state.page = Math.max(1, page || 1);
    $('#auditRows').innerHTML = '<tr><td colspan="7"><div class="audit-empty"><i class="fas fa-circle-notch fa-spin"></i>Loading ledger records…</div></td></tr>';
    $('#auditCountLabel').textContent = 'Loading ledger…';
    fetch('ajax/ajax_blockchain.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ page: String(state.page) }).toString()
    }).then(response => {
      if (!response.ok) throw new Error('Ledger request failed (' + response.status + ')');
      return response.json();
    }).then(data => {
      if (requestId !== state.request) return;
      if (!data || !Array.isArray(data.blocks)) throw new Error('Unexpected ledger response');
      state.blocks = data.blocks;
      state.page = Number(data.currentPage) || state.page;
      state.totalPages = Math.max(1, Number(data.totalPages) || 1);
      renderRows();
      renderPagination();
    }).catch(error => {
      if (requestId !== state.request) return;
      $('#auditRows').innerHTML = '';
      const tr = document.createElement('tr');
      const td = document.createElement('td'); td.colSpan = 7;
      const empty = document.createElement('div'); empty.className = 'audit-empty';
      const icon = document.createElement('i'); icon.className = 'fas fa-exclamation-triangle';
      empty.appendChild(icon); empty.appendChild(document.createTextNode('Unable to load ledger records. Check the ledger endpoint and database connection.'));
      td.appendChild(empty); tr.appendChild(td); $('#auditRows').appendChild(tr);
      $('#auditCountLabel').textContent = 'Ledger unavailable';
      $('#auditPageLabel').textContent = error.message || 'Request failed';
      $('#auditPagination').replaceChildren();
    });
  }

  $('#auditSearch').addEventListener('input', renderRows);
  $('#auditRefresh').addEventListener('click', () => loadPage(state.page));
  loadPage(1);
})();
</script>
