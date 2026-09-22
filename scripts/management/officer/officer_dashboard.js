






 (function () {
    'use strict';

    
    const REFRESH_INTERVAL_MS = 60_000; 
    
    const DATA_API = '../../../backend/routes/officer_dashboard_data.php';

    
    
    

    function animateBars() {
        const bars = document.querySelectorAll('.bar-fill[data-width]');
        if (!bars.length) return;

        
        requestAnimationFrame(() => {
            setTimeout(() => {
                bars.forEach(bar => {
                    bar.style.width = bar.dataset.width;
                });
            }, 120);
        });
    }

    
    
    
    

    function refreshWidgets() {
        fetch(DATA_API, { credentials: 'same-origin' })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (!data.success) return;

                const w = data.widgets || {};

                
                const selectors = {
                    pending_requests     : 'pending_requests',
                    active_announcements : 'active_announcements',
                    active_services      : 'active_services',
                    total_residents      : 'total_residents',
                };

                Object.entries(selectors).forEach(([key, attr]) => {
                    if (w[key] === undefined) return;
                    document.querySelectorAll(`[data-widget="${attr}"]`).forEach(el => {
                        const newVal = Number(w[key]).toLocaleString();
                        if (el.textContent.trim() !== newVal) {
                            el.textContent = newVal;
                            flashElement(el);
                        }
                    });
                });
            })
            .catch(() => {
                
            });
    }

    
    function flashElement(el) {
        el.style.transition = 'color 0.3s';
        el.style.color      = 'var(--off-primary)';
        setTimeout(() => {
            el.style.color = '';
        }, 800);
    }

    
    function init() {
        animateBars();
        setInterval(refreshWidgets, REFRESH_INTERVAL_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());