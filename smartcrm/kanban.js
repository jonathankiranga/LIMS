(function () {
    const container = document.getElementById('kanban');
    if (!container) return;

    const statuses = window.SMARTCRM_STATUSES || {};
    const priorities = window.SMARTCRM_PRIORITIES || {};
    let tasks = window.SMARTCRM_TASKS || [];
    const updateUrl = container.dataset.updateUrl || 'api.php';
    const scopeSelect = document.getElementById('scopeFilter');
    let currentScope = 'my';
    const addBtn = document.getElementById('add-activity');
    const stageInput = document.getElementById('stageDays');
    const dueInput = document.getElementById('dueDays');
    let isDragging = false;

    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.innerHTML = `
        <div class="modal-sheet">
            <h3 id="modal-title">Add Task / Activity / Contact</h3>
            <form id="activity-form">
                <div class="modal-grid">
                    <div>
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="Activity or task name">
                    </div>
                    <div>
                        <label>Owner</label>
                        <select name="owner" id="ownerSelect">
                            <option value=""><?php echo _('Unassigned'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" required>
                            ${Object.entries(statuses).map(([k,v]) => `<option value="${k}">${v}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label>Priority</label>
                        <select name="priority">
                            ${Object.entries(priorities).map(([k,v]) => `<option value="${k}">${v}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label>Action type</label>
                        <select name="action_type">
                            <option value="">General / none</option>
                            <option value="Call">Schedule a call</option>
                            <option value="Text">Send a text message</option>
                            <option value="Email">Send an email</option>
                            <option value="Meeting">Plan a meeting</option>
                            <option value="Demo">Product demo</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label>From date</label>
                        <input type="date" name="fromdue">
                    </div>
                    <div>
                        <label>To / due date</label>
                        <input type="date" name="todue">
                    </div>
                    <div>
                        <label>Repeat every</label>
                        <div class="repeat-row">
                            <input type="number" name="recur_every" min="1" placeholder="e.g. 1">
                            <select name="recur_unit">
                                <option value="">No repeat</option>
                                <option value="hour">Hour(s)</option>
                                <option value="day">Day(s)</option>
                                <option value="week">Week(s)</option>
                                <option value="month">Month(s)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label>End after (occurrences)</label>
                        <input type="number" name="recur_count" min="1" placeholder="Leave blank for no limit">
                    </div>
                    <div>
                        <label>End by date</label>
                        <input type="date" name="recur_until">
                    </div>
                    <div>
                        <label>Contact / company</label>
                        <input type="text" name="contact" list="contact-list" placeholder="Contact or company name">
                        <input type="hidden" name="contact_id">
                    </div>
                    <div>
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Where is this happening?">
                    </div>
                    <div>
                        <label>Value (optional)</label>
                        <input type="number" step="0.01" name="value" placeholder="Value of business">
                    </div>
                    <div>
                        <label>Related to (auto)</label>
                        <input type="text" name="related_title" readonly placeholder="Click a card to link an activity">
                        <input type="hidden" name="related_id">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label>Details</label>
                        <textarea name="details" placeholder="Notes, agenda, follow-up, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" id="cancel-modal" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
            <datalist id="contact-list"></datalist>
        </div>
    `;
    document.body.appendChild(modal);

    // lightweight contacts card for preview
    const contactsCard = document.createElement('div');
    contactsCard.className = 'contacts-card';
    contactsCard.innerHTML = `
        <header>
            <span>Contacts</span>
            <div>
                <button class="btn btn-sm btn-primary" id="addContactBtn">Add</button>
                <button class="close-card" aria-label="Close">&times;</button>
            </div>
        </header>
        <div class="body" id="contactsCardBody"></div>
    `;
    document.body.appendChild(contactsCard);
    const contactsCardBody = contactsCard.querySelector('#contactsCardBody');
    const closeCardBtn = contactsCard.querySelector('.close-card');
    if (closeCardBtn) closeCardBtn.addEventListener('click', () => contactsCard.style.display = 'none');
    const addContactBtn = contactsCard.querySelector('#addContactBtn');

    // Contact editor modal
    const contactModal = document.createElement('div');
    contactModal.className = 'modal-backdrop';
    contactModal.innerHTML = `
        <div class="modal-sheet">
            <h3 id="contactModalTitle">Contact</h3>
            <form id="contactForm">
                <input type="hidden" name="id">
                <div class="modal-grid">
                    <div><label>Company</label><input type="text" name="company"></div>
                    <div><label>Contact name</label><input type="text" name="Contact_Name"></div>
                    <div><label>Designation</label><input type="text" name="Contact_Designation"></div>
                    <div><label>Phone</label><input type="text" name="Contact_Telephone"></div>
                    <div><label>Email</label><input type="email" name="Contact_email"></div>
                    <div><label>Alt contact</label><input type="text" name="Alt_Contact_Name"></div>
                    <div><label>Alt phone</label><input type="text" name="Alt_Contact_Telephone"></div>
                    <div><label>Alt email</label><input type="email" name="Alt_Contact_email"></div>
                    <div><label>City</label><input type="text" name="city"></div>
                    <div><label>Country</label><input type="text" name="country"></div>
                    <div><label>PIN / VAT</label><input type="text" name="PIN_VAT"></div>
                    <div><label>Address</label><input type="text" name="Physical_Address"></div>
                </div>
                <div class="modal-actions">
                    <button type="button" id="contactCancel" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(contactModal);
    const contactForm = contactModal.querySelector('#contactForm');
    const contactModalTitle = contactModal.querySelector('#contactModalTitle');
    const contactCancel = contactModal.querySelector('#contactCancel');

    const modalTitle = modal.querySelector('#modal-title');
    const relatedIdInput = modal.querySelector('input[name=\"related_id\"]');
    const relatedTitleInput = modal.querySelector('input[name=\"related_title\"]');
    const detailsInput = modal.querySelector('textarea[name=\"details\"]');
    const titleInput = modal.querySelector('input[name=\"title\"]');
    const contactInput = modal.querySelector('input[name=\"contact\"]');
    const contactIdInput = modal.querySelector('input[name=\"contact_id\"]');
    const contactList = modal.querySelector('#contact-list');
    let contactsCache = [];
    const ownerSelect = modal.querySelector('#ownerSelect');
    let usersCache = [];

    function showModal() {
        modal.classList.add('show');
        if (titleInput) titleInput.focus();
    }
    function hideModal() {
        modal.classList.remove('show');
    }
    function resetModalForNew() {
        if (!formEl) return;
        formEl.reset();
        if (modalTitle) modalTitle.textContent = 'Add Task / Activity / Contact';
        if (relatedIdInput) relatedIdInput.value = '';
        if (relatedTitleInput) relatedTitleInput.value = '';
        if (contactIdInput) contactIdInput.value = '';
        if (detailsInput) detailsInput.placeholder = 'Notes, agenda, follow-up, etc.';
    }
    function openModalForTask(task) {
        if (!formEl) return;
        formEl.reset();
        if (modalTitle) modalTitle.textContent = `Log task for: ${task.title || 'Activity'}`;
        if (relatedIdInput) relatedIdInput.value = task.id || '';
        if (relatedTitleInput) relatedTitleInput.value = task.title || '';
        if (detailsInput) detailsInput.placeholder = 'Notes for ' + (task.title || 'this activity');
        if (contactInput && contactIdInput) {
            const match = resolveContactId(contactInput.value);
            contactIdInput.value = match || '';
        }
        showModal();
    }

    function resolveContactId(name) {
        if (!name) return '';
        const lower = name.toLowerCase();
        const match = contactsCache.find(c => (c.name || '').toLowerCase() === lower);
        return match ? match.id : '';
    }

    function renderContactOptions() {
        if (!contactList) return;
        contactList.innerHTML = '';
        if (!contactsCache.length) {
            const opt = document.createElement('option');
            opt.value = 'No data';
            contactList.appendChild(opt);
        } else {
            contactsCache.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                contactList.appendChild(opt);
            });
        }
    }

    function showContactsCard() {
        if (!contactsCard || !contactsCardBody) return;
        contactsCardBody.innerHTML = '';
        if (!contactsCache.length) {
            const div = document.createElement('div');
            div.className = 'empty';
            div.textContent = 'No data';
            contactsCardBody.appendChild(div);
        } else {
            contactsCache.forEach(c => {
                const div = document.createElement('div');
                div.className = 'item';
                div.textContent = `${c.name} (${c.id})`;
                div.dataset.id = c.id;
                div.addEventListener('click', () => openContactEditor(c.id));
                contactsCardBody.appendChild(div);
            });
        }
        contactsCard.style.display = 'block';
    }

    function hideContactModal() {
        contactModal.classList.remove('show');
    }

    async function openContactEditor(id) {
        contactForm.reset();
        contactForm.querySelector('input[name=\"id\"]').value = id || '';
        contactModalTitle.textContent = id ? 'Edit contact' : 'Add contact';
        if (id) {
            try {
                const resp = await fetch(`${updateUrl}?action=getContact&id=${id}`, { credentials: 'same-origin' });
                const data = await resp.json();
                if (data && data.data) {
                    Object.keys(data.data).forEach(k => {
                        const input = contactForm.querySelector(`[name=\"${k}\"]`);
                        if (input) input.value = data.data[k] || '';
                    });
                }
            } catch (e) {
                alert('Could not load contact');
            }
        }
        contactModal.classList.add('show');
    }

    function renderUserOptions() {
        if (!ownerSelect) return;
        ownerSelect.innerHTML = '';
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Unassigned';
        ownerSelect.appendChild(defaultOpt);
        usersCache.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name ? `${u.name} (${u.id})` : u.id;
            ownerSelect.appendChild(opt);
        });
    }

    async function loadContacts() {
        try {
            const resp = await fetch(`${updateUrl}?action=listContacts`, { credentials: 'same-origin' });
            if (!resp.ok) return;
            const data = await resp.json();
            contactsCache = Array.isArray(data.data) ? data.data : [];
            renderContactOptions();
        } catch (e) {
            // silent fail; contact quick-add still works server-side
        }
    }

    async function loadUsers() {
        try {
            const resp = await fetch(`${updateUrl}?action=listUsers`, { credentials: 'same-origin' });
            if (!resp.ok) return;
            const data = await resp.json();
            usersCache = Array.isArray(data.data) ? data.data : [];
            renderUserOptions();
        } catch (e) {
            // ignore; falls back to empty select
        }
    }

    if (contactInput) {
        contactInput.addEventListener('input', () => {
            if (!contactIdInput) return;
            contactIdInput.value = resolveContactId(contactInput.value.trim());
        });
    }

    function buildBoard() {
        container.innerHTML = '';
        Object.entries(statuses).forEach(([statusKey, label]) => {
            const col = document.createElement('div');
            col.className = 'kanban-col';
            col.dataset.status = statusKey;

            const header = document.createElement('header');
            const count = tasks.filter(t => t.status === statusKey).length;
            header.innerHTML = `<span>${label}</span><span class="pill">${count}</span>`;
            col.appendChild(header);

            const list = document.createElement('div');
            list.className = 'kanban-list';
            list.addEventListener('dragover', handleDragOver);
            list.addEventListener('dragleave', handleDragLeave);
            list.addEventListener('drop', handleDrop);
            col.appendChild(list);

            container.appendChild(col);
        });

        renderCards();
    }

    function renderCards() {
        const lists = container.querySelectorAll('.kanban-list');
        lists.forEach(list => list.innerHTML = '');

        tasks.forEach(task => {
            const list = container.querySelector(`.kanban-col[data-status="${task.status}"] .kanban-list`);
            if (!list) return;
            list.appendChild(renderCard(task));
        });

        lists.forEach(list => {
            if (!list.children.length) {
                const empty = document.createElement('div');
                empty.className = 'empty-drop';
                empty.textContent = 'Drop here';
                list.appendChild(empty);
            }
        });
    }

    function renderCard(task) {
        const card = document.createElement('div');
        card.className = 'kanban-card';
        card.draggable = true;
        card.dataset.id = task.id;

        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        card.addEventListener('click', (e) => {
            if (isDragging) return;
            openModalForTask(task);
        });

        const priorityTag = document.createElement('span');
        priorityTag.className = `tag priority-${task.priority}`;
        priorityTag.textContent = priorities[task.priority] || 'N/A';

        const ownerTag = document.createElement('span');
        ownerTag.className = 'tag';
        ownerTag.textContent = task.owner || 'Unassigned';

        card.innerHTML = `
            <h4>${escapeHtml(task.title || '')}</h4>
            <div class="meta-row">
                <span>${task.due ? `Due ${task.due}` : 'No due date'}</span>
            </div>
        `;
        const metaRow = card.querySelector('.meta-row');
        metaRow.appendChild(priorityTag);
        metaRow.appendChild(ownerTag);

        if (typeof task.daysInStage === 'number') {
            const badge = document.createElement('span');
            badge.className = 'tag';
            badge.textContent = `${task.daysInStage}d in stage`;
            metaRow.appendChild(badge);
        } else if (typeof task.daysToDue === 'number') {
            const badge = document.createElement('span');
            badge.className = 'tag';
            badge.textContent = `${task.daysToDue}d to due`;
            metaRow.appendChild(badge);
        }

        if (task.details) {
            const desc = document.createElement('div');
            desc.className = 'meta-row';
            desc.textContent = task.details.length > 120 ? task.details.substring(0, 117) + '…' : task.details;
            card.appendChild(desc);
        }

        return card;
    }

    let dragData = null;

    function handleDragStart(e) {
        dragData = { id: e.target.dataset.id };
        isDragging = true;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragData.id);
    }

    function handleDragEnd() {
        container.querySelectorAll('.dropping').forEach(el => el.classList.remove('dropping'));
        dragData = null;
        isDragging = false;
    }

    function handleDragOver(e) {
        e.preventDefault();
        this.classList.add('dropping');
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDragLeave() {
        this.classList.remove('dropping');
    }

    async function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('dropping');
        const status = this.parentElement.dataset.status;
        const id = dragData ? dragData.id : e.dataTransfer.getData('text/plain');
        if (!id || status === undefined) return;

        const card = container.querySelector(`.kanban-card[data-id="${id}"]`);
        if (card) this.appendChild(card);

        try {
            await updateStatus(id, status);
            tasks = tasks.map(t => t.id == id ? { ...t, status } : t);
            buildBoard();
        } catch (err) {
            alert('Could not update status. Please try again.');
        }
    }

    async function updateStatus(id, status) {
        const body = new URLSearchParams();
        body.append('action', 'updateTaskStatus');
        body.append('id', id);
        body.append('status', status);

        const resp = await fetch(updateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        if (!resp.ok) throw new Error('Network error');
        const data = await resp.json();
        if (!data.ok) throw new Error('Server error');
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[s]));
    }

    async function refresh() {
        const params = new URLSearchParams();
        params.append('action', 'listTasks');
        params.append('scope', currentScope);
        if (stageInput && stageInput.value) params.append('stageDays', stageInput.value);
        if (dueInput && dueInput.value) params.append('dueDays', dueInput.value);

        const resp = await fetch(`${updateUrl}?${params.toString()}`, { credentials: 'same-origin' });
        if (!resp.ok) return;
        const data = await resp.json();
        if (data && data.data) {
            tasks = data.data;
            buildBoard();
        }
    }

    if (scopeSelect) {
        scopeSelect.value = currentScope;
        scopeSelect.addEventListener('change', () => {
            currentScope = scopeSelect.value || 'all';
            refresh();
        });
    }

    if (stageInput) stageInput.addEventListener('change', refresh);
    if (dueInput) dueInput.addEventListener('change', refresh);

    // Menu actions
    const menuListContacts = document.getElementById('menuListContacts');
    if (menuListContacts) {
        menuListContacts.addEventListener('click', async () => {
            try {
                await loadContacts();
                showContactsCard();
            } catch (e) {
                alert('Could not load contacts');
            }
        });
    }
    if (addContactBtn) {
        addContactBtn.addEventListener('click', () => openContactEditor(null));
    }
    if (contactCancel) {
        contactCancel.addEventListener('click', hideContactModal);
    }
    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);
            formData.append('action', 'saveContact');
            try {
                const resp = await fetch(updateUrl, {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                });
                const json = await resp.json();
                if (!resp.ok || !json.ok) throw new Error(json.error || 'Save failed');
                hideContactModal();
                await loadContacts();
                showContactsCard();
            } catch (err) {
                alert(err.message || 'Could not save contact');
            }
        });
    }

    const refreshBtn = document.getElementById('refresh-board');
    if (refreshBtn) refreshBtn.addEventListener('click', refresh);
    if (addBtn) addBtn.addEventListener('click', () => { resetModalForNew(); showModal(); });

    // AJAX logout similar to blockchain style
    const logoutLink = document.querySelector('a[href="Logout.php"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch('ajax/logout.php', { method: 'POST', credentials: 'same-origin' });
            } catch (err) {
                // ignore errors; proceed to redirect
            }
            window.location.href = 'index.php';
        });
    }

    const cancelBtn = modal.querySelector('#cancel-modal');
    if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

    const formEl = modal.querySelector('#activity-form');
    if (formEl) {
        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            data.append('action', 'createActivity');
            // derive single due date preference
            const due = data.get('todue') || data.get('fromdue') || '';
            data.set('due', due);
            try {
                const resp = await fetch(updateUrl, {
                    method: 'POST',
                    body: new URLSearchParams(data),
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                });
                const json = await resp.json();
                if (!resp.ok || !json.ok) throw new Error(json.error || 'Server error');
                hideModal();
                form.reset();
                if (modalTitle) modalTitle.textContent = 'Add Task / Activity / Contact';
                if (relatedIdInput) relatedIdInput.value = '';
                if (relatedTitleInput) relatedTitleInput.value = '';
                if (contactIdInput) contactIdInput.value = '';
                await refresh();
            } catch (err) {
                alert(err.message || 'Could not save activity');
            }
        });
    }

    loadContacts();
    loadUsers();
    buildBoard();
    refresh();

    // expose logout similar to blockchain homepage
    window.logout = function() {
        try {
            localStorage.clear();
            localStorage.setItem('saygoodbye', 'Good Bye');
        } catch (e) {}
        window.location.href = 'Logout.php';
    };
})();
