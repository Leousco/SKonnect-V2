/* admin_announcements.js — fully connected to DB */

document.addEventListener('DOMContentLoaded', function () {

    const LIST_URL   = '../../../backend/routes/announcements_list.php';
    const ACTION_URL = '../../../backend/routes/announcement_action.php';

    /* ── STATE ─────────────────────────────────────────────── */
    let currentPage = 1;
    let searchTimer = null;
    let attachmentFiles = []; // File objects for upload

    /* ══════════════════════════════════════════════════════════
       TAB SWITCHING
       ══════════════════════════════════════════════════════════ */
    const tabs   = document.querySelectorAll('.ann-tab');
    const panels = {
        list:   document.getElementById('panel-list'),
        create: document.getElementById('panel-create'),
    };

    function switchTab(target) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === target));
        Object.entries(panels).forEach(([key, el]) => {
            el.classList.toggle('ann-panel--hidden', key !== target);
        });
        if (target === 'list') loadAnnouncements();
    }

    tabs.forEach(tab => tab.addEventListener('click', () => switchTab(tab.dataset.tab)));
    document.getElementById('btn-switch-create')?.addEventListener('click', () => switchTab('create'));

    /* ══════════════════════════════════════════════════════════
       LIST — LOAD ANNOUNCEMENTS
       ══════════════════════════════════════════════════════════ */
    function loadAnnouncements(page = currentPage) {
        const search   = document.getElementById('ann-search')?.value.trim() ?? '';
        const category = document.getElementById('ann-filter-category')?.value ?? '';
        const status   = document.getElementById('ann-filter-status')?.value ?? '';

        const params = new URLSearchParams({ search, category, status, page });
        const tbody  = document.getElementById('ann-table-body');
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--ap-text-muted);">Loading…</td></tr>`;

        fetch(`${LIST_URL}?${params}`)
            .then(r => r.json())
            .then(json => {
                if (json.status !== 'success') { tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--ap-danger);">Error loading data.</td></tr>`; return; }
                const { rows, stats, pages } = json.data;
                currentPage = page;
                renderStats(stats);
                renderRows(rows, tbody);
                renderPagination(pages, page);
            })
            .catch(() => { tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--ap-danger);">Network error.</td></tr>`; });
    }

    /* ── STATS ──────────────────────────────────────────────── */
    function renderStats(s) {
        document.getElementById('stat-total').textContent    = s.total    ?? 0;
        document.getElementById('stat-active').textContent   = s.active   ?? 0;
        document.getElementById('stat-draft').textContent    = s.draft    ?? 0;
        document.getElementById('stat-featured').textContent = s.featured ?? 0;
        document.getElementById('stat-urgent').textContent   = s.urgent   ?? 0;
    }

    /* ── TABLE ROWS ─────────────────────────────────────────── */
    const CAT_COLORS = {
        event:   { bg: '#d1fae5', color: '#065f46', cls: 'cat-event' },
        program: { bg: '#dbeafe', color: '#1d4ed8', cls: 'cat-program' },
        meeting: { bg: '#ede9fe', color: '#5b21b6', cls: 'cat-meeting' },
        notice:  { bg: '#fef3c7', color: '#92400e', cls: 'cat-notice' },
        urgent:  { bg: '#fee2e2', color: '#b91c1c', cls: 'cat-urgent' },
    };

    function renderRows(rows, tbody) {
        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--ap-text-muted);">No announcements found.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(row => {
            const cat     = CAT_COLORS[row.category] || CAT_COLORS.notice;
            const isFeat  = parseInt(row.featured) === 1;
            const pubDate = row.published_at ? new Date(row.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

            const statusPill = {
                active:   '<span class="ann-status-pill status-published">Published</span>',
                draft:    '<span class="ann-status-pill status-draft">Draft</span>',
                archived: '<span class="ann-status-pill status-archived">Archived</span>',
            }[row.status] ?? '';

            const urgentBadge = row.category === 'urgent'
                ? '<span class="ann-badge badge-urgent">Urgent</span>' : '';

            return `
            <tr class="ann-row${isFeat ? ' ann-row--featured' : ''}" data-id="${row.id}">
                <td>
                    <div class="ann-thumb" style="background:${cat.bg};">
                        ${isFeat
                            ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="${cat.color}"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.45 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd"/></svg>`
                            : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="${cat.color}"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>`
                        }
                    </div>
                </td>
                <td>
                    <div class="ann-title-cell">
                        <span class="ann-title-text">${escHtml(row.title)}</span>
                        ${urgentBadge}
                    </div>
                    <span class="ann-excerpt">By ${escHtml(row.author)}</span>
                </td>
                <td><span class="ann-cat-pill ${cat.cls}">${ucFirst(row.category)}</span></td>
                <td>${statusPill}</td>
                <td>
                    <span class="ann-featured-dot ${isFeat ? 'dot-yes' : 'dot-no'}"
                          title="${isFeat ? 'Featured' : 'Not featured'}"
                          style="cursor:pointer;"
                          data-action="toggle-featured" data-id="${row.id}">
                        ${isFeat ? '★' : '☆'}
                    </span>
                </td>
                <td class="ann-date">${pubDate}</td>
                <td>
                    <div class="ann-row-actions">
                        ${row.status !== 'archived'
                            ? `<button class="row-action-btn btn-archive" title="Archive" data-action="archive" data-id="${row.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                               </button>`
                            : `<button class="row-action-btn btn-edit" title="Restore (set active)" data-action="restore" data-id="${row.id}" style="color:var(--ap-green);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                               </button>`
                        }
                        <button class="row-action-btn btn-delete" title="Delete" data-action="delete" data-id="${row.id}" data-title="${escHtml(row.title)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        // Row action listeners
        tbody.querySelectorAll('[data-action]').forEach(el => {
            el.addEventListener('click', handleRowAction);
        });
    }

    /* ── ROW ACTIONS ────────────────────────────────────────── */
    function handleRowAction(e) {
        const btn    = e.currentTarget;
        const action = btn.dataset.action;
        const id     = parseInt(btn.dataset.id);

        if (action === 'toggle-featured') {
            postAction({ action: 'toggle-featured', id })
                .then(() => loadAnnouncements());
            return;
        }
        if (action === 'archive') {
            postAction({ action: 'set-status', id, status: 'archived' })
                .then(() => { showToast('Announcement archived.', 'info'); loadAnnouncements(); });
            return;
        }
        if (action === 'restore') {
            postAction({ action: 'set-status', id, status: 'active' })
                .then(() => { showToast('Announcement restored.', 'success'); loadAnnouncements(); });
            return;
        }
        if (action === 'delete') {
            confirm(`Delete "${btn.dataset.title}"?`, 'This cannot be undone.', () => {
                postAction({ action: 'delete', id })
                    .then(() => { showToast('Announcement deleted.', 'error'); loadAnnouncements(); });
            });
        }
    }

    function postAction(body) {
        return fetch(ACTION_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        })
        .then(r => r.json())
        .then(json => {
            if (json.status !== 'success') showToast('Error: ' + (json.message || 'Unknown'), 'error');
            return json;
        });
    }

    /* ── PAGINATION ─────────────────────────────────────────── */
    function renderPagination(pages, current) {
        const nums    = document.getElementById('ann-page-numbers');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');

        btnPrev.disabled = current <= 1;
        btnNext.disabled = current >= pages;

        nums.innerHTML = '';
        for (let i = 1; i <= pages; i++) {
            const b = document.createElement('button');
            b.className   = `ann-page-num${i === current ? ' active' : ''}`;
            b.textContent = i;
            b.addEventListener('click', () => loadAnnouncements(i));
            nums.appendChild(b);
        }

        btnPrev.onclick = () => loadAnnouncements(current - 1);
        btnNext.onclick = () => loadAnnouncements(current + 1);
    }

    /* ── SEARCH / FILTER ────────────────────────────────────── */
    document.getElementById('ann-search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadAnnouncements(1), 350);
    });
    document.getElementById('ann-filter-category')?.addEventListener('change', () => loadAnnouncements(1));
    document.getElementById('ann-filter-status')?.addEventListener('change',   () => loadAnnouncements(1));

    /* ══════════════════════════════════════════════════════════
       CREATE FORM
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
    const bodyEl        = document.getElementById('ann-body');
    const previewExcerpt = document.getElementById('preview-excerpt');
    const checkBody     = document.getElementById('check-body');

    // Placeholder behaviour
    if (bodyEl) {
        bodyEl.addEventListener('focus', () => { if (!bodyEl.textContent.trim()) bodyEl.textContent = ''; });
        bodyEl.addEventListener('blur',  () => { /* leave empty */ });
        bodyEl.addEventListener('input', () => {
            const val = bodyEl.innerText.trim();
            previewExcerpt.textContent = val ? val.slice(0, 160) + (val.length > 160 ? '…' : '') : 'The announcement body text will be summarised here for the card view.';
            toggleCheck(checkBody, val.length > 0);
        });
    }

    // Toolbar
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

    /* ── BANNER UPLOAD ────────────────────────────────────── */
    const bannerFile        = document.getElementById('banner-file');
    const bannerDropZone    = document.getElementById('banner-drop-zone');
    const bannerDropInner   = document.getElementById('banner-drop-inner');
    const bannerPreview     = document.getElementById('banner-preview');
    const bannerPreviewImg  = document.getElementById('banner-preview-img');
    const bannerRemoveBtn   = document.getElementById('banner-remove');
    const prevBannerImg     = document.getElementById('preview-banner-img');
    const prevBannerPH      = document.getElementById('preview-banner-placeholder');
    const checkBanner       = document.getElementById('check-banner');
    let bannerFileObj       = null;

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

    /* ── ATTACHMENTS ──────────────────────────────────────── */
    const attachFileInput  = document.getElementById('attach-files');
    const attachList       = document.getElementById('attach-list');
    const attachDropZone   = document.getElementById('attach-drop-zone');
    const prevAttachRow    = document.getElementById('preview-attach-row');
    const prevAttachCount  = document.getElementById('preview-attach-count');

    function getFileType(name) {
        const ext = name.split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'pdf';
        if (['doc','docx'].includes(ext)) return 'doc';
        if (['xls','xlsx'].includes(ext)) return 'xls';
        return 'img';
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
        return (bytes/1048576).toFixed(1) + ' MB';
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
        // Update preview
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

    /* ── PUBLISH DATE DEFAULT ─────────────────────────────── */
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

    /* ── SUBMIT FORM ──────────────────────────────────────── */
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

        const btn = isDraft ? document.getElementById('btn-save-draft') : document.getElementById('btn-publish');
        if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

        fetch(`${ACTION_URL}?action=create`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(json => {
                if (json.status !== 'success') { showToast('Error: ' + (json.message || 'Unknown'), 'error'); return; }
                showToast(isDraft ? 'Saved as draft!' : 'Announcement published!', 'success');
                resetForm();
                switchTab('list');
            })
            .catch(() => showToast('Network error.', 'error'))
            .finally(() => {
                if (btn) { btn.disabled = false; btn.textContent = isDraft ? 'Save as Draft' : 'Publish Now'; }
            });
    }

    document.getElementById('btn-publish')?.addEventListener('click',    () => submitAnnouncement(false));
    document.getElementById('btn-save-draft')?.addEventListener('click', () => submitAnnouncement(true));

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
    loadAnnouncements();
});