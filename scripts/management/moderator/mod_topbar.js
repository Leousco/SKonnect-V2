// mod_topbar.js — mirrors admin_topbar.js

(function () {

    // ── Clock ──────────────────────────────────────────────────
    function updateClock() {
        const now    = new Date();
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

    // ── Dropdown helper ────────────────────────────────────────
    function setupDropdown(btnId, dropdownId) {
        const btn = document.getElementById(btnId);
        const dd  = document.getElementById(dropdownId);
        if (!btn || !dd) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dd.classList.contains('open');

            // Close all dropdowns
            document.querySelectorAll('.mod-notif-dropdown.open, .mod-user-dropdown.open')
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

    setupDropdown('mod-notif-btn',  'mod-notif-dropdown');
    setupDropdown('mod-user-btn',   'mod-user-dropdown');

    // ── Close on outside click ─────────────────────────────────
    document.addEventListener('click', function () {
        document.querySelectorAll('.mod-notif-dropdown.open, .mod-user-dropdown.open')
            .forEach(el => el.classList.remove('open'));
        document.querySelectorAll('[aria-expanded="true"]')
            .forEach(el => el.setAttribute('aria-expanded', 'false'));
    });

})();
(function () {

    const signoutLink = document.getElementById('mod-signout-link');
    const overlay     = document.getElementById('mod-logout-overlay');
    const cancelBtn   = document.getElementById('mod-logout-cancel');

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
        document.querySelectorAll('.mod-user-dropdown.open, .mod-notif-dropdown.open')
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