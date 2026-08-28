(function () {

    const NOTIF_URL = '../../../backend/routes/notifications_list.php';

    /* ── Clock ───────────────────────────────────────────── */
    function updateClock() {
        const now    = new Date();
        const dateEl = document.getElementById('admin-date');
        const timeEl = document.getElementById('admin-time');
        if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
        if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── Dropdown toggles ────────────────────────────────── */
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
                if (btnId === 'admin-notif-btn') loadNotifications();
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

    setupDropdown('admin-notif-btn', 'admin-notif-dropdown');
    setupDropdown('admin-user-btn',  'admin-user-dropdown');

    // Logout interceptor — capture phase so it fires BEFORE closeAll
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href*="logout.php"]');
        if (link && link.id !== 'logout-confirm-btn') {
            e.preventDefault();
            e.stopPropagation();
            openLogoutModal();
        }
    }, true); // <-- capture: true is the key

    document.addEventListener('click', closeAll);

    /* ── Logout confirmation modal ───────────────────────── */
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

        // Inject styles
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

        // Cancel button
        document.getElementById('logout-cancel-btn').addEventListener('click', closeLogoutModal);

        // Close on overlay click
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeLogoutModal();
        });

        // Close on Escape key
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



    /* ── Load notifications from DB ──────────────────────── */
    function loadNotifications() {
        const list = document.getElementById('admin-notif-list');
        if (!list) return;

        fetch(NOTIF_URL)
            .then(r => r.json())
            .then(json => {
                if (json.status !== 'success') return;

                const { notifications, unreadCount, systemAlerts } = json;

                const badge = document.getElementById('admin-notif-badge');
                if (badge) {
                    badge.textContent   = unreadCount;
                    badge.style.display = unreadCount > 0 ? '' : 'none';
                }

                let html = '';

                if (systemAlerts.pendingRequests > 0) {
                    html += `
                        <li class="admin-notif-item unread">
                            <div class="admin-notif-dot"></div>
                            <div class="admin-notif-content">
                                <p><strong>${systemAlerts.pendingRequests}</strong> pending service request${systemAlerts.pendingRequests !== 1 ? 's' : ''} awaiting review</p>
                                <span class="admin-notif-time"><a href="admin_service_requests.php" style="color:inherit;">View requests →</a></span>
                            </div>
                        </li>`;
                }

                if (systemAlerts.flaggedReports > 0) {
                    html += `
                        <li class="admin-notif-item unread">
                            <div class="admin-notif-dot" style="background:#ef4444;"></div>
                            <div class="admin-notif-content">
                                <p><strong>${systemAlerts.flaggedReports}</strong> flagged report${systemAlerts.flaggedReports !== 1 ? 's' : ''} need attention</p>
                                <span class="admin-notif-time"><a href="admin_reports.php" style="color:inherit;">View reports →</a></span>
                            </div>
                        </li>`;
                }

                if (notifications.length > 0) {
                    notifications.forEach(n => {
                        const isUnread = !parseInt(n.is_read);
                        const time     = timeAgo(n.created_at);
                        html += `
                            <li class="admin-notif-item${isUnread ? ' unread' : ''}">
                                <div class="admin-notif-dot"></div>
                                <div class="admin-notif-content">
                                    <p>${escHtml(n.message)}</p>
                                    <span class="admin-notif-time">${time}</span>
                                </div>
                            </li>`;
                    });
                }

                if (!html) {
                    html = `<li style="padding:20px;text-align:center;color:#6b7280;font-size:12px;">No notifications</li>`;
                }

                list.innerHTML = html;
            })
            .catch(() => {
                const list = document.getElementById('admin-notif-list');
                if (list) list.innerHTML = `<li style="padding:20px;text-align:center;color:#6b7280;font-size:12px;">Unable to load notifications</li>`;
            });
    }

    /* ── Mark all read ───────────────────────────────────── */
    document.getElementById('admin-notif-mark-all')?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fetch(`${NOTIF_URL}?action=mark-read`)
            .then(r => r.json())
            .then(() => {
                const badge = document.getElementById('admin-notif-badge');
                if (badge) badge.style.display = 'none';
                document.querySelectorAll('.admin-notif-item.unread').forEach(el => el.classList.remove('unread'));
            });
    });

    /* ── Helpers ─────────────────────────────────────────── */
    function timeAgo(dateStr) {
        const now  = new Date();
        const past = new Date(dateStr);
        const diff = Math.floor((now - past) / 1000);
        if (diff < 60)     return 'Just now';
        if (diff < 3600)   return `${Math.floor(diff / 60)} min ago`;
        if (diff < 86400)  return `${Math.floor(diff / 3600)} hr ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;
        return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    loadNotifications();

})();