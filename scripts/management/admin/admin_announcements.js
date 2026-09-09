/* admin_announcements.js — fully connected to DB */

document.addEventListener('DOMContentLoaded', function () {

    const LIST_URL   = '../../../backend/routes/announcements_list.php';
    const ACTION_URL = '../../../backend/routes/announcement_action.php';

    let attachmentFiles = [];
    let editingId = null;

    /* ══════════════════════════════════════════════════════════
       TAB SWITCHING
       ══════════════════════════════════════════════════════════ */
    const tabs   = document.querySelectorAll('.ann-tab');
    const panels = {
        list:    document.getElementById('panel-list'),
        drafts:  document.getElementById('panel-drafts'),
        archive: document.getElementById('panel-archive'),
        create:  document.getElementById('panel-create'),
    };

    function switchTab(target) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === target));
        Object.entries(panels).forEach(([key, el]) => { if (el) el.classList.toggle('ann-panel--hidden', key !== target); });
        if (target === 'list')    listCtrl.load(1);
        if (target === 'drafts')  draftsCtrl.load(1);
        if (target === 'archive') archiveCtrl.load(1);
        if (target === 'create')  updateCreatePanelMode();
    }

    tabs.forEach(tab => tab.addEventListener('click', () => {
        if (tab.dataset.tab !== 'create' && editingId) { resetEditState(); resetForm(); }
        switchTab(tab.dataset.tab);
    }));

    /* ══════════════════════════════════════════════════════════
       SHARED TABLE HELPERS
       ══════════════════════════════════════════════════════════ */
    const CAT_COLORS = {
        event:   { bg: '#d1fae5', color: '#065f46', cls: 'cat-event' },
        program: { bg: '#dbeafe', color: '#1d4ed8', cls: 'cat-program' },
        meeting: { bg: '#ede9fe', color: '#5b21b6', cls: 'cat-meeting' },
        notice:  { bg: '#fef3c7', color: '#92400e', cls: 'cat-notice' },
        urgent:  { bg: '#fee2e2', color: '#b91c1c', cls: 'cat-urgent' },
    };

    function thumbHtml(cat, muted) {
        const c = CAT_COLORS[cat] || CAT_COLORS.notice;
        const bg = muted ? '#f1f5f9' : c.bg;
        const stroke = muted ? '#94a3b8' : c.color;
        return `<div class="ann-thumb" style="background:${bg};">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="${stroke}"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>
        </div>`;
    }

    function catPillHtml(cat) {
        const c = CAT_COLORS[cat] || CAT_COLORS.notice;
        return `<span class="ann-cat-pill ${c.cls}">${ucFirst(cat)}</span>`;
    }

    function excerptHtml(content) {
        return escHtml((content || '').replace(/<[^>]*>/g, '').substring(0, 100)) + '…';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function renderStats(s) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? 0; };
        set('stat-active',   s.active);
        set('stat-draft',    s.draft);
        set('stat-featured', s.featured);
        set('stat-urgent',   s.urgent);
        set('stat-archived', s.archived);
    }

    function postAction(body) {
        return fetch(ACTION_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        }).then(r => r.json()).then(json => {
            if (json.status !== 'success') showToast('Error: ' + (json.message || 'Unknown'), 'error');
            return json;
        });
    }

    function refreshAll() { listCtrl.load(); draftsCtrl.load(); archiveCtrl.load(); }

    function handleRowAction(e) {
        const btn    = e.currentTarget;
        const action = btn.dataset.action;
        const id     = parseInt(btn.dataset.id);

        if (action === 'edit') return openEditForm(id);
        if (action === 'toggle-featured') return postAction({ action: 'toggle-featured', id }).then(refreshAll);
        if (action === 'archive') {
            return postAction({ action: 'set-status', id, status: 'archived' })
                .then(() => { showToast('Announcement archived.', 'info'); refreshAll(); });
        }
        if (action === 'restore') {
            return postAction({ action: 'set-status', id, status: 'draft' })
                .then(() => { showToast('Restored to Draft.', 'success'); refreshAll(); });
        }
        if (action === 'delete') {
            confirm(`Delete "${btn.dataset.title}"?`, 'This cannot be undone.', () => {
                postAction({ action: 'delete', id }).then(() => { showToast('Announcement deleted.', 'error'); refreshAll(); });
            });
        }
    }

    /* ══════════════════════════════════════════════════════════
       GENERIC LIST CONTROLLER (one per tab)
       ══════════════════════════════════════════════════════════ */
    function createListController(opts) {
        const els = {
            tbody:   document.getElementById(`${opts.prefix}-table-body`),
            search:  document.getElementById(`${opts.prefix}-search`),
            cat:     document.getElementById(`${opts.prefix}-filter-category`),
            sort:    document.getElementById(`${opts.prefix}-filter-sort`),
            numbers: document.getElementById(`${opts.prefix}-page-numbers`),
            prev:    document.getElementById(`${opts.prefix}-btn-prev`),
            next:    document.getElementById(`${opts.prefix}-btn-next`),
        };
        let page = 1, searchTimer = null;

        function load(p) {
            page = p || page;
            const params = new URLSearchParams({
                search:   els.search?.value.trim() || '',
                category: els.cat?.value || '',
                status:   opts.status,
                sort:     els.sort?.value || 'newest',
                page,
            });
            if (els.tbody) els.tbody.innerHTML = `<tr><td colspan="${opts.colspan}" style="text-align:center;padding:32px;color:var(--ap-text-muted);">Loading…</td></tr>`;

            fetch(`${LIST_URL}?${params}`)
                .then(r => r.json())
                .then(json => {
                    if (json.status !== 'success') {
                        if (els.tbody) els.tbody.innerHTML = `<tr><td colspan="${opts.colspan}" style="text-align:center;padding:24px;color:var(--ap-danger);">Error loading data.</td></tr>`;
                        return;
                    }
                    const { rows, stats, pages } = json.data;
                    renderStats(stats);
                    renderRows(rows);
                    renderPagination(pages, page);
                })
                .catch(() => { if (els.tbody) els.tbody.innerHTML = `<tr><td colspan="${opts.colspan}" style="text-align:center;padding:24px;color:var(--ap-danger);">Network error.</td></tr>`; });
        }

        function renderRows(rows) {
            if (!els.tbody) return;
            if (!rows.length) {
                els.tbody.innerHTML = `<tr><td colspan="${opts.colspan}" style="text-align:center;padding:32px;color:var(--ap-text-muted);">${opts.emptyMsg}</td></tr>`;
                return;
            }
            els.tbody.innerHTML = rows.map(opts.renderRow).join('');
            els.tbody.querySelectorAll('[data-action]').forEach(el => el.addEventListener('click', handleRowAction));
        }

        function renderPagination(pages, current) {
            if (!els.prev || !els.next || !els.numbers) return;
            els.prev.disabled = current <= 1;
            els.next.disabled = current >= pages;
            els.numbers.innerHTML = '';
            for (let i = 1; i <= pages; i++) {
                const b = document.createElement('button');
                b.className   = `ann-page-num${i === current ? ' active' : ''}`;
                b.textContent = i;
                b.addEventListener('click', () => load(i));
                els.numbers.appendChild(b);
            }
            els.prev.onclick = () => load(current - 1);
            els.next.onclick = () => load(current + 1);
        }

        els.search?.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => load(1), 350); });
        els.cat?.addEventListener('change', () => load(1));
        els.sort?.addEventListener('change', () => load(1));

        return { load };
    }

    /* ── PUBLISHED TAB ─────────────────────────────────────── */
    const listCtrl = createListController({
        prefix: 'list', status: 'active', colspan: 6, emptyMsg: 'No published announcements found.',
        renderRow: (row) => {
            const isFeat = parseInt(row.featured) === 1;
            const featDot = isFeat
                ? '<span class="ann-featured-dot dot-yes" title="Featured" style="cursor:pointer;" data-action="toggle-featured" data-id="' + row.id + '">★</span>'
                : '<span class="ann-featured-dot dot-no" title="Not featured" style="cursor:pointer;" data-action="toggle-featured" data-id="' + row.id + '">☆</span>';
            return `
            <tr class="ann-row${isFeat ? ' ann-row--featured' : ''}" data-id="${row.id}">
                <td>${thumbHtml(row.category)}</td>
                <td>
                    <div class="ann-title-cell"><span class="ann-title-text">${escHtml(row.title)}</span></div>
                    <span class="ann-excerpt">${excerptHtml(row.content)}</span>
                </td>
                <td>${catPillHtml(row.category)}</td>
                <td>${featDot}</td>
                <td class="ann-date">${formatDate(row.published_at)}</td>
                <td>
                    <div class="ann-row-actions">
                        <button class="row-action-btn btn-edit" title="Edit" data-action="edit" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        <button class="row-action-btn btn-archive" title="Archive" data-action="archive" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                        </button>
                        <button class="row-action-btn btn-delete" title="Delete" data-action="delete" data-id="${row.id}" data-title="${escHtml(row.title)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        },
    });

    /* ── DRAFTS TAB ────────────────────────────────────────── */
    const draftsCtrl = createListController({
        prefix: 'drafts', status: 'draft', colspan: 5, emptyMsg: 'No drafts found.',
        renderRow: (row) => `
            <tr class="ann-row" data-id="${row.id}">
                <td>${thumbHtml(row.category)}</td>
                <td>
                    <div class="ann-title-cell"><span class="ann-title-text">${escHtml(row.title)}</span></div>
                    <span class="ann-excerpt">${excerptHtml(row.content)}</span>
                </td>
                <td>${catPillHtml(row.category)}</td>
                <td class="ann-date">${formatDate(row.updated_at || row.published_at)}</td>
                <td>
                    <div class="ann-row-actions">
                        <button class="row-action-btn btn-edit" title="Edit Draft" data-action="edit" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        <button class="row-action-btn btn-archive" title="Archive Draft" data-action="archive" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                        </button>
                        <button class="row-action-btn btn-delete" title="Delete Permanently" data-action="delete" data-id="${row.id}" data-title="${escHtml(row.title)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`,
    });

    /* ── ARCHIVED TAB ──────────────────────────────────────── */
    const archiveCtrl = createListController({
        prefix: 'archive', status: 'archived', colspan: 6, emptyMsg: 'Archive is empty.',
        renderRow: (row) => `
            <tr class="ann-row ann-row--archived" data-id="${row.id}">
                <td>${thumbHtml(row.category, true)}</td>
                <td>
                    <div class="ann-title-cell"><span class="ann-title-text ann-title-text--archived">${escHtml(row.title)}</span></div>
                    <span class="ann-excerpt">${excerptHtml(row.content)}</span>
                </td>
                <td>${catPillHtml(row.category)}</td>
                <td class="ann-date">${formatDate(row.published_at)}</td>
                <td class="ann-date">${formatDate(row.archived_at || row.updated_at)}</td>
                <td>
                    <div class="ann-row-actions">
                        <button class="row-action-btn btn-restore" title="Restore to Draft" data-action="restore" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        </button>
                        <button class="row-action-btn btn-delete" title="Delete Permanently" data-action="delete" data-id="${row.id}" data-title="${escHtml(row.title)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`,
    });

    /* ══════════════════════════════════════════════════════════
       CREATE / EDIT FORM
       ══════════════════════════════════════════════════════════ */

    /* ── LIVE PREVIEW: TITLE ──────────────────────────────── */
    const titleInput   = document.getElementById('ann-title');
    const previewTitle = document.getElementById('preview-title');
    const charCount    = document.getElementById('title-char');
    const checkTitle   = document.getElementById('check-title');

    titleInput?.addEventListener('input', function () {
        const val = this.value.trim();
        previewTitle.textContent = val || 'Your announcement title will appear here…';
        if (charCount) charCount.textContent = `${this.value.length} / 120`;
        toggleCheck(checkTitle, val.length > 0);
    });

    /* ── LIVE PREVIEW: BODY (contenteditable) ─────────────── */
    const bodyEl         = document.getElementById('ann-body');
    const previewExcerpt = document.getElementById('preview-excerpt');
    const checkBody      = document.getElementById('check-body');

    if (bodyEl) {
        bodyEl.addEventListener('input', () => {
            const val = bodyEl.innerText.trim();
            previewExcerpt.textContent = val ? val.slice(0, 160) + (val.length > 160 ? '…' : '') : 'The announcement body text will be summarised here for the card view.';
            toggleCheck(checkBody, val.length > 0);
        });
    }

    document.querySelectorAll('.toolbar-btn[data-cmd]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.execCommand(btn.dataset.cmd, false, null);
            bodyEl?.focus();
        });
    });

    /* ── LIVE PREVIEW: CATEGORY ───────────────────────────── */
    const catPillColors = {
        event:   'background:#d1fae5;color:#065f46;',
        program: 'background:#dbeafe;color:#1d4ed8;',
        meeting: 'background:#ede9fe;color:#5b21b6;',
        notice:  'background:#fef3c7;color:#92400e;',
        urgent:  'background:#fee2e2;color:#b91c1c;',
    };
    const previewCatPill = document.getElementById('preview-cat-pill');
    const checkCategory  = document.getElementById('check-category');

    document.querySelectorAll('input[name="category"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (previewCatPill) {
                previewCatPill.textContent = ucFirst(this.value);
                previewCatPill.setAttribute('style', catPillColors[this.value] || '');
            }
            toggleCheck(checkCategory, true);
        });
    });

    /* ── FEATURED TOGGLE ──────────────────────────────────── */
    const featuredCheckbox   = document.getElementById('featured-checkbox');
    const featuredToggleCard = document.getElementById('featured-toggle-card');
    const previewFeatBadge   = document.getElementById('preview-featured-badge');

    featuredCheckbox?.addEventListener('change', function () {
        featuredToggleCard?.classList.toggle('is-featured', this.checked);
        if (previewFeatBadge) previewFeatBadge.style.display = this.checked ? 'inline-flex' : 'none';
    });

    /* ── BANNER UPLOAD ─────────────────────────────────────── */
    const bannerFile       = document.getElementById('banner-file');
    const bannerDropZone   = document.getElementById('banner-drop-zone');
    const bannerDropInner  = document.getElementById('banner-drop-inner');
    const bannerPreview    = document.getElementById('banner-preview');
    const bannerPreviewImg = document.getElementById('banner-preview-img');
    const bannerRemoveBtn  = document.getElementById('banner-remove');
    const prevBannerImg    = document.getElementById('preview-banner-img');
    const prevBannerPH     = document.getElementById('preview-banner-placeholder');
    const checkBanner      = document.getElementById('check-banner');
    let bannerFileObj      = null;

    function loadBanner(file) {
        if (!file || !file.type.startsWith('image/')) return;
        bannerFileObj = file;
        const url = URL.createObjectURL(file);
        if (bannerDropInner) bannerDropInner.style.display = 'none';
        if (bannerPreview)   bannerPreview.style.display   = 'block';
        if (bannerPreviewImg) bannerPreviewImg.src = url;
        if (prevBannerImg)   { prevBannerImg.src = url; prevBannerImg.style.display = 'block'; }
        if (prevBannerPH)    prevBannerPH.style.display = 'none';
        toggleCheck(checkBanner, true);
    }

    function loadBannerFromUrl(url) {
        if (bannerDropInner) bannerDropInner.style.display = 'none';
        if (bannerPreview)   bannerPreview.style.display   = 'block';
        if (bannerPreviewImg) bannerPreviewImg.src = url;
        if (prevBannerImg)   { prevBannerImg.src = url; prevBannerImg.style.display = 'block'; }
        if (prevBannerPH)    prevBannerPH.style.display = 'none';
        toggleCheck(checkBanner, true);
    }

    bannerFile?.addEventListener('change', function () { if (this.files[0]) loadBanner(this.files[0]); });

    bannerRemoveBtn?.addEventListener('click', () => {
        bannerFileObj = null;
        if (bannerDropInner) bannerDropInner.style.display = '';
        if (bannerPreview)   bannerPreview.style.display   = 'none';
        if (bannerPreviewImg){ bannerPreviewImg.src = ''; bannerPreviewImg.style.display = 'none'; }
        if (prevBannerPH)    prevBannerPH.style.display = '';
        if (bannerFile)      bannerFile.value = '';
        toggleCheck(checkBanner, false);
    });

    bannerDropZone?.addEventListener('dragover', e => { e.preventDefault(); bannerDropZone.classList.add('drag-over'); });
    bannerDropZone?.addEventListener('dragleave', () => bannerDropZone.classList.remove('drag-over'));
    bannerDropZone?.addEventListener('drop', e => { e.preventDefault(); bannerDropZone.classList.remove('drag-over'); if (e.dataTransfer.files[0]) loadBanner(e.dataTransfer.files[0]); });

    /* ── ATTACHMENTS ───────────────────────────────────────── */
    const attachFileInput = document.getElementById('attach-files');
    const attachList      = document.getElementById('attach-list');
    const attachDropZone  = document.getElementById('attach-drop-zone');
    const prevAttachRow   = document.getElementById('preview-attach-row');
    const prevAttachCount = document.getElementById('preview-attach-count');

    function getFileType(name) {
        const ext = name.split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'pdf';
        if (['doc','docx'].includes(ext)) return 'doc';
        if (['xls','xlsx'].includes(ext)) return 'xls';
        return 'img';
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function renderAttachments() {
        if (!attachList) return;
        attachList.innerHTML = '';
        attachmentFiles.forEach((file, idx) => {
            const type = getFileType(file.name);
            const li   = document.createElement('li');
            li.className = 'ann-attach-item';
            li.innerHTML = `
                <div class="attach-icon attach-icon--${type}">${type.toUpperCase()}</div>
                <div class="attach-meta">
                    <span class="attach-name">${escHtml(file.name)}</span>
                    <span class="attach-size">${formatSize(file.size)}</span>
                </div>
                <button type="button" class="attach-remove-btn" data-idx="${idx}" title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>`;
            attachList.appendChild(li);
        });
        attachList.querySelectorAll('.attach-remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                attachmentFiles.splice(parseInt(this.dataset.idx), 1);
                renderAttachments();
            });
        });
        if (prevAttachRow) {
            prevAttachRow.style.display = attachmentFiles.length > 0 ? 'flex' : 'none';
            if (prevAttachCount) prevAttachCount.textContent = `${attachmentFiles.length} attachment${attachmentFiles.length !== 1 ? 's' : ''}`;
        }
    }

    function addFiles(fileList) {
        Array.from(fileList).forEach(f => attachmentFiles.push(f));
        renderAttachments();
    }

    attachFileInput?.addEventListener('change', function () { addFiles(this.files); this.value = ''; });
    attachDropZone?.addEventListener('dragover', e => { e.preventDefault(); attachDropZone.style.borderColor = 'var(--ap-lt)'; });
    attachDropZone?.addEventListener('dragleave', () => { attachDropZone.style.borderColor = ''; });
    attachDropZone?.addEventListener('drop', e => { e.preventDefault(); attachDropZone.style.borderColor = ''; addFiles(e.dataTransfer.files); });

    /* ── PUBLISH DATE DEFAULT ──────────────────────────────── */
    const publishDateInput = document.getElementById('ann-publish-date');
    const previewDate      = document.getElementById('preview-date');
    if (publishDateInput) {
        publishDateInput.value = new Date().toISOString().split('T')[0];
        publishDateInput.addEventListener('change', function () {
            if (previewDate && this.value) {
                previewDate.textContent = new Date(this.value + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        });
    }

    /* ── CANCEL BUTTON (injected, mirrors officer) ────────── */
    const btnPublish    = document.getElementById('btn-publish');
    const btnDraft      = document.getElementById('btn-save-draft');
    const formActionsEl = document.querySelector('.ann-form-actions');
    let btnCancel = null;
    if (formActionsEl) {
        btnCancel = document.createElement('button');
        btnCancel.type = 'button';
        btnCancel.className = 'btn-ann-cancel';
        btnCancel.id = 'btn-cancel-form';
        btnCancel.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg> Cancel`;
        formActionsEl.insertBefore(btnCancel, formActionsEl.firstChild);
        btnCancel.addEventListener('click', () => { resetEditState(); resetForm(); switchTab('list'); });
    }

    function updateCreatePanelMode() {
        if (btnPublish) btnPublish.textContent = editingId ? 'Update Announcement' : 'Publish Now';
        if (btnCancel)  btnCancel.title = editingId ? 'Cancel editing and go back' : 'Cancel and go back';
    }

    /* ── SUBMIT: CREATE ────────────────────────────────────── */
    function submitAnnouncement(isDraft) {
        const title    = titleInput?.value.trim() ?? '';
        const content  = bodyEl?.innerHTML.trim() ?? '';
        const category = document.querySelector('input[name="category"]:checked')?.value ?? '';

        if (!title)    { showToast('Please enter a title.', 'error'); return; }
        if (!content || bodyEl?.innerText.trim() === '') { showToast('Please enter the announcement details.', 'error'); return; }
        if (!category) { showToast('Please select a category.', 'error'); return; }

        const fd = new FormData();
        fd.append('action',     'create');
        fd.append('title',      title);
        fd.append('content',    content);
        fd.append('category',   category);
        fd.append('featured',   featuredCheckbox?.checked ? '1' : '0');
        fd.append('publish_at', publishDateInput?.value || '');
        fd.append('expired_at', document.getElementById('ann-expiry-date')?.value || '');
        fd.append('draft',      isDraft ? '1' : '0');

        if (bannerFileObj) fd.append('banner', bannerFileObj);
        attachmentFiles.forEach(f => fd.append('attachments[]', f));

        const btn = isDraft ? btnDraft : btnPublish;
        if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

        fetch(`${ACTION_URL}?action=create`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(json => {
                if (json.status !== 'success') { showToast('Error: ' + (json.message || 'Unknown'), 'error'); return; }
                showToast(isDraft ? 'Saved as draft!' : 'Announcement published!', 'success');
                resetForm();
                switchTab(isDraft ? 'drafts' : 'list');
            })
            .catch(() => showToast('Network error.', 'error'))
            .finally(() => {
                if (btn) { btn.disabled = false; btn.textContent = isDraft ? 'Save as Draft' : 'Publish Now'; }
            });
    }

    /* ── SUBMIT: EDIT ──────────────────────────────────────── */
    function submitEdit(status) {
        if (!editingId) return;
        const title    = titleInput?.value.trim() ?? '';
        const content  = bodyEl?.innerHTML.trim() ?? '';
        const category = document.querySelector('input[name="category"]:checked')?.value ?? '';

        if (!title)    { showToast('Please enter a title.', 'error'); return; }
        if (!content || bodyEl?.innerText.trim() === '') { showToast('Please enter the announcement details.', 'error'); return; }
        if (!category) { showToast('Please select a category.', 'error'); return; }

        const fd = new FormData();
        fd.append('action',       'update');
        fd.append('id',           editingId);
        fd.append('title',        title);
        fd.append('content',      content);
        fd.append('category',     category);
        fd.append('featured',     featuredCheckbox?.checked ? '1' : '0');
        fd.append('status',       status);
        fd.append('publish_date', publishDateInput?.value || '');
        fd.append('expiry_date',  document.getElementById('ann-expiry-date')?.value || '');

        if (bannerFileObj) fd.append('banner', bannerFileObj);
        attachmentFiles.forEach(f => fd.append('attachments[]', f));

        const btn = status === 'draft' ? btnDraft : btnPublish;
        if (btn) { btn.disabled = true; btn.textContent = 'Updating…'; }

        fetch(ACTION_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(json => {
                if (json.status !== 'success') { showToast('Error: ' + (json.message || 'Unknown'), 'error'); return; }
                showToast('Announcement updated.', 'success');
                resetEditState();
                resetForm();
                switchTab(status === 'draft' ? 'drafts' : 'list');
            })
            .catch(() => showToast('Update failed.', 'error'))
            .finally(() => {
                if (btn) { btn.disabled = false; updateCreatePanelMode(); if (btnDraft) btnDraft.textContent = 'Save as Draft'; }
            });
    }

    btnPublish?.addEventListener('click', () => editingId ? submitEdit('active') : submitAnnouncement(false));
    btnDraft?.addEventListener('click',   () => editingId ? submitEdit('draft')  : submitAnnouncement(true));

    /* ── OPEN EDIT FORM ────────────────────────────────────── */
    async function openEditForm(id) {
        resetEditState();
        resetForm();
        try {
            const res  = await fetch(`${ACTION_URL}?action=getForEdit&id=${id}`);
            const json = await res.json();
            if (json.status !== 'success') return showToast('Could not load announcement.', 'error');

            const a = json.data;
            editingId = a.id;

            if (titleInput) titleInput.value = a.title;
            if (bodyEl)      bodyEl.innerHTML = a.content || '';

            const catRadio = document.querySelector(`input[name="category"][value="${a.category}"]`);
            if (catRadio) { catRadio.checked = true; catRadio.dispatchEvent(new Event('change')); }

            if (featuredCheckbox) {
                featuredCheckbox.checked = parseInt(a.featured) === 1;
                featuredCheckbox.dispatchEvent(new Event('change'));
            }

            if (publishDateInput && a.published_at) publishDateInput.value = a.published_at.split(' ')[0].split('T')[0];
            const expDateEl = document.getElementById('ann-expiry-date');
            if (expDateEl) expDateEl.value = a.expired_at ? a.expired_at.split(' ')[0].split('T')[0] : '';

            if (a.banner_img) loadBannerFromUrl(a.banner_img);

            titleInput?.dispatchEvent(new Event('input'));
            bodyEl?.dispatchEvent(new Event('input'));

            updateCreatePanelMode();
            switchTab('create');
        } catch (e) {
            showToast('Failed to load announcement for editing.', 'error');
        }
    }

    function resetEditState() {
        editingId = null;
        updateCreatePanelMode();
    }

    function resetForm() {
        if (titleInput)   titleInput.value = '';
        if (bodyEl)       bodyEl.innerHTML = '';
        if (charCount)    charCount.textContent = '0 / 120';
        if (bannerFile)   bannerFile.value = '';
        bannerFileObj = null;
        attachmentFiles = [];
        renderAttachments();
        if (bannerDropInner) bannerDropInner.style.display = '';
        if (bannerPreview)   bannerPreview.style.display   = 'none';
        if (prevBannerImg)   { prevBannerImg.src = ''; prevBannerImg.style.display = 'none'; }
        if (prevBannerPH)    prevBannerPH.style.display = '';
        if (featuredCheckbox) featuredCheckbox.checked = false;
        if (featuredToggleCard) featuredToggleCard.classList.remove('is-featured');
        if (previewFeatBadge) previewFeatBadge.style.display = 'none';
        document.querySelectorAll('input[name="category"]').forEach(r => r.checked = false);
        if (previewTitle)   previewTitle.textContent   = 'Your announcement title will appear here…';
        if (previewExcerpt) previewExcerpt.textContent = 'The announcement body text will be summarised here for the card view.';
        if (previewCatPill) { previewCatPill.textContent = 'Category'; previewCatPill.removeAttribute('style'); }
        if (publishDateInput) publishDateInput.value = new Date().toISOString().split('T')[0];
        const expDateEl = document.getElementById('ann-expiry-date');
        if (expDateEl) expDateEl.value = '';
        ['check-banner','check-title','check-body','check-category'].forEach(id => toggleCheck(document.getElementById(id), false));
    }

    /* ══════════════════════════════════════════════════════════
       CONFIRM MODAL
       ══════════════════════════════════════════════════════════ */
    const confirmOverlay = document.getElementById('ann-confirm-overlay');
    const confirmTitle   = document.getElementById('confirm-title');
    const confirmMsg     = document.getElementById('confirm-msg');
    const confirmOk      = document.getElementById('confirm-ok');
    const confirmCancel  = document.getElementById('confirm-cancel');
    let   confirmCallback = null;

    function confirm(title, msg, cb) {
        confirmTitle.textContent = title;
        confirmMsg.textContent   = msg;
        confirmCallback          = cb;
        confirmOverlay.style.display = 'flex';
    }

    confirmOk?.addEventListener('click', () => {
        confirmOverlay.style.display = 'none';
        if (confirmCallback) confirmCallback();
    });
    confirmCancel?.addEventListener('click', () => { confirmOverlay.style.display = 'none'; });
    confirmOverlay?.addEventListener('click', e => { if (e.target === confirmOverlay) confirmOverlay.style.display = 'none'; });

    /* ══════════════════════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════════════════════ */
    function toggleCheck(el, done) {
        if (!el) return;
        el.classList.toggle('is-done', done);
        const empty = el.querySelector('.check-empty');
        const done2 = el.querySelector('.check-done');
        if (empty) empty.style.display = done ? 'none' : '';
        if (done2) done2.style.display = done ? ''     : 'none';
    }

    function showToast(msg, type = 'success') {
        const t = document.createElement('div');
        t.className   = `svc-toast toast-${type}`;
        t.textContent = msg;
        t.style.cssText = `position:fixed;bottom:2rem;right:2rem;padding:14px 20px;border-radius:10px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;color:white;z-index:2000;box-shadow:0 4px 20px rgba(0,0,0,.15);background:${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#5b21b6'}`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function ucFirst(s) {
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
    }

    /* ── INIT ──────────────────────────────────────────────── */
    listCtrl.load(1);
    renderAttachments();
});