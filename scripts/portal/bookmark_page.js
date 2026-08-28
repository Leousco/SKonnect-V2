
document.addEventListener('DOMContentLoaded', () => {

    const searchInput  = document.getElementById('bm-search');
    const categorySelect = document.getElementById('bm-category');
    const sortSelect     = document.getElementById('bm-sort');
    const grid           = document.getElementById('bm-grid');
    const noResults      = document.getElementById('bm-no-results');
    const pageNumbersEl  = document.getElementById('bm-page-numbers');
    const prevBtn        = document.getElementById('bm-prev-btn');
    const nextBtn        = document.getElementById('bm-next-btn');

    const CARDS_PER_PAGE = 6;
    let currentPage = 1;
    let allCards    = Array.from(document.querySelectorAll('.bmp-card'));

    const BOOKMARK_URL = window.SKONNECT?.bookmarkRouteUrl ?? '../../backend/routes/bookmarks.php';

    // FILTER, SORT, PAGINATION

    function getVisibleCards() {
        const query    = searchInput?.value.toLowerCase().trim() ?? '';
        const category = categorySelect?.value ?? 'all';
        const sort     = sortSelect?.value     ?? 'newest_bm';

        let filtered = allCards.filter(card => {
            const title   = card.dataset.title    || '';
            const cardCat = card.dataset.category || '';
            const excerpt = card.querySelector('.ann-card-excerpt')?.textContent.toLowerCase() || '';

            const matchesSearch   = !query || title.includes(query) || excerpt.includes(query);
            const matchesCategory = category === 'all' || cardCat === category;

            return matchesSearch && matchesCategory;
        });

        filtered.sort((a, b) => {
            const daPub = new Date(a.dataset.datePub || '2000-01-01');
            const dbPub = new Date(b.dataset.datePub || '2000-01-01');
            const daBm  = new Date(a.dataset.dateBm  || '2000-01-01');
            const dbBm  = new Date(b.dataset.dateBm  || '2000-01-01');

            switch (sort) {
                case 'oldest_bm':  return daBm  - dbBm;
                case 'newest_pub': return dbPub - daPub;
                case 'oldest_pub': return daPub - dbPub;
                default:           return dbBm  - daBm; 
            }
        });

        return filtered;
    }

    function renderPage() {
        const visible = getVisibleCards();
        const total   = visible.length;

        if (noResults) noResults.style.display = total === 0 ? 'block' : 'none';

        allCards.forEach(c => c.style.display = 'none');

        const totalPages = Math.max(1, Math.ceil(total / CARDS_PER_PAGE));
        currentPage      = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * CARDS_PER_PAGE;
        const slice = visible.slice(start, start + CARDS_PER_PAGE);

        slice.forEach(card => {
            grid.appendChild(card);
            card.style.display = '';
        });

        renderPagination(totalPages, total);
    }

    function renderPagination(totalPages, totalItems) {
        if (!pageNumbersEl) return;
        pageNumbersEl.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className   = 'page-num' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.addEventListener('click', () => { currentPage = i; renderPage(); });
            pageNumbersEl.appendChild(btn);
        }

        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages || totalItems === 0;
    }

    function resetAndRender() {
        currentPage = 1;
        renderPage();
    }

    searchInput?.addEventListener('input',     debounce(resetAndRender, 250));
    categorySelect?.addEventListener('change', resetAndRender);
    sortSelect?.addEventListener('change',     resetAndRender);

    prevBtn?.addEventListener('click', () => { currentPage--; renderPage(); });
    nextBtn?.addEventListener('click', () => { currentPage++; renderPage(); });

    // REMOVE BOOKMARKS

    function wireBookmarkButtons() {
        document.querySelectorAll('.bmp-remove-btn').forEach(btn => {

            if (btn.dataset.wired) return;
            btn.dataset.wired = '1';

            btn.addEventListener('click', async () => {
                const id = Number(btn.dataset.id);
                if (!id) return;

                btn.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('announcement_id', id);

                    const res  = await fetch(`${BOOKMARK_URL}?action=toggle`, {
                        method: 'POST',
                        body:   formData,
                    });
                    const data = await res.json();

                    if (data.status !== 'success') throw new Error(data.message);

                    const card = btn.closest('.bmp-card');
                    if (card) {
                        card.style.transition = 'opacity 0.25s, transform 0.25s';
                        card.style.opacity    = '0';
                        card.style.transform  = 'scale(0.95)';

                        setTimeout(() => {
                            card.remove();
                            allCards = allCards.filter(c => c !== card);
                            updateCountLabel();
                            renderPage();
                        }, 260);
                    }

                } catch (err) {
                    console.error('Remove bookmark failed:', err);
                    btn.disabled = false;
                }
            });
        });
    }

    function updateCountLabel() {
        const subtitle = document.querySelector('.bmp-subtitle');
        if (!subtitle) return;
        const remaining = allCards.length;
        if (remaining === 0) {
            subtitle.innerHTML = 'You have no saved announcements yet.';
            grid.innerHTML = `
                <div class="bmp-empty-state">
                    <div class="bmp-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                        </svg>
                    </div>
                    <h3 class="bmp-empty-title">No saved announcements</h3>
                    <p class="bmp-empty-desc">Browse announcements and tap the bookmark icon to save them here for quick access.</p>
                    <a href="announcements_page.php" class="btn-primary-portal">Browse Announcements</a>
                </div>`;
        } else {
            subtitle.innerHTML = `You have <strong>${remaining}</strong> saved announcement${remaining !== 1 ? 's' : ''}.`;
        }
    }

    // HELPER
    function debounce(fn, delay) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }

    // INITIALIZE
    wireBookmarkButtons();
    if (allCards.length > 0) renderPage();
});