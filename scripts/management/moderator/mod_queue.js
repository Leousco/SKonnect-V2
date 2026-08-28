/* mod_queue.js — SKonnect Moderation Queue */

document.addEventListener("DOMContentLoaded", function () {
  const list = document.getElementById("mq-list");
  const emptyState = document.getElementById("mq-empty");
  const shownCount = document.getElementById("mq-shown");
  const searchInput = document.getElementById("mq-search");
  const sortSelect = document.getElementById("mq-sort");

  const ITEMS_PER_PAGE = 10;
  let currentPage = 1;

  // Active filters
  let activeCategory = "all";
  let activeStatus = "all";

  /* ── FILTER BUTTONS ──────────────────────────────────── */

  document.querySelectorAll(".mq-filter-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const type = this.dataset.filterType;

      // Deactivate siblings of same type
      document
        .querySelectorAll(`.mq-filter-btn[data-filter-type="${type}"]`)
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      if (type === "category") activeCategory = this.dataset.filter;
      if (type === "status") activeStatus = this.dataset.filter;

      currentPage = 1;
      applyFilters();
    });
  });

  /* ── SEARCH & SORT ───────────────────────────────────── */

  searchInput?.addEventListener(
    "input",
    debounce(() => {
      currentPage = 1;
      applyFilters();
    }, 250)
  );

  sortSelect?.addEventListener("change", () => {
    currentPage = 1;
    sortItems();
    applyFilters();
  });

  /* ── SORT ITEMS IN DOM ───────────────────────────────── */

  function sortItems() {
    const order = sortSelect?.value || "newest";
    const items = Array.from(list.querySelectorAll(".mq-item"));

    items.sort((a, b) => {
      const da = new Date(a.dataset.date || "2000-01-01");
      const db = new Date(b.dataset.date || "2000-01-01");
      return order === "oldest" ? da - db : db - da;
    });

    items.forEach((item) => list.appendChild(item));
  }

  /* ── APPLY FILTERS + PAGINATION ─────────────────────── */

  function applyFilters() {
    const query = (searchInput?.value || "").toLowerCase().trim();
    const items = Array.from(list.querySelectorAll(".mq-item"));

    // First pass: mark each item as visible or not
    const matching = [];
    items.forEach((item) => {
      const cat = item.dataset.category || "";
      const status = item.dataset.status || "";
      const text = item.innerText.toLowerCase();

      const matchCat = activeCategory === "all" || cat === activeCategory;
      const matchStatus = activeStatus === "all" || status === activeStatus;
      const matchSearch = !query || text.includes(query);

      if (matchCat && matchStatus && matchSearch) {
        matching.push(item);
      } else {
        item.style.display = "none";
      }
    });

    // Pagination
    const totalPages = Math.max(1, Math.ceil(matching.length / ITEMS_PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    matching.forEach((item, i) => {
      item.style.display =
        i >= start && i < start + ITEMS_PER_PAGE ? "" : "none";
    });

    // Empty state
    if (emptyState)
      emptyState.style.display = matching.length === 0 ? "flex" : "none";
    if (shownCount) shownCount.textContent = matching.length;

    renderPagination(totalPages);
  }

  /* ── PAGINATION ──────────────────────────────────────── */

  function renderPagination(totalPages) {
    const numbersEl = document.getElementById("mq-page-numbers");
    const prevBtn = document.getElementById("mq-prev");
    const nextBtn = document.getElementById("mq-next");

    if (!numbersEl) return;
    numbersEl.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = "mq-page-num" + (i === currentPage ? " active" : "");
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        applyFilters();
      });
      numbersEl.appendChild(btn);
    }

    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage === totalPages;
  }

  document.getElementById("mq-prev")?.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      applyFilters();
    }
  });

  document.getElementById("mq-next")?.addEventListener("click", () => {
    const items = Array.from(list.querySelectorAll(".mq-item"));
    const query = (searchInput?.value || "").toLowerCase().trim();
    const matching = items.filter((item) => {
      const cat = item.dataset.category || "";
      const status = item.dataset.status || "";
      const text = item.innerText.toLowerCase();
      return (
        (activeCategory === "all" || cat === activeCategory) &&
        (activeStatus === "all" || status === activeStatus) &&
        (!query || text.includes(query))
      );
    });
    const totalPages = Math.max(1, Math.ceil(matching.length / ITEMS_PER_PAGE));
    if (currentPage < totalPages) {
      currentPage++;
      applyFilters();
    }
  });

  /* ── CONFIRM MODAL ───────────────────────────────────────── */

  const overlay = document.getElementById("mq-confirm-overlay");
  const confirmIcon = document.getElementById("mq-confirm-icon");
  const confirmTitle = document.getElementById("mq-confirm-title");
  const confirmBody = document.getElementById("mq-confirm-body");
  const confirmOk = document.getElementById("mq-confirm-ok");
  const confirmCxl = document.getElementById("mq-confirm-cancel");

  let pendingAction = null;

  function openConfirm({ icon, title, body, okLabel, onConfirm }) {
    confirmIcon.textContent = icon || "⚠️";
    confirmTitle.textContent = title || "Confirm Action";
    confirmBody.textContent = body || "Are you sure?";
    confirmOk.textContent = okLabel || "Confirm";
    pendingAction = onConfirm;
    overlay.style.display = "flex";
  }

  function closeConfirm() {
    overlay.style.display = "none";
    pendingAction = null;
  }

  confirmOk?.addEventListener("click", () => {
    if (typeof pendingAction === "function") pendingAction();
    closeConfirm();
  });
  confirmCxl?.addEventListener("click", closeConfirm);
  overlay?.addEventListener("click", (e) => {
    if (e.target === overlay) closeConfirm();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeConfirm();
  });

  /* ── LOADING TOAST ───────────────────────────────────────── */

  let loadingToastEl = null;

  function showLoadingToast(msg = "Processing…") {
    if (!loadingToastEl) {
      loadingToastEl = document.createElement("div");
      loadingToastEl.className = "mq-toast-loading";
      document.body.appendChild(loadingToastEl);
    }
    loadingToastEl.innerHTML = `
      <span class="mq-toast-loading-spinner"></span>
      <span class="mq-toast-loading-msg">${msg}</span>
    `;
    loadingToastEl.classList.add("mq-toast-loading--show");
  }

  function hideLoadingToast() {
    loadingToastEl?.classList.remove("mq-toast-loading--show");
  }

  /* ── SLIDE-IN PANEL ──────────────────────────────────────── */

  const mqPanel = document.getElementById("mq-thread-panel");
  const mqBackdrop = document.getElementById("mq-panel-backdrop");
  const mqPanelClose = document.getElementById("mq-panel-close");

  function openMqPanel(threadId, reportId) {
    mqPanel.classList.add("open");
    mqBackdrop.classList.add("open");
    document.body.classList.add("mod-panel-open");
    loadMqPanel(threadId, reportId);
  }

  function closeMqPanel() {
    mqPanel.classList.remove("open");
    mqBackdrop.classList.remove("open");
    document.body.classList.remove("mod-panel-open");
  }

  mqPanelClose?.addEventListener("click", closeMqPanel);
  mqBackdrop?.addEventListener("click", closeMqPanel);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeConfirm();
      closeMqPanel();
    }
  });

  /* ── ACTION BUTTONS ──────────────────────────────────────── */

  list?.addEventListener("click", function (e) {
    // Check for the View Thread panel button first (no data-action)
    const viewBtn = e.target.closest(".mq-btn-view-panel");
    if (viewBtn) {
      e.preventDefault();
      const threadId = viewBtn.dataset.threadId;
      const reportId = viewBtn.dataset.reportId;
      if (threadId) openMqPanel(threadId, reportId);
      return;
    }

    const btn = e.target.closest(".mq-action-btn[data-action]");
    if (!btn) return;
    e.preventDefault();

    const action = btn.dataset.action;
    const reportId = btn.dataset.reportId;
    const item = btn.closest(".mq-item");

    if (action === "dismiss") {
      const subject =
        btn.dataset.threadSubject ||
        item.querySelector(".mq-item-title")?.textContent?.trim() ||
        "this thread";
      openConfirm({
        icon: "🗑️",
        title: "Dismiss Report",
        body: `Dismiss the report for "${subject}"? The thread will remain visible and the author will not be notified.`,
        okLabel: "Dismiss",
        onConfirm: () => sendAction(reportId, "dismiss", btn, item),
      });
    }

    if (action === "resolve") {
      const subject =
        btn.dataset.threadSubject ||
        item.querySelector(".mq-item-title")?.textContent?.trim() ||
        "this thread";
      const category = btn.dataset.category || "a violation";
      openConfirm({
        icon: "🛡️",
        title: "Resolve & Notify",
        body: `This will hide "${subject}" from the resident feed and send an email notification to the author citing the "${category}" report. This action cannot be undone.`,
        okLabel: "Resolve & Notify",
        onConfirm: () => sendAction(reportId, "resolve", btn, item),
      });
    }
  });

  /* ── AJAX SEND ───────────────────────────────────────────── */

  async function sendAction(reportId, action, triggerBtn, itemEl) {
    itemEl.querySelectorAll(".mq-action-btn[data-action]").forEach((b) => {
      b.disabled = true;
      b.style.opacity = "0.6";
    });

    const loadingMsg =
      action === "resolve"
        ? "Resolving report & notifying author…"
        : "Processing…";
    showLoadingToast(loadingMsg);

    const fd = new FormData();
    fd.append("report_id", reportId);
    fd.append("action", action);

    try {
      const res = await fetch(
        "../../../backend/controllers/ModQueueController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();

      hideLoadingToast();

      if (data.status === "success") {
        showToast(data.message, "success");
        updateItemUI(itemEl, action, data);
      } else {
        showToast(data.message || "Action failed.", "error");
        itemEl.querySelectorAll(".mq-action-btn[data-action]").forEach((b) => {
          b.disabled = false;
          b.style.opacity = "";
        });
      }
    } catch (err) {
      hideLoadingToast();
      showToast("Network error. Please try again.", "error");
      itemEl.querySelectorAll(".mq-action-btn[data-action]").forEach((b) => {
        b.disabled = false;
        b.style.opacity = "";
      });
    }
  }

  /* ── UPDATE ITEM UI AFTER ACTION ─────────────────────── */

  function updateItemUI(itemEl, action, data) {
    const newStatus =
      data.report_status || (action === "dismiss" ? "dismissed" : "reviewed");

    // Update data-status for filter to work
    itemEl.dataset.status = newStatus;

    // Swap the status badge text + class
    const statusBadge = itemEl.querySelector(".mq-report-status-badge");
    if (statusBadge) {
      statusBadge.className = `mq-report-status-badge status-${newStatus}`;
      statusBadge.textContent =
        newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    }

    // Replace action buttons with an actioned label
    const actionsEl = itemEl.querySelector(".mq-item-actions");
    if (actionsEl) {
      // Keep the View button, remove action buttons, add label
      const viewBtn = actionsEl.querySelector(".mq-btn-view");
      actionsEl.innerHTML = "";
      if (viewBtn) actionsEl.appendChild(viewBtn);

      const label = document.createElement("span");
      label.className = "mq-actioned-label";
      label.textContent =
        newStatus === "dismissed"
          ? "Dismissed — no action taken"
          : "Reviewed — action taken";
      actionsEl.appendChild(label);
    }

    // If resolve action succeeded, show the Hidden tag
    if (action === "resolve" && data.thread_hidden) {
      const titleRow = itemEl.querySelector(".mq-item-title-row");
      if (titleRow && !titleRow.querySelector(".mq-hidden-tag")) {
        const tag = document.createElement("span");
        tag.className = "mq-hidden-tag";
        tag.textContent = "Hidden";
        titleRow.insertBefore(
          tag,
          titleRow.querySelector(".mq-report-status-badge")
        );
      }
    }

    itemEl.style.transition = "background 0.4s ease";
    itemEl.style.background = action === "dismiss" ? "#f0fdfa" : "#fef9c3";
    setTimeout(() => {
      itemEl.style.background = "";
    }, 1800);

    applyFilters();
  }

  /* ── TOAST ───────────────────────────────────────────── */

  function showToast(msg, type = "success") {
    const toast = document.getElementById("mq-toast");
    if (!toast) return;
    toast.textContent = msg;
    toast.className = `mq-toast mq-toast-${type} show`;
    setTimeout(() => {
      toast.className = "mq-toast";
    }, 3200);
  }

  /* ── UTILITY ─────────────────────────────────────────── */

  function debounce(fn, delay) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  /* ── LOAD PANEL ──────────────────────────────────────────── */

  async function loadMqPanel(threadId, reportId) {
    document.getElementById("mq-panel-loading").style.display = "flex";
    document.getElementById("mq-panel-content").style.display = "none";

    try {
      const res = await fetch(
        `../../../backend/controllers/ModGetThreadController.php?id=${threadId}`
      );
      const data = await res.json();

      if (data.status !== "success") {
        showToast("Could not load thread.", "error");
        closeMqPanel();
        return;
      }

      renderMqPanel(data.thread, data.images, data.comments, reportId);
    } catch {
      showToast("Network error loading thread.", "error");
      closeMqPanel();
    }
  }

  /* ── RENDER PANEL ────────────────────────────────────────── */

  const mqCatLabels = {
    inquiry: "Inquiry",
    complaint: "Complaint",
    suggestion: "Suggestion",
    event_question: "Event",
    other: "Other",
  };

  const mqReportCatLabels = {
    harassment: "Harassment",
    spam: "Spam",
    inappropriate: "Inappropriate",
    misinformation: "Misinformation",
  };

  function mqEscHtml(str) {
    const d = document.createElement("div");
    d.appendChild(document.createTextNode(str || ""));
    return d.innerHTML;
  }

  function mqNl2br(str) {
    return str.replace(/\n/g, "<br>");
  }

  function mqFormatDate(dateStr) {
    const d = new Date(dateStr);
    return (
      d.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      }) +
      " · " +
      d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" })
    );
  }

  function renderMqPanel(thread, images, comments, reportId) {
    // ── Badges in header ─────────────────────────────────────
    const catKey = thread.category;
    document.getElementById("mq-panel-badges").innerHTML = `
      <span class="mod-cat-badge category-${mqEscHtml(catKey)}">${mqEscHtml(mqCatLabels[catKey] || "Other")}</span>
      <span class="mod-status-badge status-${mqEscHtml(thread.status)}">${mqEscHtml(thread.status.charAt(0).toUpperCase() + thread.status.slice(1))}</span>
      ${+thread.is_removed ? `<span class="mod-remove-indicator"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>Hidden</span>` : ""}
    `;

    // ── Title ─────────────────────────────────────────────────
    document.getElementById("mq-panel-title").textContent = thread.subject;

    // ── Meta ──────────────────────────────────────────────────
    const initials = thread.author_name.substring(0, 2).toUpperCase();
    document.getElementById("mq-panel-meta").innerHTML = `
      <div class="mod-panel-author">
        <span class="mod-panel-avatar">${initials}</span>
        <div>
          <span class="mod-panel-author-name">${mqEscHtml(thread.author_name)}</span>
          <span class="mod-panel-date">${mqFormatDate(thread.created_at)}</span>
        </div>
      </div>
      <div class="mod-panel-counts">
        <span>💬 ${thread.comment_count} ${+thread.comment_count === 1 ? "comment" : "comments"}</span>
        <span>🤝 ${thread.support_count} ${+thread.support_count === 1 ? "support" : "supports"}</span>
      </div>
    `;

    // ── Body text ─────────────────────────────────────────────
    document.getElementById("mq-panel-body-text").innerHTML = mqNl2br(mqEscHtml(thread.message));

    // ── Images ────────────────────────────────────────────────
    const imagesEl = document.getElementById("mq-panel-images");
    if (images && images.length) {
      imagesEl.innerHTML = images
        .map(
          (img) =>
            `<div class="mod-panel-image-item" data-src="../../../${mqEscHtml(img.file_path)}">
              <img src="../../../${mqEscHtml(img.file_path)}" alt="${mqEscHtml(img.file_name)}" loading="lazy">
            </div>`
        )
        .join("");
      imagesEl.style.display = "";
      imagesEl.querySelectorAll(".mod-panel-image-item").forEach((item) => {
        item.addEventListener("click", () => {
          const lb = document.getElementById("mq-lightbox");
          const lbImg = document.getElementById("mq-lightbox-img");
          if (lb && lbImg) {
            lbImg.src = item.dataset.src;
            lb.style.display = "flex";
          }
        });
      });
    } else {
      imagesEl.innerHTML = "";
      imagesEl.style.display = "none";
    }

    // ── Report context banner ─────────────────────────────────
    // Find the matching report row to get reporter info + note
    const reportItem = reportId
      ? document.getElementById(`mq-item-${reportId}`)
      : null;

    const reportContextEl = document.getElementById("mq-panel-report-context");
    if (reportItem) {
      const metaEl = reportItem.querySelector(".mq-item-meta");
      const noteEl = reportItem.querySelector(".mq-item-note");
      const catTag = reportItem.querySelector(".mq-category-tag");
      const reportCat = reportItem.dataset.category || "";
      reportContextEl.innerHTML = `
        <div class="mq-panel-report-banner">
          <span class="mq-panel-report-banner-label">⚠ Report</span>
          <span class="mq-panel-report-cat">${mqEscHtml(mqReportCatLabels[reportCat] || reportCat)}</span>
          <span class="mq-panel-report-meta">${metaEl ? mqEscHtml(metaEl.innerText.trim()) : ""}</span>
          ${noteEl ? `<div class="mq-panel-report-note"><span class="mq-note-label">Reporter's note:</span> ${mqEscHtml(noteEl.innerText.replace("Reporter's note:", "").trim())}</div>` : ""}
        </div>
      `;
      reportContextEl.style.display = "";
    } else {
      reportContextEl.style.display = "none";
    }

    // ── Resolve & Notify button ───────────────────────────────
    const resolveBtn = document.getElementById("mq-panel-resolve-btn");
    const currentReportStatus = reportItem ? reportItem.dataset.status : "pending";

    resolveBtn.dataset.reportId = reportId || "";
    resolveBtn.dataset.threadId = thread.id;
    resolveBtn.dataset.threadSubject = thread.subject;
    resolveBtn.dataset.category = thread.category;

    // If already actioned, disable the button in the panel
    if (currentReportStatus !== "pending") {
      resolveBtn.disabled = true;
      resolveBtn.classList.add("mq-panel-resolve-btn--actioned");
      resolveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg> ${currentReportStatus === "dismissed" ? "Report Dismissed" : "Report Resolved"}`;
    } else {
      resolveBtn.disabled = false;
      resolveBtn.classList.remove("mq-panel-resolve-btn--actioned");
      resolveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg> Resolve &amp; Notify`;
    }

    // Wire click — mirrors the row-level resolve button behaviour
    resolveBtn.onclick = () => {
      const rId = resolveBtn.dataset.reportId;
      const subject = resolveBtn.dataset.threadSubject || "this thread";
      const category = resolveBtn.dataset.category || "a violation";
      const rowItem = rId ? document.getElementById(`mq-item-${rId}`) : null;
      const rowResolveBtn = rowItem
        ? rowItem.querySelector('.mq-action-btn[data-action="resolve"]')
        : null;

      openConfirm({
        icon: "🛡️",
        title: "Resolve & Notify",
        body: `This will hide "${subject}" from the resident feed and send an email notification to the author citing the "${category}" report. This action cannot be undone.`,
        okLabel: "Resolve & Notify",
        onConfirm: () => {
          // Reuse the existing sendAction function — same as clicking from the row
          if (rowItem) {
            sendAction(rId, "resolve", rowResolveBtn || resolveBtn, rowItem);
          } else {
            sendAction(rId, "resolve", resolveBtn, resolveBtn);
          }
          // Disable the panel button immediately
          resolveBtn.disabled = true;
          resolveBtn.classList.add("mq-panel-resolve-btn--actioned");
          resolveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg> Report Resolved`;
        },
      });
    };

    // ── Comments (read-only) ──────────────────────────────────
    const commentCount = document.getElementById("mq-panel-comments-count");
    const commentList = document.getElementById("mq-panel-comment-list");
    commentCount.textContent = comments ? comments.length : 0;

    if (!comments || comments.length === 0) {
      commentList.innerHTML = `<div class="mod-panel-no-comments">No comments yet.</div>`;
    } else {
      comments.sort((a, b) => +b.is_mod_comment - +a.is_mod_comment);
      commentList.innerHTML = comments
        .map((c) => {
          const ci = c.author_name.substring(0, 2).toUpperCase();
          const modBadge = +c.is_mod_comment
            ? `<span class="mod-comment-badge">SK Official</span>`
            : "";
          const repliesHtml =
            c.replies && c.replies.length
              ? c.replies
                  .map((r) => {
                    const ri = r.author_name.substring(0, 2).toUpperCase();
                    const rModBadge = +r.is_mod_comment
                      ? `<span class="mod-comment-badge">SK Official</span>`
                      : "";
                    return `
                    <div class="mod-panel-reply-item ${+r.is_mod_comment ? "mod-panel-comment-item--mod" : ""}">
                      <div class="mod-panel-comment-avatar mod-panel-reply-avatar">${ri}</div>
                      <div class="mod-panel-comment-body">
                        <div class="mod-panel-comment-header">
                          <span class="mod-panel-comment-author">${mqEscHtml(r.author_name)}</span>
                          ${rModBadge}
                          <span class="mod-panel-comment-date">${mqFormatDate(r.created_at)}</span>
                        </div>
                        <div class="mod-panel-comment-text">${mqNl2br(mqEscHtml(r.message))}</div>
                      </div>
                    </div>`;
                  })
                  .join("")
              : "";

          return `
          <div class="mod-panel-comment-item ${+c.is_mod_comment ? "mod-panel-comment-item--mod" : ""}" id="mq-panel-comment-${c.id}">
            <div class="mod-panel-comment-avatar">${ci}</div>
            <div class="mod-panel-comment-body">
              <div class="mod-panel-comment-header">
                <span class="mod-panel-comment-author">${mqEscHtml(c.author_name)}</span>
                ${modBadge}
                <span class="mod-panel-comment-date">${mqFormatDate(c.created_at)}</span>
              </div>
              <div class="mod-panel-comment-text">${mqNl2br(mqEscHtml(c.message))}</div>
              ${repliesHtml ? `<div class="mod-panel-replies">${repliesHtml}</div>` : ""}
            </div>
          </div>`;
        })
        .join("");
    }

    // ── Show content ──────────────────────────────────────────
    document.getElementById("mq-panel-loading").style.display = "none";
    document.getElementById("mq-panel-content").style.display = "";
  }

  // Lightbox close
  const mqLightbox = document.getElementById("mq-lightbox");
  document.getElementById("mq-lightbox-close")?.addEventListener("click", () => {
    if (mqLightbox) mqLightbox.style.display = "none";
  });
  mqLightbox?.addEventListener("click", (e) => {
    if (e.target === mqLightbox) mqLightbox.style.display = "none";
  });

  // Init: sort then filter
  sortItems();
  applyFilters();
});