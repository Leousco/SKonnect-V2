/* mod_sanctions.js — SKonnect Moderator · User Sanctions
   Updates:
   1. "View Comment" opens a slide-in panel with the full thread;
      the reported comment/reply is highlighted and smooth-scrolled into view.
   2. Dismiss & Issue Sanction update the UI live (no page reload needed).
   3. Reason field in the sanction modal is now optional.
*/

document.addEventListener("DOMContentLoaded", function () {
  /* ── HELPERS ────────────────────────────────────────────── */

  function escHtml(str) {
    const d = document.createElement("div");
    d.appendChild(document.createTextNode(str || ""));
    return d.innerHTML;
  }

  function nl2br(str) {
    return (str || "").replace(/\n/g, "<br>");
  }

  function formatDate(dateStr) {
    if (!dateStr) return "—";
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

  function debounce(fn, delay) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  /* ── TOAST ──────────────────────────────────────────────── */

  function showToast(msg, type = "success") {
    const toast = document.getElementById("ms-toast");
    if (!toast) return;
    toast.textContent = msg;
    toast.className = `ms-toast toast-${type} show`;
    clearTimeout(toast._t);
    toast._t = setTimeout(() => {
      toast.className = "ms-toast";
    }, 3500);
  }

  /* ── FORM COLLAPSE ─────────────────────────────────────── */

  const formToggle = document.getElementById("ms-form-toggle");
  const formBody = document.getElementById("ms-form-body");

  if (formToggle && formBody) {
    formToggle.addEventListener("click", function () {
      const collapsed = formBody.classList.toggle("collapsed");
      this.innerHTML = collapsed
        ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Expand`
        : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg> Collapse`;
    });
  }

  /* ── DIRECT SANCTION FORM — Clear ──────────────────────── */

  document.getElementById("ms-cancel")?.addEventListener("click", () => {
    ["ms-user-id", "ms-reason"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    const lvl = document.getElementById("ms-level");
    if (lvl) lvl.value = "1";
  });

  /* ── DIRECT SANCTION FORM — Submit ─────────────────────── */

  document
    .getElementById("ms-submit")
    ?.addEventListener("click", async function () {
      const uid = parseInt(document.getElementById("ms-user-id")?.value || "0");
      const reason = document.getElementById("ms-reason")?.value.trim() || "";
      const level = parseInt(document.getElementById("ms-level")?.value || "1");

      if (!uid || uid < 1) {
        showToast("Enter a valid User ID.", "error");
        return;
      }

      const labels = { 1: "Warning", 2: "7-Day Ban", 3: "Permanent Ban" };
      if (
        !confirm(
          `Issue Level ${level} (${labels[level]}) sanction to user ID ${uid}?`
        )
      )
        return;

      this.disabled = true;
      const fd = new FormData();
      fd.append("user_id", uid);
      fd.append("level", level);
      if (reason) fd.append("reason", reason);

      try {
        const res = await fetch(
          "../../../backend/controllers/IssueSanctionController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();
        if (data.status === "success") {
          showToast(data.message, "success");
          document.getElementById("ms-user-id").value = "";
          document.getElementById("ms-reason").value = "";
          document.getElementById("ms-level").value = "1";
        } else {
          showToast(data.message || "Failed to issue sanction.", "error");
        }
      } catch {
        showToast("Network error. Try again.", "error");
      } finally {
        this.disabled = false;
      }
    });

  /* ── TABS ───────────────────────────────────────────────── */

  const tabBtns = document.querySelectorAll(".ms-tab-btn");
  const tabPanels = document.querySelectorAll(".ms-tab-panel");

  // Mark the initially-visible tab on page load so applyFilters can find it reliably
  tabPanels.forEach((p) => {
    if (!p.style.display || p.style.display === "") {
      p.classList.add("ms-tab-active");
    }
  });

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      tabBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      const target = this.dataset.tab;
      tabPanels.forEach((p) => {
        const isActive = p.id === `tab-${target}`;
        p.style.display = isActive ? "" : "none";
        p.classList.toggle("ms-tab-active", isActive);
      });
      applyFilters();
    });
  });

  /* ── FILTER + SEARCH ────────────────────────────────────── */

  const filterBtns = document.querySelectorAll(".ms-filter-btn");
  const searchInput = document.getElementById("ms-search");
  let activeFilter = "all";

  filterBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      filterBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      activeFilter = this.dataset.filter;
      applyFilters();
    });
  });

  searchInput?.addEventListener("input", debounce(applyFilters, 200));

  function applyFilters() {
    const query = (searchInput?.value || "").toLowerCase().trim();
    const activePanel = document.querySelector(".ms-tab-panel.ms-tab-active");
    if (!activePanel) return;

    const items = activePanel.querySelectorAll(".ms-item");
    const emptyEl = activePanel.querySelector(".ms-empty[id]");
    let visible = 0;

    items.forEach((item) => {
      const matchCat =
        activeFilter === "all" ||
        (item.dataset.category || "") === activeFilter;
      const matchSearch =
        !query || item.innerText.toLowerCase().includes(query);
      const show = matchCat && matchSearch;
      item.style.display = show ? "" : "none";
      if (show) visible++;
    });

    if (emptyEl)
      emptyEl.style.display =
        visible === 0 && items.length > 0 ? "flex" : "none";
  }

  /* ── TAB BADGE COUNTER ──────────────────────────────────── */

  function updateTabBadge(tabId, delta) {
    const btn = document.querySelector(`.ms-tab-btn[data-tab="${tabId}"]`);
    if (!btn) return;
    let badge = btn.querySelector(".ms-tab-badge");
    if (!badge && delta > 0) {
      // Tab had no badge (was empty) — create one
      badge = document.createElement("span");
      const suffixMap = {
        reviewed: "ms-tab-badge--reviewed",
        dismissed: "ms-tab-badge--dismissed",
      };
      badge.className = ["ms-tab-badge", suffixMap[tabId] || ""]
        .filter(Boolean)
        .join(" ");
      btn.appendChild(badge);
    }
    if (!badge) return;
    const next = Math.max(0, (parseInt(badge.textContent) || 0) + delta);
    badge.textContent = next;
    badge.style.display = next <= 0 ? "none" : "";
  }

  /* ── ANIMATE ITEM OUT ───────────────────────────────────── */

  function animateOut(itemEl, onDone) {
    itemEl.style.transition = "opacity 0.28s ease, transform 0.28s ease";
    itemEl.style.opacity = "0";
    itemEl.style.transform = "translateX(28px)";
    setTimeout(() => {
      // collapse height smoothly
      const h = itemEl.offsetHeight;
      itemEl.style.transition +=
        ", max-height 0.3s ease, padding 0.3s ease, margin 0.3s ease, border 0.3s ease";
      itemEl.style.maxHeight = h + "px";
      itemEl.style.overflow = "hidden";
      requestAnimationFrame(() => {
        itemEl.style.maxHeight = "0";
        itemEl.style.paddingTop = "0";
        itemEl.style.paddingBottom = "0";
        itemEl.style.marginTop = "0";
        itemEl.style.marginBottom = "0";
        itemEl.style.borderTopWidth = "0";
        itemEl.style.borderBottomWidth = "0";
      });
      setTimeout(() => {
        itemEl.remove();
        if (onDone) onDone();
      }, 320);
    }, 290);
  }

  /* ── REPORT ACTION FETCH ────────────────────────────────── */

  async function doReportAction(reportId, action, itemEl) {
    const fd = new FormData();
    fd.append("report_id", reportId);
    fd.append("action", action);
    try {
      const res = await fetch(
        "../../../backend/controllers/CommentReportActionController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();
      if (data.status === "success") {
        showToast(data.message, "success");
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(data.message || "Action failed.", "error");
      }
    } catch {
      showToast("Network error. Try again.", "error");
    }
  }

  /**
   * Takes a cloned pending card, strips its action buttons, stamps a status tag,
   * and prepends it into the target tab list with a slide-in animation.
   */
  function moveCardToTab(cardEl, tabId) {
    // Remove action buttons — they don't belong in reviewed/dismissed tabs
    cardEl.querySelectorAll(".ms-item-actions").forEach((el) => el.remove());

    // Add the correct modifier class and status tag
    const statusMap = {
      dismissed: {
        cls: "ms-item--dismissed",
        label: "Dismissed",
        tagCls: "status-dismissed",
      },
      reviewed: {
        cls: "ms-item--reviewed",
        label: "Reviewed",
        tagCls: "status-reviewed",
      },
    };
    const s = statusMap[tabId];
    if (!s) return;

    cardEl.classList.add(s.cls);

    // Update sanction level badge for dismissed
    if (tabId === "dismissed") {
      const lvlBadge = cardEl.querySelector(".ms-sanction-level");
      if (lvlBadge) {
        lvlBadge.textContent = "Done";
        lvlBadge.className = "ms-sanction-level level-dismissed";
      }
      const avatar = cardEl.querySelector(".ms-avatar");
      if (avatar) avatar.classList.add("ms-avatar--muted");
      const username = cardEl.querySelector(".ms-username");
      if (username) username.classList.add("ms-username--muted");
      const content = cardEl.querySelector(".ms-reported-content");
      if (content) content.classList.add("ms-reported-content--muted");
    }

    // Inject the status tag into the header user row if not already there
    const userRow = cardEl.querySelector(".ms-item-user");
    if (userRow && !userRow.querySelector(".ms-status-tag")) {
      const tag = document.createElement("span");
      tag.className = `ms-status-tag ${s.tagCls}`;
      tag.textContent = s.label;
      userRow.appendChild(tag);
    }

    // Find or create the list container in the destination tab
    const tabPanel = document.getElementById(`tab-${tabId}`);
    if (!tabPanel) return;

    // Show the destination tab panel if it's somehow hidden (shouldn't be, but guard it)
    // We do NOT switch the active tab — user stays on Pending; the card just moves in the background.

    let listEl = tabPanel.querySelector(`#ms-list-${tabId}`);
    if (!listEl) {
      // Tab was empty — remove the static "no items" empty state and build the list
      tabPanel.querySelectorAll(".ms-empty").forEach((el) => el.remove());
      listEl = document.createElement("div");
      listEl.className = "ms-list";
      listEl.id = `ms-list-${tabId}`;
      tabPanel.prepend(listEl);
    }

    // Slide-in animation: start invisible & shifted, then settle
    cardEl.style.opacity = "0";
    cardEl.style.transform = "translateX(-24px)";
    cardEl.style.transition = "none";
    listEl.prepend(cardEl);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        cardEl.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        cardEl.style.opacity = "1";
        cardEl.style.transform = "translateX(0)";
      });
    });

    // Update the badge count for the destination tab
    updateTabBadge(tabId, +1);
  }

  /* ── DELEGATED BUTTON CLICKS ────────────────────────────── */

  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".ms-action-btn");
    if (!btn) return;
    e.preventDefault();

    const item = btn.closest(".ms-item");
    const reportId = parseInt(
      btn.dataset.reportId || item?.dataset.reportId || 0
    );

    if (btn.classList.contains("ms-btn-view")) {
      if (!item) return;
      openCommentPanel({
        threadId: parseInt(item.dataset.threadId || 0),
        targetType: item.dataset.targetType || "comment",
        targetId: parseInt(item.dataset.targetId || 0),
      });
      return;
    }

    if (btn.classList.contains("ms-btn-dismiss")) {
      if (!item) return;
      const author =
        item.querySelector(".ms-username")?.textContent || "this user";
      if (
        !confirm(
          `Dismiss the report for ${author}? No sanction will be issued.`
        )
      )
        return;
      doReportAction(reportId, "dismiss", item);
      return;
    }

    if (btn.classList.contains("ms-btn-sanction")) {
      openSanctionModal({
        reportId: reportId,
        userId: parseInt(btn.dataset.userId || 0),
        author: btn.dataset.author || "Unknown",
        currentLevel: parseInt(btn.dataset.currentLevel || "0"),
        nextLevel: parseInt(btn.dataset.nextLevel || "1"),
      });
      return;
    }
  });

  /* ══════════════════════════════════════════════════════════
     SLIDE-IN COMMENT PANEL
  ══════════════════════════════════════════════════════════ */

  const panel = document.getElementById("ms-comment-panel");
  const backdrop = document.getElementById("ms-panel-backdrop");
  const pClose = document.getElementById("ms-panel-close");

  function openCommentPanel({ threadId, targetType, targetId }) {
    if (!panel || !threadId) {
      showToast("No thread linked to this report.", "error");
      return;
    }
    panel.classList.add("open");
    backdrop?.classList.add("open");
    document.body.classList.add("ms-panel-open");
    loadCommentPanel(threadId, targetType, targetId);
  }

  function closeCommentPanel() {
    panel?.classList.remove("open");
    backdrop?.classList.remove("open");
    document.body.classList.remove("ms-panel-open");
  }

  pClose?.addEventListener("click", closeCommentPanel);
  backdrop?.addEventListener("click", closeCommentPanel);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && panel?.classList.contains("open"))
      closeCommentPanel();
  });

  async function loadCommentPanel(threadId, targetType, targetId) {
    const loading = document.getElementById("ms-panel-loading");
    const content = document.getElementById("ms-panel-content");
    if (!loading || !content) return;

    loading.style.display = "flex";
    content.style.display = "none";

    try {
      const res = await fetch(
        `../../../backend/controllers/ModGetThreadController.php?id=${threadId}`
      );
      const data = await res.json();
      if (data.status !== "success") {
        showToast("Could not load thread.", "error");
        closeCommentPanel();
        return;
      }
      renderCommentPanel(
        data.thread,
        data.images,
        data.comments,
        targetType,
        targetId
      );
    } catch {
      showToast("Network error loading thread.", "error");
      closeCommentPanel();
    }
  }

  const CAT_LABELS = {
    inquiry: "Inquiry",
    complaint: "Complaint",
    suggestion: "Suggestion",
    event_question: "Event",
    other: "Other",
  };

  function renderCommentPanel(thread, images, comments, targetType, targetId) {
    const loading = document.getElementById("ms-panel-loading");
    const content = document.getElementById("ms-panel-content");

    // Badges
    const catKey = thread.category || "other";
    document.getElementById("ms-panel-badges").innerHTML = `
      <span class="ms-panel-cat-badge cat-${catKey}">${
      CAT_LABELS[catKey] || "Other"
    }</span>
      <span class="ms-panel-status-badge status-${thread.status}">
        ${thread.status.charAt(0).toUpperCase() + thread.status.slice(1)}
      </span>
      <span class="ms-panel-report-label">📋 Viewing Reported Content</span>
    `;

    // Title & meta
    document.getElementById("ms-panel-title").textContent = thread.subject;
    const initials = (thread.author_name || "?").substring(0, 2).toUpperCase();
    document.getElementById("ms-panel-meta").innerHTML = `
      <div class="ms-panel-author">
        <span class="ms-panel-avatar">${initials}</span>
        <div>
          <span class="ms-panel-author-name">${escHtml(
            thread.author_name
          )}</span>
          <span class="ms-panel-date">${formatDate(thread.created_at)}</span>
        </div>
      </div>
      <div class="ms-panel-counts">
        <span>💬 ${thread.comment_count} ${
      +thread.comment_count === 1 ? "comment" : "comments"
    }</span>
        <span>🤝 ${thread.support_count} ${
      +thread.support_count === 1 ? "support" : "supports"
    }</span>
      </div>
    `;

    document.getElementById("ms-panel-body-text").innerHTML = nl2br(
      escHtml(thread.message)
    );

    // Images
    const imagesEl = document.getElementById("ms-panel-images");
    if (images && images.length) {
      imagesEl.innerHTML = images
        .map(
          (img) =>
            `<div class="ms-panel-image-item">
           <img src="../../../${escHtml(img.file_path)}" alt="${escHtml(
              img.file_name
            )}" loading="lazy">
         </div>`
        )
        .join("");
      imagesEl.style.display = "";
    } else {
      imagesEl.innerHTML = "";
      imagesEl.style.display = "none";
    }

    // Comments
    const listEl = document.getElementById("ms-panel-comment-list");
    const cntEl = document.getElementById("ms-panel-comments-count");
    cntEl.textContent = comments ? comments.length : 0;

    if (!comments || comments.length === 0) {
      listEl.innerHTML = `<div class="ms-panel-no-comments">No comments on this thread.</div>`;
    } else {
      listEl.innerHTML = "";

      comments.forEach((c) => {
        const isHit =
          targetType === "comment" && parseInt(c.id) === parseInt(targetId);
        const ci = (c.author_name || "?").substring(0, 2).toUpperCase();
        const modBadge = +c.is_mod_comment
          ? `<span class="ms-comment-mod-badge">SK Official</span>`
          : "";

        // Replies
        let repliesHtml = "";
        if (c.replies && c.replies.length) {
          c.replies.forEach((r) => {
            const isReplyHit =
              targetType === "reply" && parseInt(r.id) === parseInt(targetId);
            const ri = (r.author_name || "?").substring(0, 2).toUpperCase();
            const rMod = +r.is_mod_comment
              ? `<span class="ms-comment-mod-badge">SK Official</span>`
              : "";
            repliesHtml += `
              <div class="ms-panel-reply-item ${
                isReplyHit ? "ms-reported-highlight" : ""
              }"
                   id="ms-reply-${r.id}">
                <div class="ms-panel-comment-avatar ms-panel-reply-avatar">${ri}</div>
                <div class="ms-panel-comment-body">
                  <div class="ms-panel-comment-header">
                    <span class="ms-panel-comment-author">${escHtml(
                      r.author_name
                    )}</span>
                    ${rMod}
                    <span class="ms-panel-comment-date">${formatDate(
                      r.created_at
                    )}</span>
                    ${
                      isReplyHit
                        ? `<span class="ms-reported-tag">⚑ Reported Reply</span>`
                        : ""
                    }
                  </div>
                  <div class="ms-panel-comment-text">${nl2br(
                    escHtml(r.message)
                  )}</div>
                </div>
              </div>`;
          });
        }

        const commentEl = document.createElement("div");
        commentEl.className = `ms-panel-comment-item ${
          isHit ? "ms-reported-highlight" : ""
        }`;
        commentEl.id = `ms-comment-${c.id}`;
        commentEl.innerHTML = `
          <div class="ms-panel-comment-avatar">${ci}</div>
          <div class="ms-panel-comment-body">
            <div class="ms-panel-comment-header">
              <span class="ms-panel-comment-author">${escHtml(
                c.author_name
              )}</span>
              ${modBadge}
              <span class="ms-panel-comment-date">${formatDate(
                c.created_at
              )}</span>
              ${
                isHit
                  ? `<span class="ms-reported-tag">⚑ Reported Comment</span>`
                  : ""
              }
            </div>
            <div class="ms-panel-comment-text">${nl2br(
              escHtml(c.message)
            )}</div>
            ${
              repliesHtml
                ? `<div class="ms-panel-replies">${repliesHtml}</div>`
                : ""
            }
          </div>`;
        listEl.appendChild(commentEl);
      });
    }

    loading.style.display = "none";
    content.style.display = "";

    // Snap panel body to top instantly (no animation yet)
    const panelBody = document.getElementById("ms-panel-body-scroll");
    if (panelBody) {
      panelBody.style.scrollBehavior = "auto";
      panelBody.scrollTop = 0;
    }

    // Wait for the panel slide-in CSS transition (300ms) + reading pause, then smoothly scroll
    setTimeout(() => {
      const targetEl =
        targetType === "comment"
          ? document.getElementById(`ms-comment-${targetId}`)
          : document.getElementById(`ms-reply-${targetId}`);

      if (!targetEl || !panelBody) return;

      // Calculate how far to scroll so the target is vertically centered in the panel
      const panelTop = panelBody.getBoundingClientRect().top;
      const targetTop = targetEl.getBoundingClientRect().top;
      const destination =
        panelBody.scrollTop +
        (targetTop - panelTop) -
        (panelBody.clientHeight / 2 - targetEl.clientHeight / 2);

      // Smooth scroll using CSS scroll-behavior (re-enable it now that we're past the snap)
      panelBody.style.scrollBehavior = "smooth";
      panelBody.scrollTop = destination;

      // Pulse the highlight after the scroll has had time to visually land (~600ms for smooth)
      setTimeout(() => targetEl.classList.add("ms-highlight-pulse"), 750);
    }, 600); // 300ms panel open + 300ms comfortable pause before scroll begins
  }

  /* ══════════════════════════════════════════════════════════
     SANCTION MODAL
  ══════════════════════════════════════════════════════════ */

  const modal = document.getElementById("sanction-modal");
  const modalClose = document.getElementById("modal-close");
  const modalCancel = document.getElementById("modal-cancel-btn");
  const modalSubmit = document.getElementById("modal-submit-btn");
  const modalLabel = document.getElementById("modal-submit-label");
  const modalWarnLv3 = document.getElementById("modal-warn-lvl3");
  const modalRecommended = document.getElementById("modal-recommended");

  let _ctx = {};

  function openSanctionModal({
    reportId,
    userId,
    author,
    currentLevel,
    nextLevel,
  }) {
    _ctx = { reportId, userId, author, currentLevel, nextLevel };

    document.getElementById("modal-author-name").textContent = author;
    document.getElementById("modal-reason").value = "";
    document.getElementById("modal-reason-error").textContent = "";

    const recommended = Math.min(nextLevel, 3);
    modal.querySelectorAll("input[name='modal-level']").forEach((r) => {
      r.checked = parseInt(r.value) === recommended;
    });

    modalRecommended.textContent =
      currentLevel === 0
        ? `ℹ️ No prior sanctions. Recommended: Level 1 (Warning).`
        : `ℹ️ Currently at Level ${currentLevel}. Recommended next: Level ${recommended}.`;

    updateModalWarn(recommended);
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeModal() {
    modal.style.display = "none";
    document.body.style.overflow = "";
    _ctx = {};
  }

  function updateModalWarn(level) {
    if (modalWarnLv3)
      modalWarnLv3.style.display = level === 3 ? "flex" : "none";
  }

  modal?.querySelectorAll("input[name='modal-level']").forEach((r) => {
    r.addEventListener("change", function () {
      updateModalWarn(parseInt(this.value));
    });
  });

  modalClose?.addEventListener("click", closeModal);
  modalCancel?.addEventListener("click", closeModal);
  modal?.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal?.style.display === "flex") closeModal();
  });

  modalSubmit?.addEventListener("click", async function () {
    const reason = document.getElementById("modal-reason")?.value.trim() || "";
    const errEl = document.getElementById("modal-reason-error");
    if (errEl) errEl.textContent = "";

    const level = parseInt(
      modal.querySelector("input[name='modal-level']:checked")?.value || "1"
    );
    const labels = {
      1: "Level 1 — Warning",
      2: "Level 2 — 7-Day Ban",
      3: "Level 3 — Permanent Ban",
    };

    if (
      !confirm(
        `Confirm: Issue ${labels[level]} to ${_ctx.author}?\nAn email notification will be sent.`
      )
    )
      return;

    modalSubmit.disabled = true;
    modalLabel.textContent = "Sending…";

    const fd = new FormData();
    fd.append("user_id", _ctx.userId);
    fd.append("level", level);
    if (reason) fd.append("reason", reason);
    if (_ctx.reportId) fd.append("report_id", _ctx.reportId);

    try {
      const res = await fetch(
        "../../../backend/controllers/IssueSanctionController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();

      if (data.status === "success") {
        closeModal();
        showToast(
          data.message +
            (data.email_sent ? " Email sent." : " (Email failed.)"),
          "success"
        );
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(data.message || "Failed to issue sanction.", "error");
      }
    } catch {
      showToast("Network error. Try again.", "error");
    } finally {
      modalSubmit.disabled = false;
      modalLabel.textContent = "Confirm Sanction";
    }
  });

  /* ── INIT ───────────────────────────────────────────────── */
  applyFilters();
});