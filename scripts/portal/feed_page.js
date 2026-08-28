/* feed_page.js — SKonnect Community Feed (Portal) */

document.addEventListener("DOMContentLoaded", () => {
  /* ---- BAN MODAL HELPERS (hoisted outside if-block so openModal can reach them) ---- */

  const banOverlay = document.getElementById("ban-modal-overlay");
  const banDismiss = document.getElementById("ban-modal-dismiss");

  function showBanModal() {
    if (banOverlay) {
      banOverlay.style.display = "flex";
      document.body.style.overflow = "hidden";
    }
  }

  function hideBanModal() {
    if (banOverlay) banOverlay.style.display = "none";
    document.body.style.overflow = "";
  }

  banDismiss?.addEventListener("click", hideBanModal);

  /* ---- BAN ENFORCEMENT ---- */

  if (typeof USER_IS_BANNED !== "undefined" && USER_IS_BANNED) {
    const BAN_SHOWN_KEY = "banShown_feed";
    if (!sessionStorage.getItem(BAN_SHOWN_KEY)) {
      showBanModal();
      sessionStorage.setItem(BAN_SHOWN_KEY, "1");
    }

    const submitConcernBtn = document.getElementById("submit-concern-btn");
    if (submitConcernBtn) {
      submitConcernBtn.dataset.banned = "true";
      submitConcernBtn.title = "Your account is currently banned from posting.";
      submitConcernBtn.style.opacity = "0.45";
      submitConcernBtn.style.cursor = "not-allowed";
    }

    document.querySelectorAll(".support-btn").forEach((btn) => {
      btn.dataset.banned = "true";
      btn.style.opacity = "0.45";
      btn.style.cursor = "not-allowed";
      btn.title = "Unavailable while your account is banned.";
    });

    document.querySelectorAll(".bookmark-btn").forEach((btn) => {
      btn.dataset.banned = "true";
      btn.style.opacity = "0.45";
      btn.style.cursor = "not-allowed";
      btn.title = "Unavailable while your account is banned.";
    });

    document.addEventListener(
      "click",
      (e) => {
        const banned = e.target.closest('[data-banned="true"]');
        if (!banned) return;
        e.stopImmediatePropagation();
        e.preventDefault();
        showBanModal();
      },
      true
    );

    window.__bannedUser = true;
  }

  /* ---- FILTER & SORT ELEMENTS ---- */
  const searchInput = document.getElementById("feed-search");
  const categorySelect = document.getElementById("feed-category");
  const statusSelect = document.getElementById("feed-status");
  const sortSelect = document.getElementById("feed-sort");
  const cards = Array.from(document.querySelectorAll(".feed-card"));
  const noResults = document.getElementById("no-results");
  const grid = document.getElementById("feed-grid");

  /* ---- FILTER ---- */

  function filterCards() {
    const query = searchInput.value.toLowerCase().trim();
    const category = categorySelect.value;
    const status = statusSelect.value;
    let visible = 0;

    cards.forEach((card) => {
      const title =
        card.querySelector(".ann-card-title")?.textContent.toLowerCase() || "";
      const excerpt =
        card.querySelector(".ann-card-excerpt")?.textContent.toLowerCase() ||
        "";
      const cardCat = card.dataset.category || "";
      const cardSts = card.dataset.status || "";

      const matchesSearch =
        !query || title.includes(query) || excerpt.includes(query);
      const matchesCategory = category === "all" || cardCat === category;
      const matchesStatus = status === "all" || cardSts === status;

      const show = matchesSearch && matchesCategory && matchesStatus;
      card.dataset.filtered = show ? "true" : "false";
      if (show) visible++;
    });

    noResults.style.display = visible === 0 ? "block" : "none";

    currentPage = 1;
    applyPage();
  }

  /* ---- SORT ---- */

  function sortCards() {
    const order = sortSelect.value;

    const sorted = [...cards].sort((a, b) => {
      if (order === "comments") {
        return (
          parseInt(b.dataset.comments || 0) - parseInt(a.dataset.comments || 0)
        );
      }
      if (order === "supports") {
        return (
          parseInt(b.dataset.supports || 0) - parseInt(a.dataset.supports || 0)
        );
      }
      const da = new Date(a.dataset.date || "2000-01-01");
      const db = new Date(b.dataset.date || "2000-01-01");
      return order === "oldest" ? da - db : db - da;
    });

    sorted.forEach((card) => grid.appendChild(card));
    currentPage = 1;
    applyPage();
  }

  searchInput.addEventListener("input", filterCards);
  categorySelect.addEventListener("change", filterCards);
  statusSelect.addEventListener("change", filterCards);
  sortSelect.addEventListener("change", sortCards);

  /* ---- SUPPORT BUTTONS ---- */

  document.querySelectorAll(".support-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const threadId = btn.dataset.threadId;
      const fd = new FormData();
      fd.append("type", "thread");
      fd.append("id", threadId);

      try {
        const res = await fetch(
          "../../backend/controllers/ToggleSupportController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();
        if (data.status === "success") {
          btn.classList.toggle("active", data.supported);
          btn.title = data.supported ? "Remove support" : "I support this";
          btn.querySelector(".support-count").textContent = data.total;
          btn.closest(".feed-card").dataset.supports = data.total;
        }
      } catch (e) {
        showToast("Could not update support. Try again.", "error");
      }
    });
  });

  /* ---- BOOKMARK BUTTONS ---- */

  document.querySelectorAll(".bookmark-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const threadId = btn.dataset.threadId;
      const fd = new FormData();
      fd.append("thread_id", threadId);

      try {
        const res = await fetch(
          "../../backend/controllers/ToggleBookmarkController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();
        if (data.status === "success") {
          btn.classList.toggle("active", data.bookmarked);
          btn.title = data.bookmarked ? "Remove bookmark" : "Bookmark";
        }
      } catch (e) {
        showToast("Could not update bookmark. Try again.", "error");
      }
    });
  });

  /* ---- MODAL ---- */

  const submitBtn = document.getElementById("submit-concern-btn");
  const modalOverlay = document.getElementById("modal-overlay");
  const modalClose = document.getElementById("modal-close");
  const modalCancel = document.getElementById("modal-cancel");
  const modalSubmit = document.getElementById("modal-submit");
  const submitLabel = document.getElementById("submit-label");

  let selectedFiles = [];

  function openModal() {
    if (window.__bannedUser) {
      showBanModal();
      return;
    }
    modalOverlay.style.display = "flex";
    document.body.style.overflow = "hidden";
    document.getElementById("thread-form")?.reset();
    selectedFiles = [];
    renderPreviews();
    clearErrors();
    setTimeout(() => document.getElementById("m-category")?.focus(), 100);
  }

  function closeModal() {
    modalOverlay.style.display = "none";
    document.body.style.overflow = "";
  }

  submitBtn?.addEventListener("click", openModal);
  modalClose?.addEventListener("click", closeModal);
  modalCancel?.addEventListener("click", closeModal);
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });

  /* ---- FORM VALIDATION (priority removed) ---- */

  function clearErrors() {
    document
      .querySelectorAll(".field-error")
      .forEach((el) => (el.textContent = ""));
    ["m-category", "m-subject", "m-message"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.style.borderColor = "";
    });
  }

  function showError(fieldId, errId, msg) {
    const field = document.getElementById(fieldId);
    const err = document.getElementById(errId);
    if (field) field.style.borderColor = "#e11d48";
    if (err) err.textContent = msg;
  }

  function validateForm() {
    clearErrors();
    let valid = true;

    const category = document.getElementById("m-category")?.value;
    const subject = document.getElementById("m-subject")?.value.trim();
    const message = document.getElementById("m-message")?.value.trim();

    if (!category) {
      showError("m-category", "err-category", "Please select a category.");
      valid = false;
    }
    if (!subject) {
      showError("m-subject", "err-subject", "Subject is required.");
      valid = false;
    } else if (subject.length < 5) {
      showError(
        "m-subject",
        "err-subject",
        "Subject must be at least 5 characters."
      );
      valid = false;
    }
    if (!message) {
      showError("m-message", "err-message", "Message is required.");
      valid = false;
    } else if (message.length < 10) {
      showError(
        "m-message",
        "err-message",
        "Message must be at least 10 characters."
      );
      valid = false;
    }

    return valid;
  }

  /* ---- SUBMIT THREAD (AJAX) ---- */

  modalSubmit?.addEventListener("click", async () => {
    if (!validateForm()) return;

    submitLabel.textContent = "Posting…";
    modalSubmit.disabled = true;

    const fd = new FormData();
    fd.append("category", document.getElementById("m-category").value);
    fd.append("subject", document.getElementById("m-subject").value.trim());
    fd.append("message", document.getElementById("m-message").value.trim());
    selectedFiles.forEach((file) => fd.append("images[]", file));

    try {
      const res = await fetch(
        "../../backend/controllers/SubmitThreadController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();

      if (data.status === "success") {
        closeModal();
        showToast("Thread posted!", "success");
        setTimeout(() => window.location.reload(), 1200);
      } else {
        showToast(data.message || "Something went wrong.", "error");
      }
    } catch (e) {
      showToast("Network error. Please try again.", "error");
    } finally {
      submitLabel.textContent = "Post Thread";
      modalSubmit.disabled = false;
    }
  });

  /* ---- IMAGE UPLOAD & PREVIEW ---- */

  const dropZone = document.getElementById("file-drop-zone");
  const fileInput = document.getElementById("m-images");
  const previewGrid = document.getElementById("image-preview-grid");
  const browseBtn = document.getElementById("file-browse-btn");

  browseBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    fileInput?.click();
  });

  const ALLOWED_TYPES = ["image/jpeg", "image/png"];
  const MAX_BYTES = 5 * 1024 * 1024;

  function renderPreviews() {
    if (!previewGrid) return;
    previewGrid.innerHTML = "";
    selectedFiles.forEach((file, i) => {
      const url = URL.createObjectURL(file);
      const item = document.createElement("div");
      item.className = "preview-item";
      item.innerHTML = `
        <img src="${url}" alt="${file.name}">
        <button type="button" class="preview-remove" data-index="${i}" aria-label="Remove">×</button>
      `;
      previewGrid.appendChild(item);
    });
    previewGrid.querySelectorAll(".preview-remove").forEach((btn) => {
      btn.addEventListener("click", () => {
        selectedFiles.splice(parseInt(btn.dataset.index), 1);
        renderPreviews();
      });
    });
  }

  function addImages(files) {
    const errEl = document.getElementById("err-images");
    const msgs = [];
    Array.from(files).forEach((file) => {
      if (!ALLOWED_TYPES.includes(file.type)) {
        msgs.push(`"${file.name}" is not a JPEG or PNG.`);
        return;
      }
      if (file.size > MAX_BYTES) {
        msgs.push(`"${file.name}" exceeds 5MB.`);
        return;
      }
      if (
        !selectedFiles.find((f) => f.name === file.name && f.size === file.size)
      ) {
        selectedFiles.push(file);
      }
    });
    if (errEl) errEl.textContent = msgs.join(" ");
    renderPreviews();
  }

  fileInput?.addEventListener("change", () => {
    addImages(fileInput.files);
    fileInput.value = "";
  });
  dropZone?.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
  });
  dropZone?.addEventListener("dragleave", () =>
    dropZone.classList.remove("dragover")
  );
  dropZone?.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    addImages(e.dataTransfer.files);
  });

  const CARDS_PER_PAGE = 9;
  let currentPage = 1;

  function getFilteredCards() {
    return cards.filter((c) => c.dataset.filtered !== "false");
  }

  function getTotalPages() {
    return Math.max(1, Math.ceil(getFilteredCards().length / CARDS_PER_PAGE));
  }

  function renderPagination() {
    const pageNumbersEl = document.getElementById("page-numbers");
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    const totalPages = getTotalPages();

    if (!pageNumbersEl) return;
    pageNumbersEl.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = "page-num" + (i === currentPage ? " active" : "");
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        applyPage();
      });
      pageNumbersEl.appendChild(btn);
    }

    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage === totalPages;
  }

  function applyPage() {
    const filtered = getFilteredCards();
    const start = (currentPage - 1) * CARDS_PER_PAGE;

    cards.forEach((card) => {
      const idx = filtered.indexOf(card);
      if (idx === -1) {
        card.style.display = "none";
      } else {
        card.style.display =
          idx >= start && idx < start + CARDS_PER_PAGE ? "" : "none";
      }
    });

    renderPagination();
  }

  document.getElementById("prev-btn")?.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      applyPage();
    }
  });
  document.getElementById("next-btn")?.addEventListener("click", () => {
    if (currentPage < getTotalPages()) {
      currentPage++;
      applyPage();
    }
  });

  applyPage();

  /* ---- TOAST ---- */

  function showToast(msg, type = "success") {
    const toast = document.getElementById("feed-toast");
    if (!toast) return;
    toast.textContent = msg;
    toast.className = `feed-toast toast-${type} show`;
    setTimeout(() => {
      toast.className = "feed-toast";
    }, 3000);
  }

  window.showToast = showToast;
});