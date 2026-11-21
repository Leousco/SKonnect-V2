document.addEventListener('DOMContentLoaded', function () {

    const API_URL = '../../../backend/routes/dashboard_stats.php';
    const MODULES_URL = '../../../backend/routes/dashboard_modules.php';

    const CATEGORY_LABELS = {
        medical:     'Medical',
        education:   'Education',
        scholarship: 'Scholarship',
        livelihood:  'Livelihood',
        assistance:  'Assistance',
        legal:       'Legal',
        other:       'Others',
        inquiry:     'Inquiry',
        complaint:   'Complaint',
        suggestion:  'Suggestion',
        event_question: 'Event',
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

    // Fetch critical stats immediately
    fetch(API_URL)
        .then(res => {
            if (!res.ok) throw new Error(`Dashboard API returned HTTP ${res.status}`);
            return res.json();
        })
        .then(json => {
            if (json.status !== 'success') {
                throw new Error(json.message || 'Dashboard data could not be loaded.');
            }
            const d = json.data;
            populateWidgets(d);
            populatePendingTable(d.pendingList);
            populateBarChart(d.requestsByServiceType);
            populateSparkline(d.registrations, d);
            finishLoading();
        })
        .catch(err => {
            console.error('Dashboard fetch failed:', err);
            showLoadingError();
        });

    // Fetch modules data with lower priority (non-blocking)
    const scheduleModulesLoad = window.requestIdleCallback || window.setTimeout;
    scheduleModulesLoad(() => {
        fetch(MODULES_URL)
            .then(res => {
                if (!res.ok) throw new Error(`Modules API returned HTTP ${res.status}`);
                return res.json();
            })
            .then(json => {
                if (json.status !== 'success') {
                    throw new Error(json.message || 'Module data could not be loaded.');
                }
                const d = json.data;
                populateThreads(d.threads);
                populateReports(d.reports);
                populateAnnouncements(d.announcements);
                populateActivity(d.activity);
                populateEvents(d.events);
            })
            .catch(err => {
                console.error('Modules fetch failed:', err);
                showModulesError();
            });
    }, { timeout: 500 });


    function finishLoading() {
        document.querySelectorAll('.admin-widgets, .sparkline-wrap').forEach(section => {
            section.classList.remove('is-loading');
            section.setAttribute('aria-busy', 'false');
            section.querySelector('.admin-dashboard-skeleton')?.remove();
        });

        document.querySelectorAll('.requests-table-wrap, .bar-chart-wrap').forEach(section => {
            section.setAttribute('aria-busy', 'false');
        });
    }

    function showLoadingError() {
        finishLoading();

        document.querySelectorAll('.widget-number').forEach(el => {
            el.textContent = '—';
        });
        document.querySelectorAll('.widget-trend').forEach(el => {
            el.textContent = 'Unavailable';
        });

        const pendingBody = document.querySelector('.requests-table tbody');
        if (pendingBody) {
            pendingBody.innerHTML = '<tr><td colspan="5" class="dashboard-load-error">Unable to load service requests.</td></tr>';
        }

        const chart = document.querySelector('.bar-chart-wrap');
        if (chart) chart.innerHTML = '<p class="dashboard-load-error">Unable to load chart data.</p>';

        const registrations = document.querySelector('.sparkline-wrap');
        if (registrations) {
            const svg = registrations.querySelector('.sparkline-svg');
            const labels = registrations.querySelector('.sparkline-labels');
            if (svg) svg.hidden = true;
            if (labels) labels.hidden = true;
            registrations.querySelectorAll('.spark-val').forEach(el => {
                el.textContent = '—';
            });
        }
    }

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

    function populateBarChart(requestsByType) {
        const wrap = document.querySelector('.bar-chart-wrap');
        if (!wrap) return;

        if (!requestsByType || requestsByType.length === 0) {
            wrap.innerHTML = `<p style="text-align:center;color:var(--admin-text-muted);font-size:13px;">No service requests yet.</p>`;
            return;
        }

        const max = Math.max(...requestsByType.map(type => Number(type.count) || 0), 1);

        wrap.innerHTML = requestsByType.map((type, i) => {
            const count      = Number(type.count) || 0;
            const pct        = Math.round((count / max) * 100);
            const colorClass = BAR_COLOR_CLASS[i] || 'bar-muted';
            const label      = escapeHtml(CATEGORY_LABELS[type.category] || type.category);
            return `
                <div class="bar-row">
                    <span class="bar-label">${label}</span>
                    <div class="bar-track"><div class="bar-fill ${colorClass}" style="width:0%" data-target="${pct}%"></div></div>
                    <span class="bar-count">${count}</span>
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

    function showModulesError() {
        ['threads', 'reports', 'announcements', 'activity', 'events'].forEach(module => {
            const container = document.querySelector(`[data-module="${module}"]`);
            if (container) {
                container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());
                container.innerHTML = '<p class="dashboard-module-empty">Unable to load content.</p>';
            }
        });
    }

    function populateThreads(threads) {
        const container = document.querySelector('[data-module="threads"]');
        if (!container) return;
        container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());

        if (!threads || threads.length === 0) {
            container.innerHTML = '<p class="dashboard-module-empty">No community threads yet.</p>';
            return;
        }

        container.innerHTML = threads.map(thread => {
            const category = CATEGORY_LABELS[thread.category] || 'Other';
            const categoryClass = CATEGORY_LABELS[thread.category] ? thread.category : 'other';
            const authorNameParts = thread.author_name.trim().split(/\s+/);
            let authorInitials = (authorNameParts[0] || '').substring(0, 1).toUpperCase();
            if (authorNameParts.length > 1) {
                authorInitials += (authorNameParts[authorNameParts.length - 1] || '').substring(0, 1).toUpperCase();
            }
            const date = new Date(thread.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            return `
                <a class="dashboard-module-item dashboard-community-item" href="admin_threads.php">
                    <span class="dashboard-community-avatar" aria-hidden="true">${authorInitials}</span>
                    <span class="dashboard-module-item-content">
                        <span class="dashboard-community-heading">
                            <span class="dashboard-module-item-title">${escapeHtml(thread.subject)}</span>
                            <span class="dashboard-module-badge dashboard-community-category category-${categoryClass}">${category}</span>
                        </span>
                        <span class="dashboard-module-item-meta dashboard-community-meta">
                            Posted by ${escapeHtml(thread.author_name)}
                            <span aria-hidden="true">&middot;</span>
                            ${date}
                        </span>
                    </span>
                    <span class="dashboard-community-count" aria-label="${thread.comment_count} comments">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8Z" />
                        </svg>
                        ${thread.comment_count}
                    </span>
                </a>
            `;
        }).join('');
    }

    function populateReports(reports) {
        const container = document.querySelector('[data-module="reports"]');
        if (!container) return;
        container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());

        if (!reports || reports.length === 0) {
            container.innerHTML = '<p class="dashboard-module-empty">No pending reports to review.</p>';
            return;
        }

        container.innerHTML = reports.map(report => {
            const date = new Date(report.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return `
                <a class="dashboard-module-item" href="admin_reports.php">
                    <span class="dashboard-module-item-icon module-icon-report" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v3.75m-9.3 3.4c-.9 1.5.2 3.4 1.9 3.4h14.8c1.7 0 2.8-1.9 1.9-3.4L13.9 3.4c-.9-1.5-3-1.5-3.9 0L2.7 16.1ZM12 15.8h.01v.01H12Z" />
                        </svg>
                    </span>
                    <span class="dashboard-module-item-content">
                        <span class="dashboard-module-item-title">${escapeHtml(report.content || '(deleted content)')}</span>
                        <span class="dashboard-module-item-meta">
                            ${escapeHtml(report.reporter_name)}
                            <span aria-hidden="true">&middot;</span>
                            ${date}
                        </span>
                        <span class="dashboard-module-badges">
                            <span class="dashboard-module-badge report-type">${escapeHtml(report.type.charAt(0).toUpperCase() + report.type.slice(1))}</span>
                            <span class="dashboard-module-badge reason-${escapeHtml(report.reason)}">${escapeHtml(report.reason.charAt(0).toUpperCase() + report.reason.slice(1))}</span>
                        </span>
                    </span>
                </a>
            `;
        }).join('');
    }

    function populateAnnouncements(announcements) {
        const container = document.querySelector('[data-module="announcements"]');
        if (!container) return;
        container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());

        if (!announcements || announcements.length === 0) {
            container.innerHTML = '<p class="dashboard-module-empty">No announcements yet.</p>';
            return;
        }

        container.innerHTML = announcements.map(announcement => {
            const date = new Date(announcement.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return `
                <a class="dashboard-module-item" href="admin_announcements.php">
                    <span class="dashboard-module-item-icon module-icon-announcement" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.3 15.8c-.7-.1-1.4-.1-2.1-.1H7.5a4.5 4.5 0 1 1 0-9h.8c.7 0 1.4 0 2.1-.1m-.1 9.2c.3 1 .6 1.9 1 2.8.3.6.1 1.2-.4 1.5l-.7.4c-.6.3-1.3.1-1.5-.5a21 21 0 0 1-1.4-4.3m3.1.1a18 18 0 0 1-.6-4.6c0-1.6.2-3.1.6-4.6m0 9.2a23.8 23.8 0 0 1 8.8 2.5m-8.8-11.7a23.8 23.8 0 0 1 8.8-2.5m0 0a24 24 0 0 0-.4-1.1m.4 1.1a24 24 0 0 1 1 5.4m-1 8.9a24 24 0 0 0-.4 1.1m.4-1.1a24 24 0 0 0 1-5.4m0-3.5a2.2 2.2 0 0 1 0 3.5" />
                        </svg>
                    </span>
                    <span class="dashboard-module-item-content">
                        <span class="dashboard-module-item-title">${escapeHtml(announcement.title)}</span>
                        <span class="dashboard-module-item-meta">
                            ${escapeHtml(announcement.category.charAt(0).toUpperCase() + announcement.category.slice(1))}
                            <span aria-hidden="true">&middot;</span>
                            ${date}
                        </span>
                    </span>
                    <span class="dashboard-module-badge announcement-status status-${escapeHtml(announcement.status)}">
                        ${escapeHtml(announcement.status.charAt(0).toUpperCase() + announcement.status.slice(1))}
                    </span>
                </a>
            `;
        }).join('');
    }

    function populateActivity(activity) {
        const container = document.querySelector('[data-module="activity"]');
        if (!container) return;
        container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());

        if (!activity || activity.length === 0) {
            container.innerHTML = '<p class="dashboard-module-empty">No recent activity recorded.</p>';
            return;
        }

        container.innerHTML = activity.map(entry => {
            let description = entry.description.trim();
            try {
                const metadata = JSON.parse(description);
                if (typeof metadata === 'object' && metadata !== null) {
                    const parts = [];
                    if (metadata.target_type && metadata.target_name) {
                        parts.push(`${metadata.target_type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}: "${metadata.target_name}"`);
                    } else if (metadata.target_name) {
                        parts.push(`"${metadata.target_name}"`);
                    }
                    if (metadata.target_user) parts.push(`by ${metadata.target_user}`);
                    if (metadata.notes) parts.push(metadata.notes);
                    if (!parts.length) {
                        for (const [key, value] of Object.entries(metadata)) {
                            if (key !== 'target_id' && typeof value !== 'object') {
                                parts.push(`${key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}: ${value}`);
                            }
                        }
                    }
                    description = parts.join(' — ') || 'Activity recorded.';
                }
            } catch (e) { /* not JSON, use as-is */ }

            const actorParts = entry.user_name.trim().split(/\s+/).filter(Boolean);
            let actorInitials = (actorParts[0] || 'S').substring(0, 1).toUpperCase();
            if (actorParts.length > 1) {
                actorInitials += (actorParts[actorParts.length - 1] || '').substring(0, 1).toUpperCase();
            }

            const date = new Date(entry.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const time = new Date(entry.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

            return `
                <div class="activity-entry">
                    <div class="activity-avatar" aria-hidden="true">${actorInitials}</div>
                    <div class="activity-info">
                        <p>${escapeHtml(description)}</p>
                        <span>${date} · ${time}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function populateEvents(events) {
        const container = document.querySelector('[data-module="events"]');
        if (!container) return;
        container.querySelectorAll('.admin-dashboard-skeleton').forEach(el => el.remove());

        if (!events || events.length === 0) {
            container.innerHTML = '<li class="admin-event-item" style="justify-content:center;color:var(--admin-text-muted);font-size:13px;">No upcoming events.</li>';
            return;
        }

        container.innerHTML = events.map(event => {
            const dt = new Date(event.event_date);
            const day = dt.getDate();
            const mon = dt.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
            
            let meta = escapeHtml(event.location || '');
            if (event.event_time) {
                const timeFmt = new Date(`1970-01-01T${event.event_time}`).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                meta = meta ? `${meta} · ${timeFmt}` : timeFmt;
            }

            return `
                <li class="admin-event-item">
                    <div class="event-date-badge">
                        <span class="event-day">${day}</span>
                        <span class="event-mon">${mon}</span>
                    </div>
                    <div class="event-info">
                        <strong>${escapeHtml(event.title)}</strong>
                        ${meta ? `<span>${meta}</span>` : ''}
                    </div>
                </li>
            `;
        }).join('');
    }

});