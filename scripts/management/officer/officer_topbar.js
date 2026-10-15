(function () {

    
    function updateClock() {
        const now    = new Date();
        const dateEl = document.getElementById('off-date');
        const timeEl = document.getElementById('off-time');
        if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', {
            weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
        });
        if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    updateClock();
    setInterval(updateClock, 1000);

    
    function setupDropdown(btnId, dropdownId) {
        const btn = document.getElementById(btnId);
        const dd  = document.getElementById(dropdownId);
        if (!btn || !dd) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dd.classList.contains('open');

            
            document.querySelectorAll('.off-notif-dropdown.open, .off-user-dropdown.open')
                .forEach(el => el.classList.remove('open'));
            document.querySelectorAll('[aria-expanded="true"]')
                .forEach(el => el.setAttribute('aria-expanded', 'false'));

            if (!isOpen) {
                dd.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });

        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
        });
    }

    setupDropdown('off-user-btn',   'off-user-dropdown');

    const notificationApi = '../../../backend/routes/officer_notifications.php';
    const notifButton = document.getElementById('off-notif-btn');
    const notifDropdown = document.getElementById('off-notif-dropdown');
    const notifPreview = document.getElementById('off-notif-preview');
    const notifBadge = document.getElementById('off-notif-badge');
    const previewError = document.getElementById('off-notif-preview-error');
    const viewAllButton = document.getElementById('off-notif-view-all');
    const notifModal = document.getElementById('off-notif-modal');
    const modalClose = document.getElementById('off-notif-modal-close');
    const modalList = document.getElementById('off-notif-modal-list');
    const modalError = document.getElementById('off-notif-modal-error');
    const modalCount = document.getElementById('off-notif-modal-count');
    const loadMoreButton = document.getElementById('off-notif-load-more');
    const typeFilter = document.getElementById('off-notif-type');
    const readFilter = document.getElementById('off-notif-read');
    const searchInput = document.getElementById('off-notif-search');
    const markAllButton = document.getElementById('off-notif-mark-all');
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

    function notificationLabel(category) {
        return ({
            requests: 'Service request',
            attention: 'Needs attention',
            admin_updates: 'Admin change',
            events: 'Event reminder',
            announcements: 'Announcement',
        })[category] || 'Notification';
    }

    async function requestNotifications(params = {}, method = 'GET') {
        let url = notificationApi;
        const options = { method, credentials: 'same-origin', headers: {} };
        if (method === 'GET') {
            const query = new URLSearchParams(params);
            url += `?${query.toString()}`;
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
        if (!notifBadge) return;
        const unread = Number(count) || 0;
        notifBadge.hidden = unread === 0;
        notifBadge.textContent = unread > 99 ? '99+' : String(unread);
        notifButton.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
    }

    function makeNotificationMarkup(notification, modal = false) {
        const title = escapeHtml(notification.title);
        const message = escapeHtml(notification.message);
        const category = escapeHtml(notificationLabel(notification.category));
        const time = escapeHtml(relativeTime(notification.created_at));
        const unread = Number(notification.is_read) === 0;
        const rowClass = modal
            ? `off-notif-modal-row${unread ? ' unread' : ''}`
            : `off-notif-item${unread ? ' unread' : ''}`;
        const bodyClass = modal ? 'off-notif-modal-row-body' : 'off-notif-content';
        const titleHtml = modal ? `<h3>${title}</h3>` : `<p>${title}</p>`;
        const messageHtml = modal ? `<p>${message}</p>` : '';
        const link = typeof notification.link === 'string' && /^officer_[a-z_]+\.php(?:\?[a-zA-Z0-9_=&%-]*)?$/.test(notification.link)
            ? escapeHtml(notification.link)
            : '';
        const action = modal
            ? (link
                ? `<a class="off-notif-row-action" href="${link}" data-mark-read-open="${Number(notification.id)}">View</a>`
                : (unread ? `<button class="off-notif-row-action" type="button" data-mark-read="${Number(notification.id)}">Mark read</button>` : ''))
            : '';
        const timeTag = modal
            ? `<time datetime="${escapeHtml(notification.created_at)}">${time}</time>`
            : `<span class="off-notif-time">${time}</span>`;

        return `<${modal ? 'article' : 'li'} class="${rowClass}" data-notification-id="${Number(notification.id)}">
            ${unread ? '<span class="off-notif-dot" aria-label="Unread"></span>' : ''}
            <div class="${bodyClass}">
                <span class="off-notif-category">${category}</span>
                ${titleHtml}${messageHtml}
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
                : '<li class="off-notif-empty">You’re all caught up.</li>';
        } catch (error) {
            console.error('Officer notifications error:', error);
            notifPreview.innerHTML = '<li class="off-notif-empty">Notifications could not be loaded.</li>';
            if (previewError) {
                previewError.textContent = 'Please try again later.';
                previewError.hidden = false;
            }
        }
    }

    async function loadModalNotifications(append = false) {
        if (!modalList) return;
        const requestId = ++modalRequestId;
        modalError.hidden = true;
        if (!append) {
            modalOffset = 0;
            modalList.innerHTML = '<p class="off-notif-empty">Loading notifications...</p>';
        }
        const params = {
            action: 'list',
            limit: '100',
            offset: String(modalOffset),
            category: typeFilter.value,
            is_read: readFilter.value,
            search: searchInput.value.trim(),
        };
        try {
            const result = await requestNotifications(params);
            if (requestId !== modalRequestId) return;
            modalTotal = Number(result.total) || 0;
            modalCount.textContent = `${modalTotal} notification${modalTotal === 1 ? '' : 's'} · ${result.unreadCount} unread`;
            if (append) {
                modalList.insertAdjacentHTML('beforeend', result.notifications.map(item => makeNotificationMarkup(item, true)).join(''));
            } else {
                modalList.innerHTML = result.notifications.length
                    ? result.notifications.map(item => makeNotificationMarkup(item, true)).join('')
                    : '<p class="off-notif-empty">No notifications match these filters.</p>';
            }
            modalOffset += result.notifications.length;
            loadMoreButton.hidden = modalOffset >= modalTotal;
            updateBadge(result.unreadCount);
        } catch (error) {
            if (requestId !== modalRequestId) return;
            console.error('Officer notifications error:', error);
            modalList.innerHTML = '';
            modalError.textContent = 'Notifications could not be loaded. Please try again.';
            modalError.hidden = false;
        }
    }

    async function markNotificationRead(id) {
        await requestNotifications({ action: 'mark_read', id: Number(id) }, 'POST');
        await Promise.all([loadPreview(), notifModal.classList.contains('open') ? loadModalNotifications() : Promise.resolve()]);
    }

    function openNotificationsModal() {
        notifDropdown.classList.remove('open');
        notifButton.setAttribute('aria-expanded', 'false');
        notifModal.classList.add('open');
        notifModal.setAttribute('aria-hidden', 'false');
        loadModalNotifications();
        modalClose.focus();
    }

    if (notifButton && notifDropdown) {
        notifButton.addEventListener('click', event => {
            event.stopPropagation();
            const isOpen = notifDropdown.classList.contains('open');
            document.querySelectorAll('.off-notif-dropdown.open, .off-user-dropdown.open')
                .forEach(el => el.classList.remove('open'));
            document.querySelectorAll('[aria-expanded="true"]')
                .forEach(el => el.setAttribute('aria-expanded', 'false'));
            if (!isOpen) {
                notifDropdown.classList.add('open');
                notifButton.setAttribute('aria-expanded', 'true');
                loadPreview();
            }
        });
    }

    viewAllButton?.addEventListener('click', openNotificationsModal);
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
        searchTimer = setTimeout(loadModalNotifications, 250);
    });
    markAllButton?.addEventListener('click', async () => {
        markAllButton.disabled = true;
        try {
            await requestNotifications({ action: 'mark_all_read' }, 'POST');
            await Promise.all([loadPreview(), loadModalNotifications()]);
        } catch (error) {
            console.error('Mark all notifications read failed:', error);
            modalError.textContent = 'Notifications could not be updated. Please try again.';
            modalError.hidden = false;
        } finally {
            markAllButton.disabled = false;
        }
    });
    modalList?.addEventListener('click', async event => {
        const openLink = event.target.closest('[data-mark-read-open]');
        if (openLink) {
            event.preventDefault();
            try {
                await requestNotifications({ action: 'mark_read', id: Number(openLink.dataset.markReadOpen) }, 'POST');
                window.location.assign(openLink.href);
            } catch (error) {
                console.error('Mark notification read failed:', error);
                modalError.textContent = 'Notification could not be updated. Please try again.';
                modalError.hidden = false;
            }
            return;
        }
        const markButton = event.target.closest('[data-mark-read]');
        if (!markButton) return;
        markButton.disabled = true;
        try {
            await markNotificationRead(markButton.dataset.markRead);
        } catch (error) {
            console.error('Mark notification read failed:', error);
            modalError.textContent = 'Notification could not be updated. Please try again.';
            modalError.hidden = false;
            markButton.disabled = false;
        }
    });
    notifPreview?.addEventListener('click', async event => {
        const item = event.target.closest('[data-notification-id].unread');
        if (!item) return;
        try {
            await markNotificationRead(item.dataset.notificationId);
        } catch (error) {
            console.error('Mark notification read failed:', error);
        }
    });
    loadPreview();
    setInterval(loadPreview, 60000);

    document.addEventListener('click', function () {
        document.querySelectorAll('.off-notif-dropdown.open, .off-user-dropdown.open')
            .forEach(el => el.classList.remove('open'));
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(el => el.setAttribute('aria-expanded', 'false'));
    });

})();

(function () {
    const signoutLink = document.getElementById('off-signout-link');
    const overlay     = document.getElementById('off-logout-overlay');
    const cancelBtn   = document.getElementById('off-logout-cancel');

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
        document.querySelectorAll('.off-user-dropdown.open, .off-notif-dropdown.open')
            .forEach(function (el) { el.classList.remove('open'); });
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(function (el) { el.setAttribute('aria-expanded', 'false'); });
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