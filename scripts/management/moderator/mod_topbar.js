(function () {
    function updateClock() {
        const now = new Date();
        const dateEl = document.getElementById('mod-date');
        const timeEl = document.getElementById('mod-time');
        if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', {
            weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
        });
        if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    updateClock();
    setInterval(updateClock, 1000);

    const notificationApi = '../../../backend/routes/moderator_notifications.php';
    const notifButton = document.getElementById('mod-notif-btn');
    const notifDropdown = document.getElementById('mod-notif-dropdown');
    const notifPreview = document.getElementById('mod-notif-preview');
    const notifBadge = document.getElementById('mod-notif-badge');
    const previewError = document.getElementById('mod-notif-preview-error');
    const viewAllButton = document.getElementById('mod-notif-view-all');
    const notifModal = document.getElementById('mod-notif-modal');
    const modalClose = document.getElementById('mod-notif-modal-close');
    const modalList = document.getElementById('mod-notif-modal-list');
    const modalError = document.getElementById('mod-notif-modal-error');
    const modalCount = document.getElementById('mod-notif-modal-count');
    const loadMoreButton = document.getElementById('mod-notif-load-more');
    const typeFilter = document.getElementById('mod-notif-type');
    const readFilter = document.getElementById('mod-notif-read');
    const searchInput = document.getElementById('mod-notif-search');
    const markAllButton = document.getElementById('mod-notif-mark-all');
    let modalRequestId = 0;
    let modalOffset = 0;
    let modalTotal = 0;
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        })[char]);
    }

    function relativeTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function categoryLabel(category) {
        return category === 'reports' ? 'Reports'
            : category === 'admin_activity' ? 'Admin activity'
                : 'Notification';
    }

    async function requestNotifications(params = {}, method = 'GET') {
        let url = notificationApi;
        const options = { method, credentials: 'same-origin', headers: {} };
        if (method === 'GET') {
            url += `?${new URLSearchParams(params).toString()}`;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(params);
        }
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Unable to load notifications.');
        return result;
    }

    function updateBadge(count) {
        if (!notifBadge || !notifButton) return;
        const unread = Number(count) || 0;
        notifBadge.hidden = unread === 0;
        notifBadge.textContent = unread > 99 ? '99+' : String(unread);
        notifButton.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
    }

    function makeNotificationMarkup(notification, modal = false) {
        const title = escapeHtml(notification.title);
        const message = escapeHtml(notification.message);
        const category = escapeHtml(categoryLabel(notification.category));
        const time = escapeHtml(relativeTime(notification.created_at));
        const unread = Number(notification.is_read) === 0;
        const rowClass = modal
            ? `mod-notif-modal-row${unread ? ' unread' : ''}`
            : `mod-notif-item${unread ? ' unread' : ''}`;
        const bodyClass = modal ? 'mod-notif-modal-row-body' : 'mod-notif-content';
        const titleHtml = modal ? `<h3>${title}</h3><p>${message}</p>` : `<p>${title}</p>`;
        const link = typeof notification.link === 'string' &&
            /^mod_[a-z_]+\.php(?:\?[a-zA-Z0-9_=&%-]*)?$/.test(notification.link)
            ? escapeHtml(notification.link)
            : '';
        const action = modal
            ? (link
                ? `<a class="mod-notif-row-action" href="${link}" data-mark-read-open="${Number(notification.id)}">View</a>`
                : (unread ? `<button class="mod-notif-row-action" type="button" data-mark-read="${Number(notification.id)}">Mark read</button>` : ''))
            : '';
        const timeTag = modal
            ? `<time datetime="${escapeHtml(notification.created_at)}">${time}</time>`
            : `<span class="mod-notif-time">${time}</span>`;

        return `<${modal ? 'article' : 'li'} class="${rowClass}" data-notification-id="${Number(notification.id)}">
            ${unread ? '<span class="mod-notif-dot" aria-label="Unread"></span>' : ''}
            <div class="${bodyClass}">
                <span class="mod-notif-category">${category}</span>
                ${titleHtml}
                ${timeTag}
            </div>
            ${action}
        </${modal ? 'article' : 'li'}>`;
    }

    async function loadPreview() {
        if (!notifPreview) return;
        if (previewError) previewError.hidden = true;
        try {
            const result = await requestNotifications({ action: 'list', limit: '6' });
            updateBadge(result.unreadCount);
            notifPreview.innerHTML = result.notifications.length
                ? result.notifications.map(item => makeNotificationMarkup(item)).join('')
                : '<li class="mod-notif-empty">You’re all caught up.</li>';
        } catch (error) {
            console.error('Moderator notifications error:', error);
            notifPreview.innerHTML = '<li class="mod-notif-empty">Notifications could not be loaded.</li>';
            if (previewError) {
                previewError.textContent = 'Please try again later.';
                previewError.hidden = false;
            }
        }
    }

    async function loadModalNotifications(append = false) {
        if (!modalList || !modalError || !modalCount || !loadMoreButton) return;
        const requestId = ++modalRequestId;
        modalError.hidden = true;
        if (!append) {
            modalOffset = 0;
            modalList.innerHTML = '<p class="mod-notif-empty">Loading notifications...</p>';
        }
        try {
            const result = await requestNotifications({
                action: 'list',
                limit: '100',
                offset: String(modalOffset),
                category: typeFilter.value,
                is_read: readFilter.value,
                search: searchInput.value.trim(),
            });
            if (requestId !== modalRequestId) return;
            modalTotal = Number(result.total) || 0;
            modalCount.textContent = `${modalTotal} notification${modalTotal === 1 ? '' : 's'} · ${result.unreadCount} unread`;
            if (append) {
                modalList.insertAdjacentHTML('beforeend', result.notifications.map(item => makeNotificationMarkup(item, true)).join(''));
            } else {
                modalList.innerHTML = result.notifications.length
                    ? result.notifications.map(item => makeNotificationMarkup(item, true)).join('')
                    : '<p class="mod-notif-empty">No notifications match these filters.</p>';
            }
            modalOffset += result.notifications.length;
            loadMoreButton.hidden = modalOffset >= modalTotal;
            updateBadge(result.unreadCount);
        } catch (error) {
            if (requestId !== modalRequestId) return;
            console.error('Moderator notifications error:', error);
            modalList.innerHTML = '';
            modalError.textContent = 'Notifications could not be loaded. Please try again.';
            modalError.hidden = false;
        }
    }

    async function markNotificationRead(id) {
        await requestNotifications({ action: 'mark_read', id: Number(id) }, 'POST');
        await Promise.all([
            loadPreview(),
            notifModal.classList.contains('open') ? loadModalNotifications() : Promise.resolve(),
        ]);
    }

    function closeDropdowns() {
        document.querySelectorAll('.mod-notif-dropdown.open, .mod-user-dropdown.open')
            .forEach(el => el.classList.remove('open'));
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(el => el.setAttribute('aria-expanded', 'false'));
    }

    function setupDropdown(btnId, dropdownId) {
        const btn = document.getElementById(btnId);
        const dropdown = document.getElementById(dropdownId);
        if (!btn || !dropdown) return;

        btn.addEventListener('click', event => {
            event.stopPropagation();
            const isOpen = dropdown.classList.contains('open');
            closeDropdowns();
            if (!isOpen) {
                dropdown.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
                if (btnId === 'mod-notif-btn') loadPreview();
            }
        });
        btn.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                btn.click();
            }
        });
    }

    setupDropdown('mod-notif-btn', 'mod-notif-dropdown');
    setupDropdown('mod-user-btn', 'mod-user-dropdown');
    loadPreview();
    window.setInterval(loadPreview, 60000);

    viewAllButton?.addEventListener('click', () => {
        closeDropdowns();
        notifModal.classList.add('open');
        notifModal.setAttribute('aria-hidden', 'false');
        loadModalNotifications();
        modalClose.focus();
    });
    modalClose?.addEventListener('click', () => {
        notifModal.classList.remove('open');
        notifModal.setAttribute('aria-hidden', 'true');
        notifButton.focus();
    });
    typeFilter?.addEventListener('change', () => loadModalNotifications());
    readFilter?.addEventListener('change', () => loadModalNotifications());
    loadMoreButton?.addEventListener('click', () => loadModalNotifications(true));
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadModalNotifications(), 250);
    });
    markAllButton?.addEventListener('click', async () => {
        markAllButton.disabled = true;
        try {
            await requestNotifications({ action: 'mark_all_read' }, 'POST');
            await Promise.all([loadPreview(), loadModalNotifications()]);
        } catch (error) {
            console.error('Mark all moderator notifications read failed:', error);
            modalError.textContent = 'Notifications could not be updated. Please try again.';
            modalError.hidden = false;
        } finally {
            markAllButton.disabled = false;
        }
    });
    modalList?.addEventListener('click', async event => {
        const openLink = event.target.closest('[data-mark-read-open]');
        const markButton = event.target.closest('[data-mark-read]');
        if (!openLink && !markButton) return;
        event.preventDefault();
        const target = openLink || markButton;
        try {
            await markNotificationRead(target.dataset.markReadOpen || target.dataset.markRead);
            if (openLink) window.location.href = openLink.href;
        } catch (error) {
            console.error('Mark moderator notification read failed:', error);
            modalError.textContent = 'This notification could not be updated. Please try again.';
            modalError.hidden = false;
        }
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('.mod-topbar-notif-wrap, .mod-topbar-user')) closeDropdowns();
    });
})();

(function () {
    const signoutLink = document.getElementById('mod-signout-link');
    const overlay = document.getElementById('mod-logout-overlay');
    const cancelBtn = document.getElementById('mod-logout-cancel');
    if (!signoutLink || !overlay || !cancelBtn) return;

    function openModal() {
        overlay.classList.add('open');
        cancelBtn.focus();
    }

    function closeModal() {
        overlay.classList.remove('open');
    }

    signoutLink.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        document.querySelectorAll('.mod-user-dropdown.open, .mod-notif-dropdown.open')
            .forEach(el => el.classList.remove('open'));
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(el => el.setAttribute('aria-expanded', 'false'));
        openModal();
    });
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });
})();
