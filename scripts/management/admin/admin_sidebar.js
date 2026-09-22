const jsOpenState = new Map();

document.querySelectorAll('.has-submenu').forEach(li => {
    jsOpenState.set(li, li.classList.contains('open'));
});


document.querySelectorAll('.has-submenu').forEach(el => {
    const toggle = el.querySelector('.submenu-toggle');
    toggle.setAttribute('aria-expanded', jsOpenState.get(el) ? 'true' : 'false');
});


document.querySelectorAll('.submenu-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const li = btn.closest('.has-submenu');
        const isOpen = jsOpenState.get(li);

        
        document.querySelectorAll('.has-submenu').forEach(el => {
            if (el !== li && jsOpenState.get(el)) {
                jsOpenState.set(el, false);
                el.classList.remove('open');
                el.querySelector('.submenu-toggle').setAttribute('aria-expanded', 'false');
            }
        });

        
        const newState = !isOpen;
        jsOpenState.set(li, newState);
        li.classList.toggle('open', newState);
        btn.setAttribute('aria-expanded', newState ? 'true' : 'false');
    });
});