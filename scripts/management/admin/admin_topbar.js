(function () {

    const NOTIF_URL = '../../../backend/routes/admin_notifications.php';

    
    function updateClock() {
        const now    = new Date();
        const dateEl = document.getElementById('admin-date');
        const timeEl = document.getElementById('admin-time');
        if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
        if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
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
            closeAll();
            if (!isOpen) {
                dd.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });

        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
        });
    }

    function closeAll() {
        document.querySelectorAll('.admin-notif-dropdown.open, .admin-user-dropdown.open')
            .forEach(el => el.classList.remove('open'));
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(el => el.setAttribute('aria-expanded', 'false'));
    }

    setupDropdown('admin-user-btn',  'admin-user-dropdown');

    
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href*="logout.php"]');
        if (link && link.id !== 'logout-confirm-btn') {
            e.preventDefault();
            e.stopPropagation();
            openLogoutModal();
        }
    }, true); 

    document.addEventListener('click', closeAll);

    
    function injectLogoutModal() {
        if (document.getElementById('logout-modal')) return;

        const modal = document.createElement('div');
        modal.id        = 'logout-modal';
        modal.className = 'logout-modal-overlay';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'logout-modal-title');
        modal.innerHTML = `
            <div class="logout-modal-box">
                <div class="logout-modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15"/>
                        <path d="M18 15l3-3m0 0-3-3m3 3H9"/>
                    </svg>
                </div>
                <h2 class="logout-modal-title" id="logout-modal-title">Sign Out</h2>
                <p class="logout-modal-desc">Are you sure you want to sign out of your admin account?</p>
                <div class="logout-modal-actions">
                    <button class="logout-btn-cancel" id="logout-cancel-btn">Cancel</button>
                    <a href="../../../backend/routes/logout.php" class="logout-btn-confirm" id="logout-confirm-btn">
                        Sign Out
                    </a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        
        if (!document.getElementById('logout-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'logout-modal-styles';
            style.textContent = `
                .logout-modal-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(15, 10, 40, 0.55);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.2s ease;
                }
                .logout-modal-overlay.open {
                    opacity: 1;
                    pointer-events: auto;
                }
                .logout-modal-box {
                    background: #fff;
                    border-radius: 18px;
                    padding: 36px 32px 28px;
                    width: 100%;
                    max-width: 380px;
                    text-align: center;
                    box-shadow: 0 24px 60px rgba(15, 10, 40, 0.2);
                    transform: translateY(16px) scale(0.97);
                    transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
                }
                .logout-modal-overlay.open .logout-modal-box {
                    transform: translateY(0) scale(1);
                }
                .logout-modal-icon {
                    width: 60px;
                    height: 60px;
                    border-radius: 16px;
                    background: #fff0f0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 18px;
                }
                .logout-modal-icon svg {
                    width: 28px;
                    height: 28px;
                    color: #dc2626;
                }
                .logout-modal-title {
                    font-size: 20px;
                    font-weight: 700;
                    color: #1e1b4b;
                    margin: 0 0 8px;
                }
                .logout-modal-desc {
                    font-size: 14px;
                    color: #6b7280;
                    margin: 0 0 26px;
                    line-height: 1.55;
                }
                .logout-modal-actions {
                    display: flex;
                    gap: 10px;
                }
                .logout-btn-cancel {
                    flex: 1;
                    padding: 11px 0;
                    border-radius: 10px;
                    border: 1.5px solid #e5e7eb;
                    background: #fff;
                    font-size: 14px;
                    font-weight: 600;
                    color: #374151;
                    cursor: pointer;
                    transition: background 0.15s, border-color 0.15s;
                }
                .logout-btn-cancel:hover {
                    background: #f9fafb;
                    border-color: #d1d5db;
                }
                .logout-btn-confirm {
                    flex: 1;
                    padding: 11px 0;
                    border-radius: 10px;
                    border: none;
                    background: #dc2626;
                    font-size: 14px;
                    font-weight: 600;
                    color: #fff;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.15s, box-shadow 0.15s;
                }
                .logout-btn-confirm:hover {
                    background: #b91c1c;
                    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
                }
            `;
            document.head.appendChild(style);
        }

        
        document.getElementById('logout-cancel-btn').addEventListener('click', closeLogoutModal);

        
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeLogoutModal();
        });

        
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeLogoutModal();
        });
    }

    function openLogoutModal() {
        closeAll();
        injectLogoutModal();
        requestAnimationFrame(() => {
            document.getElementById('logout-modal')?.classList.add('open');
        });
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logout-modal');
        if (!modal) return;
        modal.classList.remove('open');
    }



    
    const notifButton = document.getElementById('admin-notif-btn');
    const notifDropdown = document.getElementById('admin-notif-dropdown');
    const notifPreview = document.getElementById('admin-notif-preview');
    const notifBadge = document.getElementById('admin-notif-badge');
    const previewError = document.getElementById('admin-notif-preview-error');
    const viewAllButton = document.getElementById('admin-notif-view-all');
    const notifModal = document.getElementById('admin-notif-modal');
    const modalClose = document.getElementById('admin-notif-modal-close');
    const modalList = document.getElementById('admin-notif-modal-list');
    const modalError = document.getElementById('admin-notif-modal-error');
    const modalCount = document.getElementById('admin-notif-modal-count');
    const loadMoreButton = document.getElementById('admin-notif-load-more');
    const typeFilter = document.getElementById('admin-notif-type');
    const readFilter = document.getElementById('admin-notif-read');
    const searchInput = document.getElementById('admin-notif-search');
    const markAllButton = document.getElementById('admin-notif-mark-all');
    let modalRequestId = 0;
    let modalOffset = 0;
    let modalTotal = 0;
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
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
        return ({ announcements: 'Announcements', services: 'Services', requests: 'Service requests', reports: 'Community reports', users: 'User management' })[category] || 'Notification';
    }

    async function requestNotifications(params = {}, method = 'GET') {
        let url = NOTIF_URL;
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
        const unread = Number(count) || 0;
        notifBadge.hidden = unread === 0;
        notifBadge.textContent = unread > 99 ? '99+' : String(unread);
        notifButton.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
    }

    function notificationMarkup(notification, modal = false) {
        const unread = Number(notification.is_read) === 0;
        const title = escapeHtml(notification.title);
        const message = escapeHtml(notification.message);
        const category = escapeHtml(categoryLabel(notification.category));
        const time = escapeHtml(relativeTime(notification.created_at));
        const link = typeof notification.link === 'string' && /^admin_[a-z_]+\.php(?:\?[a-zA-Z0-9_=&%-]*)?$/.test(notification.link)
            ? escapeHtml(notification.link) : '';
        if (!modal) {
            return `<li class="admin-notif-item${unread ? ' unread' : ''}" data-notification-id="${Number(notification.id)}">
                <span class="admin-notif-dot" aria-hidden="true"></span>
                <div class="admin-notif-content"><span class="admin-notif-category">${category}</span><p><strong>${title}</strong></p><p>${message}</p><span class="admin-notif-time">${time}</span></div>
            </li>`;
        }
        return `<article class="admin-notif-modal-row${unread ? ' unread' : ''}" data-notification-id="${Number(notification.id)}">
            ${unread ? '<span class="admin-notif-dot" aria-label="Unread"></span>' : ''}
            <div class="admin-notif-modal-row-body"><span class="admin-notif-category">${category}</span><h3>${title}</h3><p>${message}</p><time datetime="${escapeHtml(notification.created_at)}">${time}</time></div>
            ${link ? `<a class="admin-notif-row-action" href="${link}" data-mark-read-open="${Number(notification.id)}">View</a>` : ''}
        </article>`;
    }

    async function loadPreview() {
        if (!notifPreview) return;
        previewError.hidden = true;
        try {
            const result = await requestNotifications({ action: 'list', limit: '6' });
            updateBadge(result.unreadCount);
            notifPreview.innerHTML = result.notifications.length
                ? result.notifications.map(item => notificationMarkup(item)).join('')
                : '<li class="admin-notif-empty">You’re all caught up.</li>';
        } catch (error) {
            console.error('Admin notifications error:', error);
            notifPreview.innerHTML = '<li class="admin-notif-empty">Notifications could not be loaded.</li>';
            previewError.textContent = 'Please try again later.';
            previewError.hidden = false;
        }
    }

    async function loadModalNotifications(append = false) {
        const requestId = ++modalRequestId;
        modalError.hidden = true;
        if (!append) {
            modalOffset = 0;
            modalList.innerHTML = '<p class="admin-notif-empty">Loading notifications...</p>';
        }
        try {
            const result = await requestNotifications({
                action: 'list', limit: '100', offset: String(modalOffset),
                category: typeFilter.value, is_read: readFilter.value, search: searchInput.value.trim(),
            });
            if (requestId !== modalRequestId) return;
            modalTotal = Number(result.total) || 0;
            modalCount.textContent = `${modalTotal} notification${modalTotal === 1 ? '' : 's'} · ${result.unreadCount} unread`;
            if (append) modalList.insertAdjacentHTML('beforeend', result.notifications.map(item => notificationMarkup(item, true)).join(''));
            else modalList.innerHTML = result.notifications.length
                ? result.notifications.map(item => notificationMarkup(item, true)).join('')
                : '<p class="admin-notif-empty">No notifications match these filters.</p>';
            modalOffset += result.notifications.length;
            loadMoreButton.hidden = modalOffset >= modalTotal;
            updateBadge(result.unreadCount);
        } catch (error) {
            if (requestId !== modalRequestId) return;
            console.error('Admin notifications error:', error);
            modalList.innerHTML = '';
            modalError.textContent = 'Notifications could not be loaded. Please try again.';
            modalError.hidden = false;
        }
    }

    notifButton?.addEventListener('click', event => {
        event.stopPropagation();
        const isOpen = notifDropdown.classList.contains('open');
        closeAll();
        if (!isOpen) {
            notifDropdown.classList.add('open');
            notifButton.setAttribute('aria-expanded', 'true');
            loadPreview();
        }
    });
    viewAllButton?.addEventListener('click', () => {
        closeAll();
        notifModal.classList.add('open');
        notifModal.setAttribute('aria-hidden', 'false');
        loadModalNotifications();
        modalClose.focus();
    });
    function closeNotificationsModal() {
        notifModal.classList.remove('open');
        notifModal.setAttribute('aria-hidden', 'true');
        notifButton.focus();
    }
    modalClose?.addEventListener('click', closeNotificationsModal);
    notifModal?.addEventListener('click', event => {
        if (event.target === notifModal) closeNotificationsModal();
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
            console.error('Mark all admin notifications read failed:', error);
            modalError.textContent = 'Notifications could not be updated. Please try again.';
            modalError.hidden = false;
        } finally {
            markAllButton.disabled = false;
        }
    });
    modalList?.addEventListener('click', async event => {
        const openLink = event.target.closest('[data-mark-read-open]');
        if (!openLink) return;
        event.preventDefault();
        try {
            await requestNotifications({ action: 'mark_read', id: Number(openLink.dataset.markReadOpen) }, 'POST');
            window.location.assign(openLink.href);
        } catch (error) {
            console.error('Mark admin notification read failed:', error);
            modalError.textContent = 'This notification could not be updated. Please try again.';
            modalError.hidden = false;
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && notifModal.classList.contains('open')) closeNotificationsModal();
    });

    loadPreview();
    setInterval(loadPreview, 60000);

})();
