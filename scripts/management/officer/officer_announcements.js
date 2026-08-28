// officer_announcements.js - full backend integration for announcement feature.

const API = "../../../backend/routes/announcements.php";
const ROWS_PER_PAGE = 10;

document.addEventListener("DOMContentLoaded", function () {


  //  TAB SWITCHING
  const tabs = document.querySelectorAll(".ann-tab");
  const panels = {
    list: document.getElementById("panel-list"),
    drafts: document.getElementById("panel-drafts"),
    create: document.getElementById("panel-create"),
    archive: document.getElementById("panel-archive"),
  };

  function switchTab(target) {
    tabs.forEach((t) => t.classList.toggle("active", t.dataset.tab === target));
    Object.entries(panels).forEach(([key, el]) => {
      if (el) el.classList.toggle("ann-panel--hidden", key !== target);
    });
    if (target === "list") loadList();
    if (target === "drafts") loadDrafts();
    if (target === "archive") loadArchive();
    if (target === "create") updateCreatePanelMode();
  }

  tabs.forEach((tab) =>
    tab.addEventListener("click", () => {
      if (tab.dataset.tab !== "create" && editingId) {
        resetEditState();
        resetCreateForm();
      }
      switchTab(tab.dataset.tab);
    })
  );

  //  PAGINATION HELPER
  function buildPagination(containerEl, allRows, state, renderFn) {
    const total = allRows.length;
    const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));

    // Clamp page
    if (state.page < 1) state.page = 1;
    if (state.page > totalPages) state.page = totalPages;

    const start = (state.page - 1) * ROWS_PER_PAGE;
    const pageRows = allRows.slice(start, start + ROWS_PER_PAGE);
    renderFn(pageRows);

    if (!containerEl) return;
    containerEl.innerHTML = "";

    if (totalPages <= 1) return; // no paginator needed if rows are <= 10

    // Prev button
    const prev = document.createElement("button");
    prev.className = "ann-page-btn";
    prev.innerHTML = "&#8249; Prev";
    prev.disabled = state.page === 1;
    prev.addEventListener("click", () => {
      state.page--;
      buildPagination(containerEl, allRows, state, renderFn);
    });
    containerEl.appendChild(prev);

    // Page number buttons (max 5 visible around current)
    const numWrap = document.createElement("div");
    numWrap.className = "ann-page-numbers";

    const makePageBtn = (p) => {
      const btn = document.createElement("button");
      btn.className = "ann-page-num" + (p === state.page ? " active" : "");
      btn.textContent = p;
      btn.addEventListener("click", () => {
        state.page = p;
        buildPagination(containerEl, allRows, state, renderFn);
      });
      return btn;
    };

    const makeEllipsis = () => {
      const span = document.createElement("span");
      span.className = "ann-page-ellipsis";
      span.textContent = "…";
      return span;
    };

    if (totalPages <= 7) {
      for (let p = 1; p <= totalPages; p++) numWrap.appendChild(makePageBtn(p));
    } else {
      numWrap.appendChild(makePageBtn(1));
      if (state.page > 3) numWrap.appendChild(makeEllipsis());
      const lo = Math.max(2, state.page - 1);
      const hi = Math.min(totalPages - 1, state.page + 1);
      for (let p = lo; p <= hi; p++) numWrap.appendChild(makePageBtn(p));
      if (state.page < totalPages - 2) numWrap.appendChild(makeEllipsis());
      numWrap.appendChild(makePageBtn(totalPages));
    }
    containerEl.appendChild(numWrap);

    // Next button
    const next = document.createElement("button");
    next.className = "ann-page-btn";
    next.innerHTML = "Next &#8250;";
    next.disabled = state.page === totalPages;
    next.addEventListener("click", () => {
      state.page++;
      buildPagination(containerEl, allRows, state, renderFn);
    });
    containerEl.appendChild(next);
  }

  //  LOAD PUBLISHED ANNOUNCEMENT LIST
  const listTbody = document.getElementById("list-tbody");
  const listPagEl = document.getElementById("list-pagination");
  const listState = { page: 1 };
  let listAllRows = [];

  const listSearchInput = document.getElementById("list-search-input");
  const listCatSelect = document.getElementById("list-filter-cat");
  const listSortSelect = document.getElementById("list-filter-sort");

  //  Client-side sorting
  function sortRows(rows, sortVal, dateField) {
    const field = dateField || "published_at";
    return [...rows].sort((a, b) => {
      const dateA = new Date(a[field] || 0);
      const dateB = new Date(b[field] || 0);
      return sortVal === "oldest" ? dateA - dateB : dateB - dateA;
    });
  }

  async function loadList() {
    const params = new URLSearchParams({
      action: "listAll",
      status: "active",
      search: listSearchInput?.value || "",
      category: listCatSelect?.value || "",
    });
    try {
      const res = await fetch(`${API}?${params}`);
      const json = await res.json();
      if (json.status !== "success") return showToast(json.message, "error");
      renderStats(json.stats);
      listAllRows = sortRows(
        json.data,
        listSortSelect?.value || "newest",
        "published_at"
      );
      listState.page = 1;
      buildPagination(listPagEl, listAllRows, listState, renderListRows);
    } catch (e) {
      showToast("Failed to load announcements.", "error");
    }
  }

  function renderStats(stats) {
    const el = (id) => document.getElementById(id);
    if (el("stat-published"))
      el("stat-published").textContent = stats.published || 0;
    if (el("stat-featured"))
      el("stat-featured").textContent = stats.featured || 0;
    if (el("stat-urgent")) el("stat-urgent").textContent = stats.urgent || 0;
    if (el("stat-drafts")) el("stat-drafts").textContent = stats.drafts || 0;
    if (el("stat-archived"))
      el("stat-archived").textContent = stats.archived || 0;
  }

  function renderListRows(rows) {
    if (!listTbody) return;
    if (!rows.length) {
      listTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:#94a3b8;">No published announcements found.</td></tr>`;
      return;
    }
    listTbody.innerHTML = rows
      .map((a) => {
        const isFeatured = a.featured == 1;
        const date = formatDate(a.published_at);
        const catPill = catPillHtml(a.category);
        const featDot = isFeatured
          ? `<span class="ann-featured-dot dot-yes" title="Featured">&#9733;</span>`
          : `<span class="ann-featured-dot dot-no" title="Not Featured">&#9734;</span>`;

        return `
             <tr class="ann-row ${
               isFeatured ? "ann-row--featured" : ""
             }" data-id="${a.id}">
                 <td>
                     <div class="ann-thumb" style="background:${thumbBg(
                       a.category
                     )};">
                         ${thumbIcon(a.category)}
                     </div>
                 </td>
                 <td>
                     <div class="ann-title-cell">
                         <span class="ann-title-text">${escHtml(a.title)}</span>
                     </div>
                     <span class="ann-excerpt">${escHtml(
                       a.content.replace(/<[^>]*>/g, "").substring(0, 100)
                     )}…</span>
                 </td>
                 <td>${catPill}</td>
                 <td>${featDot}</td>
                 <td class="ann-date">${date}</td>
                 <td>
                     <button class="row-action-btn btn-preview" title="Preview announcement" data-id="${
                       a.id
                     }">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                     </button>
                 </td>
                 <td>
                     <div class="ann-row-actions">
                         <button class="row-action-btn btn-edit" title="Edit" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                         </button>
                         <button class="row-action-btn btn-archive" title="Archive" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                         </button>
                         <button class="row-action-btn btn-delete" title="Delete" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                         </button>
                     </div>
                 </td>
             </tr>`;
      })
      .join("");

    bindListActions();
  }

  function bindListActions() {
    listTbody
      ?.querySelectorAll(".btn-preview")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          openPreviewModal(parseInt(btn.dataset.id))
        )
      );
    listTbody
      ?.querySelectorAll(".btn-edit")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          openEditForm(parseInt(btn.dataset.id))
        )
      );
    listTbody
      ?.querySelectorAll(".btn-archive")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          archiveAnnouncement(parseInt(btn.dataset.id))
        )
      );
    listTbody
      ?.querySelectorAll(".btn-delete")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          deleteAnnouncement(parseInt(btn.dataset.id))
        )
      );
  }

  listSearchInput?.addEventListener(
    "input",
    debounce(() => {
      listState.page = 1;
      loadList();
    }, 300)
  );
  listCatSelect?.addEventListener("change", () => {
    listState.page = 1;
    loadList();
  });
  listSortSelect?.addEventListener("change", () => {
    listState.page = 1;
    loadList();
  });

  //  LOAD DRAFTS ANNOUNCEMENTS
  const draftsTbody = document.getElementById("drafts-tbody");
  const draftsPagEl = document.getElementById("drafts-pagination");
  const draftsState = { page: 1 };
  let draftsAllRows = [];

  const draftsSearchInput = document.getElementById("drafts-search-input");
  const draftsCatSelect = document.getElementById("drafts-filter-cat");
  const draftsSortSelect = document.getElementById("drafts-filter-sort");

  async function loadDrafts() {
    const params = new URLSearchParams({
      action: "listAll",
      status: "draft",
      search: draftsSearchInput?.value || "",
      category: draftsCatSelect?.value || "",
    });
    try {
      const res = await fetch(`${API}?${params}`);
      const json = await res.json();
      if (json.status !== "success") return showToast(json.message, "error");
      renderStats(json.stats);
      draftsAllRows = sortRows(
        json.data,
        draftsSortSelect?.value || "newest",
        "published_at"
      );
      draftsState.page = 1;
      buildPagination(draftsPagEl, draftsAllRows, draftsState, renderDraftRows);
    } catch {
      showToast("Failed to load drafts.", "error");
    }
  }

  function renderDraftRows(rows) {
    if (!draftsTbody) return;
    if (!rows.length) {
      draftsTbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:2rem;color:#94a3b8;">No drafts found.</td></tr>`;
      return;
    }
    draftsTbody.innerHTML = rows
      .map((a) => {
        // Use updated_at if available, fall back to published_at (which is the created date for drafts)
        const savedDate = formatDate(a.updated_at || a.published_at);
        return `
             <tr class="ann-row" data-id="${a.id}">
                 <td>
                     <div class="ann-thumb" style="background:${thumbBg(
                       a.category
                     )};">
                         ${thumbIcon(a.category)}
                     </div>
                 </td>
                 <td>
                     <div class="ann-title-cell">
                         <span class="ann-title-text">${escHtml(a.title)}</span>
                     </div>
                     <span class="ann-excerpt">${escHtml(
                       a.content.replace(/<[^>]*>/g, "").substring(0, 100)
                     )}…</span>
                 </td>
                 <td>${catPillHtml(a.category)}</td>
                 <td class="ann-date">${savedDate}</td>
                 <td>
                     <div class="ann-row-actions">
                         <button class="row-action-btn btn-edit" title="Edit Draft" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                         </button>
                         <button class="row-action-btn btn-archive" title="Archive Draft" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                         </button>
                         <button class="row-action-btn btn-delete" title="Delete Permanently" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                         </button>
                     </div>
                 </td>
             </tr>`;
      })
      .join("");

    draftsTbody
      .querySelectorAll(".btn-edit")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          openEditForm(parseInt(btn.dataset.id))
        )
      );
    draftsTbody
      .querySelectorAll(".btn-archive")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          archiveAnnouncement(parseInt(btn.dataset.id))
        )
      );
    draftsTbody
      .querySelectorAll(".btn-delete")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          deleteAnnouncement(parseInt(btn.dataset.id))
        )
      );
  }

  draftsSearchInput?.addEventListener(
    "input",
    debounce(() => {
      draftsState.page = 1;
      loadDrafts();
    }, 300)
  );
  draftsCatSelect?.addEventListener("change", () => {
    draftsState.page = 1;
    loadDrafts();
  });
  draftsSortSelect?.addEventListener("change", () => {
    draftsState.page = 1;
    loadDrafts();
  });

  //  LOAD ARCHIVED ANNOUNCEMENTS
  const archiveTbody = document.getElementById("archive-tbody");
  const archivePagEl = document.getElementById("archive-pagination");
  const archiveState = { page: 1 };
  let archiveAllRows = [];

  const archiveSearchInput = document.getElementById("archive-search-input");
  const archiveCatSelect = document.getElementById("archive-filter-cat");
  const archiveSortSelect = document.getElementById("archive-filter-sort");

  async function loadArchive() {
    const params = new URLSearchParams({
      action: "listAll",
      status: "archived",
      search: archiveSearchInput?.value || "",
      category: archiveCatSelect?.value || "",
    });
    try {
      const res = await fetch(`${API}?${params}`);
      const json = await res.json();
      if (json.status !== "success") return showToast(json.message, "error");
      renderStats(json.stats);
      archiveAllRows = sortRows(
        json.data,
        archiveSortSelect?.value || "newest",
        "published_at"
      );
      archiveState.page = 1;
      buildPagination(
        archivePagEl,
        archiveAllRows,
        archiveState,
        renderArchiveRows
      );
    } catch {
      showToast("Failed to load archive.", "error");
    }
  }

  function renderArchiveRows(rows) {
    if (!archiveTbody) return;
    if (!rows.length) {
      archiveTbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:#94a3b8;">Archive is empty.</td></tr>`;
      return;
    }

    archiveTbody.innerHTML = rows
      .map((a) => {
        // Archived reason: if expired_at is set AND is in the past, it is "expired";
        // otherwise it was "manually" archived by an officer.
        const wasExpired = a.expired_at && new Date(a.expired_at) < new Date();
        const reason = wasExpired ? "Expired" : "Manual";
        const reasonClass = wasExpired ? "reason-expired" : "reason-manual";

        // archived_at = the actual date it was archived (stored by archive() now)
        // Fall back to updated_at if archived_at column is empty
        const archivedOnDate = a.archived_at
          ? formatDate(a.archived_at)
          : a.updated_at
          ? formatDate(a.updated_at)
          : "—";

        return `
             <tr class="ann-row ann-row--archived" data-id="${a.id}">
                 <td>
                     <div class="ann-thumb" style="background:#f1f5f9;">
                         ${thumbIcon(a.category, "#94a3b8")}
                     </div>
                 </td>
                 <td>
                     <div class="ann-title-cell">
                         <span class="ann-title-text ann-title-text--archived">${escHtml(
                           a.title
                         )}</span>
                     </div>
                     <span class="ann-excerpt">${escHtml(
                       a.content.replace(/<[^>]*>/g, "").substring(0, 100)
                     )}…</span>
                 </td>
                 <td>${catPillHtml(a.category)}</td>
                 <td>
                     <span class="ann-archive-reason ${reasonClass}">${reason}</span>
                 </td>
                 <td class="ann-date">${formatDate(a.published_at)}</td>
                 <td class="ann-date">${archivedOnDate}</td>
                 <td>
                     <div class="ann-row-actions">
                         <button class="row-action-btn btn-restore" title="Restore to Draft" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                         </button>
                         <button class="row-action-btn btn-delete" title="Delete Permanently" data-id="${
                           a.id
                         }">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                         </button>
                     </div>
                 </td>
             </tr>`;
      })
      .join("");

    archiveTbody
      .querySelectorAll(".btn-restore")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          restoreAnnouncement(parseInt(btn.dataset.id))
        )
      );
    archiveTbody
      .querySelectorAll(".btn-delete")
      .forEach((btn) =>
        btn.addEventListener("click", () =>
          deleteAnnouncement(parseInt(btn.dataset.id))
        )
      );
  }

  archiveSearchInput?.addEventListener(
    "input",
    debounce(() => {
      archiveState.page = 1;
      loadArchive();
    }, 300)
  );
  archiveCatSelect?.addEventListener("change", () => {
    archiveState.page = 1;
    loadArchive();
  });
  archiveSortSelect?.addEventListener("change", () => {
    archiveState.page = 1;
    loadArchive();
  });

  //  CREATE ANNOUNCEMENT FORM: PUBLISH, DRAFT, CANCEL
  const btnPublish = document.querySelector(".btn-ann-primary");
  const btnDraft = document.querySelector(".btn-ann-secondary");

  // Inject Cancel button
  const formActionsEl = document.querySelector(".ann-form-actions");
  let btnCancel = null;
  if (formActionsEl) {
    btnCancel = document.createElement("button");
    btnCancel.type = "button";
    btnCancel.className = "btn-ann-cancel";
    btnCancel.id = "btn-cancel-form";
    btnCancel.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg> Cancel`;
    formActionsEl.insertBefore(btnCancel, formActionsEl.firstChild);
    btnCancel.addEventListener("click", () => {
      resetEditState();
      resetCreateForm();
      switchTab("list");
    });
  }

  async function submitForm(status) {
    const title = document.getElementById("ann-title")?.value.trim();
    const bodyEl = document.getElementById("ann-body");
    const content = bodyEl ? bodyEl.innerHTML.trim() : "";
    const contentText = bodyEl ? bodyEl.innerText.trim() : "";
    const category = document.querySelector(
      'input[name="category"]:checked'
    )?.value;

    if (!title || !contentText || !category) {
      showToast("Please fill in title, body and select a category.", "error");
      return;
    }

    const fd = new FormData();
    fd.append("action", "create");
    fd.append("title", title);
    fd.append("content", content);
    fd.append("category", category);
    fd.append(
      "featured",
      document.getElementById("featured-checkbox")?.checked ? "1" : "0"
    );
    fd.append("status", status);
    fd.append(
      "publish_date",
      document.getElementById("ann-publish-date")?.value || ""
    );
    fd.append(
      "expiry_date",
      document.getElementById("ann-expiry-date")?.value || ""
    );

    if (selectedBannerFile) fd.append("banner", selectedBannerFile);
    attachments.forEach((att) => {
      if (att.file) fd.append("attachments[]", att.file, att.name);
    });

    setFormLoading(true);
    try {
      const res = await fetch(API, { method: "POST", body: fd });
      const json = await res.json();
      if (json.status === "success") {
        showToast(json.message, "success");
        resetCreateForm();
        // Go to 'drafts' tab if saved as draft, else go to 'published' tab
        switchTab(status === "draft" ? "drafts" : "list");
      } else {
        showToast(json.message, "error");
      }
    } catch {
      showToast("An error occurred. Please try again.", "error");
    } finally {
      setFormLoading(false);
    }
  }

  btnPublish?.addEventListener("click", () => {
    if (editingId) submitEdit("active");
    else submitForm("active");
  });
  btnDraft?.addEventListener("click", () => {
    if (editingId) submitEdit("draft");
    else submitForm("draft");
  });

  function setFormLoading(loading) {
    if (btnPublish) btnPublish.disabled = loading;
    if (btnDraft) btnDraft.disabled = loading;
    if (btnCancel) btnCancel.disabled = loading;
    if (btnPublish)
      btnPublish.textContent = loading
        ? editingId
          ? "Updating…"
          : "Publishing…"
        : editingId
        ? "Update Announcement"
        : "Publish Now";
  }

  function resetCreateForm() {
    const titleEl = document.getElementById("ann-title");
    const bodyEl = document.getElementById("ann-body");
    if (titleEl) titleEl.value = "";
    if (bodyEl) bodyEl.innerHTML = "";

    document
      .querySelectorAll('input[name="category"]')
      .forEach((r) => (r.checked = false));

    const fc = document.getElementById("featured-checkbox");
    if (fc) {
      fc.checked = false;
      document
        .getElementById("featured-toggle-card")
        ?.classList.remove("is-featured");
    }

    const today = new Date().toISOString().split("T")[0];
    const pubDate = document.getElementById("ann-publish-date");
    if (pubDate) pubDate.value = today;
    const expDate = document.getElementById("ann-expiry-date");
    if (expDate) expDate.value = "";

    selectedBannerFile = null;
    existingBannerPath = null;
    const bannerFile = document.getElementById("banner-file");
    if (bannerFile) bannerFile.value = "";
    const bdi = document.getElementById("banner-drop-inner");
    if (bdi) bdi.style.display = "";
    const bp = document.getElementById("banner-preview");
    if (bp) bp.style.display = "none";
    const bpi = document.getElementById("banner-preview-img");
    if (bpi) {
      bpi.src = "";
      bpi.style.display = "none";
    }
    const pbImg = document.getElementById("preview-banner-img");
    if (pbImg) {
      pbImg.src = "";
      pbImg.style.display = "none";
    }
    const pbPh = document.getElementById("preview-banner-placeholder");
    if (pbPh) pbPh.style.display = "";
    toggleCheck(document.getElementById("check-banner"), false);

    attachments = [];
    savedAttachments = [];
    renderAttachments();

    const prevTitle = document.getElementById("preview-title");
    if (prevTitle)
      prevTitle.textContent = "Your announcement title will appear here…";
    const prevExcerpt = document.getElementById("preview-excerpt");
    if (prevExcerpt)
      prevExcerpt.textContent =
        "The announcement body text will be summarised here for the card view.";
    const prevCat = document.getElementById("preview-cat-pill");
    if (prevCat) {
      prevCat.textContent = "Category";
      prevCat.className = "ann-badge preview-img-badge";
    }
    const prevDate = document.getElementById("preview-date");
    if (prevDate)
      prevDate.textContent = new Date().toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });

    toggleCheck(document.getElementById("check-title"), false);
    toggleCheck(document.getElementById("check-body"), false);
    toggleCheck(document.getElementById("check-category"), false);

    const charCount = document.getElementById("title-char");
    if (charCount) charCount.textContent = "0 / 120";
  }

  // EDIT FORM
  let editingId = null;
  let existingBannerPath = null;
  let savedAttachments = [];

  function updateCreatePanelMode() {
    if (btnPublish)
      btnPublish.textContent = editingId
        ? "Update Announcement"
        : "Publish Now";
    if (btnCancel)
      btnCancel.title = editingId
        ? "Cancel editing and go back"
        : "Cancel and go back";
  }

  async function openEditForm(id) {
    resetEditState();
    resetCreateForm();
    try {
      const res = await fetch(`${API}?action=getForEdit&id=${id}`);
      const json = await res.json();
      if (json.status !== "success")
        return showToast("Could not load announcement.", "error");

      const a = json.data;
      editingId = a.id;

      document.getElementById("ann-title").value = a.title;
      const bodyEditor = document.getElementById("ann-body");
      if (bodyEditor) bodyEditor.innerHTML = a.content || "";

      const catRadio = document.querySelector(
        `input[name="category"][value="${a.category}"]`
      );
      if (catRadio) catRadio.checked = true;

      const fc = document.getElementById("featured-checkbox");
      if (fc) {
        fc.checked = a.featured == 1;
        document
          .getElementById("featured-toggle-card")
          ?.classList.toggle("is-featured", a.featured == 1);
      }

      const pubDateEl = document.getElementById("ann-publish-date");
      if (pubDateEl && a.published_at)
        pubDateEl.value = a.published_at.split(" ")[0];
      const expDateEl = document.getElementById("ann-expiry-date");
      if (expDateEl) expDateEl.value = a.expired_at || "";

      if (a.banner_img) {
        existingBannerPath = a.banner_img;
        loadBannerFromUrl(a.banner_img);
      }

      savedAttachments = (json.files || []).map((f) => {
        const name = f.file_path.split("/").pop();
        return {
          id: f.id,
          file_path: f.file_path,
          name,
          type: getFileType(name),
          size: "—",
          saved: true,
        };
      });
      attachments = [];
      renderAttachments();

      if (btnPublish) btnPublish.textContent = "Update Announcement";

      document.getElementById("ann-title")?.dispatchEvent(new Event("input"));
      document.getElementById("ann-body")?.dispatchEvent(new Event("input"));
      catRadio?.dispatchEvent(new Event("change"));

      switchTab("create");
    } catch (e) {
      console.error(e);
      showToast("Failed to load announcement for editing.", "error");
    }
  }

  function loadBannerFromUrl(url) {
    const bannerDropInner = document.getElementById("banner-drop-inner");
    const bannerPreview = document.getElementById("banner-preview");
    const bannerPreviewImg = document.getElementById("banner-preview-img");
    const previewBannerImg = document.getElementById("preview-banner-img");
    const previewBannerPlaceholder = document.getElementById(
      "preview-banner-placeholder"
    );

    if (bannerDropInner) bannerDropInner.style.display = "none";
    if (bannerPreview) bannerPreview.style.display = "block";
    if (bannerPreviewImg) {
      bannerPreviewImg.src = url;
      bannerPreviewImg.style.display = "block";
    }
    if (previewBannerImg) {
      previewBannerImg.src = url;
      previewBannerImg.style.display = "block";
    }
    if (previewBannerPlaceholder)
      previewBannerPlaceholder.style.display = "none";
    toggleCheck(document.getElementById("check-banner"), true);
  }

  async function submitEdit(status) {
    if (!editingId) return;

    const title = document.getElementById("ann-title")?.value.trim();
    const bodyEl2 = document.getElementById("ann-body");
    const content = bodyEl2 ? bodyEl2.innerHTML.trim() : "";
    const contentText2 = bodyEl2 ? bodyEl2.innerText.trim() : "";
    const category = document.querySelector(
      'input[name="category"]:checked'
    )?.value;

    if (!title || !contentText2 || !category) {
      showToast("Please fill in title, body and category.", "error");
      return;
    }

    const fd = new FormData();
    fd.append("action", "update");
    fd.append("id", editingId);
    fd.append("title", title);
    fd.append("content", content);
    fd.append("category", category);
    fd.append(
      "featured",
      document.getElementById("featured-checkbox")?.checked ? "1" : "0"
    );
    fd.append("status", status);
    fd.append(
      "publish_date",
      document.getElementById("ann-publish-date")?.value || ""
    );
    fd.append(
      "expiry_date",
      document.getElementById("ann-expiry-date")?.value || ""
    );

    if (selectedBannerFile) fd.append("banner", selectedBannerFile);
    attachments.forEach((att) => {
      if (att.file) fd.append("attachments[]", att.file, att.name);
    });

    setFormLoading(true);
    try {
      const res = await fetch(API, { method: "POST", body: fd });
      const json = await res.json();
      if (json.status === "success") {
        showToast(json.message, "success");
        resetEditState();
        resetCreateForm();
        switchTab(status === "draft" ? "drafts" : "list");
      } else {
        showToast(json.message, "error");
      }
    } catch {
      showToast("Update failed.", "error");
    } finally {
      setFormLoading(false);
    }
  }

  function resetEditState() {
    editingId = null;
    existingBannerPath = null;
    savedAttachments = [];
    if (btnPublish) btnPublish.textContent = "Publish Now";
  }

  //  PREVIEW MODAL
  const catThemes = {
    event: {
      bg: "#d1fae5",
      color: "#065f46",
      border: "#6ee7b7",
      accent: "#059669",
    },
    program: {
      bg: "#dbeafe",
      color: "#1d4ed8",
      border: "#93c5fd",
      accent: "#2563eb",
    },
    meeting: {
      bg: "#ede9fe",
      color: "#5b21b6",
      border: "#c4b5fd",
      accent: "#7c3aed",
    },
    notice: {
      bg: "#fef3c7",
      color: "#92400e",
      border: "#fcd34d",
      accent: "#d97706",
    },
    urgent: {
      bg: "#fee2e2",
      color: "#b91c1c",
      border: "#fca5a5",
      accent: "#dc2626",
    },
  };

  function fileIconEmoji(path) {
    const ext = (path.split(".").pop() || "").toLowerCase();
    if (ext === "pdf") return "📄";
    if (["doc", "docx"].includes(ext)) return "📝";
    if (["xls", "xlsx"].includes(ext)) return "📊";
    if (["png", "jpg", "jpeg", "webp", "gif"].includes(ext)) return "🖼️";
    return "📎";
  }

  function fmtFullDate(str) {
    if (!str) return "";
    return new Date(str).toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
  }

  async function openPreviewModal(id) {
    let modal = document.getElementById("ann-preview-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "ann-preview-modal";
      modal.innerHTML = `
                 <div class="apm-backdrop"></div>
                 <div class="apm-sheet">
                     <div class="apm-topbar">
                         <span class="apm-topbar-label">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                             Announcement Preview
                         </span>
                         <button class="apm-close" id="apm-close-btn" title="Close preview">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                         </button>
                     </div>
                     <div class="apm-body" id="apm-body">
                         <div class="apm-loading"><div class="apm-spinner"></div><span>Loading preview…</span></div>
                     </div>
                 </div>`;
      document.body.appendChild(modal);
      modal
        .querySelector(".apm-backdrop")
        .addEventListener("click", closePreviewModal);
      modal
        .querySelector("#apm-close-btn")
        .addEventListener("click", closePreviewModal);
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closePreviewModal();
      });
    }

    modal.classList.add("apm--open");
    document.body.style.overflow = "hidden";

    const apmBody = document.getElementById("apm-body");
    apmBody.innerHTML = `<div class="apm-loading"><div class="apm-spinner"></div><span>Loading preview…</span></div>`;

    try {
      const res = await fetch(`${API}?action=getForEdit&id=${id}`);
      const json = await res.json();
      if (json.status !== "success") {
        apmBody.innerHTML = `<div class="apm-error">Could not load announcement.</div>`;
        return;
      }

      const a = json.data;
      const files = json.files || [];
      const th = catThemes[a.category] || catThemes["notice"];
      const catLbl = a.category.charAt(0).toUpperCase() + a.category.slice(1);
      const pubDate = fmtFullDate(a.published_at);
      const pubDateDt = (a.published_at || "").split(" ")[0];
      const updDate = a.updated_at ? fmtFullDate(a.updated_at) : null;

      const statusMap = {
        active: ["Published", "#d1fae5", "#065f46", "#6ee7b7"],
        draft: ["Draft", "#f1f5f9", "#475569", "#cbd5e1"],
        archived: ["Archived", "#f1f5f9", "#64748b", "#cbd5e1"],
      };
      const [sLabel, sBg, sColor, sBorder] =
        statusMap[a.status] || statusMap["active"];

      const filesHtml = files.length
        ? `
                 <div class="apm-attachments">
                     <h3 class="apm-attach-title">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                         Attachments <span class="apm-attach-count">${
                           files.length
                         }</span>
                     </h3>
                     <ul class="apm-attach-list">
                         ${files
                           .map(
                             (f) => `
                         <li class="apm-attach-item">
                             <span class="apm-attach-icon">${fileIconEmoji(
                               f.file_path
                             )}</span>
                             <span class="apm-attach-name">${escHtml(
                               f.file_path.split("/").pop()
                             )}</span>
                             <a href="${escHtml(
                               f.file_path
                             )}" download class="apm-attach-dl" title="Download">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                             </a>
                         </li>`
                           )
                           .join("")}
                     </ul>
                 </div>`
        : "";

      apmBody.innerHTML = `
                 <div class="apm-layout">
                     <article class="apm-main">
                         ${
                           a.banner_img
                             ? `
                         <div class="apm-banner">
                             <img src="${escHtml(a.banner_img)}" alt="${escHtml(
                                 a.title
                               )}">
                             ${
                               a.featured == 1
                                 ? `<div class="apm-featured-ribbon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.45 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd"/></svg>Featured</div>`
                                 : ""
                             }
                         </div>`
                             : ""
                         }
                         <div class="apm-header" style="--apm-cat-bg:${
                           th.bg
                         };--apm-cat-border:${th.border};--apm-cat-accent:${
        th.accent
      };">
                             <div class="apm-badges">
                                 <span class="apm-cat-badge" style="background:${
                                   th.bg
                                 };color:${th.color};border:1px solid ${
        th.border
      };">${catLbl}</span>
                                 ${
                                   a.featured == 1
                                     ? `<span class="apm-featured-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 .975 6.323 4.793l-4.098.328c-.717.058-1.01.953-.462 1.423l3.121 2.673-.953 3.997c-.168.7.595 1.25 1.211.879L8 11.992l3.858 2.101c.616.371 1.379-.18 1.211-.879l-.953-3.997 3.121-2.673c.548-.47.255-1.365-.462-1.423L10.677 4.793 8 .975Z" clip-rule="evenodd"/></svg>Featured</span>`
                                     : ""
                                 }
                                 <span class="apm-status-badge" style="background:${sBg};color:${sColor};border:1px solid ${sBorder};">${sLabel}</span>
                             </div>
                             <h1 class="apm-title">${escHtml(a.title)}</h1>
                             <div class="apm-meta-row">
                                 <div class="apm-meta-item">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                     <span>Posted by <strong>${escHtml(
                                       a.author_name || "—"
                                     )}</strong></span>
                                 </div>
                                 <div class="apm-meta-item">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                     <time datetime="${pubDateDt}">${pubDate}</time>
                                 </div>
                                 ${
                                   updDate
                                     ? `<div class="apm-meta-item apm-meta-updated"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg><span>Updated ${updDate}</span></div>`
                                     : ""
                                 }
                             </div>
                         </div>
                         <div class="apm-divider" style="background:linear-gradient(90deg,${
                           th.accent
                         },transparent);"></div>
                         <div class="apm-body-text">${a.content}</div>
                         ${filesHtml}
                     </article>
                     <aside class="apm-sidebar">
                         <div class="apm-info-card">
                             <h3 class="apm-info-title">Announcement Info</h3>
                             <dl class="apm-info-list">
                                 <div class="apm-info-row"><dt>Category</dt><dd><span class="apm-info-cat-pill" style="background:${
                                   th.bg
                                 };color:${th.color};border:1px solid ${
        th.border
      };">${catLbl}</span></dd></div>
                                 <div class="apm-info-row"><dt>Status</dt><dd><span style="background:${sBg};color:${sColor};border:1px solid ${sBorder};display:inline-flex;padding:3px 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-radius:20px;">${sLabel}</span></dd></div>
                                 <div class="apm-info-row"><dt>Posted by</dt><dd><strong>${escHtml(
                                   a.author_name || "—"
                                 )}</strong></dd></div>
                                 <div class="apm-info-row"><dt>Published</dt><dd><strong>${pubDate}</strong></dd></div>
                                 ${
                                   updDate
                                     ? `<div class="apm-info-row"><dt>Updated</dt><dd><strong>${updDate}</strong></dd></div>`
                                     : ""
                                 }
                                 ${
                                   a.expired_at
                                     ? `<div class="apm-info-row"><dt>Expires</dt><dd><strong>${fmtFullDate(
                                         a.expired_at
                                       )}</strong></dd></div>`
                                     : ""
                                 }
                                 ${
                                   files.length
                                     ? `<div class="apm-info-row"><dt>Attachments</dt><dd><strong>${
                                         files.length
                                       }</strong> file${
                                         files.length > 1 ? "s" : ""
                                       }</dd></div>`
                                     : ""
                                 }
                             </dl>
                         </div>
                         <button class="apm-edit-btn" data-id="${a.id}">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                             Edit This Announcement
                         </button>
                     </aside>
                 </div>`;

      apmBody
        .querySelector(".apm-edit-btn")
        ?.addEventListener("click", function () {
          closePreviewModal();
          openEditForm(parseInt(this.dataset.id));
        });
    } catch (e) {
      console.error(e);
      apmBody.innerHTML = `<div class="apm-error">Failed to load announcement preview.</div>`;
    }
  }

  function closePreviewModal() {
    const modal = document.getElementById("ann-preview-modal");
    if (modal) modal.classList.remove("apm--open");
    document.body.style.overflow = "";
  }

  //  ACTIONS: ARCHIVE, RESTORE, DELETE
  async function archiveAnnouncement(id) {
    if (!confirm("Archive this announcement?")) return;
    const fd = new FormData();
    fd.append("action", "archive");
    fd.append("id", id);
    const res = await fetch(API, { method: "POST", body: fd });
    const json = await res.json();
    showToast(json.message, json.status);
    if (json.status === "success") {
      loadList();
      loadDrafts();
    }
  }

  async function restoreAnnouncement(id) {
    if (!confirm("Restore this announcement to Draft?")) return;
    const fd = new FormData();
    fd.append("action", "restore");
    fd.append("id", id);
    const res = await fetch(API, { method: "POST", body: fd });
    const json = await res.json();
    showToast(json.message, json.status);
    if (json.status === "success") loadArchive();
  }

  async function deleteAnnouncement(id) {
    if (
      !confirm("Permanently delete this announcement? This cannot be undone.")
    )
      return;
    const fd = new FormData();
    fd.append("action", "delete");
    fd.append("id", id);
    const res = await fetch(API, { method: "POST", body: fd });
    const json = await res.json();
    showToast(json.message, json.status);
    if (json.status === "success") {
      loadList();
      loadDrafts();
      loadArchive();
    }
  }

  // LIVE PREVIEW FUNCTIONS
  const titleInput = document.getElementById("ann-title");
  const previewTitle = document.getElementById("preview-title");
  const charCount = document.getElementById("title-char");
  const checkTitle = document.getElementById("check-title");

  if (titleInput) {
    titleInput.addEventListener("input", function () {
      const val = this.value.trim();
      if (previewTitle)
        previewTitle.textContent =
          val || "Your announcement title will appear here…";
      if (charCount) charCount.textContent = `${this.value.length} / 120`;
      toggleCheck(checkTitle, val.length > 0);
    });
  }

  const bodyInput = document.getElementById("ann-body");
  const previewExcerpt = document.getElementById("preview-excerpt");
  const checkBody = document.getElementById("check-body");

  if (bodyInput) {
    bodyInput.addEventListener("input", function () {
      const val = this.innerText.trim();
      if (previewExcerpt)
        previewExcerpt.textContent = val
          ? val.slice(0, 160) + (val.length > 160 ? "…" : "")
          : "The announcement body text will be summarised here for the card view.";
      toggleCheck(checkBody, val.length > 0);
    });
  }

  const catRadios = document.querySelectorAll('input[name="category"]');
  const previewCatPill = document.getElementById("preview-cat-pill");
  const checkCategory = document.getElementById("check-category");
  const catLabels = {
    event: "Event",
    program: "Program",
    meeting: "Meeting",
    notice: "Notice",
    urgent: "Urgent",
  };

  catRadios.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (previewCatPill) {
        previewCatPill.className =
          "ann-badge preview-img-badge category-" + this.value;
        previewCatPill.textContent = catLabels[this.value] || this.value;
      }
      toggleCheck(checkCategory, true);
    });
  });

  const featuredCheckbox = document.getElementById("featured-checkbox");
  const featuredToggleCard = document.getElementById("featured-toggle-card");
  if (featuredCheckbox) {
    featuredCheckbox.addEventListener("change", function () {
      featuredToggleCard?.classList.toggle("is-featured", this.checked);
    });
  }

  let selectedBannerFile = null;

  const bannerFileInput = document.getElementById("banner-file");
  const bannerDropZone = document.getElementById("banner-drop-zone");
  const bannerDropInner = document.getElementById("banner-drop-inner");
  const bannerPreview = document.getElementById("banner-preview");
  const bannerPreviewImg = document.getElementById("banner-preview-img");
  const bannerRemoveBtn = document.getElementById("banner-remove");
  const previewBannerImg = document.getElementById("preview-banner-img");
  const previewBannerPlaceholder = document.getElementById(
    "preview-banner-placeholder"
  );
  const checkBanner = document.getElementById("check-banner");

  function loadBanner(file) {
    if (!file || !file.type.startsWith("image/")) return;
    selectedBannerFile = file;
    existingBannerPath = null;
    const url = URL.createObjectURL(file);
    if (bannerDropInner) bannerDropInner.style.display = "none";
    if (bannerPreview) bannerPreview.style.display = "block";
    if (bannerPreviewImg) bannerPreviewImg.src = url;
    if (previewBannerImg) {
      previewBannerImg.src = url;
      previewBannerImg.style.display = "block";
    }
    if (previewBannerPlaceholder)
      previewBannerPlaceholder.style.display = "none";
    toggleCheck(checkBanner, true);
  }

  bannerFileInput?.addEventListener("change", function () {
    if (this.files[0]) loadBanner(this.files[0]);
  });
  bannerRemoveBtn?.addEventListener("click", function () {
    selectedBannerFile = null;
    existingBannerPath = null;
    if (bannerDropInner) bannerDropInner.style.display = "";
    if (bannerPreview) bannerPreview.style.display = "none";
    if (bannerPreviewImg) {
      bannerPreviewImg.src = "";
      bannerPreviewImg.style.display = "none";
    }
    if (previewBannerPlaceholder) previewBannerPlaceholder.style.display = "";
    if (bannerFileInput) bannerFileInput.value = "";
    toggleCheck(checkBanner, false);
  });

  if (bannerDropZone) {
    bannerDropZone.addEventListener("dragover", (e) => {
      e.preventDefault();
      bannerDropZone.classList.add("drag-over");
    });
    bannerDropZone.addEventListener("dragleave", () =>
      bannerDropZone.classList.remove("drag-over")
    );
    bannerDropZone.addEventListener("drop", (e) => {
      e.preventDefault();
      bannerDropZone.classList.remove("drag-over");
      const file = e.dataTransfer.files[0];
      if (file) loadBanner(file);
    });
  }

  const attachFileInput = document.getElementById("attach-files");
  const attachList = document.getElementById("attach-list");
  const attachDropZone = document.getElementById("attach-drop-zone");
  let attachments = [];

  function getFileType(name) {
    const ext = name.split(".").pop().toLowerCase();
    if (ext === "pdf") return "pdf";
    if (["doc", "docx"].includes(ext)) return "doc";
    if (["xls", "xlsx"].includes(ext)) return "xls";
    return "img";
  }
  function formatSize(bytes) {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / 1048576).toFixed(1) + " MB";
  }

  function renderAttachments() {
    if (!attachList) return;
    attachList.innerHTML = "";

    savedAttachments.forEach((att, idx) => {
      const li = document.createElement("li");
      li.className = "ann-attach-item ann-attach-item--saved";
      li.innerHTML = `
                 <div class="attach-icon attach-icon--${
                   att.type
                 }">${att.type.toUpperCase()}</div>
                 <div class="attach-meta">
                     <span class="attach-name">${escHtml(att.name)}</span>
                     <span class="attach-size attach-saved-label">Saved</span>
                 </div>
                 <button type="button" class="attach-remove-btn attach-remove-saved" data-idx="${idx}" title="Remove attachment">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                 </button>`;
      attachList.appendChild(li);
    });

    attachments.forEach((att, idx) => {
      const li = document.createElement("li");
      li.className = "ann-attach-item ann-attach-item--new";
      li.innerHTML = `
                 <div class="attach-icon attach-icon--${
                   att.type
                 }">${att.type.toUpperCase()}</div>
                 <div class="attach-meta">
                     <span class="attach-name">${escHtml(att.name)}</span>
                     <span class="attach-size">${att.size}</span>
                 </div>
                 <button type="button" class="attach-remove-btn" data-idx="${idx}" title="Remove">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                 </button>`;
      attachList.appendChild(li);
    });

    attachList.querySelectorAll(".attach-remove-saved").forEach((btn) => {
      btn.addEventListener("click", async function () {
        const idx = parseInt(this.dataset.idx);
        const att = savedAttachments[idx];
        if (!att) return;
        if (!confirm(`Remove "${att.name}" from this announcement?`)) return;
        savedAttachments.splice(idx, 1);
        renderAttachments();
        try {
          const fd = new FormData();
          fd.append("action", "removeFile");
          fd.append("file_id", att.id);
          fd.append("announcement_id", editingId);
          const res = await fetch(API, { method: "POST", body: fd });
          const json = await res.json();
          if (json.status !== "success") {
            showToast("Could not remove file from server.", "error");
            savedAttachments.splice(idx, 0, att);
            renderAttachments();
          }
        } catch {
          showToast("Network error removing file.", "error");
          savedAttachments.splice(idx, 0, att);
          renderAttachments();
        }
      });
    });

    attachList
      .querySelectorAll(".ann-attach-item--new .attach-remove-btn")
      .forEach((btn) => {
        btn.addEventListener("click", function () {
          attachments.splice(parseInt(this.dataset.idx), 1);
          renderAttachments();
        });
      });
  }

  attachFileInput?.addEventListener("change", function () {
    Array.from(this.files).forEach((file) =>
      attachments.push({
        name: file.name,
        size: formatSize(file.size),
        type: getFileType(file.name),
        file,
      })
    );
    this.value = "";
    renderAttachments();
  });

  if (attachDropZone) {
    attachDropZone.addEventListener("dragover", (e) => {
      e.preventDefault();
      attachDropZone.style.borderColor = "var(--op-accent)";
    });
    attachDropZone.addEventListener("dragleave", () => {
      attachDropZone.style.borderColor = "";
    });
    attachDropZone.addEventListener("drop", (e) => {
      e.preventDefault();
      attachDropZone.style.borderColor = "";
      Array.from(e.dataTransfer.files).forEach((file) =>
        attachments.push({
          name: file.name,
          size: formatSize(file.size),
          type: getFileType(file.name),
          file,
        })
      );
      renderAttachments();
    });
  }

  // Publish date default
  const publishDateInput = document.getElementById("ann-publish-date");
  if (publishDateInput) {
    publishDateInput.value = new Date().toISOString().split("T")[0];
    const previewDate = document.getElementById("preview-date");
    if (previewDate) {
      previewDate.textContent = new Date().toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
    }
    publishDateInput.addEventListener("change", function () {
      if (previewDate && this.value) {
        previewDate.textContent = new Date(
          this.value + "T00:00:00"
        ).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
        });
      }
    });
  }

  //  TEXT EDITOR TOOLS FUNCTION
  (function initRichEditor() {
    const toolbar = document.getElementById("ann-toolbar");
    const editor = document.getElementById("ann-body");
    if (!toolbar || !editor) return;

    let savedRange = null;

    function saveSelection() {
      const sel = window.getSelection();
      if (sel && sel.rangeCount > 0)
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    function restoreSelection() {
      if (!savedRange) return;
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(savedRange);
    }
    function execCmd(cmd, value = null) {
      editor.focus();
      document.execCommand(cmd, false, value);
      updateActiveStates();
      editor.dispatchEvent(new Event("input", { bubbles: true }));
    }
    function updateActiveStates() {
      toolbar.querySelectorAll(".toolbar-btn[data-cmd]").forEach((btn) => {
        const cmd = btn.dataset.cmd;
        try {
          btn.classList.toggle(
            "toolbar-btn--active",
            document.queryCommandState(cmd)
          );
        } catch (e) {}
      });
    }

    toolbar.addEventListener("mousedown", (e) => {
      const btn = e.target.closest(".toolbar-btn[data-cmd]");
      if (!btn) return;
      e.preventDefault();
      const cmd = btn.dataset.cmd;
      if (cmd === "createLink") {
        saveSelection();
        const url = prompt("Enter URL (include https://):", "https://");
        if (url && url !== "https://") {
          restoreSelection();
          execCmd("createLink", url);
          editor.querySelectorAll("a").forEach((a) => {
            a.target = "_blank";
            a.rel = "noopener noreferrer";
          });
        }
        return;
      }
      execCmd(cmd);
    });

    editor.addEventListener("keyup", updateActiveStates);
    editor.addEventListener("mouseup", updateActiveStates);
    editor.addEventListener("focus", updateActiveStates);

    function togglePlaceholder() {
      editor.classList.toggle(
        "ann-richtext--empty",
        editor.innerText.trim() === ""
      );
    }
    editor.addEventListener("input", togglePlaceholder);
    editor.addEventListener("focus", togglePlaceholder);
    editor.addEventListener("blur", togglePlaceholder);
    togglePlaceholder();

    editor.addEventListener("keydown", (e) => {
      if (e.key === "Tab") {
        e.preventDefault();
        document.execCommand("insertHTML", false, "&nbsp;&nbsp;&nbsp;&nbsp;");
      }
    });
  })();

  //  TOAST
  function showToast(message, type = "success") {
    let toast = document.getElementById("ann-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "ann-toast";
      toast.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.75rem 1.25rem;border-radius:.5rem;font-size:.875rem;font-weight:500;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:opacity .3s;pointer-events:none;`;
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.background = type === "success" ? "#10b981" : "#ef4444";
    toast.style.opacity = "1";
    clearTimeout(toast._t);
    toast._t = setTimeout(() => {
      toast.style.opacity = "0";
    }, 3500);
  }

  //  UTILITIES
  function toggleCheck(el, done) {
    if (!el) return;
    el.classList.toggle("is-done", done);
    const empty = el.querySelector(".check-empty");
    const checkDone = el.querySelector(".check-done");
    if (empty) empty.style.display = done ? "none" : "";
    if (checkDone) checkDone.style.display = done ? "" : "none";
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function formatDate(dateStr) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }

  function catPillHtml(cat) {
    return `<span class="ann-cat-pill cat-${cat}">${cat.charAt(0).toUpperCase() + cat.slice(1)}</span>`;
  }

  function thumbBg(cat) {
    return (
      {
        event: "#d1fae5",
        program: "#dbeafe",
        meeting: "#ede9fe",
        notice: "#fef3c7",
        urgent: "#fee2e2",
      }[cat] || "#f1f5f9"
    );
  }

  function thumbIcon(cat, color) {
    const c =
      color ||
      {
        event: "#059669",
        program: "#2563eb",
        meeting: "#7c3aed",
        notice: "#d97706",
        urgent: "#dc2626",
      }[cat] ||
      "#64748b";
    return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="${c}"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>`;
  }

  function debounce(fn, delay) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  //  INITIAL LOAD
  loadList();
  renderAttachments();
});