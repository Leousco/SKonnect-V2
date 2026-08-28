/* scripts/management/admin/admin_reports.js */

document.addEventListener('DOMContentLoaded', () => {

    /* ── STATE ───────────────────────────────────────────── */
    let allReports    = [];
    let currentReport = null;

    const REPORT_API = window.REPORT_API || '../../../backend/routes/admin_reports.php';

    /* ── DOM REFS ────────────────────────────────────────── */
    const searchInput  = document.getElementById('report-search');
    const typeSelect   = document.getElementById('report-type');
    const reasonSelect = document.getElementById('report-reason');
    const statusSelect = document.getElementById('report-status');
    const tbody        = document.getElementById('report-tbody');
    const noResults    = document.getElementById('no-results');
    const overlay      = document.getElementById('report-modal-overlay');

    /* ── ICON / LABEL MAPS ───────────────────────────────── */
    const reasonIcons = {
        spam:           '🚫',
        inappropriate:  '⚠️',
        harassment:     '😡',
        misinformation: '❌',
        other:          '📋',
    };

    /* ── LOAD ────────────────────────────────────────────── */
    async function loadReports() {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center; padding:2rem;
                    color:var(--ap-text-muted); font-family:'Poppins',sans-serif; font-size:13px;">
                    Loading reports…
                </td>
            </tr>`;
        noResults.style.display = 'none';

        try {
            const res  = await fetch(`${REPORT_API}?action=list`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            allReports = json.data;
            updateStats();
            renderTable(allReports);
        } catch (err) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem;
                        color:var(--ap-danger); font-family:'Poppins',sans-serif; font-size:13px;">
                        Failed to load reports: ${err.message}
                    </td>
                </tr>`;
        }
    }

    /* ── STATS ───────────────────────────────────────────── */
    function updateStats() {
        document.getElementById('stat-total').textContent     = allReports.length;
        document.getElementById('stat-pending').textContent   = allReports.filter(r => r.status === 'pending').length;
        document.getElementById('stat-reviewed').textContent  = allReports.filter(r => r.status === 'reviewed').length;
        document.getElementById('stat-dismissed').textContent = allReports.filter(r => r.status === 'dismissed').length;
    }

    /* ── RENDER TABLE ────────────────────────────────────── */
    function renderTable(reports) {
        tbody.innerHTML = '';

        if (reports.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        reports.forEach((r, idx) => {
            const icon    = reasonIcons[r.reason] ?? '📋';
            const typeLabel = r.type === 'thread'
                ? '🧵 Thread'
                : r.type === 'reply' ? '↩️ Reply' : '💬 Comment';
            const date = new Date(r.date).toLocaleDateString('en-PH', {
                year: 'numeric', month: 'short', day: 'numeric'
            });

            const tr = document.createElement('tr');
            tr.className = 'rpt-row';
            tr.dataset.type     = r.type;
            tr.dataset.reason   = r.reason;
            tr.dataset.status   = r.status;
            tr.dataset.content  = (r.content  ?? '').toLowerCase();
            tr.dataset.reporter = (r.reported_by ?? '').toLowerCase();

            tr.innerHTML = `
                <td class="rpt-td-id">${idx + 1}</td>
                <td>
                    <span class="rpt-type-badge type-${r.type === 'reply' ? 'comment' : r.type}">
                        ${typeLabel}
                    </span>
                </td>
                <td class="rpt-td-content">
                    <span class="rpt-content-title">${escHtml(r.content)}</span>
                </td>
                <td>
                    <span class="rpt-reason-badge reason-${r.reason}">
                        ${icon} ${capitalize(r.reason)}
                    </span>
                </td>
                <td class="rpt-td-date">${date}</td>
                <td>
                    <span class="rpt-status-badge status-${r.status}">
                        ${capitalize(r.status)}
                    </span>
                </td>
                <td>
                    <button class="btn-rpt-review" data-id="${r.id}" onclick="openReportModal(${r.id})">
                        👁️ Review
                    </button>
                </td>`;

            tbody.appendChild(tr);
        });
    }

    /* ── CLIENT-SIDE FILTER ──────────────────────────────── */
    function filterRows() {
        const query  = searchInput.value.toLowerCase().trim();
        const type   = typeSelect.value;
        const reason = reasonSelect.value;
        const status = statusSelect.value;

        const filtered = allReports.filter(r => {
            const matchSearch = !query
                || (r.content    ?? '').toLowerCase().includes(query)
                || (r.reported_by ?? '').toLowerCase().includes(query);
            const matchType   = type   === 'all' || r.type   === type;
            const matchReason = reason === 'all' || r.reason  === reason;
            const matchStatus = status === 'all' || r.status  === status;
            return matchSearch && matchType && matchReason && matchStatus;
        });

        renderTable(filtered);
    }

    searchInput?.addEventListener('input',   filterRows);
    typeSelect?.addEventListener('change',   filterRows);
    reasonSelect?.addEventListener('change', filterRows);
    statusSelect?.addEventListener('change', filterRows);

    /* ── OPEN MODAL ──────────────────────────────────────── */
    function openReportModal(id) {
        const r = allReports.find(x => x.id == id);
        if (!r) { showToast('Report not found.', 'error'); return; }

        currentReport = r;

        const icon = reasonIcons[r.reason] ?? '⚠️';
        const date = new Date(r.date).toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const typeLabel = r.type === 'thread'
            ? '🧵 Thread'
            : r.type === 'reply' ? '↩️ Reply' : '💬 Comment';

        document.getElementById('report-modal-icon').textContent     = icon;
        document.getElementById('report-modal-title').textContent    = r.content;
        document.getElementById('report-modal-subtitle').textContent = `${typeLabel} · Reported ${date}`;
        document.getElementById('report-modal-reporter').textContent = r.reported_by ?? '—';
        document.getElementById('report-modal-author').textContent   = r.author      ?? '—';
        document.getElementById('report-modal-reason').textContent   = `${icon} ${capitalize(r.reason)}`;
        document.getElementById('report-modal-date').textContent     = date;
        document.getElementById('report-modal-excerpt').textContent  = r.excerpt  ? `"${r.excerpt}"` : '—';
        document.getElementById('report-modal-details').textContent  = r.details  || 'No additional details provided.';
        document.getElementById('report-admin-note').value           = '';

        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    /* ── CLOSE MODAL ─────────────────────────────────────── */
    function closeReportModal() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        currentReport = null;
    }

    overlay?.addEventListener('click', e => { if (e.target === overlay) closeReportModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReportModal(); });

    /* ── ACTIONS ─────────────────────────────────────────── */
    async function reportAction(action) {
        if (!currentReport) return;

        const note = document.getElementById('report-admin-note').value.trim();

        // Confirm destructive actions
        if (action === 'ban') {
            if (!confirm(`Ban "${currentReport.author}"? This cannot be undone.`)) return;
        }
        if (action === 'delete_content') {
            if (!confirm('Delete this post? This cannot be undone.')) return;
        }

        const payload = {
            id:        currentReport.id,
            type:      currentReport.type,
            target_id: currentReport.target_id,
            author_id: currentReport.author_id,
            note,
        };

        const toastLabels = {
            ignore:         '✅ Report dismissed.',
            warn:           '⚠️ Warning issued to user.',
            delete_content: '❌ Post deleted.',
            ban:            '🚫 User has been banned.',
        };
        const toastTypes = {
            ignore:         'info',
            warn:           'info',
            delete_content: 'error',
            ban:            'error',
        };

        try {
            const res  = await fetch(`${REPORT_API}?action=${action}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            // Update local status so UI reflects change without full reload
            const r = allReports.find(x => x.id == currentReport.id);
            if (r) {
                r.status = action === 'ignore' ? 'dismissed' : 'reviewed';
            }

            showToast(toastLabels[action] ?? 'Done.', toastTypes[action] ?? 'info');
            closeReportModal();
            updateStats();
            filterRows();

        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    /* ── TOAST ───────────────────────────────────────────── */
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className   = `rpt-toast toast-${type}`;
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

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    /* ── EXPOSE GLOBALS ──────────────────────────────────── */
    window.openReportModal  = openReportModal;
    window.closeReportModal = closeReportModal;
    window.reportAction     = reportAction;

    /* ── INIT ────────────────────────────────────────────── */
    loadReports();
});