window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});



document.addEventListener('DOMContentLoaded', () => {

    const searchInput    = document.getElementById('pub-search');
    const categorySelect = document.getElementById('pub-category');
    const sortSelect     = document.getElementById('pub-sort');
    const grid           = document.getElementById('pub-grid');
    const noResults      = document.getElementById('pub-no-results');
    const pageNumbersEl  = document.getElementById('pub-page-numbers');
    const prevBtn        = document.getElementById('pub-prev');
    const nextBtn        = document.getElementById('pub-next');
    const pagination     = document.querySelector('.pagination');

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
        if (pagination) pagination.style.display = totalPages > 1 ? '' : 'none';
        const start = (currentPage - 1) * CARDS_PER_PAGE;
        visible.slice(start, start + CARDS_PER_PAGE).forEach(card => {
            grid.appendChild(card);
            card.style.display = '';
        });

        
        pageNumbersEl.innerHTML = '';
        window.SKONNECT_PAGINATION.getPageRange(currentPage, totalPages).forEach(page => {
            if (page === '…') {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = page;
                pageNumbersEl.appendChild(ellipsis);
                return;
            }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className   = 'page-btn' + (page === currentPage ? ' active' : '');
            btn.textContent = page;
            btn.style.fontWeight = page === currentPage ? '700' : '400';
            if (page === currentPage) btn.setAttribute('aria-current', 'page');
            btn.addEventListener('click', () => { currentPage = page; renderPage(); });
            pageNumbersEl.appendChild(btn);
        });

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || total === 0;
    }

    function reset() { currentPage = 1; renderPage(); }

    searchInput?.addEventListener('input',  debounce(reset, 250));
    categorySelect?.addEventListener('change', reset);
    sortSelect?.addEventListener('change',     reset);
    prevBtn?.addEventListener('click', () => {
        if (currentPage > 1) currentPage--;
        renderPage();
    });
    nextBtn?.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(getVisible().length / CARDS_PER_PAGE));
        if (currentPage < totalPages) currentPage++;
        renderPage();
    });

    function debounce(fn, ms) {
        let t;
        return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    }

    renderPage();
});