const jsOpenState = new Map();

document.querySelectorAll('.has-submenu').forEach(li => {
    jsOpenState.set(li, li.classList.contains('open'));
});

// Sync aria-expanded on load to match PHP-rendered state
document.querySelectorAll('.has-submenu').forEach(el => {
    const toggle = el.querySelector('.submenu-toggle');
    toggle.setAttribute('aria-expanded', jsOpenState.get(el) ? 'true' : 'false');
});

// Collapsible submenu toggle
document.querySelectorAll('.submenu-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const li = btn.closest('.has-submenu');
        const isOpen = jsOpenState.get(li);

        // Close all other open submenus
        document.querySelectorAll('.has-submenu').forEach(el => {
            if (el !== li && jsOpenState.get(el)) {
                jsOpenState.set(el, false);
                el.classList.remove('open');
                el.querySelector('.submenu-toggle').setAttribute('aria-expanded', 'false');
            }
        });

        // Toggle the clicked one
        const newState = !isOpen;
        jsOpenState.set(li, newState);
        li.classList.toggle('open', newState);
        btn.setAttribute('aria-expanded', newState ? 'true' : 'false');
    });
});