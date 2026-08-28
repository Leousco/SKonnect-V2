// mod_sidebar.js — mirrors admin_sidebar.js

document.querySelectorAll('.submenu-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const li     = btn.closest('.has-submenu');
        const isOpen = li.classList.contains('open');

        // Close all open submenus first
        document.querySelectorAll('.has-submenu.open').forEach(el => {
            el.classList.remove('open');
            el.querySelector('.submenu-toggle').setAttribute('aria-expanded', 'false');
        });

        // Open this one if it was closed
        if (!isOpen) {
            li.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
        }
    });
});