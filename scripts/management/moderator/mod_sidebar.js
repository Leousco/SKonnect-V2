

document.querySelectorAll('.submenu-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const li     = btn.closest('.has-submenu');
        const isOpen = li.classList.contains('open');

        
        document.querySelectorAll('.has-submenu.open').forEach(el => {
            el.classList.remove('open');
            el.querySelector('.submenu-toggle').setAttribute('aria-expanded', 'false');
        });

        
        if (!isOpen) {
            li.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
        }
    });
});