




 document.addEventListener("DOMContentLoaded", () => {
  

  const grid = document.getElementById("adm-feed-grid");
  const noResults = document.getElementById("adm-no-results");
  const countLabel = document.getElementById("adm-feed-count");

  const searchInput = document.getElementById("adm-feed-search");
  const selCategory = document.getElementById("adm-feed-category");
  const selStatus = document.getElementById("adm-feed-status");
  const selVisibility = document.getElementById("adm-feed-visibility");
  const selSort = document.getElementById("adm-feed-sort");

  const overlay = document.getElementById("adm-confirm-overlay");
  const confirmIcon = document.getElementById("adm-confirm-icon");
  const confirmTitle = document.getElementById("adm-confirm-title");
  const confirmBody = document.getElementById("adm-confirm-body");
  const confirmOk = document.getElementById("adm-confirm-ok");
  const confirmCxl = document.getElementById("adm-confirm-cancel");
  const toastEl = document.getElementById("adm-toast");

  

  let toastTimer = null;
  function showToast(msg, type = "success") {
    clearTimeout(toastTimer);
    toastEl.textContent = msg;
    toastEl.className = `adm-toast adm-toast--${type} adm-toast--show`;
    toastTimer = setTimeout(
      () => toastEl.classList.remove("adm-toast--show"),
      3400
    );
  }

  

  let loadingToastEl = null;

  function showLoadingToast(msg = "Processing…") {
    if (!loadingToastEl) {
      loadingToastEl = document.createElement("div");
      loadingToastEl.className = "adm-toast-loading";
      document.body.appendChild(loadingToastEl);
    }
    loadingToastEl.innerHTML = `
      <span class="adm-toast-loading-spinner"></span>
      <span class="adm-toast-loading-msg">${msg}</span>
    `;
    loadingToastEl.classList.add("adm-toast-loading--show");
  }

  function hideLoadingToast() {
    if (loadingToastEl) {
      loadingToastEl.classList.remove("adm-toast-loading--show");
    }
  }

  

  let pendingAction = null;

  function openConfirm({ icon, title, body, okLabel, okDanger, onConfirm }) {
    confirmIcon.textContent = icon;
    confirmTitle.textContent = title;
    confirmBody.textContent = body;
    confirmOk.textContent = okLabel || "Confirm";
    confirmOk.className = okDanger
      ? "adm-confirm-ok adm-confirm-ok--danger"
      : "adm-confirm-ok";
    pendingAction = onConfirm;
    overlay.style.display = "flex";
  }

  function closeConfirm() {
    overlay.style.display = "none";
    pendingAction = null;
  }

  confirmOk.addEventListener("click", () => {
    if (typeof pendingAction === "function") pendingAction();
    closeConfirm();
  });
  confirmCxl.addEventListener("click", closeConfirm);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) closeConfirm();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeConfirm();
      closePanel();
      closeFlagModal();
    }
  });

  

  function getCards() {
    return Array.from(grid.querySelectorAll(".adm-feed-card"));
  }

  async function postAction(url, body) {
    const fd = new FormData();
    Object.entries(body).forEach(([k, v]) => fd.append(k, v));
    const res = await fetch(url, { method: "POST", body: fd });
    return res.json();
  }

  

  const CARDS_PER_PAGE = 9;
  let currentPage = 1;

  function applyFilters() {
    const q = searchInput.value.toLowerCase().trim();
    const category = selCategory.value;
    const status = selStatus.value;
    const visibility = selVisibility.value;

    let visible = 0;

    getCards().forEach((card) => {
      const text = (
        (card.querySelector(".adm-feed-title")?.textContent || "") +
        " " +
        (card.querySelector(".adm-feed-excerpt")?.textContent || "")
      ).toLowerCase();

      const matchSearch = !q || text.includes(q);
      const matchCategory =
        category === "all" || card.dataset.category === category;
      const matchStatus = status === "all" || card.dataset.status === status;
      const isRemoved = card.dataset.removed === "1";
      const matchVis =
        visibility === "all" ||
        (visibility === "visible" && !isRemoved) ||
        (visibility === "hidden" && isRemoved);

      const show = matchSearch && matchCategory && matchStatus && matchVis;
      card.dataset.filterVisible = show ? "1" : "0";
      if (show) visible++;
    });

    countLabel.textContent = `Showing ${visible} thread${
      visible !== 1 ? "s" : ""
    }`;
    noResults.style.display = visible === 0 ? "flex" : "none";

    currentPage = 1;
    applyPage();
  }

  function applySort() {
    const order = selSort.value;
    const cards = getCards();

    cards.sort((a, b) => {
      if (order === "newest" || order === "oldest") {
        const da = new Date(a.dataset.date || 0);
        const db = new Date(b.dataset.date || 0);
        return order === "newest" ? db - da : da - db;
      }
      if (order === "comments") {
        return (
          parseInt(b.dataset.comments || 0) - parseInt(a.dataset.comments || 0)
        );
      }
      if (order === "flagged") {
        return (
          (b.dataset.flagged === "1" ? 1 : 0) -
          (a.dataset.flagged === "1" ? 1 : 0)
        );
      }
      if (order === "pinned") {
        return (
          (b.dataset.pinned === "1" ? 1 : 0) -
          (a.dataset.pinned === "1" ? 1 : 0)
        );
      }
      return 0;
    });

    cards.forEach((c) => grid.appendChild(c));
    applyFilters();
  }

  

  function getTotalPages() {
    const visible = getCards().filter((c) => c.dataset.filterVisible === "1");
    return Math.max(1, Math.ceil(visible.length / CARDS_PER_PAGE));
  }

  function applyPage() {
    const visible = getCards().filter((c) => c.dataset.filterVisible === "1");
    const start = (currentPage - 1) * CARDS_PER_PAGE;

    getCards().forEach((c) => (c.style.display = "none"));
    visible.forEach((c, i) => {
      c.style.display = i >= start && i < start + CARDS_PER_PAGE ? "" : "none";
    });

    renderPageButtons();
  }

  function renderPageButtons() {
    const pageNumbers = document.getElementById("adm-page-numbers");
    const prevBtn = document.getElementById("adm-prev-btn");
    const nextBtn = document.getElementById("adm-next-btn");
    const total = getTotalPages();

    pageNumbers.innerHTML = "";
    for (let i = 1; i <= total; i++) {
      const btn = document.createElement("button");
      btn.className = "adm-page-num" + (i === currentPage ? " active" : "");
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        applyPage();
      });
      pageNumbers.appendChild(btn);
    }

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === total;
  }

  document.getElementById("adm-prev-btn").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      applyPage();
    }
  });
  document.getElementById("adm-next-btn").addEventListener("click", () => {
    if (currentPage < getTotalPages()) {
      currentPage++;
      applyPage();
    }
  });

  searchInput.addEventListener("input", applyFilters);
  selCategory.addEventListener("change", applyFilters);
  selStatus.addEventListener("change", applyFilters);
  selVisibility.addEventListener("change", applyFilters);
  selSort.addEventListener("change", applySort);

  

  function bindStatusTogglers(container) {
    container.querySelectorAll(".adm-status-toggler").forEach((toggler) => {
      toggler.querySelectorAll(".adm-status-opt").forEach((btn) => {
        btn.addEventListener("click", () => {
          if (btn.classList.contains("active")) return;

          const threadId = toggler.dataset.threadId;
          const newStatus = btn.dataset.status;

          
          const currentBtn = toggler.querySelector(".adm-status-opt.active");
          const currentStatus = currentBtn
            ? currentBtn.dataset.status
            : "current";
          const statusIcons = {
            pending: "🕐",
            responded: "💬",
            resolved: "✅",
          };

          openConfirm({
            icon: statusIcons[newStatus] || "🔄",
            title: "Update Thread Status",
            body: `Change Thread #${threadId} status from "${capitalize(
              currentStatus
            )}" to "${capitalize(
              newStatus
            )}"? An email notification will be sent to the thread author.`,
            okLabel: `Set to ${capitalize(newStatus)}`,
            okDanger: false,
            onConfirm: async () => {
              showLoadingToast(`Updating status…`);
              try {
                const data = await postAction(
                  "../../../backend/controllers/AdminUpdateThreadStatusController.php",
                  { thread_id: threadId, status: newStatus }
                );

                if (data.status === "success") {
                  toggler
                    .querySelectorAll(".adm-status-opt")
                    .forEach((b) => b.classList.remove("active"));
                  btn.classList.add("active");

                  const card = grid.querySelector(
                    `.adm-feed-card[data-id="${threadId}"]`
                  );
                  if (card) {
                    card.dataset.status = newStatus;
                    const badge = card.querySelector(".adm-status-badge");
                    if (badge) {
                      badge.className = `adm-status-badge status-${newStatus}`;
                      badge.textContent =
                        newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }
                    const panelToggler = document.getElementById(
                      "panel-status-toggler"
                    );
                    if (
                      panelToggler &&
                      panelToggler.dataset.threadId === threadId
                    ) {
                      panelToggler
                        .querySelectorAll(".adm-status-opt")
                        .forEach((b) => {
                          b.classList.toggle(
                            "active",
                            b.dataset.status === newStatus
                          );
                        });
                      const panelBadges =
                        document.getElementById("panel-badges");
                      const panelBadge =
                        panelBadges?.querySelector(".adm-status-badge");
                      if (panelBadge) {
                        panelBadge.className = `adm-status-badge status-${newStatus}`;
                        panelBadge.textContent =
                          newStatus.charAt(0).toUpperCase() +
                          newStatus.slice(1);
                      }
                    }
                  }

                  showToast(
                    `Status updated to "${capitalize(newStatus)}".`,
                    "success"
                  );
                } else {
                  showToast(
                    data.message || "Failed to update status.",
                    "danger"
                  );
                }
              } catch {
                showToast("Network error. Please try again.", "danger");
              } finally {
                hideLoadingToast();
              }
            },
          });
        });
      });
    });
  }

  

  const flagModalOverlay = document.getElementById("adm-flag-modal-overlay");
  const flagModalClose   = document.getElementById("adm-flag-modal-close");
  const flagModalCancel  = document.getElementById("adm-flag-modal-cancel");
  const flagModalSubmit  = document.getElementById("adm-flag-modal-submit");
  const flagCatError     = document.getElementById("adm-flag-cat-error");

  let _flagCtx = {}; 

  function openFlagModal(threadId, onSuccess) {
    _flagCtx = { threadId, onSuccess };
    
    flagModalOverlay.querySelectorAll("input[name='adm-flag-category']")
      .forEach(r => r.checked = false);
    if (flagCatError) flagCatError.textContent = "";
    flagModalOverlay.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeFlagModal() {
    flagModalOverlay.style.display = "none";
    document.body.style.overflow = "";
    _flagCtx = {};
  }

  flagModalClose?.addEventListener("click", closeFlagModal);
  flagModalCancel?.addEventListener("click", closeFlagModal);
  flagModalOverlay?.addEventListener("click", e => {
    if (e.target === flagModalOverlay) closeFlagModal();
  });

  flagModalSubmit?.addEventListener("click", async () => {
    const selected = flagModalOverlay.querySelector("input[name='adm-flag-category']:checked");
    if (!selected) {
      if (flagCatError) flagCatError.textContent = "Please select a reason before flagging.";
      return;
    }
    if (flagCatError) flagCatError.textContent = "";

    const category = selected.value;
    const { threadId, onSuccess } = _flagCtx;

    flagModalSubmit.disabled = true;
    flagModalSubmit.textContent = "Flagging…";
    showLoadingToast("Flagging thread…");

    try {
      const data = await postAction(
        "../../../backend/controllers/AdminFlagThreadController.php",
        { thread_id: threadId, category }
      );
      if (data.status === "success") {
        closeFlagModal();
        onSuccess(true);
        showToast("Thread flagged.", "warning");
      } else {
        if (flagCatError) flagCatError.textContent = data.message || "Failed to flag thread.";
      }
    } catch {
      if (flagCatError) flagCatError.textContent = "Network error. Please try again.";
    } finally {
      flagModalSubmit.disabled = false;
      flagModalSubmit.textContent = "Flag Thread";
      hideLoadingToast();
    }
  });

  

  function handleFlag(threadId, isCurrentlyFlagged, onSuccess) {
    if (isCurrentlyFlagged) {
      
      openConfirm({
        icon: "🏳️",
        title: "Remove Flag",
        body: `Remove the flag from Thread #${threadId}?`,
        okLabel: "Remove Flag",
        okDanger: false,
        onConfirm: async () => {
          try {
            const data = await postAction(
              "../../../backend/controllers/AdminThreadActionController.php",
              { thread_id: threadId, action: "unflag" }
            );
            if (data.status === "success") {
              onSuccess(false);
              showToast("Flag removed.", "success");
            } else {
              showToast(data.message || "Action failed.", "danger");
            }
          } catch {
            showToast("Network error.", "danger");
          }
        },
      });
    } else {
      
      openFlagModal(threadId, onSuccess);
    }
  }

  function handleRemove(threadId, isCurrentlyRemoved, onSuccess) {
    const action = isCurrentlyRemoved ? "restore" : "remove";
    openConfirm({
      icon: isCurrentlyRemoved ? "👁️" : "🚫",
      title: isCurrentlyRemoved ? "Restore Thread" : "Hide Thread",
      body: isCurrentlyRemoved
        ? `Restore Thread #${threadId} so residents can see it again?`
        : `Hide Thread #${threadId} from residents? They will no longer see it.`,
      okLabel: isCurrentlyRemoved ? "Restore" : "Hide",
      okDanger: !isCurrentlyRemoved,
      onConfirm: async () => {
        showLoadingToast(
          isCurrentlyRemoved
            ? "Restoring thread…"
            : "Hiding thread…"
        );
        try {
          const data = await postAction(
            "../../../backend/controllers/AdminThreadActionController.php",
            { thread_id: threadId, action }
          );
          if (data.status === "success") {
            onSuccess(!isCurrentlyRemoved);
            showToast(
              isCurrentlyRemoved ? "Thread restored." : "Thread hidden.",
              isCurrentlyRemoved ? "success" : "warning"
            );
          } else {
            showToast(data.message || "Action failed.", "danger");
          }
        } catch {
          showToast("Network error.", "danger");
        } finally {
          hideLoadingToast();
        }
      },
    });
  }

  function handlePin(threadId, isCurrentlyPinned, onSuccess) {
    const action = isCurrentlyPinned ? "unpin" : "pin";
    openConfirm({
      icon: isCurrentlyPinned ? "📌" : "📍",
      title: isCurrentlyPinned ? "Unpin Thread" : "Pin Thread",
      body: isCurrentlyPinned
        ? `Unpin Thread #${threadId}? It will no longer be pinned at the top.`
        : `Pin Thread #${threadId}? It will appear at the top of the resident feed.`,
      okLabel: isCurrentlyPinned ? "Unpin" : "Pin",
      okDanger: false,
      onConfirm: async () => {
        showLoadingToast(
          isCurrentlyPinned
            ? "Unpinning thread…"
            : "Pinning thread…"
        );
        try {
          const data = await postAction(
            "../../../backend/controllers/AdminThreadActionController.php",
            { thread_id: threadId, action }
          );
          if (data.status === "success") {
            onSuccess(data.pinned);
            showToast(
              isCurrentlyPinned ? "Thread unpinned." : "Thread pinned.",
              "success"
            );
          } else {
            showToast(data.message || "Action failed.", "danger");
          }
        } catch {
          showToast("Network error.", "danger");
        } finally {
          hideLoadingToast();
        }
      },
    });
  }

  

  function syncCardFlag(card, flagBtn, flagged) {
    card.dataset.flagged = flagged ? "1" : "0";
    card.classList.toggle("adm-feed-card--flagged", flagged);

    const existingBadge = card.querySelector(".adm-flag-indicator");
    if (flagged && !existingBadge) {
      const badge = document.createElement("span");
      badge.className = "adm-flag-indicator";
      badge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd"/></svg>Flagged`;
      card.querySelector(".adm-feed-badges")?.appendChild(badge);
    } else if (!flagged && existingBadge) {
      existingBadge.remove();
    }

    flagBtn.classList.toggle("adm-action-flag--active", flagged);
    flagBtn.title = flagged ? "Unflag Thread" : "Flag for Review";
    flagBtn.innerHTML = flagged
      ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd"/></svg>Unflag`
      : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>Flag`;
  }

  function syncCardRemove(card, removeBtn, removed) {
    card.dataset.removed = removed ? "1" : "0";
    card.classList.toggle("adm-feed-card--removed", removed);

    const existingBadge = card.querySelector(".adm-remove-indicator");
    if (removed && !existingBadge) {
      const badge = document.createElement("span");
      badge.className = "adm-remove-indicator";
      badge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>Hidden`;
      card.querySelector(".adm-feed-badges")?.appendChild(badge);
    } else if (!removed && existingBadge) {
      existingBadge.remove();
    }

    removeBtn.classList.toggle("adm-action-remove--active", removed);
    removeBtn.title = removed ? "Restore Thread" : "Hide Thread";
    removeBtn.innerHTML = removed
      ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>Restore`
      : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>Hide`;
  }

  function syncCardPin(card, pinBtn, pinned) {
    card.dataset.pinned = pinned ? "1" : "0";
    card.classList.toggle("adm-feed-card--pinned", pinned);

    const existingBadge = card.querySelector(".adm-pin-indicator");
    if (pinned && !existingBadge) {
      const badge = document.createElement("span");
      badge.className = "adm-pin-indicator";
      badge.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M15.75 1.5a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM12.75 7.5a.75.75 0 0 0-1.5 0v5.69l-2.22-2.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V7.5Z"/></svg>Pinned`;
      
      const flagBadge = card.querySelector(".adm-flag-indicator");
      const badgesEl = card.querySelector(".adm-feed-badges");
      if (flagBadge) badgesEl.insertBefore(badge, flagBadge);
      else badgesEl.appendChild(badge);
    } else if (!pinned && existingBadge) {
      existingBadge.remove();
    }

    pinBtn.classList.toggle("adm-action-pin--active", pinned);
    pinBtn.title = pinned ? "Unpin Thread" : "Pin Thread";
    pinBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>${
      pinned ? "Unpin" : "Pin"
    }`;
  }

  

  grid.addEventListener("click", (e) => {
    const card = e.target.closest(".adm-feed-card");
    if (!card) return;
    const threadId = card.dataset.id;

    
    if (
      e.target.closest(".adm-status-toggler") ||
      e.target.closest(".adm-action-flag") ||
      e.target.closest(".adm-action-remove")
    ) {
      
    }

    
    if (e.target.closest(".adm-action-view")) {
      openPanel(threadId);
      return;
    }

    
    const pinBtn = e.target.closest(".adm-action-pin");
    if (pinBtn) {
      const pinned = card.dataset.pinned === "1";
      handlePin(threadId, pinned, (newPinned) => {
        syncCardPin(card, pinBtn, newPinned);
        const panelPinBtn = document.getElementById("panel-pin-btn");
        if (panelPinBtn?.dataset.threadId === threadId) {
          syncPanelPin(panelPinBtn, newPinned);
        }
      });
      return;
    }

    
    const flagBtn = e.target.closest(".adm-action-flag");
    if (flagBtn && !flagBtn.id) {
      
      const flagged = card.dataset.flagged === "1";
      handleFlag(threadId, flagged, (newFlagged) => {
        syncCardFlag(card, flagBtn, newFlagged);
        const panelFlagBtn = document.getElementById("panel-flag-btn");
        if (panelFlagBtn?.dataset.threadId === threadId) {
          syncPanelFlag(panelFlagBtn, newFlagged);
        }
      });
      return;
    }

    
    const removeBtn = e.target.closest(".adm-action-remove");
    if (removeBtn && !removeBtn.id) {
      const removed = card.dataset.removed === "1";
      handleRemove(threadId, removed, (newRemoved) => {
        syncCardRemove(card, removeBtn, newRemoved);
        applyFilters();
        const panelRemoveBtn = document.getElementById("panel-remove-btn");
        if (panelRemoveBtn?.dataset.threadId === threadId) {
          syncPanelRemove(panelRemoveBtn, newRemoved);
        }
      });
      return;
    }

    
    if (e.target.closest(".adm-status-toggler")) return;

    
    openPanel(threadId);
  });

  

  bindStatusTogglers(grid);

  

  const panel = document.getElementById("adm-thread-panel");
  const backdrop = document.getElementById("adm-panel-backdrop");
  const panelClose = document.getElementById("adm-panel-close");

  
  let currentPanelThreadId = null;

  function openPanel(threadId) {
    currentPanelThreadId = threadId;
    panel.classList.add("open");
    backdrop.classList.add("open");
    document.body.classList.add("adm-panel-open");
    loadPanel(threadId);
  }

  function closePanel() {
    panel.classList.remove("open");
    backdrop.classList.remove("open");
    document.body.classList.remove("adm-panel-open");
    currentPanelThreadId = null;
  }

  panelClose?.addEventListener("click", closePanel);
  backdrop?.addEventListener("click", closePanel);

  async function loadPanel(threadId) {
    document.getElementById("adm-panel-loading").style.display = "flex";
    document.getElementById("adm-panel-content").style.display = "none";

    try {
      const res = await fetch(
        `../../../backend/controllers/AdminGetThreadController.php?id=${threadId}`
      );
      const data = await res.json();

      if (data.status !== "success") {
        showToast("Could not load thread.", "danger");
        closePanel();
        return;
      }

      renderPanel(data.thread, data.images, data.comments);
    } catch {
      showToast("Network error loading thread.", "danger");
      closePanel();
    }
  }

  const catLabels = {
    inquiry: "Inquiry",
    complaint: "Complaint",
    suggestion: "Suggestion",
    event_question: "Event",
    other: "Other",
  };

  function renderPanel(thread, images, comments) {
    const loading = document.getElementById("adm-panel-loading");
    const content = document.getElementById("adm-panel-content");

    
    const catKey = thread.category;
    document.getElementById("panel-badges").innerHTML = `
      <span class="adm-cat-badge category-${catKey}">${
      catLabels[catKey] || "Other"
    }</span>
      <span class="adm-status-badge status-${thread.status}">${capitalize(
      thread.status
    )}</span>
      ${
        +thread.is_pinned
          ? `<span class="adm-pin-indicator"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M15.75 1.5a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM12.75 7.5a.75.75 0 0 0-1.5 0v5.69l-2.22-2.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V7.5Z"/></svg>Pinned</span>`
          : ""
      }
      ${
        +thread.is_flagged
          ? `<span class="adm-flag-indicator"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd"/></svg>Flagged</span>`
          : ""
      }
      ${
        +thread.is_removed
          ? `<span class="adm-remove-indicator"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>Hidden</span>`
          : ""
      }
    `;

    
    document.getElementById("panel-title").textContent = thread.subject;

    
    const initials = thread.author_name.substring(0, 2).toUpperCase();
    document.getElementById("panel-meta").innerHTML = `
      <div class="adm-panel-author">
        <span class="adm-panel-avatar">${initials}</span>
        <div>
          <span class="adm-panel-author-name">${escHtml(
            thread.author_name
          )}</span>
          <span class="adm-panel-date">${formatDate(thread.created_at)}</span>
        </div>
      </div>
      <div class="adm-panel-counts">
        <span>💬 ${thread.comment_count} ${
      +thread.comment_count === 1 ? "comment" : "comments"
    }</span>
        <span>🤝 ${thread.support_count} ${
      +thread.support_count === 1 ? "support" : "supports"
    }</span>
      </div>
    `;

    
    document.getElementById("panel-body-text").innerHTML = nl2br(
      escHtml(thread.message)
    );

    
    const imagesEl = document.getElementById("panel-images");
    if (images && images.length) {
      imagesEl.innerHTML = images
        .map(
          (img) =>
            `<div class="adm-panel-image-item" data-src="../../../${escHtml(
              img.file_path
            )}">
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

    
    const panelToggler = document.getElementById("panel-status-toggler");
    panelToggler.dataset.threadId = thread.id;
    panelToggler.querySelectorAll(".adm-status-opt").forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.status === thread.status);
    });
    bindStatusTogglers(document.getElementById("panel-actions"));

    
    const panelPinBtn = document.getElementById("panel-pin-btn");
    panelPinBtn.dataset.threadId = thread.id;
    syncPanelPin(panelPinBtn, +thread.is_pinned === 1);

    
    const panelFlagBtn = document.getElementById("panel-flag-btn");
    const panelRemoveBtn = document.getElementById("panel-remove-btn");
    panelFlagBtn.dataset.threadId = thread.id;
    panelRemoveBtn.dataset.threadId = thread.id;
    syncPanelFlag(panelFlagBtn, +thread.is_flagged === 1);
    syncPanelRemove(panelRemoveBtn, +thread.is_removed === 1);

    
    const commentCount = document.getElementById("panel-comments-count");
    const commentList = document.getElementById("panel-comment-list");
    commentCount.textContent = comments ? comments.length : 0;

    if (!comments || comments.length === 0) {
      commentList.innerHTML = `<div class="adm-panel-no-comments">No comments yet.</div>`;
    } else {
      
      
      comments.sort((a, b) => {
        const aIsMod = +a.is_mod_comment;
        const bIsMod = +b.is_mod_comment;
        if (bIsMod !== aIsMod) return bIsMod - aIsMod; 
        
        return new Date(b.created_at) - new Date(a.created_at);
      });

      
      function deletionTag(item, isReply = false) {
        if (!+item.is_removed) return "";
        if (+item.removed_by_mod) {
          return `<span class="adm-deleted-tag adm-deleted-tag--mod">🚫 Removed by Mod</span>`;
        }
        if (+item.removed_by_user) {
          return `<span class="adm-deleted-tag adm-deleted-tag--user">🚫 Deleted by ${isReply ? "Author" : "Author"}</span>`;
        }
        return `<span class="adm-deleted-tag adm-deleted-tag--user">🚫 Deleted</span>`;
      }

      commentList.innerHTML = comments
        .map((c) => {
          const ci = c.author_name.substring(0, 2).toUpperCase();
          const modBadge = +c.is_mod_comment
            ? `<span class="adm-comment-badge">SK Official</span>`
            : "";
          const removedClass = +c.is_removed ? " adm-panel-comment-item--removed" : "";

          const repliesHtml =
            c.replies && c.replies.length
              ? c.replies
                  .map((r) => {
                    const ri = r.author_name.substring(0, 2).toUpperCase();
                    const rModBadge = +r.is_mod_comment
                      ? `<span class="adm-comment-badge">SK Official</span>`
                      : "";
                    const rRemovedClass = +r.is_removed ? " adm-panel-comment-item--removed" : "";

                    return `
              <div class="adm-panel-reply-item${rRemovedClass} ${
                +r.is_mod_comment ? "adm-panel-comment-item--mod" : ""
              }">
                <div class="adm-panel-comment-avatar adm-panel-reply-avatar">${ri}</div>
                <div class="adm-panel-comment-body">
                  <div class="adm-panel-comment-header">
                    <span class="adm-panel-comment-author">${escHtml(r.author_name)}</span>
                    ${rModBadge}
                    ${deletionTag(r, true)}
                    <span class="adm-panel-comment-date">${formatDate(r.created_at)}</span>
                  </div>
                  <div class="adm-panel-comment-text">${nl2br(escHtml(r.message))}</div>
                </div>
              </div>`;
                  })
                  .join("")
              : "";

          return `
        <div class="adm-panel-comment-item${removedClass} ${
          +c.is_mod_comment ? "adm-panel-comment-item--mod" : ""
        }" id="panel-comment-${c.id}" data-created-at="${escHtml(c.created_at)}">
          <div class="adm-panel-comment-avatar">${ci}</div>
          <div class="adm-panel-comment-body">
            <div class="adm-panel-comment-header">
              <span class="adm-panel-comment-author">${escHtml(c.author_name)}</span>
              ${modBadge}
              ${deletionTag(c)}
              <span class="adm-panel-comment-date">${formatDate(c.created_at)}</span>
            </div>
            <div class="adm-panel-comment-text">${nl2br(escHtml(c.message))}</div>
            ${
              c.replies && c.replies.length
                ? `<div class="adm-panel-replies">${repliesHtml}</div>`
                : ""
            }
            <div class="adm-panel-inline-reply-wrap">
              <button class="adm-panel-reply-toggle" data-comment-id="${c.id}">💬 Reply</button>
              <div class="adm-panel-inline-reply-box" id="panel-reply-box-${c.id}" style="display:none;">
                <textarea class="adm-panel-reply-textarea adm-panel-inline-textarea" rows="2" placeholder="Reply as admin…"></textarea>
                <div class="adm-panel-inline-footer">
                  <button class="adm-panel-inline-cancel" data-comment-id="${c.id}">Cancel</button>
                  <button class="adm-panel-inline-submit adm-panel-reply-submit" data-comment-id="${c.id}">
                    <span class="adm-panel-inline-label">Post Reply</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>`;
        })
        .join("");

      
      commentList
        .querySelectorAll(".adm-panel-comment-item")
        .forEach((item) => {
          bindPanelReplyUI(item, thread.id);
        });
    }

    
    setupModReplyBox(thread.id, commentCount, commentList);

    
    const modInitial = (
      typeof ADMIN_NAME === "string" ? ADMIN_NAME[0] : "M"
    ).toUpperCase();
    document.getElementById("adm-panel-reply-avatar").textContent = modInitial;

    
    imagesEl.querySelectorAll(".adm-panel-image-item").forEach((item) => {
      item.addEventListener("click", () => openLightbox(item.dataset.src));
    });

    loading.style.display = "none";
    content.style.display = "";

    
    panelPinBtn.onclick = () => {
      const pinned = panelPinBtn.classList.contains("adm-action-pin--active");
      const card = grid.querySelector(`.adm-feed-card[data-id="${thread.id}"]`);
      handlePin(thread.id, pinned, (newPinned) => {
        syncPanelPin(panelPinBtn, newPinned);
        
        document
          .getElementById("panel-badges")
          .querySelector(".adm-pin-indicator")
          ?.remove();
        if (newPinned) {
          const b = document.createElement("span");
          b.className = "adm-pin-indicator";
          b.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M15.75 1.5a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM12.75 7.5a.75.75 0 0 0-1.5 0v5.69l-2.22-2.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V7.5Z"/></svg>Pinned`;
          document.getElementById("panel-badges").prepend(b);
        }
        if (card) {
          const cardPinBtn = card.querySelector(".adm-action-pin");
          if (cardPinBtn) syncCardPin(card, cardPinBtn, newPinned);
        }
      });
    };

    panelFlagBtn.onclick = () => {
      const flagged = panelFlagBtn.classList.contains(
        "adm-action-flag--active"
      );
      const card = grid.querySelector(`.adm-feed-card[data-id="${thread.id}"]`);
      handleFlag(thread.id, flagged, (newFlagged) => {
        syncPanelFlag(panelFlagBtn, newFlagged);
        document
          .getElementById("panel-badges")
          .querySelector(".adm-flag-indicator")
          ?.remove();
        if (newFlagged) {
          const b = document.createElement("span");
          b.className = "adm-flag-indicator";
          b.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd"/></svg>Flagged`;
          document.getElementById("panel-badges").appendChild(b);
        }
        if (card) {
          const cardFlagBtn = card.querySelector(".adm-action-flag");
          if (cardFlagBtn) syncCardFlag(card, cardFlagBtn, newFlagged);
        }
      });
    };

    panelRemoveBtn.onclick = () => {
      const removed = panelRemoveBtn.classList.contains(
        "adm-action-remove--active"
      );
      const card = grid.querySelector(`.adm-feed-card[data-id="${thread.id}"]`);
      handleRemove(thread.id, removed, (newRemoved) => {
        syncPanelRemove(panelRemoveBtn, newRemoved);
        document
          .getElementById("panel-badges")
          .querySelector(".adm-remove-indicator")
          ?.remove();
        if (newRemoved) {
          const b = document.createElement("span");
          b.className = "adm-remove-indicator";
          b.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>Hidden`;
          document.getElementById("panel-badges").appendChild(b);
        }
        if (card) {
          const cardRemoveBtn = card.querySelector(".adm-action-remove");
          if (cardRemoveBtn) syncCardRemove(card, cardRemoveBtn, newRemoved);
          applyFilters();
        }
      });
    };
  }

  

  function setupModReplyBox(threadId, commentCountEl, commentListEl) {
    const textarea = document.getElementById("adm-panel-reply-textarea");
    const submitBtn = document.getElementById("adm-panel-reply-submit");
    const labelEl = document.getElementById("adm-panel-reply-label");

    
    textarea.value = "";

    
    const newSubmit = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newSubmit, submitBtn);
    const newLabel =
      newSubmit.querySelector("#adm-panel-reply-label") || newSubmit;

    newSubmit.addEventListener("click", async () => {
      const message = textarea.value.trim();
      if (!message || message.length < 2) {
        textarea.style.borderColor = "#e11d48";
        textarea.focus();
        return;
      }
      textarea.style.borderColor = "";
      newSubmit.disabled = true;
      newSubmit.textContent = "Posting…";

      const fd = new FormData();
      fd.append("thread_id", threadId);
      fd.append("message", message);

      try {
        const res = await fetch(
          "../../../backend/controllers/PostCommentController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();

        if (data.status === "success") {
          textarea.value = "";
          const c = data.comment;
          const noComments = commentListEl.querySelector(
            ".adm-panel-no-comments"
          );
          if (noComments) noComments.remove();

          const item = document.createElement("div");
          item.className = "adm-panel-comment-item adm-panel-comment-item--mod";
          item.id = `panel-comment-${c.id}`;
          item.dataset.createdAt = c.created_at;
          item.innerHTML = `
            <div class="adm-panel-comment-avatar">${(
              c.first_name?.[0] ?? "M"
            ).toUpperCase()}</div>
            <div class="adm-panel-comment-body">
              <div class="adm-panel-comment-header">
                <span class="adm-panel-comment-author">${escHtml(
                  c.first_name + " " + c.last_name
                )}</span>
                <span class="adm-comment-badge">SK Official</span>
                <span class="adm-panel-comment-date">${formatDate(
                  c.created_at
                )}</span>
              </div>
              <div class="adm-panel-comment-text">${nl2br(
                escHtml(c.message)
              )}</div>
              <div class="adm-panel-inline-reply-wrap">
                <button class="adm-panel-reply-toggle" data-comment-id="${
                  c.id
                }">💬 Reply</button>
                <div class="adm-panel-inline-reply-box" id="panel-reply-box-${
                  c.id
                }" style="display:none;">
                  <textarea class="adm-panel-reply-textarea adm-panel-inline-textarea" rows="2" placeholder="Reply as admin…"></textarea>
                  <div class="adm-panel-inline-footer">
                    <button class="adm-panel-inline-cancel" data-comment-id="${
                      c.id
                    }">Cancel</button>
                    <button class="adm-panel-inline-submit adm-panel-reply-submit" data-comment-id="${
                      c.id
                    }">
                      <span class="adm-panel-inline-label">Post Reply</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>`;

          commentListEl.prepend(item);

          
          
          const allItems = Array.from(
            commentListEl.querySelectorAll(".adm-panel-comment-item")
          );
          allItems.sort((a, b) => {
            const aIsMod = a.classList.contains("adm-panel-comment-item--mod") ? 1 : 0;
            const bIsMod = b.classList.contains("adm-panel-comment-item--mod") ? 1 : 0;
            if (bIsMod !== aIsMod) return bIsMod - aIsMod;
            
            const da = new Date(a.dataset.createdAt || 0);
            const db = new Date(b.dataset.createdAt || 0);
            return db - da;
          });
          allItems.forEach((el) => commentListEl.appendChild(el));

          bindPanelReplyUI(item, threadId);

          const curr = parseInt(commentCountEl.textContent) || 0;
          commentCountEl.textContent = curr + 1;

          
          const card = grid.querySelector(
            `.adm-feed-card[data-id="${threadId}"]`
          );
          if (card) {
            const cardComments = card.querySelector(".adm-feed-comments");
            if (cardComments) {
              const n = parseInt(card.dataset.comments || 0) + 1;
              card.dataset.comments = n;
              cardComments.textContent = `💬 ${n}`;
            }
          }

          item.scrollIntoView({ behavior: "smooth", block: "end" });
          showToast("Comment posted!", "success");
        } else {
          showToast(data.message || "Could not post comment.", "danger");
        }
      } catch {
        showToast("Network error. Try again.", "danger");
      } finally {
        newSubmit.disabled = false;
        newSubmit.textContent = "Post Comment";
      }
    });

    textarea.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) newSubmit.click();
    });
  }

  

  function bindPanelReplyUI(commentEl, threadId) {
    if (commentEl.dataset.replyBound) return;
    commentEl.dataset.replyBound = "1";

    const commentId = commentEl.id.replace("panel-comment-", "");
    const toggleBtn = commentEl.querySelector(".adm-panel-reply-toggle");
    const replyBox = document.getElementById(`panel-reply-box-${commentId}`);
    const cancelBtn = replyBox?.querySelector(".adm-panel-inline-cancel");
    const submitBtn = replyBox?.querySelector(".adm-panel-inline-submit");
    const textarea = replyBox?.querySelector(".adm-panel-inline-textarea");

    if (!toggleBtn || !replyBox) return;

    toggleBtn.addEventListener("click", () => {
      const isOpen = replyBox.style.display !== "none";
      replyBox.style.display = isOpen ? "none" : "block";
      if (!isOpen) textarea?.focus();
    });

    cancelBtn?.addEventListener("click", () => {
      replyBox.style.display = "none";
      if (textarea) textarea.value = "";
    });

    submitBtn?.addEventListener("click", async () => {
      const message = textarea?.value.trim();
      if (!message || message.length < 2) {
        if (textarea) textarea.style.borderColor = "#e11d48";
        textarea?.focus();
        return;
      }
      if (textarea) textarea.style.borderColor = "";
      submitBtn.disabled = true;
      submitBtn.textContent = "Posting…";

      const fd = new FormData();
      fd.append("comment_id", commentId);
      fd.append("message", message);

      try {
        const res = await fetch(
          "../../../backend/controllers/PostReplyController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();

        if (data.status === "success") {
          textarea.value = "";
          replyBox.style.display = "none";

          const r = data.reply;
          
          let repliesEl = commentEl.querySelector(".adm-panel-replies");
          if (!repliesEl) {
            repliesEl = document.createElement("div");
            repliesEl.className = "adm-panel-replies";
            commentEl
              .querySelector(".adm-panel-comment-body")
              .insertBefore(
                repliesEl,
                commentEl.querySelector(".adm-panel-inline-reply-wrap")
              );
          }

          const item = document.createElement("div");
          item.className = "adm-panel-reply-item adm-panel-comment-item--mod";
          item.innerHTML = `
            <div class="adm-panel-comment-avatar adm-panel-reply-avatar">${(
              r.first_name?.[0] ?? "M"
            ).toUpperCase()}</div>
            <div class="adm-panel-comment-body">
              <div class="adm-panel-comment-header">
                <span class="adm-panel-comment-author">${escHtml(
                  r.first_name + " " + r.last_name
                )}</span>
                <span class="adm-comment-badge">SK Official</span>
                <span class="adm-panel-comment-date">${formatDate(
                  r.created_at
                )}</span>
              </div>
              <div class="adm-panel-comment-text">${nl2br(
                escHtml(r.message)
              )}</div>
            </div>`;

          repliesEl.appendChild(item);
          item.scrollIntoView({ behavior: "smooth", block: "end" });
          showToast("Reply posted!", "success");
        } else {
          showToast(data.message || "Could not post reply.", "danger");
        }
      } catch {
        showToast("Network error. Try again.", "danger");
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Post Reply";
      }
    });

    textarea?.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) submitBtn?.click();
    });
  }

  

  function syncPanelPin(btn, pinned) {
    btn.classList.toggle("adm-action-pin--active", pinned);
    btn.title = pinned ? "Unpin Thread" : "Pin Thread";
    document.getElementById("panel-pin-label").textContent = pinned
      ? "Unpin"
      : "Pin";
  }

  function syncPanelFlag(btn, flagged) {
    btn.classList.toggle("adm-action-flag--active", flagged);
    btn.title = flagged ? "Unflag Thread" : "Flag for Review";
    document.getElementById("panel-flag-label").textContent = flagged
      ? "Unflag"
      : "Flag";
  }

  function syncPanelRemove(btn, removed) {
    btn.classList.toggle("adm-action-remove--active", removed);
    btn.title = removed ? "Restore Thread" : "Hide Thread";
    document.getElementById("panel-remove-label").textContent = removed
      ? "Restore"
      : "Hide";
  }

  

  const lightbox = document.getElementById("adm-lightbox");
  const lightboxImg = document.getElementById("adm-lightbox-img");
  const lightboxClose = document.getElementById("adm-lightbox-close");

  function openLightbox(src) {
    lightboxImg.src = src;
    lightbox.style.display = "flex";
  }

  lightboxClose?.addEventListener(
    "click",
    () => (lightbox.style.display = "none")
  );
  lightbox?.addEventListener("click", (e) => {
    if (e.target === lightbox) lightbox.style.display = "none";
  });

  

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function escHtml(str) {
    const d = document.createElement("div");
    d.appendChild(document.createTextNode(str || ""));
    return d.innerHTML;
  }

  function nl2br(str) {
    return str.replace(/\n/g, "<br>");
  }

  function formatDate(dateStr) {
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

  

  getCards().forEach((c) => (c.dataset.filterVisible = "1"));
  applyFilters();
});