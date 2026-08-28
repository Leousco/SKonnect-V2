(function () {
  const DATA_API      = '../../../backend/routes/officer_users_data.php';
  const AVATAR_COLORS = ['av-0', 'av-1', 'av-2', 'av-3', 'av-4'];
  const MONTHS_SHORT  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  let usersData    = [];
  let searchQuery  = '';
  let filterStatus = 'all';
  let filterAge    = 'all';
  let sortBy       = 'name-asc';
  let openUserId   = null;

  const tbody          = document.getElementById('usr-tbody');
  const emptyEl        = document.getElementById('usr-empty');
  const countEl        = document.getElementById('usr-count');
  const searchEl       = document.getElementById('usr-search');
  const filterStatusEl = document.getElementById('usr-filter-status');
  const filterAgeEl    = document.getElementById('usr-filter-age');
  const sortEl         = document.getElementById('usr-sort');
  const statTotal      = document.getElementById('stat-total');
  const statVerified   = document.getElementById('stat-verified');
  const statNew        = document.getElementById('stat-new');
  const statActive     = document.getElementById('stat-active');
  const drawerOverlay  = document.getElementById('usrDrawerOverlay');
  const drawerClose    = document.getElementById('usrDrawerClose');
  const drawerAvatar   = document.getElementById('drawerAvatar');
  const drawerName     = document.getElementById('drawerName');
  const drawerEmail    = document.getElementById('drawerEmail');
  const drawerPhone    = document.getElementById('drawerPhone');
  const drawerDob      = document.getElementById('drawerDob');
  const drawerAge      = document.getElementById('drawerAge');
  const drawerPurok    = document.getElementById('drawerPurok');
  const drawerAddress  = document.getElementById('drawerAddress');
  const drawerCivil    = document.getElementById('drawerCivil');
  const drawerNat      = document.getElementById('drawerNat');
  const drawerReligion = document.getElementById('drawerReligion');
  const drawerEdu      = document.getElementById('drawerEdu');
  const drawerEmp      = document.getElementById('drawerEmp');
  const drawerVoter    = document.getElementById('drawerVoter');
  const drawerGender   = document.getElementById('drawerGender');
  const drawerVerified = document.getElementById('drawerVerified');
  const drawerJoined   = document.getElementById('drawerJoined');
  const drawerReqSummary = document.getElementById('drawerReqSummary');
  const drawerActivity   = document.getElementById('drawerActivity');

  /* ── Helpers ─────────────────────────────────────────────── */

  function fullName(u) {
      return [u.first_name, u.last_name].filter(Boolean).join(' ');
  }

  function initials(name) {
      return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
  }

  function formatDate(str) {
      if (!str) return '—';
      const parts = str.split(/[-T ]/);
      return MONTHS_SHORT[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
  }

  function ageGroup(age) {
      const a = parseInt(age);
      if (a >= 15 && a <= 17) return '15-17';
      if (a >= 18 && a <= 24) return '18-24';
      if (a >= 25 && a <= 30) return '25-30';
      return 'other';
  }

  function escHtml(str) {
      return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function titleCase(str) {
      if (!str) return '—';
      return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function buildAddress(u) {
      return [u.purok, u.street_address].filter(Boolean).join(', ') || '—';
  }

  /* ── Stats ───────────────────────────────────────────────── */

  function updateStats(stats) {
      statTotal.textContent    = stats.total;
      statVerified.textContent = stats.verified;
      statNew.textContent      = stats.new_this_month;
      statActive.textContent   = stats.active_requestors;
  }

  /* ── Filter + Sort ───────────────────────────────────────── */

  function getFiltered() {
      let list = usersData.slice();

      if (searchQuery) {
          const q = searchQuery.toLowerCase();
          list = list.filter(u =>
              fullName(u).toLowerCase().includes(q) ||
              u.email.toLowerCase().includes(q) ||
              buildAddress(u).toLowerCase().includes(q)
          );
      }

      if (filterStatus !== 'all') {
          list = list.filter(u => filterStatus === 'verified' ? u.is_verified == 1 : u.is_verified == 0);
      }

      if (filterAge !== 'all') {
          list = list.filter(u => ageGroup(u.age) === filterAge);
      }

      list.sort((a, b) => {
          const na = fullName(a), nb = fullName(b);
          if (sortBy === 'name-asc')  return na.localeCompare(nb);
          if (sortBy === 'name-desc') return nb.localeCompare(na);
          if (sortBy === 'date-desc') return b.created_at.localeCompare(a.created_at);
          if (sortBy === 'date-asc')  return a.created_at.localeCompare(b.created_at);
          return 0;
      });

      return list;
  }

  /* ── Render table ────────────────────────────────────────── */

  function renderTable() {
      const list = getFiltered();
      tbody.innerHTML = '';
      countEl.textContent = list.length + ' resident' + (list.length !== 1 ? 's' : '');

      if (!list.length) { emptyEl.style.display = 'block'; return; }
      emptyEl.style.display = 'none';

      list.forEach((user, i) => {
          const avColor  = AVATAR_COLORS[i % AVATAR_COLORS.length];
          const name     = fullName(user);
          const address  = buildAddress(user);
          const phone    = user.mobile_number || '—';
          const reqCount = parseInt(user.request_total) || 0;
          const verified = parseInt(user.is_verified);

          const verifiedHtml = verified
              ? `<span class="usr-status-pill verified"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Verified</span>`
              : `<span class="usr-status-pill unverified"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>Unverified</span>`;

          const tr = document.createElement('tr');
          tr.innerHTML = `
              <td><div class="usr-name-cell">
                  <div class="usr-avatar ${avColor}">${initials(name)}</div>
                  <span class="usr-name-text">${escHtml(name)}</span>
              </div></td>
              <td><div class="usr-contact-cell">
                  <span class="usr-email">${escHtml(user.email)}</span>
                  <span class="usr-phone">${escHtml(phone)}</span>
              </div></td>
              <td><span class="usr-address-text" title="${escHtml(address)}">${escHtml(address)}</span></td>
              <td><span class="usr-age-val">${user.age}</span></td>
              <td>${verifiedHtml}</td>
              <td>${formatDate(user.created_at)}</td>
              <td style="text-align:center"><span class="${reqCount > 0 ? 'usr-req-badge has-requests' : 'usr-req-badge'}">${reqCount}</span></td>
              <td style="text-align:center">
                  <button class="usr-view-btn" data-id="${user.id}" title="View details">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                      </svg>
                  </button>
              </td>`;
          tbody.appendChild(tr);
      });
  }

  /* ── Scroll lock (preserves scrollbar gutter) ────────────── */

  function lockScroll() {
      const sbw = window.innerWidth - document.documentElement.clientWidth;
      document.body.style.paddingRight = sbw + 'px';
      document.body.style.overflow = 'hidden';
  }

  function unlockScroll() {
      document.body.style.paddingRight = '';
      document.body.style.overflow = '';
  }

  /* ── Drawer ──────────────────────────────────────────────── */

  function resetDrawer() {
      const dash = '—';
      drawerAvatar.textContent   = '…';
      drawerAvatar.className     = 'usr-drawer-avatar av-0';
      drawerName.textContent     = 'Loading…';
      [drawerEmail, drawerPhone, drawerDob, drawerAge, drawerPurok, drawerAddress,
       drawerCivil, drawerNat, drawerReligion, drawerEdu, drawerEmp, drawerVoter,
       drawerGender, drawerJoined].forEach(el => { el.textContent = dash; });
      drawerVerified.innerHTML   = dash;
      drawerReqSummary.innerHTML = '';
      drawerActivity.innerHTML   = '';
  }

  function openDrawer(id) {
      openUserId = id;
      resetDrawer();
      lockScroll();
      drawerOverlay.classList.add('is-open');

      fetch(`${DATA_API}?action=detail&id=${id}`, { credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => {
              if (!data.success || openUserId !== id) return;
              populateDrawer(data);
          })
          .catch(() => { drawerName.textContent = 'Error loading user'; });
  }

  function populateDrawer(data) {
      const u   = data.user;
      const sum = data.summary;
      const rec = data.recent;

      const name  = fullName(u);
      const avIdx = usersData.findIndex(x => x.id == u.id);

      drawerAvatar.textContent = initials(name);
      drawerAvatar.className   = 'usr-drawer-avatar ' + AVATAR_COLORS[Math.max(0, avIdx) % AVATAR_COLORS.length];
      drawerName.textContent   = name;
      drawerEmail.textContent  = u.email      || '—';
      drawerPhone.textContent  = u.mobile_number || '—';
      drawerDob.textContent    = formatDate(u.birth_date);
      drawerAge.textContent    = u.age + ' years old';
      drawerPurok.textContent  = u.purok         || '—';
      drawerAddress.textContent = u.street_address || '—';
      drawerCivil.textContent  = titleCase(u.civil_status);
      drawerNat.textContent    = u.nationality    || '—';
      drawerReligion.textContent = u.religion      || '—';
      drawerEdu.textContent    = titleCase(u.educational_attainment);
      drawerEmp.textContent    = titleCase(u.employment_status);
      drawerVoter.textContent  = u.is_registered_voter == 1 ? 'Yes' : 'No';
      drawerGender.textContent = titleCase(u.gender);
      drawerJoined.textContent = formatDate(u.created_at);

      drawerVerified.innerHTML = parseInt(u.is_verified)
          ? `<span class="usr-status-pill verified"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Verified</span>`
          : `<span class="usr-status-pill unverified"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>Unverified</span>`;

      const summaryStats = [
          { key: 'total',          label: 'Total',      cls: 'stat-total'    },
          { key: 'approved',       label: 'Approved',   cls: 'stat-approved' },
          { key: 'pending',        label: 'Pending',    cls: 'stat-pending'  },
          { key: 'rejected',       label: 'Rejected',   cls: 'stat-rejected' },
          { key: 'cancelled',      label: 'Cancelled',  cls: 'stat-cancelled'},
          { key: 'action_required',label: 'Action Req.',cls: 'stat-action'   },
      ];
      drawerReqSummary.innerHTML = summaryStats.map(s =>
          `<div class="usr-req-stat ${s.cls}">
              <span class="usr-req-stat-num">${parseInt(sum[s.key] ?? 0)}</span>
              <span class="usr-req-stat-lbl">${s.label}</span>
          </div>`
      ).join('');

      const statusDot = {
          approved:       'dot-green',
          pending:        '',
          rejected:       'dot-coral',
          cancelled:      'dot-slate',
          action_required:'dot-violet',
      };

      if (!rec || !rec.length) {
          drawerActivity.innerHTML = '<li style="font-size:12.5px;color:var(--off-text-muted);font-style:italic;padding:10px 0;">No requests yet.</li>';
      } else {
          drawerActivity.innerHTML = rec.map(r =>
              `<li class="usr-activity-item">
                  <div class="usr-activity-dot ${statusDot[r.status] || ''}"></div>
                  <div class="usr-activity-content">
                      <p class="usr-activity-text"><strong>${escHtml(r.service_name)}</strong> — ${titleCase(r.status)}</p>
                      <span class="usr-activity-time">${formatDate(r.submitted_at)}</span>
                  </div>
              </li>`
          ).join('');
      }
  }

  function closeDrawer() {
      drawerOverlay.classList.remove('is-open');
      unlockScroll();
      openUserId = null;
  }

  /* ── Event listeners ─────────────────────────────────────── */

  searchEl.addEventListener('input',  function () { searchQuery  = this.value.trim(); renderTable(); });
  filterStatusEl.addEventListener('change', function () { filterStatus = this.value; renderTable(); });
  filterAgeEl.addEventListener('change',    function () { filterAge    = this.value; renderTable(); });
  sortEl.addEventListener('change',         function () { sortBy       = this.value; renderTable(); });

  tbody.addEventListener('click', function (e) {
      const btn = e.target.closest('.usr-view-btn');
      if (!btn) return;
      openDrawer(parseInt(btn.dataset.id, 10));
  });

  drawerClose.addEventListener('click', closeDrawer);
  drawerOverlay.addEventListener('click', e => { if (e.target === drawerOverlay) closeDrawer(); });
  document.addEventListener('keydown',   e => { if (e.key === 'Escape' && drawerOverlay.classList.contains('is-open')) closeDrawer(); });

  /* ── Init ────────────────────────────────────────────────── */

  function loadUsers() {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px 0;color:var(--off-text-muted);font-size:13px;">Loading residents…</td></tr>`;

      fetch(`${DATA_API}?action=list`, { credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => {
              if (!data.success) throw new Error(data.message || 'API error');
              usersData = data.users;
              updateStats(data.stats);
              renderTable();
          })
          .catch(err => {
              tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px 0;color:var(--off-coral);font-size:13px;">Failed to load residents: ${escHtml(err.message)}</td></tr>`;
          });
  }

  loadUsers();
})();