document.addEventListener('DOMContentLoaded', () => {

    const API_URL = '../../../backend/controllers/UserController.php';

    const roleLabels = {
        admin:      '🔑 Admin',
        moderator:  '💬 Moderator',
        sk_officer: '🌟 SK Officer',
        resident:   '👤 Resident',
    };

    const roleColorClass = {
        admin:      'role-admin',
        moderator:  'role-moderator',
        sk_officer: 'role-officer',
        resident:   'role-resident',
    };

    const avatarColors = {
        admin:      '#5b21b6',
        moderator:  '#0891b2',
        sk_officer: '#d97706',
        resident:   '#059669',
    };

    let allUsers    = [];
    let currentUser = null;

    async function loadUsers() {
        try {
            const res  = await fetch(API_URL + '?action=get_users');
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            allUsers = data.data.users;
            renderTable(allUsers);
            updateStats(allUsers);
        } catch (err) {
            showToast('Failed to load users: ' + err.message, 'error');
        }
    }

    function renderTable(users) {
        const tbody     = document.getElementById('user-tbody');
        const noResults = document.getElementById('no-results');
        tbody.innerHTML = '';

        if (users.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        users.forEach(user => {
            const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
            const fullName = [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ');
            const joined   = new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            const tr = document.createElement('tr');
            tr.className        = 'user-row';
            tr.dataset.role     = user.role;
            tr.dataset.gender   = user.gender;
            tr.dataset.verified = user.is_verified;
            tr.dataset.name     = fullName.toLowerCase();
            tr.dataset.email    = user.email.toLowerCase();

            tr.innerHTML = `
                <td>
                    <div class="user-name-cell">
                        <div class="user-avatar role-avatar-${user.role}">${initials}</div>
                        <div>
                            <div class="user-fullname">${esc(fullName)}</div>
                            <div class="user-id">ID: ${user.id}</div>
                        </div>
                    </div>
                </td>
                <td class="user-email">${esc(user.email)}</td>
                <td><span class="user-role-badge ${roleColorClass[user.role] || ''}">${roleLabels[user.role] || user.role}</span></td>
                <td>${cap(user.gender)}</td>
                <td>${user.age}</td>
                <td>${statusBadge(user)}</td>
                <td class="user-date">${joined}</td>
                <td>
                    <div class="user-actions">
                        <button class="btn-user-action btn-view" data-id="${user.id}">View</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.btn-view').forEach(b =>
            b.addEventListener('click', () => openViewModal(getUser(b.dataset.id)))
        );
    }

    function statusBadge(user) {
        if (user.is_banned)   return '<span class="user-verified-badge verified-no">⛔ Banned</span>';
        if (user.is_verified) return '<span class="user-verified-badge verified-yes">✅ Verified</span>';
        return '<span class="user-verified-badge verified-no">❌ Unverified</span>';
    }

    function updateStats(users) {
        const counts = {
            total:      users.length,
            verified:   users.filter(u => u.is_verified == 1).length,
            admin:      users.filter(u => u.role === 'admin').length,
            moderator:  users.filter(u => u.role === 'moderator').length,
            sk_officer: users.filter(u => u.role === 'sk_officer').length,
            resident:   users.filter(u => u.role === 'resident').length,
        };
        document.querySelectorAll('[data-stat]').forEach(el => {
            el.textContent = counts[el.dataset.stat] ?? 0;
        });
    }

    function getUser(id) {
        return allUsers.find(u => String(u.id) === String(id));
    }

    document.getElementById('user-search')  ?.addEventListener('input',  applyFilter);
    document.getElementById('user-role')    ?.addEventListener('change', applyFilter);
    document.getElementById('user-gender')  ?.addEventListener('change', applyFilter);
    document.getElementById('user-verified')?.addEventListener('change', applyFilter);

    function applyFilter() {
        const query    = document.getElementById('user-search').value.toLowerCase().trim();
        const role     = document.getElementById('user-role').value;
        const gender   = document.getElementById('user-gender').value;
        const verified = document.getElementById('user-verified').value;

        const filtered = allUsers.filter(u => {
            const name = [u.first_name, u.middle_name, u.last_name].filter(Boolean).join(' ').toLowerCase();
            return (!query    || name.includes(query) || u.email.toLowerCase().includes(query))
                && (role     === 'all' || u.role === role)
                && (gender   === 'all' || u.gender === gender)
                && (verified === 'all' || String(u.is_verified) === verified);
        });

        renderTable(filtered);
        document.getElementById('no-results').style.display = filtered.length === 0 ? 'block' : 'none';
    }

    /* ── ADD USER MODAL ──────────────────────────────────── */

    const addOverlay     = document.getElementById('add-user-modal-overlay');
    const pwField        = document.getElementById('add-password');
    const pwConfirmField = document.getElementById('add-password-confirm');
    const pwHint         = document.getElementById('pw-match-hint');

    function closeAddModal() {
        addOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.getElementById('btn-add-user')?.addEventListener('click', () => {
        document.getElementById('add-user-form').reset();
        pwHint.textContent = '';
        pwHint.className   = 'mu-pw-match-hint';
        pwConfirmField.classList.remove('input-error');
        addOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    });

    document.getElementById('add-user-close') ?.addEventListener('click', closeAddModal);
    document.getElementById('add-user-cancel')?.addEventListener('click', closeAddModal);
    addOverlay?.addEventListener('click', e => { if (e.target === addOverlay) closeAddModal(); });

    function checkPasswordMatch() {
        const pw  = pwField.value;
        const cfw = pwConfirmField.value;
        if (!pw || !cfw) { pwHint.textContent = ''; pwHint.className = 'mu-pw-match-hint'; return; }

        if (pw === cfw) {
            pwHint.textContent = '✓ Passwords match';
            pwHint.className   = 'mu-pw-match-hint pw-match';
            pwConfirmField.classList.remove('input-error');
        } else {
            pwHint.textContent = '✗ Passwords do not match';
            pwHint.className   = 'mu-pw-match-hint pw-no-match';
            pwConfirmField.classList.add('input-error');
        }
    }

    pwField?.addEventListener('input',        checkPasswordMatch);
    pwConfirmField?.addEventListener('input', checkPasswordMatch);

    document.getElementById('add-user-submit')?.addEventListener('click', async () => {
        const fieldIds = ['add-first-name', 'add-last-name', 'add-email', 'add-password', 'add-password-confirm', 'add-role', 'add-gender', 'add-birth-date'];
        const vals     = {};
        let valid      = true;

        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el.value.trim()) { el.classList.add('input-error'); valid = false; }
            else { el.classList.remove('input-error'); vals[id] = el.value.trim(); }
        });

        if (!valid) { showToast('Please fill in all required fields.', 'error'); return; }

        if (vals['add-password'] !== vals['add-password-confirm']) {
            document.getElementById('add-password-confirm').classList.add('input-error');
            showToast('Passwords do not match.', 'error');
            return;
        }

        if (vals['add-password'].length < 8) {
            document.getElementById('add-password').classList.add('input-error');
            showToast('Password must be at least 8 characters.', 'error');
            return;
        }

        const loading = showLoadingToast('Creating user…');
        try {
            const res = await apiFetch({
                action:      'add_user',
                first_name:  vals['add-first-name'],
                last_name:   vals['add-last-name'],
                middle_name: document.getElementById('add-middle-name').value.trim(),
                email:       vals['add-email'],
                password:    vals['add-password'],
                role:        vals['add-role'],
                gender:      vals['add-gender'],
                birth_date:  vals['add-birth-date'],
            });
            loading.resolve('✅ ' + res.message, 'success');
            closeAddModal();
            await loadUsers();
        } catch (err) {
            loading.resolve(err.message, 'error');
        }
    });

    /* ── VIEW / EDIT USER MODAL ──────────────────────────── */

    const viewOverlay   = document.getElementById('user-modal-overlay');
    const footerDefault = document.getElementById('footer-default');
    const footerEdit    = document.getElementById('footer-edit');
    const banBtn        = document.getElementById('user-modal-ban');

    function openViewModal(user) {
        if (!user) return;
        currentUser = user;

        const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
        const fullName = [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ');
        const isBanned = parseInt(user.is_banned);

        const avatar = document.getElementById('user-modal-avatar');
        avatar.textContent      = initials;
        avatar.style.background = avatarColors[user.role] || '#5b21b6';

        document.getElementById('user-modal-name').textContent        = fullName;
        document.getElementById('user-modal-email-disp').textContent  = user.email;
        document.getElementById('user-modal-role-disp').textContent   = roleLabels[user.role] || user.role;
        document.getElementById('user-modal-gender-disp').textContent = cap(user.gender);
        document.getElementById('user-modal-status-disp').textContent =
            isBanned ? '⛔ Banned' : (user.is_verified ? '✅ Verified' : '❌ Unverified');

        document.getElementById('user-modal-age').textContent    = user.age + ' years old';
        document.getElementById('user-modal-joined').textContent = new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        document.getElementById('user-modal-id').textContent     = '#' + user.id;

        const address = [user.purok, user.street_address].filter(Boolean).join(', ');
        document.getElementById('user-modal-mobile').textContent  = user.mobile_number || '—';
        document.getElementById('user-modal-address').textContent = address || '—';

        document.getElementById('edit-first-name').value  = user.first_name;
        document.getElementById('edit-last-name').value   = user.last_name;
        document.getElementById('edit-middle-name').value = user.middle_name ?? '';
        document.getElementById('edit-email').value       = user.email;
        document.getElementById('edit-gender').value      = user.gender;
        document.getElementById('edit-birth-date').value  = user.birth_date ?? '';
        document.getElementById('user-modal-role-select').value = user.role;

        banBtn.textContent = isBanned ? '🔓 Unban User' : '⛔ Ban User';
        banBtn.classList.toggle('btn-svc-danger',   !isBanned);
        banBtn.classList.toggle('btn-svc-activate', !!isBanned);

        collapseEditMode();
        viewOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        viewOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
        currentUser = null;
    }

    function collapseEditMode() {
        document.getElementById('edit-section').style.display = 'none';
        footerDefault.style.display = '';
        footerEdit.style.display    = 'none';
    }

    function expandEditMode() {
        document.getElementById('edit-section').style.display = 'block';
        footerDefault.style.display = 'none';
        footerEdit.style.display    = '';
    }

    document.getElementById('user-modal-close')?.addEventListener('click', closeViewModal);
    viewOverlay?.addEventListener('click', e => { if (e.target === viewOverlay) closeViewModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeViewModal(); closeAddModal(); closeAllConfirms(); }
    });

    document.getElementById('btn-toggle-edit')?.addEventListener('click', expandEditMode);
    document.getElementById('btn-cancel-edit')?.addEventListener('click', collapseEditMode);

    document.getElementById('btn-save-role')?.addEventListener('click', () => {
        if (!currentUser) return;
        const newRole = document.getElementById('user-modal-role-select').value;
        if (newRole === currentUser.role) { showToast('No change in role.', 'info'); return; }

        const body = `Change <strong>${esc(currentUser.first_name)}'s</strong> role from <strong>${roleLabels[currentUser.role]}</strong> to <strong>${roleLabels[newRole]}</strong>?`;
        openConfirm('confirm-role-overlay', body, async () => {
            const loading = showLoadingToast('Updating role and sending notification email…');
            try {
                await apiFetch({ action: 'update_role', id: currentUser.id, role: newRole });
                loading.resolve('Role updated. Notification email sent.', 'success');
                closeViewModal();
                await loadUsers();
            } catch (err) { loading.resolve(err.message, 'error'); }
        });
    });

    document.getElementById('btn-save-edit')?.addEventListener('click', () => {
        if (!currentUser) return;

        const payload = {
            action:      'update_user',
            id:          currentUser.id,
            first_name:  document.getElementById('edit-first-name').value.trim(),
            last_name:   document.getElementById('edit-last-name').value.trim(),
            middle_name: document.getElementById('edit-middle-name').value.trim(),
            email:       document.getElementById('edit-email').value.trim(),
            gender:      document.getElementById('edit-gender').value,
            birth_date:  document.getElementById('edit-birth-date').value,
        };

        if (!payload.first_name || !payload.last_name || !payload.email) {
            showToast('First name, last name, and email are required.', 'error');
            return;
        }

        openConfirm('confirm-edit-overlay', null, async () => {
            const loading = showLoadingToast('Saving changes…');
            try {
                await apiFetch(payload);
                loading.resolve('User info updated successfully.', 'success');
                closeViewModal();
                await loadUsers();
            } catch (err) { loading.resolve(err.message, 'error'); }
        });
    });

    banBtn?.addEventListener('click', () => { if (currentUser) handleBan(currentUser.id); });
    document.getElementById('user-modal-delete')?.addEventListener('click', () => { if (currentUser) handleDelete(currentUser.id); });

    /* ── SHARED ACTIONS ──────────────────────────────────── */

    function handleBan(id) {
        const user = getUser(id);
        if (!user) return;
        const name     = `${user.first_name} ${user.last_name}`;
        const isBanned = parseInt(user.is_banned);

        if (!isBanned) {
            document.getElementById('confirm-ban-body').textContent = `You are about to ban "${name}". They will lose access to their account immediately.`;
            document.getElementById('confirm-ban-reason').value = '';

            openConfirm('confirm-ban-overlay', null, async () => {
                const reason  = document.getElementById('confirm-ban-reason').value.trim();
                const loading = showLoadingToast('Banning user account…');
                try {
                    const res = await apiFetch({ action: 'ban_user', id, reason });
                    loading.resolve(res.message + ' Notification email sent.', 'error');
                    closeViewModal();
                    await loadUsers();
                } catch (err) { loading.resolve(err.message, 'error'); }
            });
        } else {
            document.getElementById('confirm-unban-body').textContent = `Unban "${name}"? They will regain full access to their account.`;

            openConfirm('confirm-unban-overlay', null, async () => {
                const loading = showLoadingToast('Unbanning user account…');
                try {
                    const res = await apiFetch({ action: 'ban_user', id, reason: null });
                    loading.resolve(res.message + ' Notification email sent.', 'success');
                    closeViewModal();
                    await loadUsers();
                } catch (err) { loading.resolve(err.message, 'error'); }
            });
        }
    }

    function handleDelete(id) {
        const user = getUser(id);
        if (!user) return;
        const name = `${user.first_name} ${user.last_name}`;

        document.getElementById('confirm-delete-body').textContent = `Are you sure you want to permanently delete the account of "${name}"?`;

        openConfirm('confirm-delete-overlay', null, () => {
            document.getElementById('confirm-delete2-body').textContent = `You are about to permanently delete "${name}". All their data will be removed from the system.`;

            openConfirm('confirm-delete2-overlay', null, async () => {
                const loading = showLoadingToast('Deleting user account…');
                try {
                    const res = await apiFetch({ action: 'delete_user', id });
                    loading.resolve(res.message + ' Notification email sent.', 'error');
                    closeViewModal();
                    await loadUsers();
                } catch (err) { loading.resolve(err.message, 'error'); }
            });
        });
    }

    /* ── CONFIRM MODAL SYSTEM ────────────────────────────── */

    function openConfirm(overlayId, bodyHtml, onConfirm) {
        const overlay = document.getElementById(overlayId);
        if (!overlay) return;

        if (bodyHtml !== null) {
            const bodyEl = overlay.querySelector('.mu-confirm-body');
            if (bodyEl) bodyEl.innerHTML = bodyHtml;
        }

        overlay.classList.add('is-open');

        const okBtn     = overlay.querySelector('[id$="-ok"]');
        const cancelBtn = overlay.querySelector('[id$="-cancel"]');

        function cleanup() {
            overlay.classList.remove('is-open');
            okBtn?.removeEventListener('click', handleOk);
            cancelBtn?.removeEventListener('click', handleCancel);
        }

        function handleOk()     { cleanup(); onConfirm(); }
        function handleCancel() { cleanup(); }

        okBtn?.addEventListener('click',     handleOk);
        cancelBtn?.addEventListener('click', handleCancel);
        overlay.addEventListener('click', e => { if (e.target === overlay) handleCancel(); }, { once: true });
    }

    function closeAllConfirms() {
        document.querySelectorAll('.mu-confirm-overlay.is-open').forEach(o => o.classList.remove('is-open'));
    }

    /* ── API HELPER ──────────────────────────────────────── */

    async function apiFetch(payload) {
        const res  = await fetch(API_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message || 'Unknown error');
        return data;
    }

    /* ── LOADING TOAST ───────────────────────────────────── */

    function showLoadingToast(msg) {
        const t = document.createElement('div');
        t.className = 'svc-toast toast-loading';
        t.innerHTML = `<span class="toast-spinner"></span><span>${msg}</span>`;
        document.body.appendChild(t);
        setTimeout(() => t.classList.add('toast-visible'), 10);

        return {
            resolve(resultMsg, type = 'success') {
                t.classList.remove('toast-visible');
                setTimeout(() => { t.remove(); showToast(resultMsg, type); }, 280);
            }
        };
    }

    /* ── TOAST ───────────────────────────────────────────── */

    function showToast(msg, type = 'success') {
        const t = document.createElement('div');
        t.className   = `svc-toast toast-${type}`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.classList.add('toast-visible'), 10);
        setTimeout(() => { t.classList.remove('toast-visible'); setTimeout(() => t.remove(), 300); }, 3500);
    }

    /* ── UTILS ───────────────────────────────────────────── */

    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    loadUsers();
});