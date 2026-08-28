/**
 * admin_activity_logs.js
 * Fetches activity log data from ActivityLogController.php.
 * Supports: search, filter by action/date range, pagination, CSV export.
 */

 document.addEventListener('DOMContentLoaded', function () {

    const API_URL   = '../../../backend/controllers/ActivityLogController.php';
    const PAGE_SIZE = 10;

    let currentPage  = 1;
    let totalPages   = 1;
    let totalRecords = 0;

    const searchInput   = document.getElementById('logSearch');
    const filterAction  = document.getElementById('filterAction');
    const filterFrom    = document.getElementById('filterDateFrom');
    const filterTo      = document.getElementById('filterDateTo');
    const resetBtn      = document.getElementById('resetFilters');
    const exportBtn     = document.getElementById('exportCSV');
    const logBody       = document.getElementById('logBody');
    const logEmpty      = document.getElementById('logEmpty');
    const totalCount    = document.getElementById('totalCount');
    const filteredCount = document.getElementById('filteredCount');
    const pageInfo      = document.getElementById('pageInfo');
    const pageNumbers   = document.getElementById('pageNumbers');
    const prevBtn       = document.getElementById('prevPage');
    const nextBtn       = document.getElementById('nextPage');

    const ACTION_CONFIG = {
        approved:    { cls: 'act-approved',  icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>' },
        declined:    { cls: 'act-declined',  icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>' },
        published:   { cls: 'act-published', icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09"/>' },
        flagged:     { cls: 'act-flagged',   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>' },
        deleted:     { cls: 'act-deleted',   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/>' },
        created:     { cls: 'act-created',   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>' },
        updated:     { cls: 'act-updated',   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>' },
        login:       { cls: 'act-login',     icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/>' },
    };

    // ── Helpers ───────────────────────────────────────────

    function getRoleCls(role) {
        return { admin: 'role-admin', moderator: 'role-staff', sk_officer: 'role-staff', resident: 'role-member', system: 'role-member' }[role] ?? 'role-member';
    }

    function formatRole(role) {
        return { admin: 'Admin', moderator: 'Moderator', sk_officer: 'SK Officer', resident: 'Resident', system: 'System' }[role] ?? capitalize(role);
    }

    function getInitials(name) {
        if (!name || name === 'System') return 'SY';
        const parts = name.trim().split(' ');
        return (parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '');
    }

    function parseDateTime(dateStr) {
        if (!dateStr) return { date: '—', time: '—' };
        const d = new Date(dateStr.replace(' ', 'T'));
        return {
            date: d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }),
            time: d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }),
        };
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Converts a description string to human-readable HTML.
     * Handles both plain text and JSON-encoded meta objects produced by ActivityLogModel.
     *
     * JSON shape: { target_type, target_id, target_name, target_user, notes, ... }
     */
    function formatDescription(raw) {
        if (!raw) return '—';

        // Try to parse as JSON (descriptions logged by ActivityLogModel are JSON-encoded)
        let meta;
        try { meta = JSON.parse(raw); } catch { /* not JSON */ }

        if (!meta || typeof meta !== 'object') {
            // Plain-text description — render as-is (may contain safe HTML from ActivityLogController)
            return `<span class="log-desc">${raw}</span>`;
        }

        // Build a readable sentence from known meta keys
        const parts = [];

        if (meta.target_type && meta.target_name) {
            const type = capitalize(meta.target_type.replace(/_/g, ' '));
            parts.push(`<strong>${escHtml(type)}:</strong> "${escHtml(meta.target_name)}"`);
        } else if (meta.target_name) {
            parts.push(`"${escHtml(meta.target_name)}"`);
        }

        if (meta.target_user) {
            parts.push(`by <strong>${escHtml(meta.target_user)}</strong>`);
        }

        if (meta.notes) {
            parts.push(escHtml(meta.notes));
        }

        // Fallback: show all remaining keys if nothing matched
        if (!parts.length) {
            const fallback = Object.entries(meta)
                .filter(([k]) => !['target_id'].includes(k))
                .map(([k, v]) => `${capitalize(k.replace(/_/g, ' '))}: ${escHtml(String(v))}`)
                .join('; ');
            parts.push(fallback || escHtml(raw));
        }

        return `<span class="log-desc">${parts.join(' — ')}</span>`;
    }

    /** Plain-text version of formatDescription for CSV export */
    function formatDescriptionPlain(raw) {
        if (!raw) return '';
        let meta;
        try { meta = JSON.parse(raw); } catch { /* not JSON */ }

        if (!meta || typeof meta !== 'object') {
            return raw.replace(/<[^>]+>/g, '');
        }

        const parts = [];
        if (meta.target_type && meta.target_name) parts.push(`${capitalize(meta.target_type.replace(/_/g, ' '))}: "${meta.target_name}"`);
        else if (meta.target_name) parts.push(`"${meta.target_name}"`);
        if (meta.target_user) parts.push(`by ${meta.target_user}`);
        if (meta.notes) parts.push(meta.notes);

        return parts.length ? parts.join(' — ') : raw.replace(/<[^>]+>/g, '');
    }

    // ── Fetch ─────────────────────────────────────────────

    async function fetchLogs(page = 1) {
        const params = new URLSearchParams({
            action:        'get_logs',
            page,
            page_size:     PAGE_SIZE,
            search:        searchInput.value.trim(),
            action_filter: filterAction.value,
            date_from:     filterFrom.value,
            date_to:       filterTo.value,
        });

        try {
            setLoadingState(true);
            const res  = await fetch(`${API_URL}?${params}`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message ?? 'Unknown error');

            const d      = json.data;
            totalPages   = d.pages;
            totalRecords = d.total;
            currentPage  = d.page;

            render(d.logs, d.total, d.pages, d.page);
        } catch (err) {
            showError('Failed to load activity logs: ' + err.message);
        } finally {
            setLoadingState(false);
        }
    }

    // ── Render ────────────────────────────────────────────

    function render(logs, total, pages, page) {
        totalCount.textContent    = total;
        filteredCount.textContent = total;
        pageInfo.textContent      = `Page ${page} of ${Math.max(1, pages)}`;

        logBody.innerHTML = logs.map(log => {
            const actionConf = ACTION_CONFIG[log.action] ?? ACTION_CONFIG['updated'];
            const dt         = parseDateTime(log.created_at);

            return `
            <tr>
                <td>
                    <div class="log-ts">
                        <span class="log-ts-date">${dt.date}</span>
                        <span class="log-ts-time">${dt.time}</span>
                    </div>
                </td>
                <td>
                    <div class="log-user">
                        <div class="log-avatar">${getInitials(log.user_name)}</div>
                        <span class="log-username">${escHtml(log.user_name)}</span>
                    </div>
                </td>
                <td><span class="log-role-badge ${getRoleCls(log.user_role)}">${formatRole(log.user_role)}</span></td>
                <td>
                    <span class="log-action-badge ${actionConf.cls}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">${actionConf.icon}</svg>
                        ${capitalize(log.action)}
                    </span>
                </td>
                <td>${formatDescription(log.description)}</td>
            </tr>`;
        }).join('');

        logEmpty.style.display = logs.length === 0 ? 'flex' : 'none';
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= pages;
        renderPageNumbers(page, pages);
    }

    function renderPageNumbers(page, pages) {
        pageNumbers.innerHTML = '';
        buildPageRange(page, pages).forEach(p => {
            if (p === '…') {
                const el = document.createElement('span');
                el.textContent = '…';
                el.style.cssText = 'padding:0 4px;color:var(--admin-text-muted);font-size:12px;';
                pageNumbers.appendChild(el);
            } else {
                const btn = document.createElement('button');
                btn.className   = 'log-page-num' + (p === page ? ' active' : '');
                btn.textContent = p;
                btn.addEventListener('click', () => fetchLogs(p));
                pageNumbers.appendChild(btn);
            }
        });
    }

    function buildPageRange(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        if (current <= 4) return [1, 2, 3, 4, 5, '…', total];
        if (current >= total - 3) return [1, '…', total-4, total-3, total-2, total-1, total];
        return [1, '…', current-1, current, current+1, '…', total];
    }

    // ── Loading / error states ────────────────────────────

    function setLoadingState(loading) {
        if (loading) {
            logBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;
                color:var(--admin-text-muted);font-size:13px;">Loading logs…</td></tr>`;
            logEmpty.style.display = 'none';
        }
    }

    function showError(msg) {
        logBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;
            color:#f87171;font-size:13px;">${escHtml(msg)}</td></tr>`;
    }

    // ── Events ────────────────────────────────────────────

    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchLogs(1), 350);
    });
    filterAction.addEventListener('change', () => fetchLogs(1));
    filterFrom.addEventListener('change',   () => fetchLogs(1));
    filterTo.addEventListener('change',     () => fetchLogs(1));

    prevBtn.addEventListener('click', () => { if (currentPage > 1)          fetchLogs(currentPage - 1); });
    nextBtn.addEventListener('click', () => { if (currentPage < totalPages)  fetchLogs(currentPage + 1); });

    resetBtn.addEventListener('click', () => {
        searchInput.value  = '';
        filterAction.value = '';
        filterFrom.value   = '';
        filterTo.value     = '';
        fetchLogs(1);
    });

    // ── CSV Export ────────────────────────────────────────

    exportBtn.addEventListener('click', async () => {
        exportBtn.disabled    = true;
        exportBtn.textContent = 'Exporting…';

        try {
            const params = new URLSearchParams({
                action:        'get_logs',
                page:          1,
                page_size:     10000,
                search:        searchInput.value.trim(),
                action_filter: filterAction.value,
                date_from:     filterFrom.value,
                date_to:       filterTo.value,
            });

            const res  = await fetch(`${API_URL}?${params}`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            const headers = ['Date', 'Time', 'User', 'Role', 'Action', 'Description'];
            const rows    = json.data.logs.map(log => {
                const dt = parseDateTime(log.created_at);
                return [
                    dt.date,
                    dt.time,
                    log.user_name,
                    formatRole(log.user_role),
                    log.action,
                    formatDescriptionPlain(log.description),
                ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(',');
            });

            const csv  = [headers.join(','), ...rows].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `activity_logs_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        } catch (err) {
            alert('Export failed: ' + err.message);
        } finally {
            exportBtn.disabled  = false;
            exportBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg> Export CSV`;
        }
    });

    // ── Init ──────────────────────────────────────────────
    fetchLogs(1);
});