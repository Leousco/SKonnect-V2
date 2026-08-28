
document.addEventListener('DOMContentLoaded', () => {

    const searchInput    = document.getElementById('ann-search');
    const categorySelect = document.getElementById('ann-category');
    const sortSelect     = document.getElementById('ann-sort');
    const grid           = document.getElementById('announcements-grid');
    const noResults      = document.getElementById('no-results');
    const pageNumbersEl  = document.getElementById('page-numbers');
    const prevBtn        = document.getElementById('prev-btn');
    const nextBtn        = document.getElementById('next-btn');
    const countBadge     = document.getElementById('bm-count-badge');

    const CARDS_PER_PAGE = 6;
    let currentPage = 1;
    let allCards    = Array.from(document.querySelectorAll('.ann-card'));

    const bookmarkedIds  = new Set((window.SKONNECT?.bookmarkedIds ?? []).map(Number));
    const BOOKMARK_URL   = window.SKONNECT?.bookmarkRouteUrl ?? '../../backend/routes/bookmarks.php';

    function getVisibleCards() {
        const query    = searchInput.value.toLowerCase().trim();
        const category = categorySelect.value;
        const sort     = sortSelect.value;

        let filtered = allCards.filter(card => {
            const title   = card.dataset.title || '';
            const cardCat = card.dataset.category || '';
            const excerpt = card.querySelector('.ann-card-excerpt')?.textContent.toLowerCase() || '';

            const matchesSearch   = !query || title.includes(query) || excerpt.includes(query);
            const matchesCategory = category === 'all' || cardCat === category;

            return matchesSearch && matchesCategory;
        });

        filtered.sort((a, b) => {
            const da = new Date(a.dataset.date || '2000-01-01');
            const db = new Date(b.dataset.date || '2000-01-01');
            return sort === 'oldest' ? da - db : db - da;
        });

        return filtered;
    }

    function renderPage() {
        const visible = getVisibleCards();
        const total   = visible.length;

        noResults.style.display = total === 0 ? 'block' : 'none';

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
        pageNumbersEl.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-num' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.addEventListener('click', () => { currentPage = i; renderPage(); });
            pageNumbersEl.appendChild(btn);
        }

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || totalItems === 0;
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


    function applyBookmarkState(btn) {
        const id         = Number(btn.dataset.id);
        const isActive   = bookmarkedIds.has(id);
        const icon       = btn.querySelector('.bm-icon');

        btn.classList.toggle('active', isActive);
        btn.title = isActive ? 'Remove bookmark' : 'Bookmark this';

        if (icon) {
            icon.setAttribute('fill', isActive ? 'currentColor' : 'none');
        }
    }

    // Wire up all bookmark buttons
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        applyBookmarkState(btn);

        btn.addEventListener('click', async () => {
            const id = Number(btn.dataset.id);
            if (!id) return;

          
            const willBeBookmarked = !bookmarkedIds.has(id);
            if (willBeBookmarked) {
                bookmarkedIds.add(id);
            } else {
                bookmarkedIds.delete(id);
            }
            applyBookmarkState(btn);

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

                if (data.bookmarked) {
                    bookmarkedIds.add(id);
                } else {
                    bookmarkedIds.delete(id);
                }
                applyBookmarkState(btn);

            } catch (err) {
                console.error('Bookmark toggle failed:', err);
                if (willBeBookmarked) {
                    bookmarkedIds.delete(id);
                } else {
                    bookmarkedIds.add(id);
                }
                applyBookmarkState(btn);
            } finally {
                btn.disabled = false;
            }
        });
    });


    function debounce(fn, delay) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }

    renderPage();
});