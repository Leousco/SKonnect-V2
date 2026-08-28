/* scripts/management/admin/admin_threads.js */

document.addEventListener('DOMContentLoaded', () => {

    let allThreads    = [];
    let currentThread = null;
    let currentPage   = 1;
    const THREADS_PER_PAGE = 9;

    const THREAD_API = window.THREAD_API || '../../../backend/routes/admin_threads.php';

    const searchInput    = document.getElementById('thread-search');
    const categorySelect = document.getElementById('thread-category');
    const statusSelect   = document.getElementById('thread-status');
    const grid           = document.getElementById('thread-grid');
    const noResults      = document.getElementById('no-results');
    const overlay        = document.getElementById('thread-modal-overlay');
    const btnPin         = document.getElementById('btn-pin');

    const categoryLabels = {
        inquiry:        'Inquiry',
        complaint:      'Complaint',
        suggestion:     'Suggestion',
        event_question: 'Event Question',
        other:          'Other',
    };

    async function loadThreads() {
        grid.innerHTML = '<div class="svc-loading">Loading threads…</div>';
        noResults.style.display = 'none';

        try {
            const res  = await fetch(`${THREAD_API}?action=list`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            allThreads = json.data;
            updateStats();
            currentPage = 1;
            renderGrid(allThreads);
            renderPagination();
        } catch (err) {
            grid.innerHTML = `<div class="svc-no-results"><p>Failed to load threads: ${err.message}</p></div>`;
        }
    }

    function updateStats() {
        document.getElementById('stat-total').textContent     = allThreads.length;
        document.getElementById('stat-pending').textContent   = allThreads.filter(t => t.status === 'pending').length;
        document.getElementById('stat-responded').textContent = allThreads.filter(t => t.status === 'responded').length;
        document.getElementById('stat-resolved').textContent  = allThreads.filter(t => t.status === 'resolved').length;
        document.getElementById('stat-pinned').textContent    = allThreads.filter(t => t.pinned).length;
    }

    function getFilteredThreads() {
        const query    = searchInput.value.toLowerCase().trim();
        const category = categorySelect.value;
        const status   = statusSelect.value;

        return allThreads.filter(t => {
            const matchSearch   = !query || t.title.toLowerCase().includes(query) || t.author.toLowerCase().includes(query);
            const matchCategory = category === 'all' || t.category === category;
            const matchStatus   = status === 'all' || t.status === status;
            return matchSearch && matchCategory && matchStatus;
        });
    }

    function getPaginatedThreads(threads) {
        const start = (currentPage - 1) * THREADS_PER_PAGE;
        return threads.slice(start, start + THREADS_PER_PAGE);
    }

    function getTotalPages(filteredThreads) {
        return Math.max(1, Math.ceil(filteredThreads.length / THREADS_PER_PAGE));
    }

    function renderPagination() {
        const filteredThreads = getFilteredThreads();
        const totalPages = getTotalPages(filteredThreads);
        
        // Remove existing pagination if any
        const existingPagination = document.querySelector('.svc-pagination-wrapper');
        if (existingPagination) existingPagination.remove();

        if (totalPages <= 1) return;

        const paginationWrapper = document.createElement('div');
        paginationWrapper.className = 'svc-pagination-wrapper';
        
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'svc-pagination';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'svc-page-btn svc-page-prev';
        prevBtn.innerHTML = '‹ Previous';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderGrid(filteredThreads);
                renderPagination();
            }
        });
        paginationContainer.appendChild(prevBtn);
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `svc-page-num ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                renderGrid(filteredThreads);
                renderPagination();
            });
            paginationContainer.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'svc-page-btn svc-page-next';
        nextBtn.innerHTML = 'Next ›';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderGrid(filteredThreads);
                renderPagination();
            }
        });
        paginationContainer.appendChild(nextBtn);
        
        // Page info
        const pageInfo = document.createElement('div');
        pageInfo.className = 'svc-page-info';
        const startItem = (currentPage - 1) * THREADS_PER_PAGE + 1;
        const endItem = Math.min(startItem + THREADS_PER_PAGE - 1, filteredThreads.length);
        pageInfo.textContent = `Showing ${startItem}–${endItem} of ${filteredThreads.length} threads`;
        
        paginationWrapper.appendChild(paginationContainer);
        paginationWrapper.appendChild(pageInfo);
        
        // Insert after the grid
        grid.parentNode.insertBefore(paginationWrapper, grid.nextSibling);
    }

    function renderGrid(threadsToFilter) {
        const filteredThreads = getFilteredThreads();
        const paginatedThreads = getPaginatedThreads(filteredThreads);
        
        grid.innerHTML = '';

        if (paginatedThreads.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        noResults.style.display = 'none';

        paginatedThreads.forEach(t => {
            const catLabel  = categoryLabels[t.category] ?? 'Other';
            const statusCls = `badge-thread-${t.status}`;
            const pinnedCls = t.pinned ? 'is-pinned' : '';
            const date      = new Date(t.created_at).toLocaleDateString('en-PH', {
                year: 'numeric', month: 'short', day: 'numeric'
            });

            const card = document.createElement('article');
            card.className = `svc-card thread-card ${pinnedCls}`;
            card.dataset.category = t.category;
            card.dataset.status   = t.status;
            card.dataset.title    = (t.title  ?? '').toLowerCase();
            card.dataset.author   = (t.author ?? '').toLowerCase();

            card.innerHTML = `
                <div class="svc-card-body">
                    <div class="svc-card-top" style="flex-wrap:wrap; gap:6px;">
                        <div style="display:flex; gap:6px; flex-wrap:wrap; flex:1;">
                            <span class="thread-cat-badge cat-${t.category}">${escHtml(catLabel)}</span>
                            <span class="svc-badge ${statusCls}">${capitalize(t.status)}</span>
                            ${t.pinned  ? '<span class="thread-flag-badge">📌 Pinned</span>'  : ''}
                            ${t.flagged ? '<span class="thread-flag-badge">🚩 Flagged</span>' : ''}
                        </div>
                    </div>
                    <h3 class="svc-card-title">${escHtml(t.title)}</h3>
                    <p class="svc-card-excerpt">${escHtml(t.excerpt)}</p>
                    <div class="svc-thread-meta">
                        <span class="meta-item">👤 <strong>${escHtml(t.author)}</strong></span>
                        <span class="meta-sep">·</span>
                        <span class="meta-item">📅 <strong>${date}</strong></span>
                        <span class="meta-sep">·</span>
                        <span class="meta-item">💬 <strong>${t.comments}</strong></span>
                        <span class="meta-sep">·</span>
                        <span class="meta-item">👍 <strong>${t.support_count ?? 0}</strong></span>
                    </div>
                    <div class="svc-card-actions">
                        <button class="btn-svc-primary" onclick="openThreadModal(${t.id})">
                            👁️ View &amp; Act
                        </button>
                    </div>
                </div>`;

            grid.appendChild(card);
        });
    }

    function filterCards() {
        currentPage = 1;
        renderGrid();
        renderPagination();
    }

    searchInput?.addEventListener('input',     filterCards);
    categorySelect?.addEventListener('change', filterCards);
    statusSelect?.addEventListener('change',   filterCards);

    function openThreadModal(id) {
        const t = allThreads.find(x => x.id == id);
        if (!t) { showToast('Thread not found.', 'error'); return; }

        currentThread = t;

        const date = new Date(t.created_at).toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric'
        });

        document.getElementById('thread-modal-title').textContent    = t.title;
        document.getElementById('thread-modal-subtitle').textContent = `Posted by ${t.author}`;
        document.getElementById('thread-modal-author').textContent   = t.author;
        document.getElementById('thread-modal-category').textContent = categoryLabels[t.category] ?? 'Other';
        document.getElementById('thread-modal-status').textContent   = capitalize(t.status);
        document.getElementById('thread-modal-excerpt').textContent  = t.excerpt;
        document.getElementById('thread-modal-meta').textContent     = `📅 ${date} · 💬 ${t.comments} comment${t.comments !== 1 ? 's' : ''}`;

        btnPin.textContent = t.pinned ? '📌 Unpin' : '📌 Pin';

        overlay.classList.add('is-open');
    }

    function closeThreadModal() {
        overlay.classList.remove('is-open');
        currentThread = null;
    }

    overlay?.addEventListener('click', e => { if (e.target === overlay) closeThreadModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeThreadModal(); });

    async function threadAction(action) {
        if (!currentThread) return;

        const id = currentThread.id;

        if (action === 'delete') {
            if (!confirm(`Delete thread "${currentThread.title}"? This cannot be undone.`)) return;
        }

        try {
            const res  = await fetch(`${THREAD_API}?action=${action}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id }),
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            if (action === 'pin') {
                const t = allThreads.find(x => x.id == id);
                if (t) t.pinned = json.data.pinned;
                showToast(json.message, 'success');
            } else if (action === 'delete') {
                allThreads = allThreads.filter(x => x.id != id);
                showToast('🗑️ Thread deleted!', 'error');
            }

            closeThreadModal();
            updateStats();
            filterCards();

        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className   = `svc-toast toast-${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    window.openThreadModal  = openThreadModal;
    window.closeThreadModal = closeThreadModal;
    window.threadAction     = threadAction;

    loadThreads();
});