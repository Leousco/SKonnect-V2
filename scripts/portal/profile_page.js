/* profile_page.js
 * Depends on: window.profileData, window.profileIncomplete, window.PROFILE_CTRL
 */

document.addEventListener('DOMContentLoaded', () => {

    const CTRL      = window.PROFILE_CTRL;
    const NOTIF_CTRL = window.NOTIF_CTRL;

    /* ─── RENDER — update all view fields from a profile object ─── */

    function renderProfile(p) {
        const fullName = [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(' ');

        setEl('pf-fullname',  fullName || '—');
        setEl('hero-fullname', fullName || '—');

        if (p.birth_date) {
            const d = new Date(p.birth_date + 'T00:00:00');
            setEl('pf-dob', d.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }));
        }

        setEl('pf-age',         p.age ? p.age + ' years old' : '—');
        setEl('pf-sex',         mapGender(p.gender));
        setEl('pf-civil',       mapCivil(p.civil_status));
        setEl('pf-nationality', p.nationality    || '—');
        setEl('pf-religion',    p.religion       || '—');
        setEl('pf-email',       p.email          || '—');
        setEl('pf-mobile',      p.mobile_number  || '—');
        setEl('pf-purok',       p.purok          || '—');
        setEl('pf-street',      p.street_address || '—');
        setEl('pf-edu',         mapEdu(p.educational_attainment));
        setEl('pf-school',      p.school_institution || '—');
        setEl('pf-course',      p.course_strand      || '—');
        setEl('pf-employment',  mapEmp(p.employment_status));

        const voterEl = document.getElementById('pf-voter');
        if (voterEl) {
            voterEl.innerHTML = p.is_registered_voter == 1
                ? '<span class="req-status-badge status-approved">Yes</span>'
                : '<span class="req-status-badge status-pending">No / Not Set</span>';
        }

        const avatarImg      = document.getElementById('profile-avatar-img');
        const avatarInitials = document.getElementById('profile-initials');
        const initials = ((p.first_name?.[0] ?? '') + (p.last_name?.[0] ?? '')).toUpperCase();
        if (avatarInitials) avatarInitials.textContent = initials;
        if (p.avatar_path && avatarImg) {
            avatarImg.src           = p.avatar_path;
            avatarImg.style.display = 'block';
            if (avatarInitials) avatarInitials.style.display = 'none';
        }

        renderHeroTags(p);
        populateForms(p);
    }

    function renderHeroTags(p) {
        const wrap = document.getElementById('hero-tags');
        if (!wrap) return;
        const tags = [];
        if (p.is_registered_voter == 1) tags.push('🗳️ Registered Voter');
        if (p.employment_status)        tags.push('💼 ' + mapEmp(p.employment_status));
        if (p.purok)                    tags.push('📍 ' + p.purok);
        wrap.innerHTML = tags.length
            ? tags.map(t => `<span class="hero-tag">${escHtml(t)}</span>`).join('')
            : '<span class="hero-tag hero-tag-empty">Complete your profile to show tags</span>';
    }

    function populateForms(p) {
        setVal('e-firstname',   p.first_name   ?? '');
        setVal('e-lastname',    p.last_name    ?? '');
        setVal('e-middlename',  p.middle_name  ?? '');
        setVal('e-dob',         p.birth_date   ?? '');
        setSelect('e-gender',   p.gender       ?? 'male');
        setSelect('e-civil',    p.civil_status ?? '');
        setVal('e-nationality', p.nationality  ?? '');
        setVal('e-religion',    p.religion     ?? '');
        setVal('e-email',       p.email        ?? '');
        setVal('e-mobile',      p.mobile_number  ?? '');
        setVal('e-purok',       p.purok          ?? '');
        setVal('e-street',      p.street_address ?? '');
        setSelect('e-edu',        p.educational_attainment ?? '');
        setSelect('e-employment', p.employment_status      ?? '');
        setVal('e-school', p.school_institution ?? '');
        setVal('e-course', p.course_strand      ?? '');

        const voterYes = document.getElementById('e-voter-yes');
        const voterNo  = document.getElementById('e-voter-no');
        if (voterYes) voterYes.checked = p.is_registered_voter == 1;
        if (voterNo)  voterNo.checked  = p.is_registered_voter != 1;
    }

    if (window.profileData) renderProfile(window.profileData);

    /* ─── ACTIVITY SUMMARY + STATS FETCH ─── */

    async function loadActivity() {
        try {
            const res  = await fetch(CTRL + '?action=get_activity');
            const json = await res.json();
            if (json.status !== 'success') return;

            const s = json.summary;

            setEl('stat-requests', s.total);
            setEl('stat-posts',    s.threads);
            setEl('stat-approved', s.approved);

            setEl('sum-requests', s.total);
            setEl('sum-approved', s.approved);
            setEl('sum-pending',  s.pending);
            setEl('sum-rejected', s.rejected);
            setEl('sum-threads',  s.threads);

            renderRecentRequests(json.recent);
            renderUserThreads(json.threads);
        } catch (e) {
            console.error('Activity load failed', e);
        }
    }

    function renderRecentRequests(items) {
        const list = document.getElementById('recent-requests');
        if (!list) return;

        if (!items || items.length === 0) {
            list.innerHTML = '<li class="recent-list-placeholder">No service requests yet.</li>';
            return;
        }

        const statusLabel = {
            pending:         { text: 'Pending',         cls: 'status-pending'       },
            approved:        { text: 'Approved',        cls: 'status-approved'      },
            rejected:        { text: 'Rejected',        cls: 'status-rejected'      },
            cancelled:       { text: 'Cancelled',       cls: 'status-cancelled'     },
            action_required: { text: 'Action Required', cls: 'status-action-required'},
        };

        const statusIcon = {
            pending:         '🔍',
            approved:        '✅',
            rejected:        '❌',
            cancelled:       '🚫',
            action_required: '⚠️',
        };

        list.innerHTML = items.map(r => {
            const st   = statusLabel[r.status] ?? { text: r.status, cls: '' };
            const icon = statusIcon[r.status]  ?? '📋';
            const dt   = new Date(r.submitted_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
            return `
                <li class="profile-recent-item">
                    <div class="profile-recent-icon">${icon}</div>
                    <div class="profile-recent-body">
                        <span class="profile-recent-title">${escHtml(r.service_name)}</span>
                        <span class="profile-recent-meta">${dt}</span>
                    </div>
                    <span class="req-status-badge ${st.cls}">${st.text}</span>
                </li>`;
        }).join('');
    }

    function renderUserThreads(threads) {
        const list = document.getElementById('user-threads-list');
        if (!list) return;
    
        if (!threads || threads.length === 0) {
            list.innerHTML = '<li class="recent-list-placeholder">You haven\'t posted any threads yet.</li>';
            return;
        }
    
        const categoryLabel = {
            inquiry:        'Inquiry',
            complaint:      'Complaint',
            suggestion:     'Suggestion',
            event_question: 'Event Question',
            other:          'Other',
        };
    
        const statusLabel = {
            pending:   { text: 'Pending',   cls: 'status-pending'  },
            responded: { text: 'Responded', cls: 'status-approved' },
            resolved:  { text: 'Resolved',  cls: 'status-approved' },
            closed:    { text: 'Closed',    cls: 'status-cancelled'},
        };
    
        const categoryIcon = {
            inquiry:        '❓',
            complaint:      '📢',
            suggestion:     '💡',
            event_question: '📅',
            other:          '💬',
        };
    
        const BASE = '../../views/portal/thread_view.php';
    
        list.innerHTML = threads.map(t => {
            const st   = statusLabel[t.status]     ?? { text: t.status,   cls: '' };
            const cat  = categoryLabel[t.category] ?? t.category;
            const icon = categoryIcon[t.category]  ?? '💬';
            const dt   = new Date(t.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Determine category class for color coding
            let categoryClass = 'thread-cat-tag';
            switch (t.category) {
                case 'inquiry':
                    categoryClass += ' category-inquiry';
                    break;
                case 'complaint':
                    categoryClass += ' category-complaint';
                    break;
                case 'suggestion':
                    categoryClass += ' category-suggestion';
                    break;
                case 'event_question':
                    categoryClass += ' category-event_question';
                    break;
                default:
                    categoryClass += ' category-other';
            }
            
            return `
                <li class="profile-recent-item">
                    <div class="profile-recent-icon">${icon}</div>
                    <div class="profile-recent-body">
                        <a href="${BASE}?id=${t.id}" class="profile-recent-title profile-thread-link" title="${escHtml(t.subject)}">${escHtml(truncate(t.subject))}</a>
                        <span class="profile-recent-meta">
                            <span class="${categoryClass}">${escHtml(cat)}</span>
                            &nbsp;·&nbsp; ${dt}
                            &nbsp;·&nbsp; 💬 ${t.comment_count} &nbsp; ♥ ${t.support_count}
                        </span>
                    </div>
                    <span class="req-status-badge ${st.cls}">${st.text}</span>
                </li>`;
        }).join('');
    }

    loadActivity();

    /* ─── NOTIFICATION STATS ─── */

    async function loadNotifStats() {
        // Seed instantly from server-rendered data, then confirm with a live fetch
        const seeded = window.notifStats;
        if (seeded) applyNotifStats(seeded);

        try {
            const res  = await fetch(NOTIF_CTRL + '?action=list');
            const json = await res.json();
            if (json.status === 'success') applyNotifStats(json.stats);
        } catch (e) {
            console.error('Notif stats load failed', e);
        }
    }

    function applyNotifStats(stats) {
        setEl('sum-notifs', stats.total ?? 0);  // total non-dismissed; topbar badge uses unread separately

        // Update topbar badge if it exists (class used by topbar.php)
        const badge = document.querySelector('.notif-badge, #notif-count, .notif-count-badge');
        if (badge) {
            const count = stats.unread ?? 0;
            badge.textContent    = count > 99 ? '99+' : count;
            badge.style.display  = count > 0 ? '' : 'none';
        }
    }

    loadNotifStats();

    /* ─── AVATAR UPLOAD ─── */

    const avatarChangeBtn = document.getElementById('avatar-change-btn');
    const avatarFileInput = document.getElementById('avatar-file-input');
    const avatarImg       = document.getElementById('profile-avatar-img');
    const avatarInitials  = document.getElementById('profile-initials');

    avatarChangeBtn?.addEventListener('click', () => avatarFileInput?.click());

    avatarFileInput?.addEventListener('change', async () => {
        const file = avatarFileInput.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) { showToast('Please upload a valid image file.', false); return; }

        const reader = new FileReader();
        reader.onload = e => {
            if (avatarImg) { avatarImg.src = e.target.result; avatarImg.style.display = 'block'; }
            if (avatarInitials) avatarInitials.style.display = 'none';
        };
        reader.readAsDataURL(file);

        const form = new FormData();
        form.append('action', 'upload_avatar');
        form.append('avatar', file);

        try {
            const res  = await fetch(CTRL, { method: 'POST', body: form });
            const json = await res.json();
            if (json.status === 'success') {
                showToast('Profile photo updated.');
                if (window.profileData) window.profileData.avatar_path = json.path;
            } else {
                showToast(json.message || 'Upload failed.', false);
            }
        } catch { showToast('Network error during upload.', false); }

        avatarFileInput.value = '';
    });

    /* ─── INLINE EDIT SECTIONS ─── */

    document.querySelectorAll('.card-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            const viewEl = document.getElementById(`view-${target}`);
            const formEl = document.getElementById(`form-${target}`);
            if (!viewEl || !formEl) return;
            const isEditing = formEl.style.display !== 'none';
            viewEl.style.display = isEditing ? 'flex' : 'none';
            formEl.style.display = isEditing ? 'none' : 'flex';
            btn.textContent      = isEditing ? 'Edit' : '✕ Cancel';
        });
    });

    document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => closeSection(btn.dataset.target));
    });

    document.querySelectorAll('.save-edit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const target = btn.dataset.target;
            if (validateSection(target).length) return;

            btn.disabled    = true;
            btn.textContent = 'Saving…';

            const payload = buildPayload(target);
            payload.action = 'save_' + target;

            try {
                const res  = await fetch(CTRL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    window.profileData = json.profile;
                    renderProfile(json.profile);
                    closeSection(target);
                    showToast(json.message || 'Changes saved successfully.');
                    updateIncompleteUI(json.profile);
                } else {
                    showToast(json.message || 'Save failed. Please try again.', false);
                }
            } catch { showToast('Network error. Please check your connection.', false); }
            finally {
                btn.disabled    = false;
                btn.textContent = 'Save Changes';
            }
        });
    });

    function closeSection(target) {
        const viewEl  = document.getElementById(`view-${target}`);
        const formEl  = document.getElementById(`form-${target}`);
        const editBtn = document.querySelector(`.card-edit-btn[data-target="${target}"]`);
        if (viewEl)  viewEl.style.display = 'flex';
        if (formEl)  formEl.style.display = 'none';
        if (editBtn) editBtn.textContent  = '✏️ Edit';
        clearSectionErrors(target);
    }

    function clearSectionErrors(target) {
        document.querySelectorAll(`#form-${target} .field-error`)
            .forEach(el => { el.textContent = ''; });
        document.querySelectorAll(`#form-${target} .ann-search-input, #form-${target} .ann-select`)
            .forEach(el => { el.style.borderColor = ''; });
    }

    function showFieldError(inputId, errId, msg) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(errId);
        if (input) input.style.borderColor = '#e11d48';
        if (err)   err.textContent = msg;
    }

    function validateSection(target) {
        clearSectionErrors(target);
        const errors = [];

        if (target === 'personal') {
            if (!getVal('e-firstname')) { showFieldError('e-firstname', 'err-firstname', 'First name is required.'); errors.push(1); }
            if (!getVal('e-lastname'))  { showFieldError('e-lastname',  'err-lastname',  'Last name is required.');  errors.push(1); }
            if (!getVal('e-dob'))       { showFieldError('e-dob',       'err-dob',       'Date of birth is required.'); errors.push(1); }
        }

        if (target === 'contact') {
            const email  = getVal('e-email');
            const mobile = getVal('e-mobile').replace(/\s/g, '');
            const purok  = getVal('e-purok');
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('e-email', 'err-email', 'Enter a valid email address.'); errors.push(1);
            }
            if (!mobile || !/^(09|\+639)\d{9}$/.test(mobile)) {
                showFieldError('e-mobile', 'err-mobile', 'Enter a valid PH mobile number (e.g. 09XX XXX XXXX).'); errors.push(1);
            }
            if (!purok) { showFieldError('e-purok', 'err-purok', 'Purok / Zone is required.'); errors.push(1); }
        }

        return errors;
    }

    function buildPayload(target) {
        if (target === 'personal') {
            return {
                first_name: getVal('e-firstname'), last_name: getVal('e-lastname'),
                middle_name: getVal('e-middlename'), birth_date: getVal('e-dob'),
                gender: getSelectVal('e-gender'), civil_status: getSelectVal('e-civil'),
                nationality: getVal('e-nationality'), religion: getVal('e-religion'),
            };
        }
        if (target === 'contact') {
            return {
                email: getVal('e-email'), mobile_number: getVal('e-mobile'),
                purok: getVal('e-purok'), street_address: getVal('e-street'),
            };
        }
        if (target === 'membership') {
            const voter = document.querySelector('input[name="voter"]:checked');
            return {
                educational_attainment: getSelectVal('e-edu'),
                employment_status:      getSelectVal('e-employment'),
                school_institution:     getVal('e-school'),
                course_strand:          getVal('e-course'),
                is_registered_voter:    voter ? voter.value : '0',
            };
        }
        return {};
    }

    /* ─── INCOMPLETE BADGE UPDATE ─── */

    function updateIncompleteUI(profile) {
        const isComplete = !!(profile.mobile_number && profile.purok);
        const badge = document.getElementById('incomplete-badge');
        if (badge) badge.style.display = isComplete ? 'none' : '';
    }

    /* ─── PROFILE EDIT TRIGGER (hero button) ─── */

    document.getElementById('profile-edit-trigger')?.addEventListener('click', () => {
        document.getElementById('card-personal')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            document.querySelector('.card-edit-btn[data-target="personal"]')?.click();
        }, 400);
    });

    /* ─── PROFILE SETUP MODAL ─── */

    const setupOverlay  = document.getElementById('setup-overlay');
    const setupSaveBtn  = document.getElementById('setup-save-btn');
    const setupReminder = document.getElementById('setup-remind-later');

    function openSetupModal() {
        if (!setupOverlay) return;
        setupOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeSetupModal() {
        if (!setupOverlay) return;
        setupOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (window.profileIncomplete) setTimeout(openSetupModal, 600);

    setupReminder?.addEventListener('click', closeSetupModal);

    setupSaveBtn?.addEventListener('click', async () => {
        ['setup-err-mobile', 'setup-err-purok'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });
        ['setup-mobile', 'setup-purok'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.borderColor = '';
        });

        const mobile = (document.getElementById('setup-mobile')?.value ?? '').trim();
        const purok  = (document.getElementById('setup-purok')?.value  ?? '').trim();
        let valid = true;

        if (!mobile || !/^(09|\+639)\d{9}$/.test(mobile.replace(/\s/g, ''))) {
            const el  = document.getElementById('setup-mobile');
            const err = document.getElementById('setup-err-mobile');
            if (el)  el.style.borderColor = '#e11d48';
            if (err) err.textContent = 'Enter a valid PH mobile number (e.g. 09XX XXX XXXX).';
            valid = false;
        }
        if (!purok) {
            const el  = document.getElementById('setup-purok');
            const err = document.getElementById('setup-err-purok');
            if (el)  el.style.borderColor = '#e11d48';
            if (err) err.textContent = 'Purok / Zone is required.';
            valid = false;
        }
        if (!valid) return;

        setupSaveBtn.disabled    = true;
        setupSaveBtn.textContent = 'Saving…';

        const payload = {
            action:         'complete_setup',
            mobile_number:  mobile,
            purok:          purok,
            street_address: (document.getElementById('setup-street')?.value      ?? '').trim(),
            nationality:    (document.getElementById('setup-nationality')?.value  ?? '').trim(),
            religion:       (document.getElementById('setup-religion')?.value     ?? '').trim(),
        };

        try {
            const res  = await fetch(CTRL, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.status === 'success') {
                window.profileData       = json.profile;
                window.profileIncomplete = false;
                renderProfile(json.profile);
                updateIncompleteUI(json.profile);
                closeSetupModal();
                showToast('Profile setup complete! Welcome aboard. 🎉');
            } else {
                showToast(json.message || 'Could not save. Try again.', false);
            }
        } catch { showToast('Network error. Please check your connection.', false); }
        finally {
            setupSaveBtn.disabled    = false;
            setupSaveBtn.textContent = 'Save & Complete Profile';
        }
    });

    /* ─── DEACTIVATE CONFIRM MODAL ─── */

    const confirmOverlay    = document.getElementById('confirm-overlay');
    const confirmClose      = document.getElementById('confirm-close');
    const confirmCancel     = document.getElementById('confirm-cancel');
    const confirmDeactivate = document.getElementById('confirm-deactivate');

    document.getElementById('deactivate-btn')?.addEventListener('click', () => {
        if (!confirmOverlay) return;
        confirmOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    function closeConfirm() {
        if (!confirmOverlay) return;
        confirmOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    confirmClose?.addEventListener('click',  closeConfirm);
    confirmCancel?.addEventListener('click', closeConfirm);
    confirmOverlay?.addEventListener('click', e => { if (e.target === confirmOverlay) closeConfirm(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeConfirm(); closeSetupModal(); } });

    confirmDeactivate?.addEventListener('click', () => {
        closeConfirm();
        showToast('Account deactivation coming soon.', false);
    });

    /* ─── TOAST ─── */

    let toastTimer = null;

    function showToast(message, success = true) {
        const toast = document.getElementById('profile-toast');
        const icon  = document.getElementById('toast-icon');
        const text  = document.getElementById('toast-text');
        if (!toast || !icon || !text) return;

        icon.textContent       = success ? '✅' : '⚠️';
        text.textContent       = message;
        toast.style.background = success ? 'var(--navy)' : '#b91c1c';
        toast.style.display    = 'flex';
        toast.style.opacity    = '1';
        toast.style.transition = '';

        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.style.opacity    = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => { toast.style.display = 'none'; toast.style.opacity = '1'; toast.style.transition = ''; }, 300);
        }, 3200);
    }

    /* ─── UTILITIES ─── */

    function truncate(str, max = 35) { return str.length > max ? str.slice(0, max).trimEnd() + '…' : str; }
    function setEl(id, text)    { const el = document.getElementById(id); if (el) el.textContent = text; }
    function setVal(id, value)  { const el = document.getElementById(id); if (el) el.value = value ?? ''; }
    function setSelect(id, val) {
        const el = document.getElementById(id);
        if (!el) return;
        for (const opt of el.options) opt.selected = opt.value === (val ?? '');
    }
    function getVal(id)       { return (document.getElementById(id)?.value ?? '').trim(); }
    function getSelectVal(id) { return document.getElementById(id)?.value ?? ''; }
    function escHtml(str) {
        return str.replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }

    function mapGender(g) { return { male:'Male', female:'Female', other:'Prefer not to say' }[g] ?? '—'; }
    function mapCivil(c)  { return { single:'Single', married:'Married', widowed:'Widowed', separated:'Separated', annulled:'Annulled' }[c] ?? '—'; }
    function mapEdu(e)    {
        return {
            elementary:'Elementary', high_school:'High School Graduate', senior_high:'Senior High School Graduate',
            vocational:'Vocational / Technical', college_level:'College Level',
            college_graduate:'College Graduate', post_graduate:'Post-Graduate',
        }[e] ?? '—';
    }
    function mapEmp(e) { return { student:'Student', employed:'Employed', unemployed:'Unemployed', self_employed:'Self-Employed' }[e] ?? '—'; }

});