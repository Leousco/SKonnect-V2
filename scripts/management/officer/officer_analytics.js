/**
 * officer_analytics.js
 * scripts/management/officer/officer_analytics.js
 */

 document.addEventListener('DOMContentLoaded', () => {

    const DATA_API = '../../../backend/routes/officer_analytics_data.php';

    // ── PALETTE ──────────────────────────────────────────────────
    const C = {
        primary  : '#2d7d9a', primaryLt : '#d6edf5',
        green    : '#16a34a', greenLt   : '#dcfce7',
        coral    : '#c2410c', coralLt   : '#ffedd5',
        violet   : '#7c3aed', violetLt  : '#ede9fe',
        slate    : '#4f6eb4', slateLt   : '#e0e9f8',
        amber    : '#d97706', amberLt   : '#fef3c7',
        red      : '#e11d48', redLt     : '#fee2e2',
        border   : '#c8dfe8',
        muted    : '#7a96a6',
        dark     : '#1a2e3b',
    };

    // ── STATE ─────────────────────────────────────────────────────
    let activePeriod = 'month';
    let chartVolume  = null;
    let chartEvents  = null;
    let chartAnn     = null;
    let lastData     = null;

    // ── CHART.JS DEFAULTS ─────────────────────────────────────────
    Chart.defaults.font.family            = "'Poppins', sans-serif";
    Chart.defaults.font.size              = 11;
    Chart.defaults.color                  = C.muted;
    Chart.defaults.plugins.legend.display = false;

    // ── FETCH & RENDER ────────────────────────────────────────────
    function loadPeriod(period) {
        setLoading(true);
        fetch(`${DATA_API}?period=${period}`, { credentials: 'same-origin' })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (!data.success) throw new Error(data.message || 'API error');
                lastData = data;
                render(data);
                setLoading(false);
                updateTimestamp();
            })
            .catch(err => {
                setLoading(false);
                showError(err.message);
            });
    }

    function setLoading(on) {
        document.querySelectorAll('.an-panel, .off-widget-card').forEach(el => {
            el.style.opacity = on ? '0.5' : '';
            el.style.pointerEvents = on ? 'none' : '';
        });
    }

    function showError(msg) {
        console.error('Analytics error:', msg);
    }

    function updateTimestamp() {
        const el = document.querySelector('.an-last-updated strong');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                + ' — ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }
    }

    // ── RENDER ALL ────────────────────────────────────────────────
    function render(d) {
        updateKPIs(d);
        renderVolumeChart(d);
        renderServiceBars(d);
        renderEventsSection(d);
        renderAnnouncementsDonut(d);
        renderServicesList(d);
        renderActivity(d);
        document.getElementById('an-volume-period').textContent = d.period;
    }

    // ── KPIs ──────────────────────────────────────────────────────
    function updateKPIs(d) {
        setKPI('total-requests', d.kpi.totalRequests);
        setKPI('approval-rate',  d.kpi.approvalRate);
        setKPI('avg-days',       d.kpi.avgDays);
        setKPI('announcements',  d.kpi.announcements);
        setKPI('spark-pending',    d.spark.pending);
        setKPI('spark-processing', d.spark.processing);
        setKPI('spark-approved',   d.spark.approved);
        setKPI('spark-declined',   d.spark.declined);
    }

    function setKPI(key, val) {
        document.querySelectorAll(`[data-kpi="${key}"]`).forEach(el => {
            const unit = el.querySelector('.widget-unit');
            if (unit) {
                el.childNodes[0].textContent = val;
            } else {
                el.textContent = val;
            }
        });
    }

    // ── VOLUME LINE CHART ─────────────────────────────────────────
    function renderVolumeChart(d) {
        const ctx = document.getElementById('chart-volume').getContext('2d');
        if (chartVolume) chartVolume.destroy();

        chartVolume = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.volume.labels,
                datasets: [
                    {
                        label: 'Approved',
                        data: d.volume.approved,
                        borderColor: C.green,
                        backgroundColor: C.greenLt + '55',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Pending',
                        data: d.volume.pending,
                        borderColor: C.coral,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 3],
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.4,
                    },
                    {
                        label: 'Declined',
                        data: d.volume.declined,
                        borderColor: C.red,
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        borderDash: [3, 3],
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true, position: 'top', align: 'end',
                        labels: { boxWidth: 10, padding: 12, font: { size: 11 } },
                    },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: {
                        grid: { color: C.border },
                        ticks: {
                            font: { size: 10 }, maxRotation: 0,
                            // Thin out labels on daily view to avoid crowding
                            callback(val, i, ticks) {
                                if (ticks.length > 15) return i % 3 === 0 ? this.getLabelForValue(val) : '';
                                return this.getLabelForValue(val);
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: C.border },
                        ticks: { stepSize: 1, font: { size: 10 } },
                    },
                },
            },
        });
    }

    // ── SERVICE HORIZONTAL BARS ───────────────────────────────────
    function renderServiceBars(d) {
        const container = document.getElementById('an-bar-service');
        if (!d.serviceBreakdown.length) {
            container.innerHTML = '<p style="font-size:12px;color:var(--off-text-muted);padding:16px 0">No requests this period.</p>';
            return;
        }
        const max = Math.max(...d.serviceBreakdown.map(s => s.count), 1);
        container.innerHTML = d.serviceBreakdown.map(s => {
            const pct = Math.round((s.count / max) * 100);
            return `
                <div class="an-bar-row">
                    <span class="an-bar-label" title="${s.label}">${s.label}</span>
                    <div class="an-bar-track">
                        <div class="an-bar-fill bar-${s.key}" style="width:${pct}%"></div>
                    </div>
                    <span class="an-bar-count">${s.count}</span>
                </div>`;
        }).join('');
    }

    // ── EVENTS SECTION ────────────────────────────────────────────
    function renderEventsSection(d) {
        document.getElementById('an-events-grid').innerHTML = `
            <div class="an-events-stat stat-upcoming">
                <span class="an-events-stat-num">${d.events.upcoming}</span>
                <span class="an-events-stat-lbl">Upcoming</span>
            </div>
            <div class="an-events-stat stat-past">
                <span class="an-events-stat-num">${d.events.past}</span>
                <span class="an-events-stat-lbl">Past</span>
            </div>
            <div class="an-events-stat stat-total">
                <span class="an-events-stat-num">${d.events.total}</span>
                <span class="an-events-stat-lbl">Total</span>
            </div>`;

        const ctx = document.getElementById('chart-events').getContext('2d');
        if (chartEvents) chartEvents.destroy();

        chartEvents = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.events.monthlyLabels,
                datasets: [{
                    label: 'Events',
                    data: d.events.monthlyCounts,
                    backgroundColor: C.primary,
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { tooltip: { callbacks: { label: ctx => ` ${ctx.raw} event${ctx.raw !== 1 ? 's' : ''}` } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: C.border }, ticks: { stepSize: 1, precision: 0, font: { size: 10 } } },
                },
            },
        });
    }

    // ── ANNOUNCEMENTS DONUT ───────────────────────────────────────
    function renderAnnouncementsDonut(d) {
        const { published, drafts, archived } = d.announcements;
        const ctx = document.getElementById('chart-announcements').getContext('2d');
        if (chartAnn) chartAnn.destroy();

        chartAnn = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Published', 'Drafts', 'Archived'],
                datasets: [{
                    data: [published, drafts, archived],
                    backgroundColor: [C.green, C.primary, C.muted],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: false,
                cutout: '68%',
                plugins: { tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } } },
            },
        });

        document.getElementById('an-ann-legend').innerHTML = [
            { label: 'Published', count: published, color: C.green   },
            { label: 'Drafts',    count: drafts,    color: C.primary  },
            { label: 'Archived',  count: archived,  color: C.muted    },
        ].map(r => `
            <div class="an-legend-row">
                <span class="an-legend-dot" style="background:${r.color}"></span>
                <span class="an-legend-label">${r.label}</span>
                <span class="an-legend-count">${r.count}</span>
            </div>`).join('');
    }

    // ── SERVICES LIST ─────────────────────────────────────────────
    const svcIcons = {
        medical:    `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>`,
        education:  `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"/></svg>`,
        scholarship:`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497"/></svg>`,
        livelihood: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/></svg>`,
        assistance: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75s.168-.75.375-.75.375.336.375.75Zm4.875 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z"/></svg>`,
        legal:      `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>`,
        other:      `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>`,
    };

    function renderServicesList(d) {
        const list = document.getElementById('an-services-list');
        if (!d.services.length) {
            list.innerHTML = '<p style="font-size:12px;color:var(--off-text-muted);padding:16px 0">No services found.</p>';
            return;
        }
        list.innerHTML = d.services.map(svc => `
            <div class="an-service-row">
                <div class="an-service-icon svc-icon-${svc.category}">
                    ${svcIcons[svc.category] || svcIcons.other}
                </div>
                <div class="an-service-info">
                    <p class="an-service-name">${svc.name}</p>
                    <p class="an-service-requests">${svc.requests} request${svc.requests !== 1 ? 's' : ''} this period</p>
                </div>
                <span class="an-service-status ${svc.status}">
                    ${svc.status === 'active'
                        ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>Active`
                        : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>Inactive`}
                </span>
            </div>`).join('');
    }

    // ── ACTIVITY FEED ─────────────────────────────────────────────
    const activityIcons = {
        approve:  `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>`,
        decline:  `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>`,
        process:  `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>`,
        announce: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
        event:    `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5"/></svg>`,
    };

    function renderActivity(d) {
        const feed = document.getElementById('an-activity-feed');
        if (!d.activity.length) {
            feed.innerHTML = '<li style="font-size:12px;color:var(--off-text-muted);padding:16px 0">No recent activity.</li>';
            return;
        }
        feed.innerHTML = d.activity.map(a => `
            <li class="an-activity-item">
                <div class="an-activity-icon icon-${a.icon}">
                    ${activityIcons[a.type] || activityIcons.event}
                </div>
                <div class="an-activity-body">
                    <p class="an-activity-text">${a.text}</p>
                    <span class="an-activity-time">${a.time}</span>
                </div>
            </li>`).join('');
    }

    // ── PERIOD TABS ───────────────────────────────────────────────
    document.querySelectorAll('.an-period-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.an-period-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activePeriod = tab.dataset.period;
            loadPeriod(activePeriod);
        });
    });

    // ── EXPORT PDF ────────────────────────────────────────────────
    document.getElementById('an-export-btn').addEventListener('click', async () => {
        const d = lastData;
        if (!d) return;

        const btn = document.getElementById('an-export-btn');
        btn.disabled = true;
        btn.textContent = 'Generating PDF…';

        try {
            const { jsPDF } = window.jspdf;
            const pdf  = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            const PW   = 210;   // A4 width  mm
            const PH   = 297;   // A4 height mm
            const ML   = 14;    // margin left
            const MR   = 14;    // margin right
            const CW   = PW - ML - MR;   // content width
            const now  = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

            let y = 0; // current Y cursor (mm)

            // ── helpers ──────────────────────────────────────────────
            const TEAL   = [45, 125, 154];
            const DARK   = [26, 46, 59];
            const MUTED  = [122, 150, 166];
            const GREEN  = [22, 163, 74];
            const AMBER  = [217, 119, 6];
            const VIOLET = [124, 58, 237];
            const CORAL  = [194, 65, 12];
            const WHITE  = [255, 255, 255];
            const BG     = [248, 252, 254];
            const BORDER = [200, 223, 232];

            function setFont(size, style = 'normal', color = DARK) {
                pdf.setFontSize(size);
                pdf.setFont('helvetica', style);
                pdf.setTextColor(...color);
            }

            function fillRect(x, rx, ry, rw, rh, color, radius = 0) {
                pdf.setFillColor(...color);
                if (radius > 0) pdf.roundedRect(x, ry, rw, rh, radius, radius, 'F');
                else            pdf.rect(x, ry, rw, rh, 'F');
            }

            function drawLine(lx1, ly, lx2, color = BORDER) {
                pdf.setDrawColor(...color);
                pdf.setLineWidth(0.3);
                pdf.line(lx1, ly, lx2, ly);
            }

            function addPage() {
                pdf.addPage();
                y = 14;
                // subtle header stripe on continuation pages
                pdf.setFillColor(...BG);
                pdf.rect(0, 0, PW, 10, 'F');
                setFont(7, 'normal', MUTED);
                pdf.text('SKonnect | Officer Analytics Report', ML, 6.5);
                pdf.text(`${d.period}  ·  ${dateStr}`, PW - MR, 6.5, { align: 'right' });
                drawLine(0, 10, PW, BORDER);
            }

            function checkPageBreak(needed) {
                if (y + needed > PH - 14) addPage();
            }

            // ── SECTION HEADING ───────────────────────────────────
            function sectionHeading(title) {
                checkPageBreak(12);
                pdf.setFillColor(...TEAL);
                pdf.rect(ML, y, 2.5, 7, 'F');
                setFont(11, 'bold', DARK);
                pdf.text(title, ML + 5, y + 5.2);
                y += 12;
            }

            // ── CAPTURE CANVAS AS IMAGE ───────────────────────────
            async function canvasToImage(canvasId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return null;
                // Use html2canvas to capture actual rendered state
                const capture = await html2canvas(canvas, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                    logging: false,
                });
                return capture.toDataURL('image/png');
            }

            // ─────────────────────────────────────────────────────
            // PAGE 1 — COVER HEADER
            // ─────────────────────────────────────────────────────
            // Teal header band
            pdf.setFillColor(...TEAL);
            pdf.rect(0, 0, PW, 42, 'F');

            // Logo / org name
            setFont(18, 'bold', WHITE);
            pdf.text('SKonnect', ML, 18);
            setFont(10, 'normal', [180, 220, 235]);
            pdf.text('Sangguniang Kabataan Management Platform', ML, 25);

            // Report title
            setFont(13, 'bold', WHITE);
            pdf.text('Officer Analytics Report', ML, 34);
            setFont(8, 'normal', [180, 220, 235]);
            pdf.text(`Period: ${d.period}  ·  Exported: ${dateStr}, ${timeStr}`, ML, 39);

            // Period badge (right side)
            const periodLabel = { month: 'Monthly', quarter: 'Quarterly', year: 'Annual' }[activePeriod] || 'Report';
            pdf.setFillColor(255, 255, 255, 0.2);
            pdf.setFillColor(60, 150, 175);
            pdf.roundedRect(PW - ML - 32, 12, 32, 12, 3, 3, 'F');
            setFont(9, 'bold', WHITE);
            pdf.text(periodLabel, PW - ML - 16, 19.5, { align: 'center' });

            y = 52;

            // ─────────────────────────────────────────────────────
            // KPI CARDS (4-up row)
            // ─────────────────────────────────────────────────────
            const kpiCards = [
                { label: 'Total Requests',      value: d.kpi.totalRequests, unit: '',  color: TEAL   },
                { label: 'Approval Rate',        value: d.kpi.approvalRate,  unit: '',  color: GREEN  },
                { label: 'Avg. Processing Time', value: d.kpi.avgDays,       unit: 'd', color: AMBER  },
                { label: 'Announcements',        value: d.kpi.announcements, unit: '',  color: VIOLET },
            ];
            const cardW = (CW - 9) / 4;
            kpiCards.forEach((k, i) => {
                const cx = ML + i * (cardW + 3);
                // Card bg
                pdf.setFillColor(...BG);
                pdf.roundedRect(cx, y, cardW, 26, 3, 3, 'F');
                pdf.setDrawColor(...k.color);
                pdf.setLineWidth(0.5);
                pdf.roundedRect(cx, y, cardW, 26, 3, 3, 'S');
                // Top accent bar
                pdf.setFillColor(...k.color);
                pdf.roundedRect(cx, y, cardW, 3, 1.5, 1.5, 'F');
                // Label
                setFont(6.5, 'bold', MUTED);
                pdf.text(k.label.toUpperCase(), cx + cardW / 2, y + 9, { align: 'center' });
                // Value
                setFont(18, 'bold', k.color);
                pdf.text(`${k.value}${k.unit}`, cx + cardW / 2, y + 20, { align: 'center' });
            });
            y += 32;

            // ─────────────────────────────────────────────────────
            // REQUEST STATUS BREAKDOWN
            // ─────────────────────────────────────────────────────
            sectionHeading('Request Status Breakdown');

            const statusItems = [
                { label: 'Pending',    value: d.spark.pending,    color: CORAL  },
                { label: 'Processing', value: d.spark.processing, color: AMBER  },
                { label: 'Approved',   value: d.spark.approved,   color: GREEN  },
                { label: 'Declined',   value: d.spark.declined,   color: [225, 29, 72] },
            ];
            const total = statusItems.reduce((s, i) => s + Number(i.value), 0) || 1;
            const sW = (CW - 9) / 4;

            statusItems.forEach((s, i) => {
                const sx = ML + i * (sW + 3);
                const pct = Math.round((Number(s.value) / total) * 100);
                pdf.setFillColor(...BG);
                pdf.roundedRect(sx, y, sW, 22, 3, 3, 'F');
                pdf.setFillColor(...s.color);
                pdf.roundedRect(sx, y, sW, 3, 1.5, 1.5, 'F');
                setFont(7, 'bold', MUTED);
                pdf.text(s.label.toUpperCase(), sx + sW / 2, y + 9, { align: 'center' });
                setFont(15, 'bold', s.color);
                pdf.text(String(s.value), sx + sW / 2, y + 17.5, { align: 'center' });
                setFont(6.5, 'normal', MUTED);
                pdf.text(`${pct}%`, sx + sW / 2, y + 22, { align: 'center' });
            });
            y += 28;

            // ─────────────────────────────────────────────────────
            // REQUEST VOLUME CHART
            // ─────────────────────────────────────────────────────
            sectionHeading('Request Volume');
            const volImg = await canvasToImage('chart-volume');
            if (volImg) {
                checkPageBreak(60);
                pdf.addImage(volImg, 'PNG', ML, y, CW, 55, undefined, 'FAST');
                y += 59;
            }

            // ─────────────────────────────────────────────────────
            // SERVICE TYPE BREAKDOWN (horizontal bars)
            // ─────────────────────────────────────────────────────
            checkPageBreak(14 + d.serviceBreakdown.length * 9 + 10);
            sectionHeading('Requests by Service Type');

            if (d.serviceBreakdown.length) {
                const maxCount = Math.max(...d.serviceBreakdown.map(s => Number(s.count)), 1);
                const trackW  = CW - 50;
                const barH    = 5;
                const barColors = [TEAL, GREEN, VIOLET, CORAL, AMBER, [22, 163, 163], [200, 60, 60], MUTED];

                d.serviceBreakdown.forEach((s, i) => {
                    checkPageBreak(10);
                    const pct  = Number(s.count) / maxCount;
                    const bClr = barColors[i % barColors.length];
                    // Label
                    setFont(8, 'normal', DARK);
                    pdf.text(s.label, ML, y + barH - 0.5);
                    // Track bg
                    pdf.setFillColor(...BORDER);
                    pdf.roundedRect(ML + 36, y, trackW, barH, 2, 2, 'F');
                    // Fill
                    if (pct > 0) {
                        pdf.setFillColor(...bClr);
                        pdf.roundedRect(ML + 36, y, Math.max(trackW * pct, 2), barH, 2, 2, 'F');
                    }
                    // Count
                    setFont(8, 'bold', DARK);
                    pdf.text(String(s.count), PW - MR, y + barH - 0.5, { align: 'right' });
                    y += 9;
                });
                y += 4;
            } else {
                setFont(9, 'normal', MUTED);
                pdf.text('No service data for this period.', ML, y);
                y += 10;
            }

            // ─────────────────────────────────────────────────────
            // PAGE BREAK → EVENTS + ANNOUNCEMENTS
            // ─────────────────────────────────────────────────────
            checkPageBreak(60);
            sectionHeading('Events Overview');

            // Event stat boxes
            const evItems = [
                { label: 'Upcoming', value: d.events.upcoming, color: TEAL  },
                { label: 'Past',     value: d.events.past,     color: GREEN  },
                { label: 'Total',    value: d.events.total,    color: MUTED  },
            ];
            const evW = (CW - 6) / 3;
            evItems.forEach((e, i) => {
                const ex = ML + i * (evW + 3);
                pdf.setFillColor(...BG);
                pdf.roundedRect(ex, y, evW, 22, 3, 3, 'F');
                pdf.setFillColor(...e.color);
                pdf.rect(ex, y, evW, 2.5, 'F');
                setFont(7, 'bold', MUTED);
                pdf.text(e.label.toUpperCase(), ex + evW / 2, y + 9, { align: 'center' });
                setFont(16, 'bold', e.color);
                pdf.text(String(e.value), ex + evW / 2, y + 18.5, { align: 'center' });
            });
            y += 27;

            // Events bar chart
            const evImg = await canvasToImage('chart-events');
            if (evImg) {
                checkPageBreak(44);
                pdf.addImage(evImg, 'PNG', ML, y, CW, 38, undefined, 'FAST');
                y += 42;
            }

            // ─────────────────────────────────────────────────────
            // ANNOUNCEMENTS
            // ─────────────────────────────────────────────────────
            checkPageBreak(60);
            sectionHeading('Announcements Breakdown');

            // Donut chart
            const annImg = await canvasToImage('chart-announcements');
            const annData = [
                { label: 'Published', value: d.announcements.published, color: GREEN  },
                { label: 'Drafts',    value: d.announcements.drafts,    color: TEAL   },
                { label: 'Archived',  value: d.announcements.archived,  color: MUTED  },
            ];
            const annTotal = annData.reduce((s, r) => s + Number(r.value), 0) || 1;
            const chartColW = CW * 0.45;
            const legendX   = ML + chartColW + 10;

            if (annImg) {
                pdf.addImage(annImg, 'PNG', ML, y, chartColW, chartColW * 0.75, undefined, 'FAST');
            }
            // Legend beside chart
            let ly = y + 8;
            annData.forEach(r => {
                pdf.setFillColor(...r.color);
                pdf.circle(legendX + 3, ly + 2.5, 3, 'F');
                setFont(9, 'bold', DARK);
                pdf.text(r.label, legendX + 9, ly + 4.5);
                setFont(9, 'normal', MUTED);
                const pct = Math.round((Number(r.value) / annTotal) * 100);
                pdf.text(`${r.value}  (${pct}%)`, legendX + 9, ly + 10);
                ly += 18;
            });
            y += chartColW * 0.75 + 6;

            // ─────────────────────────────────────────────────────
            // SERVICES STATUS TABLE
            // ─────────────────────────────────────────────────────
            checkPageBreak(20 + d.services.length * 10);
            sectionHeading('Services Status');

            if (d.services.length) {
                // Table header
                pdf.setFillColor(...TEAL);
                pdf.roundedRect(ML, y, CW, 8, 2, 2, 'F');
                setFont(7.5, 'bold', WHITE);
                pdf.text('Service Name', ML + 4, y + 5.5);
                pdf.text('Requests', ML + CW * 0.6, y + 5.5);
                pdf.text('Status', ML + CW * 0.82, y + 5.5);
                y += 8;

                d.services.forEach((svc, i) => {
                    checkPageBreak(10);
                    const rowBg = i % 2 === 0 ? BG : WHITE;
                    pdf.setFillColor(...rowBg);
                    pdf.rect(ML, y, CW, 9, 'F');
                    drawLine(ML, y + 9, ML + CW);

                    setFont(8, 'normal', DARK);
                    pdf.text(svc.name, ML + 4, y + 6);

                    setFont(8, 'bold', DARK);
                    pdf.text(String(svc.requests), ML + CW * 0.6, y + 6);

                    const stColor = svc.status === 'active' ? GREEN : CORAL;
                    const stLabel = svc.status === 'active' ? 'Active' : 'Inactive';
                    pdf.setFillColor(...stColor);
                    pdf.roundedRect(ML + CW * 0.82, y + 1.5, 22, 6, 3, 3, 'F');
                    setFont(7, 'bold', WHITE);
                    pdf.text(stLabel, ML + CW * 0.82 + 11, y + 5.8, { align: 'center' });
                    y += 9;
                });
                y += 6;
            } else {
                setFont(9, 'normal', MUTED);
                pdf.text('No services data found.', ML, y);
                y += 10;
            }

            // ─────────────────────────────────────────────────────
            // RECENT ACTIVITY
            // ─────────────────────────────────────────────────────
            checkPageBreak(20);
            sectionHeading('Recent Activity');

            if (d.activity.length) {
                const iconColors = { approve: GREEN, decline: [225, 29, 72], process: AMBER, announce: VIOLET, event: TEAL };
                d.activity.forEach((a, i) => {
                    checkPageBreak(16);
                    const iClr = iconColors[a.type] || TEAL;
                    // Icon circle
                    pdf.setFillColor(...iClr);
                    pdf.circle(ML + 4, y + 4, 4, 'F');
                    // Text
                    setFont(8.5, 'normal', DARK);
                    // Strip HTML tags from activity text
                    const plain = a.text.replace(/<[^>]+>/g, '');
                    pdf.text(plain, ML + 12, y + 4.5, { maxWidth: CW - 40 });
                    setFont(7.5, 'normal', MUTED);
                    pdf.text(a.time, PW - MR, y + 4.5, { align: 'right' });
                    drawLine(ML, y + 11, ML + CW);
                    y += 13;
                });
            } else {
                setFont(9, 'normal', MUTED);
                pdf.text('No recent activity recorded.', ML, y);
                y += 10;
            }

            // ─────────────────────────────────────────────────────
            // FOOTER on every page
            // ─────────────────────────────────────────────────────
            const totalPages = pdf.getNumberOfPages();
            for (let p = 1; p <= totalPages; p++) {
                pdf.setPage(p);
                const footY = PH - 8;
                drawLine(ML, footY - 3, PW - MR);
                setFont(7, 'normal', MUTED);
                pdf.text(`SKonnect Officer Analytics — ${d.period}`, ML, footY);
                pdf.text(`Page ${p} of ${totalPages}`, PW - MR, footY, { align: 'right' });
                pdf.text(`Generated ${dateStr}, ${timeStr}`, PW / 2, footY, { align: 'center' });
            }

            // ─────────────────────────────────────────────────────
            // SAVE
            // ─────────────────────────────────────────────────────
            const filename = `SKonnect_Analytics_${activePeriod}_${now.toISOString().slice(0, 10)}.pdf`;
            pdf.save(filename);

        } catch (err) {
            console.error('PDF export error:', err);
            alert('PDF export failed. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg> Export Report`;
        }
    });

    // ── INIT ──────────────────────────────────────────────────────
    loadPeriod(activePeriod);
});