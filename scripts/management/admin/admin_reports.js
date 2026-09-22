document.addEventListener('DOMContentLoaded', () => {

    let allReports    = [];
    let currentReport = null;
    let activeFilters = { type: 'all', reason: 'all', status: 'all' };

    const REPORT_API = window.REPORT_API || '../../../backend/routes/admin_reports.php';
    const THREAD_API = window.THREAD_API || '../../../backend/controllers/AdminGetThreadController.php';

    const searchInput = document.getElementById('report-search');
    const tbody        = document.getElementById('report-tbody');
    const noResults     = document.getElementById('no-results');

    const panelBackdrop = document.getElementById('arp-panel-backdrop');
    const panel          = document.getElementById('arp-panel');
    const panelClose      = document.getElementById('arp-panel-close');
    const panelLoading     = document.getElementById('arp-panel-loading');
    const panelContent      = document.getElementById('arp-panel-content');
    const panelBadges        = document.getElementById('arp-panel-badges');
    const panelBodyScroll      = document.getElementById('arp-panel-body-scroll');

    const lightbox     = document.getElementById('arp-lightbox');
    const lightboxImg   = document.getElementById('arp-lightbox-img');
    const lightboxClose  = document.getElementById('arp-lightbox-close');

    const sanctionModal    = document.getElementById('arp-sanction-modal');
    const modalAuthorName   = document.getElementById('arp-modal-author-name');
    const modalRecommended   = document.getElementById('arp-modal-recommended');
    const modalReason          = document.getElementById('arp-modal-reason');
    const modalWarnLvl3          = document.getElementById('arp-modal-warn-lvl3');
    const modalSubmit             = document.getElementById('arp-modal-submit');
    const modalSubmitLabel         = document.getElementById('arp-modal-submit-label');

    const confirmOverlay = document.getElementById('rpt-confirm-overlay');
    const confirmTitle     = document.getElementById('rpt-confirm-title');
    const confirmBody        = document.getElementById('rpt-confirm-body');
    const confirmOk             = document.getElementById('rpt-confirm-ok');
    const confirmCancel           = document.getElementById('rpt-confirm-cancel');

    const reasonIcons = { spam: '🚫', inappropriate: '⚠️', harassment: '😡', misinformation: '❌', other: '📋' };

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

    function initials(name) {
        return (name || '?').trim().charAt(0).toUpperCase();
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatDateTime(d) {
        return new Date(d).toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    async function loadReports() {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:var(--ap-text-muted); font-family:'Poppins',sans-serif; font-size:13px;">Loading reports…</td></tr>`;
        noResults.style.display = 'none';

        try {
            const res  = await fetch(`${REPORT_API}?action=list`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            allReports = json.data;
            updateStats();
            filterRows();
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:var(--ap-danger); font-family:'Poppins',sans-serif; font-size:13px;">Failed to load reports: ${escHtml(err.message)}</td></tr>`;
        }
    }

    function updateStats() {
        document.getElementById('stat-total').textContent     = allReports.length;
        document.getElementById('stat-pending').textContent   = allReports.filter(r => r.status === 'pending').length;
        document.getElementById('stat-reviewed').textContent  = allReports.filter(r => r.status === 'reviewed').length;
        document.getElementById('stat-dismissed').textContent = allReports.filter(r => r.status === 'dismissed').length;
    }

    function typeLabel(type) {
        return type === 'thread' ? 'Thread' : type === 'reply' ? 'Reply' : 'Comment';
    }

    function renderTable(reports) {
        tbody.innerHTML = '';

        if (reports.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        reports.forEach((r, idx) => {
            const icon = reasonIcons[r.reason] ?? '📋';
            const badgeType = r.type === 'reply' ? 'comment' : r.type;

            const tr = document.createElement('tr');
            tr.className = 'rpt-row';

            tr.innerHTML = `
                <td class="rpt-td-id">${idx + 1}</td>
                <td><span class="rpt-type-badge type-${badgeType}">${typeLabel(r.type)}</span></td>
                <td class="rpt-td-content">
                    <span class="rpt-content-title">${escHtml(r.content || '(deleted)')}</span>
                    ${r.content_removed ? `<span class="rpt-removed-tag"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>Removed</span>` : ''}
                </td>
                <td>${escHtml(r.reported_by || '—')}</td>
                <td>${escHtml(r.author || '—')}</td>
                <td><span class="rpt-reason-badge reason-${r.reason}">${icon} ${capitalize(r.reason)}</span></td>
                <td class="rpt-td-date">${formatDate(r.date)}</td>
                <td><span class="rpt-status-badge status-${r.status}">${capitalize(r.status)}</span></td>
                <td>
                    <button class="btn-rpt-review" data-id="${r.id}" data-type="${r.type}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Review
                    </button>
                </td>`;

            tr.querySelector('.btn-rpt-review').addEventListener('click', () => openPanel(r));
            tbody.appendChild(tr);
        });
    }

    function filterRows() {
        const query = searchInput.value.toLowerCase().trim();

        const filtered = allReports.filter(r => {
            const matchSearch = !query
                || (r.content     ?? '').toLowerCase().includes(query)
                || (r.reported_by ?? '').toLowerCase().includes(query)
                || (r.author      ?? '').toLowerCase().includes(query);
            const matchType   = activeFilters.type   === 'all' || r.type   === activeFilters.type;
            const matchReason = activeFilters.reason === 'all' || r.reason === activeFilters.reason;
            const matchStatus = activeFilters.status === 'all' || r.status === activeFilters.status;
            return matchSearch && matchType && matchReason && matchStatus;
        });

        renderTable(filtered);
    }

    searchInput?.addEventListener('input', filterRows);

    document.querySelectorAll('.rpt-filter-select').forEach(select => {
        select.addEventListener('change', () => {
            activeFilters[select.dataset.filterType] = select.value;
            filterRows();
        });
    });

    document.querySelectorAll('.rpt-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.dataset.filterType;
            document.querySelectorAll(`.rpt-filter-btn[data-filter-type="${group}"]`).forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilters[group] = btn.dataset.filter;
            filterRows();
        });
    });

    function categoryColor(reason) {
        const map = {
            spam: { bg: '#fee2e2', color: '#b91c1c' },
            inappropriate: { bg: '#ffedd5', color: '#c2410c' },
            harassment: { bg: '#ffe4e6', color: '#9f1239' },
            misinformation: { bg: '#ede9fe', color: '#5b21b6' },
        };
        return map[reason] || { bg: '#f1f5f9', color: '#475569' };
    }

    function statusColor(status) {
        const map = {
            pending:   { bg: '#fef3c7', color: '#92400e' },
            reviewed:  { bg: '#d1fae5', color: '#065f46' },
            dismissed: { bg: '#f1f5f9', color: '#64748b' },
        };
        return map[status] || { bg: '#f1f5f9', color: '#64748b' };
    }

    function renderPanelBadges(report) {
        const cat = categoryColor(report.reason);
        const stat = statusColor(report.status);
        panelBadges.innerHTML = `
            <span class="rpt-type-badge type-${report.type === 'reply' ? 'comment' : report.type}">${typeLabel(report.type)}</span>
            <span class="arp-panel-badge" style="background:${cat.bg};color:${cat.color};">${capitalize(report.reason)}</span>
            <span class="arp-panel-badge" style="background:${stat.bg};color:${stat.color};">${capitalize(report.status)}</span>
        `;
    }

    function renderReportBanner(report) {
        const banner = document.getElementById('arp-report-banner');
        banner.innerHTML = `
            <div class="arp-report-banner">
                <span class="arp-report-banner-label">Reported Content</span>
                <span class="arp-report-banner-cat">${capitalize(report.reason)} — reported by ${escHtml(report.reported_by || 'a resident')}</span>
                <span class="arp-report-banner-meta">Submitted ${formatDateTime(report.date)}</span>
                ${report.details ? `<span class="arp-report-banner-note">${escHtml(report.details)}</span>` : ''}
            </div>
        `;
    }

    function renderPanelActions(report) {
        const actionsWrap = document.getElementById('arp-panel-actions');
        const actioned = report.status !== 'pending';

        actionsWrap.innerHTML = `
            <div class="arp-panel-action-row">
                <button class="arp-panel-btn arp-btn-ignore" id="arp-act-ignore" ${actioned ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Dismiss
                </button>
                <button class="arp-panel-btn arp-btn-sanction" id="arp-act-sanction" ${actioned ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    Issue Sanction
                </button>
                <button class="arp-panel-btn arp-btn-delete" id="arp-act-delete" ${actioned || report.content_removed ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    Delete Content
                </button>
            </div>
            ${actioned ? `<p class="rpt-actioned-label">This report has already been ${report.status}.</p>` : ''}
            ${!actioned && report.content_removed ? `<p class="rpt-actioned-label">The reported content has already been removed.</p>` : ''}
        `;

        if (!actioned) {
            document.getElementById('arp-act-ignore').addEventListener('click', () => {
                openConfirm('Dismiss Report', 'This report will be marked as dismissed with no action taken on the user or content.', async () => {
                    await postAction('ignore', { id: report.id, type: report.type });
                });
            });
            document.getElementById('arp-act-sanction').addEventListener('click', () => openSanctionModal(report));
            if (!report.content_removed) {
                document.getElementById('arp-act-delete').addEventListener('click', () => {
                    openConfirm('Delete Content', 'This will hide the reported content from residents. This cannot be undone from here.', async () => {
                        await postAction('delete_content', { id: report.id, type: report.type, target_id: report.target_id });
                    });
                });
            }
        }
    }

    function renderCommentNode(c, report, isReply = false) {
        const isTarget = (report.type === 'comment' && !isReply && c.id == report.target_id)
                       || (report.type === 'reply'   && isReply && c.id == report.target_id);
        return `
            <div class="${isReply ? 'arp-panel-reply-item' : 'arp-panel-comment-item'} ${isTarget ? 'arp-reported-highlight arp-highlight-pulse' : ''}">
                <div class="arp-panel-comment-avatar ${isReply ? 'arp-panel-reply-avatar' : ''}">${initials(c.author_name)}</div>
                <div class="arp-panel-comment-body">
                    <div class="arp-panel-comment-header">
                        <span class="arp-panel-comment-author">${escHtml(c.author_name)}</span>
                        ${c.is_mod_comment ? '<span class="arp-comment-mod-badge">Mod</span>' : ''}
                        ${isTarget ? '<span class="arp-reported-tag">Reported</span>' : ''}
                        <span class="arp-panel-comment-date">${formatDateTime(c.created_at)}</span>
                    </div>
                    <p class="arp-panel-comment-text">${escHtml(c.message)}</p>
                </div>
            </div>
        `;
    }

    function renderComments(comments, report) {
        const list  = document.getElementById('arp-panel-comment-list');
        const count = document.getElementById('arp-panel-comments-count');

        if (!comments || comments.length === 0) {
            list.innerHTML = '<p class="arp-panel-no-comments">No comments yet.</p>';
            count.textContent = '0';
            return;
        }

        count.textContent = comments.length;

        list.innerHTML = comments.map(c => {
            const repliesHtml = (c.replies && c.replies.length)
                ? `<div class="arp-panel-replies">${c.replies.map(r => renderCommentNode(r, report, true)).join('')}</div>`
                : '';
            return renderCommentNode(c, report, false) + repliesHtml;
        }).join('');

        const target = list.querySelector('.arp-reported-highlight');
        if (target) {
            requestAnimationFrame(() => target.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        }
    }

    async function openPanel(report) {
        currentReport = report;

        renderPanelBadges(report);
        renderReportBanner(report);
        renderPanelActions(report);

        panelContent.style.display = 'none';
        panelLoading.style.display = 'flex';
        panelBackdrop.classList.add('open');
        panel.classList.add('open');
        document.body.classList.add('arp-panel-open');
        panelBodyScroll.scrollTop = 0;

        try {
            const res  = await fetch(`${THREAD_API}?id=${report.thread_id}`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            const { thread, images, comments } = json;

            document.getElementById('arp-panel-title').textContent = thread.subject;
            document.getElementById('arp-panel-meta').innerHTML = `
                <div class="arp-panel-author">
                    <div class="arp-panel-avatar">${initials(thread.author_name)}</div>
                    <div>
                        <span class="arp-panel-author-name">${escHtml(thread.author_name)}</span>
                        <span class="arp-panel-date">${formatDateTime(thread.created_at)}</span>
                    </div>
                </div>
                <div class="arp-panel-counts">
                    <span>${thread.comment_count ?? 0} comments</span>
                    <span>${thread.support_count ?? 0} supports</span>
                </div>
            `;
            document.getElementById('arp-panel-body-text').textContent = thread.message;

            const imagesWrap = document.getElementById('arp-panel-images');
            if (images && images.length) {
                imagesWrap.style.display = 'grid';
                imagesWrap.innerHTML = images.map(img => `
                    <div class="arp-panel-image-item" data-src="../../../${img.file_path}">
                        <img src="../../../${img.file_path}" alt="${escHtml(img.file_name || '')}">
                    </div>
                `).join('');
                imagesWrap.querySelectorAll('.arp-panel-image-item').forEach(item => {
                    item.addEventListener('click', () => {
                        lightboxImg.src = item.dataset.src;
                        lightbox.style.display = 'flex';
                    });
                });
            } else {
                imagesWrap.style.display = 'none';
                imagesWrap.innerHTML = '';
            }

            renderComments(comments, report);

            panelLoading.style.display = 'none';
            panelContent.style.display = 'block';
        } catch (err) {
            panelLoading.innerHTML = `<span style="color:var(--ap-danger);">Could not load thread: ${escHtml(err.message)}</span>`;
        }
    }

    function closePanel() {
        panelBackdrop.classList.remove('open');
        panel.classList.remove('open');
        document.body.classList.remove('arp-panel-open');
        currentReport = null;
    }

    panelClose?.addEventListener('click', closePanel);
    panelBackdrop?.addEventListener('click', closePanel);

    lightboxClose?.addEventListener('click', () => { lightbox.style.display = 'none'; });
    lightbox?.addEventListener('click', e => { if (e.target === lightbox) lightbox.style.display = 'none'; });

    function openSanctionModal(report) {
        modalAuthorName.textContent = report.author || '—';
        modalReason.value = '';

        const currentLevel = parseInt(report.author_sanction_level || 0, 10);
        const recommended = Math.min(currentLevel + 1, 3);
        document.querySelector(`input[name="arp-level"][value="${recommended}"]`).checked = true;

        const levelText = { 1: 'Warning', 2: '7-Day Ban', 3: 'Permanent Ban' };
        modalRecommended.textContent = currentLevel > 0
            ? `This user currently has an active Level ${currentLevel} sanction. Recommended next step: Level ${recommended} (${levelText[recommended]}).`
            : `This user has no active sanctions. Recommended: Level ${recommended} (${levelText[recommended]}).`;

        toggleLvl3Warning();
        sanctionModal.classList.add('open');
    }

    function toggleLvl3Warning() {
        const selected = document.querySelector('input[name="arp-level"]:checked').value;
        modalWarnLvl3.style.display = selected === '3' ? 'flex' : 'none';
        modalSubmitLabel.textContent = selected === '3' ? 'Confirm Permanent Ban' : 'Confirm Sanction';
    }

    document.querySelectorAll('input[name="arp-level"]').forEach(input => {
        input.addEventListener('change', toggleLvl3Warning);
    });

    function closeSanctionModal() {
        sanctionModal.classList.remove('open');
    }

    document.getElementById('arp-modal-close')?.addEventListener('click', closeSanctionModal);
    document.getElementById('arp-modal-cancel')?.addEventListener('click', closeSanctionModal);

    modalSubmit?.addEventListener('click', async () => {
        if (!currentReport) return;
        const level  = parseInt(document.querySelector('input[name="arp-level"]:checked').value, 10);
        const reason = modalReason.value.trim();

        modalSubmit.disabled = true;
        try {
            await postAction('sanction', {
                id: currentReport.id,
                type: currentReport.type,
                target_id: currentReport.target_id,
                author_id: currentReport.author_id,
                level,
                reason,
            });
            closeSanctionModal();
        } finally {
            modalSubmit.disabled = false;
        }
    });

    let confirmCallback = null;

    function openConfirm(title, body, onConfirm) {
        confirmTitle.textContent = title;
        confirmBody.textContent  = body;
        confirmCallback = onConfirm;
        confirmOverlay.classList.add('open');
    }

    function closeConfirm() {
        confirmOverlay.classList.remove('open');
        confirmCallback = null;
    }

    confirmCancel?.addEventListener('click', closeConfirm);
    confirmOk?.addEventListener('click', async () => {
        if (confirmCallback) await confirmCallback();
        closeConfirm();
    });

    async function postAction(action, payload) {
        try {
            const res  = await fetch(`${REPORT_API}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            const r = allReports.find(x => x.id == payload.id && x.type === payload.type);
            if (r) {
                if (action === 'ignore') r.status = 'dismissed';
                if (action === 'delete_content') { r.status = 'reviewed'; r.content_removed = true; }
                if (action === 'sanction') {
                    r.status = 'reviewed';
                    if (json.data?.content_removed) r.content_removed = true;
                }
                currentReport = r;
                renderPanelBadges(r);
                renderReportBanner(r);
                renderPanelActions(r);
            }

            showToast(json.message || 'Done.', 'success');
            updateStats();
            filterRows();
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    function showToast(msg, type = 'success') {
        const toast = document.getElementById('rpt-toast');
        toast.textContent = msg;
        toast.className = `rpt-toast toast-${type} show`;
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (sanctionModal.classList.contains('open')) { closeSanctionModal(); return; }
        if (lightbox.style.display === 'flex') { lightbox.style.display = 'none'; return; }
        if (confirmOverlay.classList.contains('open')) { closeConfirm(); return; }
        if (panel.classList.contains('open')) closePanel();
    });

    loadReports();
});