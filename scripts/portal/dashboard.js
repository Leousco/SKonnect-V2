(function () {

    const API = '../../backend/controllers/DashboardController.php';
  
    const EVENT_COLORS = [
        { bg: '#dbeafe', dot: '#2563eb', text: '#1e3a8a' },
        { bg: '#dcfce7', dot: '#16a34a', text: '#14532d' },
        { bg: '#fef3c7', dot: '#d97706', text: '#78350f' },
        { bg: '#fce7f3', dot: '#db2777', text: '#831843' },
        { bg: '#ede9fe', dot: '#7c3aed', text: '#4c1d95' },
        { bg: '#ffedd5', dot: '#ea580c', text: '#7c2d12' },
    ];
  
    const MONTHS       = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  
    const today   = new Date();
    let current   = new Date(today.getFullYear(), today.getMonth(), 1);
    let eventsMap = {};
  
    const ACTIVITY_ICONS = {
        thread:            { icon: '💬', label: 'Thread' },
        request_submitted: { icon: '📋', label: 'Request' },
        comment:           { icon: '💭', label: 'Comment' },
        reply:             { icon: '↩️', label: 'Reply' },
    };
  
    function pad(n) { return String(n).padStart(2, '0'); }
  
    function formatDate(dateStr) {
        const [y, m, d] = dateStr.split('-').map(Number);
        return `${MONTHS_SHORT[m - 1]} ${d}, ${y}`;
    }
  
    function timeAgo(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)   return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        const d = new Date(dateStr);
        return `${MONTHS_SHORT[d.getMonth()]} ${d.getDate()}`;
    }
  
    function daysUntil(dateStr) {
        const [y, m, d] = dateStr.split('-').map(Number);
        const target = new Date(y, m - 1, d);
        const diff = Math.round((target - today) / 86400000);
        if (diff === 0) return 'Today';
        if (diff === 1) return 'Tomorrow';
        if (diff < 0)  return `${Math.abs(diff)}d ago`;
        return `In ${diff} days`;
    }
  
    async function fetchJSON(action) {
        const res = await fetch(`${API}?action=${action}`);
        return res.json();
    }
  
    // ── STATS ────────────────────────────────────────────────
    async function loadStats() {
        const data = await fetchJSON('stats');
        if (data.status !== 'success') return;
        const s = data.data;
        document.getElementById('stat-requests').textContent = s.active_requests;
        document.getElementById('stat-posts').textContent    = s.community_posts;
        document.getElementById('stat-events').textContent   = s.upcoming_events;
        document.getElementById('stat-notifs').textContent   = s.unread_notifs;
    }
  
    // ── ACTIVITY ─────────────────────────────────────────────
    async function loadActivity() {
        const listEl = document.getElementById('activity-list');
        const data   = await fetchJSON('activity');
  
        if (data.status !== 'success' || !data.data.length) {
            listEl.innerHTML = '<p class="empty-state">No recent activity yet.</p>';
            return;
        }
  
        const items = data.data.slice(0, 7);
  
        listEl.innerHTML = items.map(item => {
            const meta      = ACTIVITY_ICONS[item.type] || { icon: '📌', label: 'Activity' };
            const typeClass = item.type.replace(/_/g, '-');
            return `
                <div class="activity-item">
                    <div class="activity-dot ${typeClass}" title="${meta.label}">${meta.icon}</div>
                    <div class="activity-meta">
                        <p>${escapeHtml(item.description)}</p>
                        <span class="activity-date">${timeAgo(item.activity_at)}</span>
                    </div>
                </div>`;
        }).join('');
    }
  
    // ── ANNOUNCEMENTS ────────────────────────────────────────
    async function loadAnnouncements() {
        const listEl = document.getElementById('announcement-list');
        const data   = await fetchJSON('announcements');
  
        if (data.status !== 'success' || !data.data.length) {
            listEl.innerHTML = '<li class="empty-state">No announcements yet.</li>';
            return;
        }
  
        const categoryBadge = {
            urgent:  'badge-urgent',
            event:   'badge-event',
            program: 'badge-program',
            meeting: 'badge-meeting',
            notice:  'badge-notice',
        };
  
        listEl.innerHTML = data.data.map(ann => {
            const badge = categoryBadge[ann.category] || '';
            const date  = timeAgo(ann.published_at);
            return `
                <li>
                    <a href="announcement_view.php?id=${ann.id}">
                        <span class="ann-title">${escapeHtml(ann.title)}</span>
                        <span class="ann-meta">
                            <span class="ann-badge ${badge}">${ann.category}</span>
                            <span class="ann-date">${date}</span>
                        </span>
                    </a>
                </li>`;
        }).join('');
    }
  
    // ── EVENTS / CALENDAR ────────────────────────────────────
    async function loadEvents() {
        const data = await fetchJSON('events');
        if (data.status !== 'success') return;
  
        eventsMap = {};
        data.data.forEach((ev, i) => {
            eventsMap[ev.event_date] = {
                ...ev,
                title: decodeHtml(ev.title),
                color: EVENT_COLORS[i % EVENT_COLORS.length],
            };
        });
  
        renderCalendar();
    }
  
    function renderCalendar() {
        const year  = current.getFullYear();
        const month = current.getMonth();
  
        document.querySelector('.month-year').textContent = `${MONTHS[month]} ${year}`;
  
        const datesEl = document.querySelector('.calendar-dates');
        datesEl.innerHTML = '';
  
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
  
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-day empty';
            datesEl.appendChild(empty);
        }
  
        for (let d = 1; d <= lastDate; d++) {
            const key     = `${year}-${pad(month + 1)}-${pad(d)}`;
            const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const ev      = eventsMap[key];
  
            const cell = document.createElement('div');
            cell.className = 'cal-day' + (isToday ? ' today' : '') + (ev ? ' event' : '');
            cell.textContent = d;
  
            if (ev) {
                if (!isToday) {
                    cell.style.background  = ev.color.bg;
                    cell.style.color       = ev.color.text;
                    cell.style.fontWeight  = '600';
                }
                const dot = document.createElement('span');
                dot.className       = 'cal-event-dot';
                dot.style.background = isToday ? 'white' : ev.color.dot;
                cell.appendChild(dot);
                cell.title = ev.title;
            }
  
            datesEl.appendChild(cell);
        }
  
        renderEventsList(year, month);
        renderLegend(year, month);
    }
  
    function renderEventsList(year, month) {
        const listEl  = document.getElementById('events-list');
        const emptyEl = document.getElementById('events-empty');
        listEl.innerHTML = '';
  
        const monthEvents = Object.entries(eventsMap)
            .filter(([key]) => {
                const [y, m] = key.split('-').map(Number);
                return y === year && m - 1 === month;
            })
            .sort(([a], [b]) => a.localeCompare(b));
  
        if (!monthEvents.length) {
            emptyEl.style.display = 'block';
            return;
        }
        emptyEl.style.display = 'none';
  
        monthEvents.forEach(([dateStr, ev]) => {
            const [, , d] = dateStr.split('-').map(Number);
            const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const isPast  = new Date(dateStr) < today && !isToday;
  
            const timeStr = ev.event_time
                ? formatTime(ev.event_time) + (ev.event_time_end ? ' – ' + formatTime(ev.event_time_end) : '')
                : '';
  
            const li = document.createElement('li');
            li.className = 'event-item';
            li.innerHTML = `
                <div class="event-color-bar" style="background:${ev.color.dot}"></div>
                <div class="event-item-body" style="background:${ev.color.bg}">
                    <div class="event-item-top">
                        <span class="event-item-label" style="color:${ev.color.text}">${escapeHtml(ev.title)}</span>
                        ${isToday ? '<span class="event-badge today-badge">Today</span>' : ''}
                        ${isPast  ? '<span class="event-badge past-badge">Past</span>'  : ''}
                    </div>
                    <div class="event-item-meta">
                        <span class="event-item-date">${formatDate(dateStr)}${timeStr ? ' · ' + timeStr : ''}</span>
                        ${ev.location ? `<span class="event-item-location">📍 ${escapeHtml(ev.location)}</span>` : ''}
                        <span class="event-item-countdown" style="color:${ev.color.dot}">${daysUntil(dateStr)}</span>
                    </div>
                </div>`;
            li.addEventListener('click', () => openModal(ev, dateStr));
            listEl.appendChild(li);
        });
    }
  
    function renderLegend(year, month) {
        const legendEl = document.getElementById('legend-events');
        legendEl.innerHTML = '';
  
        const monthEvents = Object.entries(eventsMap).filter(([key]) => {
            const [y, m] = key.split('-').map(Number);
            return y === year && m - 1 === month;
        });
  
        if (!monthEvents.length) return;
  
        monthEvents.forEach(([, ev]) => {
            const item = document.createElement('div');
            item.className = 'legend-item';
            item.title = ev.title; // full name on hover
            item.innerHTML = `<div class="legend-dot" style="background:${ev.color.dot}"></div><span>${escapeHtml(ev.title)}</span>`;
            legendEl.appendChild(item);
        });
    }
  
    function formatTime(timeStr) {
        if (!timeStr) return '';
        const [h, m] = timeStr.split(':').map(Number);
        const period = h >= 12 ? 'PM' : 'AM';
        const hour   = h % 12 || 12;
        return `${hour}:${pad(m)} ${period}`;
    }
  
    function decodeHtml(str) {
      const el = document.createElement('textarea');
      el.innerHTML = str;
      return el.value;
  }

  function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
  
    // ── EVENT MODAL ───────────────────────────────────────────
  
    function buildModal() {
        const overlay = document.createElement('div');
        overlay.className = 'event-modal-overlay';
        overlay.id = 'event-modal-overlay';
        overlay.innerHTML = `
            <div class="event-modal" id="event-modal" role="dialog" aria-modal="true">
                <div class="event-modal-header">
                    <div class="event-modal-header-left">
                        <div class="event-modal-color-pill" id="modal-color-pill"></div>
                        <div class="event-modal-title-block">
                            <div class="event-modal-title" id="modal-title"></div>
                            <span class="event-modal-countdown" id="modal-countdown"></span>
                        </div>
                    </div>
                    <button class="event-modal-close" id="modal-close" aria-label="Close">&#x2715;</button>
                </div>
                <div class="event-modal-body">
                    <div class="event-modal-meta-row" id="modal-row-date">
                        <div class="event-modal-meta-icon">📅</div>
                        <div class="event-modal-meta-text">
                            <span class="event-modal-meta-label">Date</span>
                            <span class="event-modal-meta-value" id="modal-date"></span>
                        </div>
                    </div>
                    <div class="event-modal-meta-row" id="modal-row-time" style="display:none">
                        <div class="event-modal-meta-icon">🕐</div>
                        <div class="event-modal-meta-text">
                            <span class="event-modal-meta-label">Time</span>
                            <span class="event-modal-meta-value" id="modal-time"></span>
                        </div>
                    </div>
                    <div class="event-modal-meta-row" id="modal-row-location" style="display:none">
                        <div class="event-modal-meta-icon">📍</div>
                        <div class="event-modal-meta-text">
                            <span class="event-modal-meta-label">Location</span>
                            <span class="event-modal-meta-value" id="modal-location"></span>
                        </div>
                    </div>
                    <div class="event-modal-divider" id="modal-divider" style="display:none"></div>
                    <div id="modal-row-desc" style="display:none">
                        <span class="event-modal-meta-label" style="display:block;margin-bottom:6px;">Description</span>
                        <p class="event-modal-description" id="modal-description"></p>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(overlay);
  
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
        document.getElementById('modal-close').addEventListener('click', closeModal);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    }
  
    function openModal(ev, dateStr) {
        const overlay   = document.getElementById('event-modal-overlay');
        const timeStr   = ev.event_time
            ? formatTime(ev.event_time) + (ev.event_time_end ? ' – ' + formatTime(ev.event_time_end) : '')
            : '';
        const countdown = daysUntil(dateStr);
  
        document.getElementById('modal-title').textContent      = ev.title;
        document.getElementById('modal-date').textContent       = formatDate(dateStr);
        document.getElementById('modal-color-pill').style.background = ev.color.dot;
  
        const countdownEl = document.getElementById('modal-countdown');
        countdownEl.textContent = countdown;
        countdownEl.style.background = ev.color.bg;
        countdownEl.style.color      = ev.color.text;
  
        const rowTime = document.getElementById('modal-row-time');
        if (timeStr) {
            document.getElementById('modal-time').textContent = timeStr;
            rowTime.style.display = 'flex';
        } else {
            rowTime.style.display = 'none';
        }
  
        const rowLoc = document.getElementById('modal-row-location');
        if (ev.location) {
            document.getElementById('modal-location').textContent = ev.location;
            rowLoc.style.display = 'flex';
        } else {
            rowLoc.style.display = 'none';
        }
  
        const hasDesc = ev.description && ev.description.trim();
        document.getElementById('modal-divider').style.display  = hasDesc ? 'block' : 'none';
        const rowDesc = document.getElementById('modal-row-desc');
        if (hasDesc) {
            document.getElementById('modal-description').textContent = ev.description;
            rowDesc.style.display = 'block';
        } else {
            rowDesc.style.display = 'none';
        }
  
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
  
    function closeModal() {
        const overlay = document.getElementById('event-modal-overlay');
        if (!overlay) return;
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
  
    // ── CALENDAR NAV ─────────────────────────────────────────
    document.querySelector('.prev-month').addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() - 1, 1);
        renderCalendar();
    });
  
    document.querySelector('.next-month').addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
        renderCalendar();
    });
  
    // ── INIT ─────────────────────────────────────────────────
    buildModal();
    loadStats();
    loadActivity();
    loadAnnouncements();
    loadEvents();
  
  })();