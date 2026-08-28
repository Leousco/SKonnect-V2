/* scripts/public/announcements.js */

document.addEventListener('DOMContentLoaded', () => {

    const searchInput    = document.getElementById('pub-search');
    const categorySelect = document.getElementById('pub-category');
    const sortSelect     = document.getElementById('pub-sort');
    const grid           = document.getElementById('pub-grid');
    const noResults      = document.getElementById('pub-no-results');
    const pageNumbersEl  = document.getElementById('pub-page-numbers');
    const prevBtn        = document.getElementById('pub-prev');
    const nextBtn        = document.getElementById('pub-next');

    const CARDS_PER_PAGE = 6;
    let currentPage = 1;
    const allCards  = Array.from(document.querySelectorAll('.announcement-card'));

    function getVisible() {
        const query = searchInput?.value.toLowerCase().trim() || '';
        const cat   = categorySelect?.value || 'all';
        const sort  = sortSelect?.value || 'newest';

        let filtered = allCards.filter(card => {
            const title   = card.dataset.title || '';
            const excerpt = card.querySelector('.excerpt')?.textContent.toLowerCase() || '';
            const cardCat = card.dataset.category || '';

            return (!query || title.includes(query) || excerpt.includes(query))
                && (cat === 'all' || cardCat === cat);
        });

        filtered.sort((a, b) => {
            const da = new Date(a.dataset.date || '2000-01-01');
            const db = new Date(b.dataset.date || '2000-01-01');
            return sort === 'oldest' ? da - db : db - da;
        });

        return filtered;
    }

    function renderPage() {
        const visible    = getVisible();
        const total      = visible.length;
        const totalPages = Math.max(1, Math.ceil(total / CARDS_PER_PAGE));

        noResults.style.display = total === 0 ? 'block' : 'none';
        allCards.forEach(c => c.style.display = 'none');

        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * CARDS_PER_PAGE;
        visible.slice(start, start + CARDS_PER_PAGE).forEach(card => {
            grid.appendChild(card);
            card.style.display = '';
        });

        // Pagination buttons
        pageNumbersEl.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className   = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.style.fontWeight = i === currentPage ? '700' : '400';
            btn.addEventListener('click', () => { currentPage = i; renderPage(); });
            pageNumbersEl.appendChild(btn);
        }

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || total === 0;
    }

    function reset() { currentPage = 1; renderPage(); }

    searchInput?.addEventListener('input',  debounce(reset, 250));
    categorySelect?.addEventListener('change', reset);
    sortSelect?.addEventListener('change',     reset);
    prevBtn?.addEventListener('click', () => { currentPage--; renderPage(); });
    nextBtn?.addEventListener('click', () => { currentPage++; renderPage(); });

    function debounce(fn, ms) {
        let t;
        return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    }

    renderPage();
});