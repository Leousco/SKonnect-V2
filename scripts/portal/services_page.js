
   document.addEventListener('DOMContentLoaded', () => {
       const loadingSections = Array.from(document.querySelectorAll('.services-loading'));
       const skeletonTimer = window.setTimeout(() => {
           loadingSections.forEach(section => section.classList.add('services-skeleton-active'));
       }, 150);

       const finishLoading = () => {
           window.clearTimeout(skeletonTimer);
           loadingSections.forEach(section => {
               section.classList.remove('services-loading', 'services-skeleton-active');
               section.setAttribute('aria-busy', 'false');
           });
       };

       const searchInput    = document.getElementById('svc-search');
    const catSelect    = document.getElementById('svc-category');
    const typeSelect   = document.getElementById('svc-type');
    const statusSelect = document.getElementById('svc-status');
    const cards        = Array.from(document.querySelectorAll('.res-svc-card'));
    const noResults    = document.getElementById('no-results');
    const svcCount     = document.getElementById('svc-count');

    function getRequirementLines(requirements) {
        return requirements
            .split('\n')
            .map(line => line.trim().replace(/^[-•]\s*/, ''))
            .filter(Boolean);
    }

    function filterCards() {
        const query    = searchInput.value.toLowerCase().trim();
        const category = catSelect.value;
        const type     = typeSelect.value;
        const status   = statusSelect.value;
        let   visible  = 0;

        cards.forEach(card => {
            const title    = card.querySelector('.ann-card-title')?.textContent.toLowerCase()  || '';
            const desc     = card.querySelector('.svc-card-excerpt')?.textContent.toLowerCase() || '';
            const cardCat  = card.dataset.category || '';
            const cardType = card.dataset.type    || '';
            const cardSts  = card.dataset.status  || '';

            const matchSearch   = !query    || title.includes(query) || desc.includes(query);
            const matchCategory = category === 'all' || cardCat === category;
            const matchType     = type     === 'all' || cardType === type;
            const matchStatus   = status   === 'all' || cardSts === status;

            const show = matchSearch && matchCategory && matchType && matchStatus;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noResults.style.display = visible === 0 ? 'block' : 'none';
        if (svcCount) svcCount.textContent = `Showing ${visible} service${visible !== 1 ? 's' : ''}`;
    }

    searchInput?.addEventListener('input',   filterCards);
    catSelect?.addEventListener('change',    filterCards);
    typeSelect?.addEventListener('change',   filterCards);
    statusSelect?.addEventListener('change', filterCards);

    









    function updateTitleStates() {
        cards.forEach(card => {
            const titleEl = card.querySelector('.ann-card-title');
            if (!titleEl) return;

            const lineHeight = parseFloat(getComputedStyle(titleEl).lineHeight) || 21;
            const lines = Math.round(titleEl.clientHeight / lineHeight);

            card.classList.toggle('svc-title-two-lines', lines >= 2);
        });
    }

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(updateTitleStates);
    }
    updateTitleStates();

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateTitleStates, 150);
    });

    



    const detailsOverlay   = document.getElementById('details-modal-overlay');
    const detailsClose     = document.getElementById('details-modal-close');
    const detailsCancel    = document.getElementById('details-modal-cancel');
    const detailsIcon      = document.getElementById('details-modal-icon');
    const detailsTitle     = document.getElementById('details-modal-title');
    const detailsTypeLabel = document.getElementById('details-modal-type-label');
    const detailsStatusEl  = document.getElementById('details-status-strip');
    const detailsDesc      = document.getElementById('details-description');
    const detailsElig      = document.getElementById('details-eligibility');
    const detailsProc      = document.getElementById('details-processing');
    const detailsCapWrap   = document.getElementById('details-cap-wrap');
    const detailsCap       = document.getElementById('details-capacity');
    const detailsReqSec    = document.getElementById('details-req-section');
    const detailsReqList   = document.getElementById('details-req-list');
    const detailsContSec   = document.getElementById('details-contact-section');
    const detailsCont      = document.getElementById('details-contact');

    let bodyScrollState = null;

    function lockPageScroll() {
        if (bodyScrollState) return;
        bodyScrollState = { overflow: document.body.style.overflow };
        document.body.style.overflow = 'hidden';
    }

    function unlockPageScroll() {
        if (!bodyScrollState) return;
        document.body.style.overflow = bodyScrollState.overflow;
        bodyScrollState = null;
    }

    function openDetailsModal(btn) {
        const service         = btn.dataset.service        || 'Service';
        const icon            = btn.dataset.icon           || '';
        const categoryKey     = btn.dataset.categoryKey    || 'other';
        const typeLabel       = btn.dataset.typeLabel      || '—';
        const status          = btn.dataset.status         || 'closed';
        const description     = btn.dataset.description    || '—';
        const eligibility     = btn.dataset.eligibility    || '—';
        const processing      = btn.dataset.processing     || '—';
        const requirements    = btn.dataset.requirements   || '';
        const contact         = btn.dataset.contact        || '';
        const capacity        = btn.dataset.capacity       || '';
        const attachmentNames = btn.dataset.attachmentNames || '';
        const attachmentPaths = btn.dataset.attachmentPaths || '';

        detailsTitle.textContent     = service;
        detailsTypeLabel.textContent = typeLabel;
        detailsDesc.textContent      = description;
        detailsElig.textContent      = eligibility;
        detailsProc.textContent      = processing;

        detailsIcon.innerHTML = icon;
        detailsIcon.className = 'modal-icon modal-icon-svc svc-icon-' + categoryKey;

        detailsStatusEl.className = 'details-status-strip strip-' + status;
        const statusLabels = {
            open:    'Open — Accepting requests',
            limited: 'Limited Slots — Hurry, slots are filling up',
            closed:  'Closed — Not currently accepting requests',
        };
        detailsStatusEl.textContent = statusLabels[status] || status;

        if (capacity) {
            const parts   = capacity.split('/');
            const current = parseInt(parts[0], 10) || 0;
            const max     = parseInt(parts[1], 10) || 1;
            const pct     = Math.min(100, Math.round((current / max) * 100));
            const isFull  = current >= max;
            const isWarn  = !isFull && pct >= 70;

            detailsCapWrap.style.display = '';
            detailsCap.textContent = `${current} / ${max} slots filled`;
            detailsCap.className   = 'details-meta-value' + (isFull ? ' slots-full' : isWarn ? ' slots-limited' : '');

            let barWrap = document.getElementById('details-cap-bar-wrap');
            if (!barWrap) {
                barWrap = document.createElement('div');
                barWrap.id = 'details-cap-bar-wrap';
                barWrap.className = 'details-cap-bar-wrap';
                barWrap.innerHTML = '<div class="details-cap-bar"><div class="details-cap-fill" id="details-cap-fill"></div></div>';
                detailsCapWrap.appendChild(barWrap);
            }
            const fill = document.getElementById('details-cap-fill');
            if (fill) {
                fill.style.width = pct + '%';
                fill.className   = 'details-cap-fill' + (isFull ? ' bar-full' : isWarn ? ' bar-warn' : '');
            }
        } else {
            detailsCapWrap.style.display = 'none';
        }

        const reqLines = getRequirementLines(requirements);
        if (reqLines.length) {
            detailsReqSec.style.display = '';
            detailsReqList.innerHTML = reqLines.map(r => `<li>${escapeHtml(r)}</li>`).join('');
        } else {
            detailsReqSec.style.display = 'none';
        }

        const attNamesArr = attachmentNames ? attachmentNames.split(',').map(n => n.trim()).filter(Boolean) : [];
        const attPathsArr = attachmentPaths ? attachmentPaths.split(',').map(p => p.trim()).filter(Boolean) : [];
        let detailsAttSec = document.getElementById('details-att-section');
        if (attNamesArr.length) {
            if (!detailsAttSec) {
                detailsAttSec = document.createElement('div');
                detailsAttSec.id = 'details-att-section';
                detailsAttSec.className = 'details-section';
                detailsAttSec.innerHTML = `
                    <span class="details-section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                        Downloadable Attachments
                    </span>
                    <div class="details-att-list" id="details-att-list"></div>`;
                const modalBody  = document.querySelector('#details-modal-overlay .modal-body');
                const contactSec = document.getElementById('details-contact-section');
                if (contactSec && modalBody) modalBody.insertBefore(detailsAttSec, contactSec.nextSibling);
                else if (modalBody) modalBody.appendChild(detailsAttSec);
            }
            detailsAttSec.style.display = '';
            const attList = document.getElementById('details-att-list');
            if (attList) {
                attList.innerHTML = attNamesArr.map((name, i) => {
                    const path = attPathsArr[i] || '#';
                    return `<a href="${escapeHtml(path)}" class="details-att-link" target="_blank" download title="${escapeHtml(name)}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        <span class="details-att-name">${escapeHtml(name)}</span>
                    </a>`;
                }).join('');
            }
        } else if (detailsAttSec) {
            detailsAttSec.style.display = 'none';
        }

        if (contact.trim()) {
            detailsContSec.style.display = '';
            detailsCont.textContent = contact;
        } else {
            detailsContSec.style.display = 'none';
        }

        detailsOverlay.style.display = 'flex';
        lockPageScroll();
    }

    function closeDetailsModal() {
        detailsOverlay.style.display = 'none';
        unlockPageScroll();
    }

    document.querySelectorAll('.res-view-btn').forEach(btn => btn.addEventListener('click', () => openDetailsModal(btn)));
    detailsClose?.addEventListener('click',  closeDetailsModal);
    detailsCancel?.addEventListener('click', closeDetailsModal);
    detailsOverlay?.addEventListener('click', e => { if (e.target === detailsOverlay) closeDetailsModal(); });

    



    const applyOverlay = document.getElementById('apply-modal-overlay');
    const applyClose   = document.getElementById('apply-modal-close');
    const applyCancel  = document.getElementById('apply-modal-cancel');
    const applySubmit  = document.getElementById('apply-modal-submit');
    const applyIcon    = document.getElementById('apply-modal-icon');
    const applyTitle   = document.getElementById('apply-modal-title');
    const applyServId  = document.getElementById('apply-service-id');
    const applyElig    = document.getElementById('apply-sum-eligibility');
    const applyProc    = document.getElementById('apply-sum-processing');
    const applyReqs    = document.getElementById('apply-sum-requirements');
    const applyName    = document.getElementById('r-name');
    const applyContact = document.getElementById('r-contact');
    const applyEmail   = document.getElementById('r-email');
    const applyAddress = document.getElementById('r-address');
    const applyAgree   = document.getElementById('r-agree');
    const applyDocsLabel = document.getElementById('r-docs-label');
    const applyDocsRequired = document.getElementById('r-docs-required');

    
    const discardOverlay = document.getElementById('discard-confirm-overlay');
    const discardCancel  = document.getElementById('discard-confirm-cancel');
    const discardOk      = document.getElementById('discard-confirm-ok');

    function openDiscardConfirm() {
        discardOverlay.style.display = 'flex';
    }

    function closeDiscardConfirm() {
        discardOverlay.style.display = 'none';
    }

    discardCancel?.addEventListener('click', closeDiscardConfirm);
    discardOk?.addEventListener('click', () => {
        closeDiscardConfirm();
        closeApplyModal();
    });
    
    discardOverlay?.addEventListener('click', e => { if (e.target === discardOverlay) closeDiscardConfirm(); });

    let selectedFiles = [];
    let isSubmitting = false;
    let applyRequiresDocuments = true;

    function isApplyFormReady() {
        return Boolean(
            applyName?.value.trim() &&
            applyContact?.value.trim() &&
            applyEmail?.value.trim() &&
            applyAddress?.value.trim() &&
            (!applyRequiresDocuments || selectedFiles.length > 0) &&
            applyAgree?.checked
        );
    }

    function updateApplySubmitState() {
        if (applySubmit) applySubmit.disabled = isSubmitting || !isApplyFormReady();
    }

    [applyName, applyContact, applyAddress].forEach(field => {
        field?.addEventListener('input', updateApplySubmitState);
    });
    applyAgree?.addEventListener('change', updateApplySubmitState);

    function openApplyModal(btn) {
        const id           = btn.dataset.id          || '';
        const service      = btn.dataset.service     || 'Service Request';
        const icon         = btn.dataset.icon        || '';
        const categoryKey  = btn.dataset.categoryKey || 'other';
        const eligibility  = btn.dataset.eligibility || '—';
        const processing   = btn.dataset.processing  || '—';
        const requirements = btn.dataset.requirements || '';

        applyTitle.textContent = service;
        applyIcon.innerHTML    = icon;
        applyIcon.className    = 'modal-icon modal-icon-svc svc-icon-' + categoryKey;
        applyServId.value      = id;
        applyElig.textContent  = eligibility;
        applyProc.textContent  = processing;

        const reqSummary = getRequirementLines(requirements);
        applyRequiresDocuments = reqSummary.length > 0;
        if (applyDocsLabel) {
            applyDocsLabel.textContent = applyRequiresDocuments
                ? 'Upload Required Documents'
                : 'Upload Documents (Optional)';
        }
        if (applyDocsRequired) applyDocsRequired.hidden = !applyRequiresDocuments;
        applyReqs.textContent = reqSummary.length
            ? reqSummary.slice(0, 3).join(', ') + (reqSummary.length > 3 ? '…' : '')
            : '—';

        document.getElementById('apply-form')?.reset();
        const purposeEl = document.getElementById('r-purpose');
        if (purposeEl) purposeEl.value = '';
        const fl = document.getElementById('file-list');
        if (fl) fl.innerHTML = '';
        selectedFiles = [];
        updateApplySubmitState();

        
        const emailEl = document.getElementById('r-email');
        if (emailEl && typeof SESSION_USER_EMAIL !== 'undefined') {
            emailEl.value = SESSION_USER_EMAIL;
        }
        clearApplyErrors();

        applyOverlay.style.display   = 'flex';
        lockPageScroll();
        setTimeout(() => document.getElementById('r-name')?.focus(), 100);
    }

    function closeApplyModal() {
        applyOverlay.style.display   = 'none';
        unlockPageScroll();
    }

    document.querySelectorAll('.svc-apply-btn').forEach(btn => btn.addEventListener('click', () => openApplyModal(btn)));
    applyClose?.addEventListener('click',  openDiscardConfirm);
    applyCancel?.addEventListener('click', openDiscardConfirm);
    

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (discardOverlay?.style.display === 'flex') { closeDiscardConfirm(); return; }
        if (applyOverlay?.style.display === 'flex')   { openDiscardConfirm(); return; }
        if (detailsOverlay?.style.display === 'flex') closeDetailsModal();
    });

    



    function clearApplyErrors() {
        document.querySelectorAll('#apply-modal-overlay .field-error').forEach(el => el.textContent = '');
        ['r-name', 'r-contact', 'r-email', 'r-address'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.borderColor = '';
        });
        const dz = document.getElementById('file-drop-zone');
        if (dz) dz.style.borderColor = '';
    }

    function showError(fieldId, errId, msg) {
        const field = document.getElementById(fieldId);
        const err   = document.getElementById(errId);
        if (field) field.style.borderColor = '#e11d48';
        if (err)   err.textContent = msg;
    }

    function validateApplyForm() {
        clearApplyErrors();
        let valid = true;

        const name    = document.getElementById('r-name')?.value.trim();
        const contact = document.getElementById('r-contact')?.value.trim();
        const email   = document.getElementById('r-email')?.value.trim();
        const address = document.getElementById('r-address')?.value.trim();
        const agree   = document.getElementById('r-agree')?.checked;

        if (!name) { showError('r-name', 'err-name', 'Full name is required.'); valid = false; }

        if (!contact) {
            showError('r-contact', 'err-contact', 'Contact number is required.');
            valid = false;
        } else if (!/^(09|\+639)\d{9}$/.test(contact.replace(/\s/g, ''))) {
            showError('r-contact', 'err-contact', 'Enter a valid PH mobile number (e.g. 09XX XXX XXXX).');
            valid = false;
        }

        if (!email) {
            showError('r-email', 'err-email', 'Email address is required.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('r-email', 'err-email', 'Enter a valid email address.');
            valid = false;
        }

        if (!address) { showError('r-address', 'err-address', 'Home address is required.'); valid = false; }

        if (applyRequiresDocuments && selectedFiles.length === 0) {
            const dz  = document.getElementById('file-drop-zone');
            const err = document.getElementById('err-docs');
            if (dz)  dz.style.borderColor = '#e11d48';
            if (err) err.textContent = 'Please upload at least one required document.';
            valid = false;
        }

        if (!agree) {
            const err = document.getElementById('err-agree');
            if (err) err.textContent = 'You must confirm the acknowledgement to proceed.';
            valid = false;
        }

        return valid;
    }

    



    applySubmit?.addEventListener('click', async () => {
        if (!validateApplyForm()) return;

        
        const fd = new FormData();
        fd.append('action',     'submit');
        fd.append('service_id', applyServId.value);
        fd.append('full_name',  document.getElementById('r-name').value.trim());
        fd.append('contact',    document.getElementById('r-contact').value.trim());
        fd.append('email',      document.getElementById('r-email').value.trim());
        fd.append('address',    document.getElementById('r-address').value.trim());
        fd.append('purpose',    document.getElementById('r-purpose')?.value.trim() ?? '');

        selectedFiles.forEach(file => fd.append('documents[]', file));

        
        isSubmitting = true;
        updateApplySubmitState();
        applySubmit.innerHTML  = '<span style="opacity:.7">Submitting…</span>';
        showLoadingToast('Submitting request…');

        try {
            const res  = await fetch('../../backend/routes/service_requests.php', {
                method: 'POST',
                body:   fd,
            });
            const json = await res.json();
            dismissToast();

            if (json.success) {
                closeApplyModal();
                showToast('Request submitted!', 'success');

                
                const serviceId = applyServId.value;
                document.querySelectorAll(`.svc-apply-btn[data-id="${serviceId}"]`).forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = '✓ Applied';
                    btn.classList.add('svc-apply-btn--applied');
                });
            } else {
                const msgs = json.errors ? json.errors.join('\n') : (json.message || 'Submission failed.');
                showToast(msgs, 'error');
            }
        } catch (err) {
            dismissToast();
            showToast('Network error. Please check your connection and try again.', 'error');
        } finally {
            isSubmitting = false;
            applySubmit.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg> Submit Request`;
            updateApplySubmitState();
        }
    });

    



    const dropZone  = document.getElementById('file-drop-zone');
    const fileInput = document.getElementById('r-docs');
    const fileList  = document.getElementById('file-list');
    const browseBtn = document.getElementById('file-browse-btn');

    browseBtn?.addEventListener('click', e => { e.stopPropagation(); fileInput?.click(); });

    function renderFileList() {
        if (!fileList) return;
        fileList.innerHTML = '';
        selectedFiles.forEach((file, i) => {
            const li = document.createElement('li');
            li.className = 'file-list-item';
            li.innerHTML = `
                <span title="${escapeHtml(file.name)}">
                    📄 ${escapeHtml(file.name)}
                    <em style="color:var(--text-muted);font-style:normal;">(${(file.size / 1024).toFixed(1)} KB)</em>
                </span>
                <button type="button" class="file-remove-btn" data-index="${i}" aria-label="Remove file">&times;</button>
            `;
            fileList.appendChild(li);
        });

        fileList.querySelectorAll('.file-remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedFiles.splice(parseInt(btn.dataset.index), 1);
                renderFileList();
                updateApplySubmitState();
            });
        });
        updateApplySubmitState();
    }

    function addFiles(files) {
        const MAX = 5 * 1024 * 1024;
        Array.from(files).forEach(file => {
            if (file.size > MAX) {
                showToast(`"${file.name}" exceeds the 5 MB limit.`, 'error');
                return;
            }
            if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });
        renderFileList();
        updateApplySubmitState();
    }

    fileInput?.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });

    dropZone?.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone?.addEventListener('dragleave', ()  => dropZone.classList.remove('dragover'));
    dropZone?.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });

    



    function showToast(message, type = 'success') {
        const existing = document.getElementById('svc-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'svc-toast';
        toast.style.cssText = `
            position:fixed; bottom:28px; right:28px; z-index:9999;
            display:flex; align-items:center; gap:10px;
            padding:14px 20px; border-radius:10px;
            font-size:13px; font-weight:500;
            font-family:"Poppins",sans-serif;
            box-shadow:0 8px 32px rgba(15,37,69,0.18);
            animation:toast-in 0.3s cubic-bezier(0.16,1,0.3,1);
            max-width:380px; line-height:1.5; white-space:pre-line;
            ${type === 'success'
                ? 'background:#15803d;color:#fff;border:1px solid #15803d;'
                : 'background:#b91c1c;color:#fff;border:1px solid #b91c1c;'}
        `;
        toast.innerHTML = `
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="width:18px;height:18px;flex-shrink:0;">
                ${type === 'success'
                    ? '<path d="m5 12 4 4L19 6"/>'
                    : '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>'}
            </svg>
            <span>${escapeHtml(message)}</span>
        `;

        if (!document.getElementById('toast-style')) {
            const style = document.createElement('style');
            style.id = 'toast-style';
            style.textContent = `@keyframes toast-in{from{opacity:0;transform:translateY(12px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes spin{to{transform:rotate(360deg)}}`;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);
        setTimeout(() => { toast.style.transition = 'opacity 0.3s'; toast.style.opacity = '0'; }, 3500);
        setTimeout(() => toast.remove(), 3800);
    }

    function showLoadingToast(message) {
        const existing = document.getElementById('svc-toast');
        if (existing) existing.remove();

        if (!document.getElementById('toast-style')) {
            const style = document.createElement('style');
            style.id = 'toast-style';
            style.textContent = `@keyframes toast-in{from{opacity:0;transform:translateY(12px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes spin{to{transform:rotate(360deg)}}`;
            document.head.appendChild(style);
        }

        const toast = document.createElement('div');
        toast.id = 'svc-toast';
        toast.style.cssText = `
            position:fixed; bottom:28px; right:28px; z-index:9999;
            display:flex; align-items:center; gap:10px;
            padding:14px 20px; border-radius:10px;
            font-size:13px; font-weight:500;
            font-family:"Poppins",sans-serif;
            box-shadow:0 8px 32px rgba(15,37,69,0.18);
            animation:toast-in 0.3s cubic-bezier(0.16,1,0.3,1);
            max-width:380px; line-height:1.5;
            background:#1d4ed8; color:#fff; border:1px solid #1d4ed8;
        `;
        toast.innerHTML = `
            <span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.45);
                border-top-color:#fff;border-radius:50%;
                animation:spin 0.7s linear infinite;flex-shrink:0;"></span>
            <span>${escapeHtml(message)}</span>
        `;
        document.body.appendChild(toast);
    }

    function dismissToast() {
        const t = document.getElementById('svc-toast');
        if (t) { t.style.transition = 'opacity 0.2s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 220); }
    }

    



    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    if (document.readyState === 'complete') {
        finishLoading();
    } else {
        window.addEventListener('load', finishLoading, { once: true });
    }

});