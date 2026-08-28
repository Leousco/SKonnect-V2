document.addEventListener('DOMContentLoaded', function () {

    const API_URL = '../../../backend/routes/dashboard_stats.php';

    const CATEGORY_LABELS = {
        medical:     'Medical Assist.',
        education:   'Education',
        scholarship: 'Scholarship',
        livelihood:  'Livelihood',
        assistance:  'Assistance',
        legal:       'Legal Aid',
        other:       'Others',
    };

    const CATEGORY_BADGE_CLASS = {
        scholarship: 'badge-scholarship',
        medical:     'badge-medical',
        livelihood:  'badge-livelihood',
        education:   'badge-education',
        assistance:  'badge-assistance',
        legal:       'badge-legal',
        other:       'badge-other',
    };

    const BAR_COLOR_CLASS = [
        'bar-violet', 'bar-amber', 'bar-indigo', 'bar-teal', 'bar-muted'
    ];

    fetch(API_URL)
        .then(res => res.json())
        .then(json => {
            if (json.status !== 'success') {
                console.error('Dashboard API error:', json.message);
                return;
            }
            const d = json.data;
            populateWidgets(d);
            populatePendingTable(d.pendingList);
            populateBarChart(d.byCategory);
            populateSparkline(d.registrations, d);
        })
        .catch(err => console.error('Dashboard fetch failed:', err));

    function populateWidgets(d) {
        setWidget(
            '.widget-violet .widget-number',
            d.totalMembers,
            '.widget-violet .widget-trend',
            `▲ ${d.membersThisMonth} this month`,
            'up'
        );
        setWidget(
            '.widget-amber .widget-number',
            d.pendingRequests,
            '.widget-amber .widget-trend',
            d.pendingRequests > 0 ? '▶ Needs attention' : '✓ All clear',
            d.pendingRequests > 0 ? 'warning' : 'up'
        );
        setWidget(
            '.widget-indigo .widget-number',
            d.announcements,
            '.widget-indigo .widget-trend',
            d.expiringSoon > 0 ? `${d.expiringSoon} expiring soon` : 'None expiring soon',
            d.expiringSoon > 0 ? 'neutral' : 'up'
        );
        setWidget(
            '.widget-red .widget-number',
            d.flaggedReports,
            '.widget-red .widget-trend',
            d.flaggedReports > 0 ? '▲ Review required' : '✓ No pending reports',
            d.flaggedReports > 0 ? 'danger' : 'up'
        );

        document.querySelectorAll('.widget-number').forEach(el => {
            const target = parseInt(el.textContent, 10);
            if (isNaN(target)) return;
            let start = 0;
            const step = Math.max(1, Math.ceil(target / (800 / 16)));
            const timer = setInterval(() => {
                start = Math.min(start + step, target);
                el.textContent = start;
                if (start >= target) clearInterval(timer);
            }, 16);
        });
    }

    function setWidget(numberSel, value, trendSel, trendText, trendClass) {
        const numEl   = document.querySelector(numberSel);
        const trendEl = document.querySelector(trendSel);
        if (numEl)   numEl.textContent = value;
        if (trendEl) {
            trendEl.textContent = trendText;
            trendEl.className   = `widget-trend ${trendClass}`;
        }
    }

    function populatePendingTable(list) {
        const tbody = document.querySelector('.requests-table tbody');
        if (!tbody) return;

        if (!list || list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--admin-text-muted);">No pending requests</td></tr>`;
            return;
        }

        tbody.innerHTML = list.map(row => {
            const initials    = row.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const badgeClass  = CATEGORY_BADGE_CLASS[row.category] || 'badge-other';
            const label       = CATEGORY_LABELS[row.category] || row.category;
            const date        = new Date(row.submitted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const statusClass = row.status === 'action_required' ? 'status-review' : 'status-pending';
            const statusLabel = row.status === 'action_required' ? 'Under Review' : 'Pending';

            return `
                <tr>
                    <td>
                        <div class="req-name">
                            <div class="req-avatar">${initials}</div>
                            ${escapeHtml(row.full_name)}
                        </div>
                    </td>
                    <td><span class="req-badge ${badgeClass}">${label}</span></td>
                    <td>${date}</td>
                    <td><span class="status-pill ${statusClass}">${statusLabel}</span></td>
                    <td><a href="admin_service_requests.php?id=${row.id}" class="action-link">Review</a></td>
                </tr>
            `;
        }).join('');
    }

    function populateBarChart(byCategory) {
        const wrap = document.querySelector('.bar-chart-wrap');
        if (!wrap) return;

        if (!byCategory || byCategory.length === 0) {
            wrap.innerHTML = `<p style="color:var(--admin-text-muted);font-size:13px;">No data for this month.</p>`;
            return;
        }

        const max = Math.max(...byCategory.map(c => c.count));

        wrap.innerHTML = byCategory.map((cat, i) => {
            const pct        = max > 0 ? Math.round((cat.count / max) * 100) : 0;
            const colorClass = BAR_COLOR_CLASS[i] || 'bar-muted';
            const label      = CATEGORY_LABELS[cat.category] || cat.category;
            return `
                <div class="bar-row">
                    <span class="bar-label">${label}</span>
                    <div class="bar-track"><div class="bar-fill ${colorClass}" style="width:0%" data-target="${pct}%"></div></div>
                    <span class="bar-count">${cat.count}</span>
                </div>
            `;
        }).join('');

        requestAnimationFrame(() => {
            setTimeout(() => {
                wrap.querySelectorAll('.bar-fill').forEach(bar => {
                    bar.style.width = bar.dataset.target;
                });
            }, 100);
        });
    }

    function populateSparkline(registrations, d) {
        const statEls = document.querySelectorAll('.spark-val');
        if (statEls.length >= 4) {
            statEls[0].textContent = `+${d.membersThisMonth}`;
            statEls[1].textContent = d.totalMembers;
            statEls[2].textContent = `${d.activeRate}%`;
            statEls[3].textContent = `+${d.membersSince6}`;
        }

        if (!registrations || registrations.length === 0) return;

        const svg    = document.querySelector('.sparkline-svg');
        if (!svg) return;

        const counts = registrations.map(r => parseInt(r.count));
        const labels = registrations.map(r => r.month);
        const maxVal = Math.max(...counts, 1);
        const W = 560, H = 120, PAD = 10;

        const points = counts.map((v, i) => {
            const x = counts.length === 1 ? W / 2 : (i / (counts.length - 1)) * W;
            const y = H - PAD - (v / maxVal) * (H - PAD * 2);
            return [x, y];
        });

        const pathD = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0]},${p[1]}`).join(' ');
        const areaD = pathD + ` L${W},${H} L0,${H} Z`;

        svg.innerHTML = `
            <defs>
                <linearGradient id="sparkGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.25"/>
                    <stop offset="100%" stop-color="#7c3aed" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <path d="${pathD}" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="${areaD}" fill="url(#sparkGrad)"/>
        `;

        const labelsWrap = document.querySelector('.sparkline-labels');
        if (labelsWrap && labels.length > 0) {
            labelsWrap.innerHTML = labels.map(l => `<span>${l}</span>`).join('');
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});