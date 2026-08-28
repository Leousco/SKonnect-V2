document.addEventListener('DOMContentLoaded', () => {

    const searchInput  = document.getElementById('pub-svc-search');
    const catSelect    = document.getElementById('pub-svc-category');
    const typeSelect   = document.getElementById('pub-svc-type');
    const cards        = Array.from(document.querySelectorAll('.pub-svc-card'));
    const noResults    = document.getElementById('pub-no-results');
    const svcCount     = document.getElementById('pub-svc-count');

    function filterCards() {
        const query    = searchInput.value.toLowerCase().trim();
        const category = catSelect.value;
        const type     = typeSelect.value;
        let   visible  = 0;

        cards.forEach(card => {
            const title   = card.querySelector('.pub-card-title')?.textContent.toLowerCase()  || '';
            const desc    = card.querySelector('.pub-card-excerpt')?.textContent.toLowerCase() || '';
            const cardCat = card.dataset.category || '';
            const cardType= card.dataset.type     || '';

            const matchSearch   = !query    || title.includes(query) || desc.includes(query);
            const matchCategory = category === 'all' || cardCat  === category;
            const matchType     = type     === 'all' || cardType === type;

            const show = matchSearch && matchCategory && matchType;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noResults) noResults.style.display = visible === 0 ? 'flex' : 'none';
        if (svcCount)  svcCount.textContent = `Showing ${visible} service${visible !== 1 ? 's' : ''}`;
    }

    searchInput?.addEventListener('input',  filterCards);
    catSelect?.addEventListener('change',   filterCards);
    typeSelect?.addEventListener('change',  filterCards);

    const overlay        = document.getElementById('pub-details-overlay');
    const closeBtn       = document.getElementById('pub-details-close');
    const cancelBtn      = document.getElementById('pub-details-cancel');
    const modalIcon      = document.getElementById('pub-details-icon');
    const modalTitle     = document.getElementById('pub-details-title');
    const modalTypeLabel = document.getElementById('pub-details-type-label');
    const statusStrip    = document.getElementById('pub-details-status');
    const descEl         = document.getElementById('pub-details-description');
    const eligEl         = document.getElementById('pub-details-eligibility');
    const procEl         = document.getElementById('pub-details-processing');
    const capWrap        = document.getElementById('pub-details-cap-wrap');
    const capEl          = document.getElementById('pub-details-capacity');
    const reqSection     = document.getElementById('pub-details-req-section');
    const reqList        = document.getElementById('pub-details-req-list');
    const contactSection = document.getElementById('pub-details-contact-section');
    const contactEl      = document.getElementById('pub-details-contact');
    const ctaBlock       = document.getElementById('pub-details-cta');

    const statusMessages = {
        open:    'Open — Currently accepting requests',
        limited: 'Limited Slots — Hurry, slots are filling up fast',
        closed:  'Closed — Not currently accepting requests',
    };

    function openModal(btn) {
        const service      = btn.dataset.service        || 'Service';
        const icon         = btn.dataset.icon           || '';
        const categoryKey  = btn.dataset.categoryKey    || 'other';
        const typeLabel    = btn.dataset.typeLabel      || '—';
        const status       = btn.dataset.status         || 'closed';
        const description  = btn.dataset.description    || '—';
        const eligibility  = btn.dataset.eligibility    || '—';
        const processing   = btn.dataset.processing     || '—';
        const requirements = btn.dataset.requirements   || '';
        const contact      = btn.dataset.contact        || '';
        const capacity     = btn.dataset.capacity       || '';
        const attNames     = btn.dataset.attachmentNames|| '';
        const attPaths     = btn.dataset.attachmentPaths|| '';
        const isInfo       = btn.dataset.isInfo         === '1';

        modalTitle.textContent     = service;
        modalTypeLabel.textContent = typeLabel;
        modalIcon.innerHTML        = icon;
        modalIcon.className        = 'pub-modal-icon svc-icon-' + categoryKey;

        statusStrip.className   = 'pub-details-status-strip strip-' + status;
        statusStrip.textContent = statusMessages[status] || status;

        descEl.textContent = description;
        eligEl.textContent = eligibility;
        procEl.textContent = processing;

        if (capacity) {
            const [cur, max] = capacity.split('/').map(Number);
            const pct  = Math.min(100, Math.round((cur / max) * 100));
            const full = cur >= max;
            const warn = !full && pct >= 70;

            capWrap.style.display  = '';
            capEl.textContent      = `${cur} / ${max} slots filled`;
            capEl.className        = 'pub-details-meta-value' + (full ? ' slots-full' : warn ? ' slots-limited' : '');

            let barWrap = document.getElementById('pub-modal-cap-bar');
            if (!barWrap) {
                barWrap = document.createElement('div');
                barWrap.id = 'pub-modal-cap-bar';
                barWrap.style.cssText = 'margin-top:8px;';
                barWrap.innerHTML = '<div style="height:5px;background:#d1dae6;border-radius:10px;overflow:hidden;"><div id="pub-modal-cap-fill" style="height:100%;border-radius:10px;transition:width .4s;"></div></div>';
                capWrap.appendChild(barWrap);
            }
            const fill = document.getElementById('pub-modal-cap-fill');
            if (fill) {
                fill.style.width      = pct + '%';
                fill.style.background = full ? '#dc2626' : warn ? '#f59e0b' : '#0f2545';
            }
        } else {
            capWrap.style.display = 'none';
        }

        const reqLines = requirements.split('\n').map(l => l.trim().replace(/^[-•]\s*/, '')).filter(Boolean);
        if (reqLines.length) {
            reqSection.style.display = '';
            reqList.innerHTML = reqLines.map(r => `<li>${escapeHtml(r)}</li>`).join('');
        } else {
            reqSection.style.display = 'none';
        }

        const attNamesArr = attNames ? attNames.split(',').map(n => n.trim()).filter(Boolean) : [];
        const attPathsArr = attPaths ? attPaths.split(',').map(p => p.trim()).filter(Boolean) : [];
        let attSection = document.getElementById('pub-modal-att-section');
        if (attNamesArr.length) {
            if (!attSection) {
                attSection = document.createElement('div');
                attSection.id = 'pub-modal-att-section';
                attSection.className = 'pub-details-section';
                attSection.innerHTML = `
                    <span class="pub-details-section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                        Downloadable Forms
                    </span>
                    <div id="pub-modal-att-list" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;"></div>`;
                const modalBody = overlay.querySelector('.pub-modal-body');
                if (contactSection && modalBody) modalBody.insertBefore(attSection, contactSection);
                else if (modalBody) modalBody.appendChild(attSection);
            }
            attSection.style.display = '';
            const attListEl = document.getElementById('pub-modal-att-list');
            if (attListEl) {
                attListEl.innerHTML = attNamesArr.map((name, i) => {
                    const path = attPathsArr[i] || '#';
                    return `<a href="${escapeHtml(path)}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#1e5fa8;text-decoration:none;padding:6px 10px;border-radius:6px;border:1px solid #d1dae6;background:#f0f4f8;transition:background 0.15s;" target="_blank" download title="${escapeHtml(name)}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        ${escapeHtml(name)}
                    </a>`;
                }).join('');
            }
        } else if (attSection) {
            attSection.style.display = 'none';
        }

        if (contact.trim()) {
            contactSection.style.display = '';
            contactEl.textContent = contact;
        } else {
            contactSection.style.display = 'none';
        }

        ctaBlock.style.display = (!isInfo && status !== 'closed') ? '' : 'none';

        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.pub-view-btn').forEach(btn => btn.addEventListener('click', () => openModal(btn)));
    closeBtn?.addEventListener('click',  closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

});