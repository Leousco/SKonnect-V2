/**
 * officer_dashboard.js
 * Handles:
 *  1. Bar-chart fill animation on page load
 *  2. Auto-refresh of widget counts every 60 seconds
 */

 (function () {
    'use strict';

    // ── CONFIG ───────────────────────────────────────────────────
    const REFRESH_INTERVAL_MS = 60_000; // 60 seconds
    // Path to the data API — relative from the page location
    const DATA_API = '../../../backend/routes/officer_dashboard_data.php';

    // ── 1. BAR CHART ANIMATION ───────────────────────────────────
    // Bars are rendered with width:0% by PHP.
    // We read the target from data-width and animate after a brief delay.

    function animateBars() {
        const bars = document.querySelectorAll('.bar-fill[data-width]');
        if (!bars.length) return;

        // Small delay so the browser has painted the 0-width state first
        requestAnimationFrame(() => {
            setTimeout(() => {
                bars.forEach(bar => {
                    bar.style.width = bar.dataset.width;
                });
            }, 120);
        });
    }

    // ── 2. AUTO-REFRESH ──────────────────────────────────────────
    // Fetches the JSON API and silently updates widget numbers.
    // If the fetch fails for any reason we simply skip that cycle
    // so the page never breaks.

    function refreshWidgets() {
        fetch(DATA_API, { credentials: 'same-origin' })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (!data.success) return;

                const w = data.widgets || {};

                // Update every element that has a matching data-widget attribute
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
                // Silent fail — stale data is acceptable during a single missed cycle
            });
    }

    // Briefly highlights an element when its value changes
    function flashElement(el) {
        el.style.transition = 'color 0.3s';
        el.style.color      = 'var(--off-primary)';
        setTimeout(() => {
            el.style.color = '';
        }, 800);
    }

    // ── INIT ─────────────────────────────────────────────────────
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