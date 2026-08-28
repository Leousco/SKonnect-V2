/* notifications_page.js — Portal Notifications */

document.addEventListener('DOMContentLoaded', () => {

    const ROUTE = '../../backend/routes/notifications.php';

    /* ---- ELEMENTS ---- */
    const searchInput = document.getElementById('notif-search');
    const typeSelect  = document.getElementById('notif-type');
    const readSelect  = document.getElementById('notif-read');
    const markAllBtn  = document.getElementById('mark-all-btn');
    // Scope to .announcements-section to avoid colliding with #notif-list inside the topbar dropdown
    const pageSection = document.querySelector('.announcements-section');
    const notifList   = pageSection ? pageSection.querySelector('#notif-list') : null;
    const notifEmpty  = document.getElementById('notif-empty');
    const unreadLabel = document.getElementById('unread-count-label');

    /* ---- PAGINATION CONFIG ---- */
    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;

    /* ---- UNREAD COUNT ---- */

    function updateUnreadLabel() {
        const count = document.querySelectorAll('.notif-item-row.notif-unread').length;
        if (unreadLabel) {
            unreadLabel.textContent = count === 0 ? 'All read' : `${count} unread`;
            unreadLabel.classList.toggle('is-zero', count === 0);
        }
    }

    updateUnreadLabel();

    /* ---- FILTER + PAGINATION ---- */

    function getVisibleItems() {
        const query      = searchInput.value.toLowerCase().trim();
        const type       = typeSelect.value;
        const readFilter = readSelect.value;

        return Array.from(document.querySelectorAll('.notif-item-row')).filter(item => {
            const title    = (item.dataset.title || '').toLowerCase();
            const body     = (item.dataset.body  || '').toLowerCase();
            const itemType = item.dataset.type   || '';
            const isUnread = item.classList.contains('notif-unread');

            const matchSearch = !query || title.includes(query) || body.includes(query);
            const matchType   = type === 'all' || itemType === type;
            const matchRead   =
                readFilter === 'all'    ? true :
                readFilter === 'unread' ? isUnread :
                readFilter === 'read'   ? !isUnread : true;

            return matchSearch && matchType && matchRead;
        });
    }

    function renderPage() {
        const allVisible  = getVisibleItems();
        const totalItems  = allVisible.length;
        const totalPages  = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));

        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = start + ITEMS_PER_PAGE;

        // Show/hide all items
        Array.from(document.querySelectorAll('.notif-item-row')).forEach(item => {
            item.style.display = 'none';
        });

        allVisible.forEach((item, idx) => {
            item.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        const isEmpty = totalItems === 0;
        if (notifEmpty) notifEmpty.style.display = isEmpty ? 'flex' : 'none';
        if (notifList)  notifList.style.display  = isEmpty ? 'none' : '';

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const container = document.getElementById('page-numbers');
        const prevBtn   = document.getElementById('prev-btn');
        const nextBtn   = document.getElementById('next-btn');
        if (!container) return;

        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-num' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.addEventListener('click', () => { currentPage = i; renderPage(); });
            container.appendChild(btn);
        }

        if (prevBtn) {
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick  = () => { if (currentPage > 1) { currentPage--; renderPage(); } };
        }
        if (nextBtn) {
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick  = () => { if (currentPage < totalPages) { currentPage++; renderPage(); } };
        }
    }

    function filterItems() {
        currentPage = 1;
        renderPage();
    }

    searchInput?.addEventListener('input',  filterItems);
    typeSelect?.addEventListener('change',  filterItems);
    readSelect?.addEventListener('change',  filterItems);

    renderPage();

    /* ---- SERVER HELPERS ---- */

    function persist(action, id = null) {
        const body = new URLSearchParams({ action });
        if (id) body.append('id', id);
        fetch(ROUTE, { method: 'POST', body }).catch(() => {});
    }

    /* ---- MARK AS READ (individual) ---- */

    function markAsRead(item) {
        if (!item.classList.contains('notif-unread')) return;
        item.classList.remove('notif-unread');

        const dot     = item.querySelector('.notif-unread-dot');
        const markBtn = item.querySelector('.mark-read-btn');
        if (dot)     dot.remove();
        if (markBtn) markBtn.remove();

        persist('markRead', item.dataset.id);
        updateUnreadLabel();
    }

    notifList?.addEventListener('click', e => {
        const markBtn = e.target.closest('.mark-read-btn');
        if (!markBtn) return;
        e.stopPropagation();
        const item = markBtn.closest('.notif-item-row');
        if (item) markAsRead(item);
    });

    /* ---- MARK ALL READ ---- */

    markAllBtn?.addEventListener('click', () => {
        document.querySelectorAll('.notif-item-row.notif-unread').forEach(item => markAsRead(item));
        persist('markAllRead');
    });

    /* ---- DISMISS ---- */

    notifList?.addEventListener('click', e => {
        const dismissBtn = e.target.closest('.dismiss-btn');
        if (!dismissBtn) return;
        e.stopPropagation();

        const item = dismissBtn.closest('.notif-item-row');
        if (!item) return;

        const notifId = item.dataset.id;

        item.style.transition  = 'opacity 0.25s, max-height 0.3s, padding 0.3s';
        item.style.opacity     = '0';
        item.style.maxHeight   = item.offsetHeight + 'px';
        item.style.overflow    = 'hidden';

        requestAnimationFrame(() => requestAnimationFrame(() => {
            item.style.maxHeight   = '0';
            item.style.padding     = '0';
            item.style.borderWidth = '0';
        }));

        setTimeout(() => {
            item.remove();
            persist('dismiss', notifId);
            updateUnreadLabel();
            renderPage();
        }, 320);
    });

    /* ---- ROW CLICK → OPEN MODAL ---- */

    notifList?.addEventListener('click', e => {
        if (e.target.closest('.notif-action-btn') || e.target.closest('.notif-link')) return;
        const row = e.target.closest('.notif-item-row');
        if (row) { markAsRead(row); openModal(row); }
    });

    /* ---- MODAL ---- */

    const modalOverlay    = document.getElementById('modal-overlay');
    const modalClose      = document.getElementById('modal-close');
    const modalCloseBtn   = document.getElementById('modal-close-btn');
    const modalTitle      = document.getElementById('modal-notif-title');
    const modalTime       = document.getElementById('modal-notif-time');
    const modalIcon       = document.getElementById('modal-notif-icon');
    const modalTypeTag    = document.getElementById('modal-type-tag');
    const modalBodyText   = document.getElementById('modal-body-text');
    const modalActionLk   = document.getElementById('modal-action-link');
    const modalOffBadge   = document.getElementById('modal-official-badge');

    const typeIconMap = {
        service:      '✅',
        announcement: '📣',
        new_service:  '🆕',
        thread:       '💬',
        system:       '🔔',
    };

    const typeTagMap = {
        service:      { label: 'Service Update',    cls: 'tag-service' },
        announcement: { label: 'Announcement',       cls: 'tag-announcement' },
        new_service:  { label: 'New Service',        cls: 'tag-new-service' },
        thread:       { label: 'Community Thread',   cls: 'tag-thread' },
        system:       { label: 'System',             cls: 'tag-system' },
    };

    function formatDatetime(isoStr) {
        if (!isoStr) return '—';
        try {
            return new Date(isoStr).toLocaleString('en-PH', {
                month: 'long', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit', hour12: true,
            });
        } catch { return isoStr; }
    }

    function openModal(row) {
        const type       = row.dataset.type  || 'system';
        const title      = row.dataset.title || 'Notification';
        const body       = row.dataset.body  || '—';
        const time       = row.dataset.time  || '';
        const link       = row.dataset.link  || '';
        const isRejected = row.querySelector('.type-rejected') !== null;
        const isOfficial = row.querySelector('.notif-official-badge') !== null;

        modalTitle.textContent    = title;
        modalTime.textContent     = formatDatetime(time);
        modalIcon.textContent     = isRejected ? '❌' : (typeIconMap[type] || '🔔');
        modalBodyText.textContent = body;

        if (modalOffBadge) {
            modalOffBadge.style.display = isOfficial ? '' : 'none';
        }

        const tagCfg = typeTagMap[type] || { label: type, cls: 'tag-system' };
        modalTypeTag.textContent = tagCfg.label;
        modalTypeTag.className   = `notif-type-tag ${tagCfg.cls}`;

        if (link) {
            modalActionLk.href         = link;
            modalActionLk.style.display = '';
        } else {
            modalActionLk.style.display = 'none';
        }

        modalOverlay.style.display   = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modalOverlay.style.display   = 'none';
        document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click',    closeModal);
    modalCloseBtn?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click',  e => { if (e.target === modalOverlay) closeModal(); });
    document.addEventListener('keydown',     e => { if (e.key === 'Escape') closeModal(); });
});