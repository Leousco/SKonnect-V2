/* =========================================================
   topbar.js — SKonnect Dashboard
   ========================================================= */

/* ---- Live Clock ---- */
(function () {
    const dateEl = document.getElementById('topbar-date');
    const timeEl = document.getElementById('topbar-time');
    if (!dateEl || !timeEl) return;

    const DAYS   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function tick() {
        const now  = new Date();
        dateEl.textContent = `${DAYS[now.getDay()]}, ${MONTHS[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
        const h    = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        const hh   = h % 12 || 12;
        timeEl.textContent = `${hh}:${String(m).padStart(2, '0')} ${ampm}`;
    }
    tick();
    setInterval(tick, 1000);
})();

/* ---- Notification Dropdown (live data) ---- */
(function () {

    // ── Config ────────────────────────────────────────────────────────────────
    // Adjust this path if your folder structure differs.
    const NOTIF_API   = '../../backend/routes/notifications.php';
    const MAX_PREVIEW = 5;   // items shown in the dropdown

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const btn         = document.getElementById('topbar-notif-btn');
    const dropdown    = document.getElementById('notif-dropdown');
    const list        = document.getElementById('notif-list');
    const badge       = document.getElementById('notif-badge');
    const markAllBtn  = document.getElementById('notif-mark-all-btn');

    if (!btn || !dropdown || !list || !badge) return;

    // ── State ─────────────────────────────────────────────────────────────────
    let loaded      = false;   // have we fetched at least once?
    let loading     = false;
    // Parse initial count robustly: prefer the rendered text content, fall back to 0
    let unreadCount = (function () {
        const txt = badge.textContent.trim();
        if (txt === '99+') return 99;
        const n = parseInt(txt, 10);
        return isNaN(n) ? 0 : n;
    }());

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Relative time label, e.g. "3 hours ago" */
    function relativeTime(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)       return 'Just now';
        if (diff < 3600)     return `${Math.floor(diff / 60)} min ago`;
        if (diff < 86400)    return `${Math.floor(diff / 3600)} hour${Math.floor(diff / 3600) > 1 ? 's' : ''} ago`;
        if (diff < 172800)   return 'Yesterday';
        if (diff < 604800)   return `${Math.floor(diff / 86400)} days ago`;
        return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    }

    /** Icon SVG per notification type */
    function typeIcon(type, isOfficial) {
        if (isOfficial) return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`;
        const icons = {
            service:      `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
            announcement: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
            new_service:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`,
            thread:       `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
        };
        return icons[type] || icons.announcement;
    }

    /** Update the badge element */
    function setBadge(count) {
        unreadCount = Math.max(0, count);
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.classList.toggle('notif-badge--hidden', unreadCount === 0);
        btn.setAttribute('aria-label', `${unreadCount} unread notifications`);
    }

    /** Render skeleton loaders */
    function renderSkeleton() {
        list.innerHTML = Array.from({ length: 3 }, () => `
            <li class="notif-item notif-skeleton" aria-hidden="true">
                <span class="notif-icon-wrap notif-skeleton-icon"></span>
                <div class="notif-content">
                    <div class="notif-skeleton-line notif-skeleton-line--title"></div>
                    <div class="notif-skeleton-line notif-skeleton-line--body"></div>
                    <div class="notif-skeleton-line notif-skeleton-line--time"></div>
                </div>
            </li>`).join('');
    }

    /** Render empty state */
    function renderEmpty() {
        list.innerHTML = `
            <li class="notif-empty" role="listitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    <line x1="4" y1="4" x2="20" y2="20" stroke-width="1.5"/>
                </svg>
                <p>You're all caught up!</p>
                <span>No new notifications</span>
            </li>`;
    }

    /** Render actual notifications */
    function renderNotifications(items) {
        if (!items || items.length === 0) { renderEmpty(); return; }

        const html = items.slice(0, MAX_PREVIEW).map(n => {
            const isUnread    = n.is_read == 0;
            const isOfficial  = n.is_official == 1;
            const linkAttr    = n.link ? `href="${encodeURI(n.link)}"` : '';
            const Tag         = n.link ? 'a' : 'div';
            const officialCls = isOfficial ? ' notif-item--official' : '';

            return `
            <li class="notif-item${isUnread ? ' unread' : ''}${officialCls}"
                role="listitem" data-id="${n.id}">
                <${Tag} class="notif-item-inner" ${linkAttr}>
                    <span class="notif-icon-wrap${isOfficial ? ' notif-icon-wrap--official' : ''}">
                        ${typeIcon(n.type, isOfficial)}
                    </span>
                    <div class="notif-content">
                        <p class="notif-title">${escapeHtml(n.title)}</p>
                        <p class="notif-body">${escapeHtml(n.message)}</p>
                        <time class="notif-time" datetime="${n.created_at}">
                            ${relativeTime(n.created_at)}
                        </time>
                    </div>
                    ${isUnread ? '<span class="notif-dot" aria-hidden="true"></span>' : ''}
                </${Tag}>
            </li>`;
        }).join('');

        list.innerHTML = html;

        // Mark individual item as read on click
        list.querySelectorAll('.notif-item[data-id]').forEach(function (item) {
            item.addEventListener('click', function () {
                const id = parseInt(item.dataset.id, 10);
                if (item.classList.contains('unread')) {
                    markRead(id, item);
                }
            });
        });
    }

    /** Minimal HTML escaper */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── API calls ─────────────────────────────────────────────────────────────

    function fetchNotifications() {
        if (loading) return;
        loading = true;
        renderSkeleton();

        fetch(`${NOTIF_API}?action=list`, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    renderNotifications(res.data);
                    setBadge(res.stats.unread);
                    loaded = true;
                } else {
                    renderEmpty();
                }
            })
            .catch(function () { renderEmpty(); })
            .finally(function () { loading = false; });
    }

    function markRead(id, itemEl) {
        const fd = new FormData();
        fd.append('action', 'markRead');
        fd.append('id', id);
        fetch(NOTIF_API, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success' && itemEl) {
                    itemEl.classList.remove('unread');
                    const dot = itemEl.querySelector('.notif-dot');
                    if (dot) dot.remove();
                    // Use server count if available, otherwise decrement safely
                    const newCount = (res.stats && res.stats.unread != null)
                        ? res.stats.unread
                        : Math.max(0, unreadCount - 1);
                    setBadge(newCount);
                }
            });
    }

    function markAllRead() {
        const fd = new FormData();
        fd.append('action', 'markAllRead');
        fetch(NOTIF_API, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    list.querySelectorAll('.notif-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                        const dot = el.querySelector('.notif-dot');
                        if (dot) dot.remove();
                    });
                    setBadge(0);
                }
            });
    }

    // ── Mark-all button ───────────────────────────────────────────────────────
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            markAllRead();
        });
    }

    // ── Dropdown open / close ─────────────────────────────────────────────────
    function openDropdown() {
        // Close user dropdown if open
        const userDrop = document.getElementById('user-dropdown');
        const userBtn  = document.getElementById('topbar-user-btn');
        if (userDrop) userDrop.classList.remove('open');
        if (userBtn)  userBtn.setAttribute('aria-expanded', 'false');

        dropdown.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');

        // Fetch fresh data every time the dropdown opens
        fetchNotifications();
    }

    function closeDropdown() {
        dropdown.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = btn.getAttribute('aria-expanded') === 'true';
        isOpen ? closeDropdown() : openDropdown();
    });

    btn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
        if (e.key === 'Escape') closeDropdown();
    });

    // Prevent clicks inside the dropdown from bubbling to document
    dropdown.addEventListener('click', function (e) { e.stopPropagation(); });

    // Close on outside click
    document.addEventListener('click', function () { closeDropdown(); });

})();

/* ---- User-menu Dropdown (unchanged logic) ---- */
(function () {
    const btn  = document.getElementById('topbar-user-btn');
    const drop = document.getElementById('user-dropdown');
    if (!btn || !drop) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = btn.getAttribute('aria-expanded') === 'true';

        // Close notif dropdown if open
        const notifDrop = document.getElementById('notif-dropdown');
        const notifBtn  = document.getElementById('topbar-notif-btn');
        if (notifDrop) notifDrop.classList.remove('open');
        if (notifBtn)  notifBtn.setAttribute('aria-expanded', 'false');

        if (!isOpen) {
            drop.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            drop.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    btn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
        if (e.key === 'Escape') { drop.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }
    });

    drop.addEventListener('click', function (e) { e.stopPropagation(); });

    document.addEventListener('click', function () {
        drop.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    });
})();
/* ---- Logout Confirmation Modal ---- */
(function () {
    const signoutLink = document.getElementById('signout-link');
    const overlay     = document.getElementById('logout-modal-overlay');
    const cancelBtn   = document.getElementById('logout-cancel-btn');

    if (!signoutLink || !overlay || !cancelBtn) return;

    function openModal() {
        overlay.classList.add('open');
        cancelBtn.focus();
    }

    function closeModal() {
        overlay.classList.remove('open');
    }

    signoutLink.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const drop = document.getElementById('user-dropdown');
        const btn  = document.getElementById('topbar-user-btn');
        if (drop) drop.classList.remove('open');
        if (btn)  btn.setAttribute('aria-expanded', 'false');
        openModal();
    });

    cancelBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });
}());