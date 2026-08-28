/* scripts/management/admin/admin_manage_services.js */

document.addEventListener('DOMContentLoaded', () => {

    /* ── STATE ──────────────────────────────────────────── */
    let allServices = [];   // master copy fetched from server

    const categoryIcons = {
        medical:     '🏥',
        education:   '🎓',
        scholarship: '🏅',
        livelihood:  '🛠️',
        assistance:  '🤝',
        legal:       '⚖️',
        other:       '📋',
    };

    /* ── DOM REFS ────────────────────────────────────────── */
    const searchInput    = document.getElementById('svc-search');
    const categorySelect = document.getElementById('svc-category');
    const statusSelect   = document.getElementById('svc-status');
    const grid           = document.getElementById('svc-grid');
    const noResults      = document.getElementById('no-results');
    const overlay        = document.getElementById('svc-modal-overlay');
    const btnSave        = document.getElementById('btn-save');

    /* ── FETCH / LOAD ────────────────────────────────────── */
    async function loadServices() {
        grid.innerHTML = '<div class="svc-loading">Loading services…</div>';
        noResults.style.display = 'none';

        try {
            const res  = await fetch(`${SVC_API}?action=list`);
            const json = await res.json();

            if (json.status !== 'success') throw new Error(json.message);

            allServices = json.data;
            updateStats();
            renderGrid(allServices);
        } catch (err) {
            grid.innerHTML = `<div class="svc-no-results"><p>Failed to load services: ${err.message}</p></div>`;
        }
    }

    /* ── STATS ───────────────────────────────────────────── */
    function updateStats() {
        const active   = allServices.filter(s => s.status === 'active').length;
        const inactive = allServices.filter(s => s.status === 'inactive').length;
        document.getElementById('stat-active').textContent   = active;
        document.getElementById('stat-inactive').textContent = inactive;
        document.getElementById('stat-total').textContent    = allServices.length;
    }

    /* ── RENDER GRID ─────────────────────────────────────── */
    function renderGrid(services) {
        grid.innerHTML = '';

        if (services.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        services.forEach(s => {
            const icon     = categoryIcons[s.category] ?? '📋';
            const badgeCls = s.status === 'active' ? 'badge-active' : 'badge-inactive';
            const badgeTxt = s.status === 'active' ? 'Active' : 'Inactive';

            const cap = s.max_capacity
                ? `${s.current_count} / ${s.max_capacity}`
                : 'Unlimited';

            const card = document.createElement('article');
            card.className = 'svc-card';
            card.dataset.category = s.category;
            card.dataset.status   = s.status;
            card.dataset.name     = (s.name ?? '').toLowerCase();

            card.innerHTML = `
                <div class="svc-card-body">
                    <div class="svc-card-top">
                        <div class="svc-icon-wrap svc-icon-${s.category}">${icon}</div>
                        <span class="svc-badge ${badgeCls}">${badgeTxt}</span>
                    </div>
                    <h3 class="svc-card-title">${escHtml(s.name)}</h3>
                    <p class="svc-card-excerpt">${escHtml(s.description)}</p>
                    <ul class="svc-details">
                        <li>
                            <span class="svc-detail-label">Eligibility</span>
                            <span class="svc-detail-value">${escHtml(s.eligibility ?? '—')}</span>
                        </li>
                        <li>
                            <span class="svc-detail-label">Processing</span>
                            <span class="svc-detail-value">${escHtml(s.processing_time ?? '—')}</span>
                        </li>
                        <li>
                            <span class="svc-detail-label">Required</span>
                            <span class="svc-detail-value">${escHtml(s.requirements ?? '—')}</span>
                        </li>
                        <li>
                            <span class="svc-detail-label">Capacity</span>
                            <span class="svc-detail-value">${escHtml(cap)}</span>
                        </li>
                    </ul>
                    <div class="svc-card-actions">
                        <button class="btn-svc-primary" onclick="openEditModal(${s.id})">✏️ Edit</button>
                        <button class="btn-svc-danger"  onclick="deleteService(${s.id}, '${escAttr(s.name)}')">🗑️ Delete</button>
                        <span class="svc-cat-tag tag-${s.category}">${capitalize(s.category)}</span>
                    </div>
                </div>`;

            grid.appendChild(card);
        });
    }

    /* ── CLIENT-SIDE FILTER ──────────────────────────────── */
    function filterCards() {
        const query    = searchInput.value.toLowerCase().trim();
        const category = categorySelect.value;
        const status   = statusSelect.value;

        const filtered = allServices.filter(s => {
            const matchSearch   = !query    || (s.name ?? '').toLowerCase().includes(query);
            const matchCategory = category === 'all' || s.category === category;
            const matchStatus   = status   === 'all' || s.status   === status;
            return matchSearch && matchCategory && matchStatus;
        });

        renderGrid(filtered);
    }

    searchInput?.addEventListener('input',     filterCards);
    categorySelect?.addEventListener('change', filterCards);
    statusSelect?.addEventListener('change',   filterCards);

    /* ── MODAL — ADD ─────────────────────────────────────── */
    function openAddModal() {
        document.getElementById('modal-title').textContent        = 'Add Service';
        document.getElementById('modal-icon').textContent         = '📋';
        document.getElementById('serviceId').value                = '';
        document.getElementById('serviceName').value              = '';
        document.getElementById('serviceCategory').value          = 'medical';
        document.getElementById('serviceType').value              = 'document';
        document.getElementById('serviceDescription').value       = '';
        document.getElementById('serviceApprovalMessage').value   = '';
        document.getElementById('serviceEligibility').value       = '';
        document.getElementById('serviceTime').value              = '';
        document.getElementById('serviceRequirements').value      = '';
        document.getElementById('serviceMaxCapacity').value       = '';
        document.getElementById('serviceStatus').value            = 'active';
        clearErrors();
        overlay.classList.add('is-open');
    }

    /* ── MODAL — EDIT ────────────────────────────────────── */
    function openEditModal(id) {
        const s = allServices.find(x => x.id == id);
        if (!s) { showToast('Service not found.', 'error'); return; }

        document.getElementById('modal-title').textContent        = 'Edit Service';
        document.getElementById('modal-icon').textContent         = categoryIcons[s.category] ?? '📋';
        document.getElementById('serviceId').value                = s.id;
        document.getElementById('serviceName').value              = s.name             ?? '';
        document.getElementById('serviceCategory').value          = s.category         ?? 'medical';
        document.getElementById('serviceType').value              = s.service_type     ?? 'document';
        document.getElementById('serviceDescription').value       = s.description      ?? '';
        document.getElementById('serviceApprovalMessage').value   = s.approval_message ?? '';
        document.getElementById('serviceEligibility').value       = s.eligibility      ?? '';
        document.getElementById('serviceTime').value              = s.processing_time  ?? '';
        document.getElementById('serviceRequirements').value      = s.requirements     ?? '';
        document.getElementById('serviceMaxCapacity').value       = s.max_capacity     ?? '';
        document.getElementById('serviceStatus').value            = s.status           ?? 'active';
        clearErrors();
        overlay.classList.add('is-open');
    }

    /* ── MODAL — CLOSE ───────────────────────────────────── */
    function closeModal() {
        overlay.classList.remove('is-open');
    }

    overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // Update icon when category changes
    document.getElementById('serviceCategory')?.addEventListener('change', function () {
        document.getElementById('modal-icon').textContent = categoryIcons[this.value] ?? '📋';
    });

    /* ── VALIDATION ──────────────────────────────────────── */
    function clearErrors() {
        document.querySelectorAll('.svc-field-error').forEach(el => el.textContent = '');
        document.getElementById('serviceName')?.classList.remove('is-error');
    }

    function validateService() {
        clearErrors();
        let valid = true;
        const name = document.getElementById('serviceName').value.trim();
        if (!name) {
            document.getElementById('err-name').textContent = 'Service name is required.';
            document.getElementById('serviceName').classList.add('is-error');
            valid = false;
        }
        return valid;
    }

    /* ── SAVE (CREATE or UPDATE) ─────────────────────────── */
    async function saveService() {
        if (!validateService()) return;

        const id = document.getElementById('serviceId').value;
        const isEdit = id !== '';

        const payload = {
            id:               isEdit ? parseInt(id) : undefined,
            name:             document.getElementById('serviceName').value.trim(),
            category:         document.getElementById('serviceCategory').value,
            service_type:     document.getElementById('serviceType').value,
            description:      document.getElementById('serviceDescription').value.trim(),
            approval_message: document.getElementById('serviceApprovalMessage').value.trim(),
            eligibility:      document.getElementById('serviceEligibility').value.trim(),
            processing_time:  document.getElementById('serviceTime').value.trim(),
            requirements:     document.getElementById('serviceRequirements').value.trim(),
            max_capacity:     document.getElementById('serviceMaxCapacity').value.trim(),
            status:           document.getElementById('serviceStatus').value,
        };

        const action = isEdit ? 'update' : 'create';

        btnSave.disabled = true;
        btnSave.textContent = '⏳ Saving…';

        try {
            const res  = await fetch(`${SVC_API}?action=${action}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const json = await res.json();

            if (json.status !== 'success') throw new Error(json.message);

            showToast(isEdit ? '✏️ Service updated!' : '✅ Service added!', 'success');
            closeModal();
            await loadServices();
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            btnSave.disabled    = false;
            btnSave.textContent = '💾 Save Service';
        }
    }

    /* ── DELETE ──────────────────────────────────────────── */
    async function deleteService(id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

        try {
            const res  = await fetch(`${SVC_API}?action=delete`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id }),
            });
            const json = await res.json();

            if (json.status !== 'success') throw new Error(json.message);

            showToast(`🗑️ "${name}" deleted!`, 'error');
            await loadServices();
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    /* ── TOAST ───────────────────────────────────────────── */
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className   = `svc-toast toast-${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    /* ── UTILS ───────────────────────────────────────────── */
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str ?? '').replace(/'/g, "\\'");
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    /* ── EXPOSE TO GLOBAL SCOPE (PHP onclick handlers) ───── */
    window.openAddModal  = openAddModal;
    window.openEditModal = openEditModal;
    window.closeModal    = closeModal;
    window.saveService   = saveService;
    window.deleteService = deleteService;

    /* ── INIT ────────────────────────────────────────────── */
    loadServices();
});