/* ============================================================
   officer_requests.js — SK Officer | Service Requests
   Status flow: pending → action_required (via note) → approved | rejected
   Notes work as a thread; status updates persist via DB + re-fetch.
   ============================================================ */

   document.addEventListener("DOMContentLoaded", () => {
    /* ── BACKEND ROUTE ─────────────────────────────────────── */
    const API = "../../../backend/routes/officer_service_requests.php";
  
    /* ── REFS ──────────────────────────────────────────────── */
    const tbody = document.getElementById("req-tbody");
    const noResults = document.getElementById("req-no-results");
    const countLabel = document.getElementById("req-count");
  
    const searchInput = document.getElementById("req-search");
    const selCategory = document.getElementById("req-category");
    const selSort = document.getElementById("req-sort");
    const tabs = document.querySelectorAll(".req-tab");
  
    // Drawer
    const drawerOverlay = document.getElementById("req-drawer-overlay");
    const drawerClose = document.getElementById("req-drawer-close");
    const drawerTitle = document.getElementById("drawer-title");
    const drawerSubtitle = document.getElementById("drawer-subtitle");
    const drawerLoading = document.getElementById("drawer-loading");
    const drawerContent = document.getElementById("drawer-content");
    const drawerAvatar = document.getElementById("drawer-avatar");
    const drawerResident = document.getElementById("drawer-resident-name");
    const drawerResidentSub = document.getElementById("drawer-resident-sub");
    const drawerContact = document.getElementById("drawer-contact");
    const drawerEmail = document.getElementById("drawer-email");
    const drawerAddress = document.getElementById("drawer-address");
    const drawerService = document.getElementById("drawer-service");
    const drawerCategory = document.getElementById("drawer-category");
    const drawerStatusWrap = document.getElementById("drawer-status-wrap");
    const drawerDate = document.getElementById("drawer-date");
    const drawerPurpose = document.getElementById("drawer-purpose");
    const drawerFiles = document.getElementById("drawer-files");
    const drawerFulfillmentSection = document.getElementById("drawer-fulfillment-section");
    const drawerFulfillmentFile    = document.getElementById("drawer-fulfillment-file");
    const drawerNotesSection = document.getElementById(
      "drawer-notes-thread-section"
    );
    const drawerNotesThread = document.getElementById("drawer-notes-thread");
    const drawerNoteInput = document.getElementById("drawer-note-input-section");
    const drawerResponse = document.getElementById("drawer-response");
    const drawerFooter = document.getElementById("req-drawer-footer");
  
    // Confirm modal
    const confirmOverlay = document.getElementById("req-confirm-overlay");
    const confirmIcon = document.getElementById("req-confirm-icon");
    const confirmTitle = document.getElementById("req-confirm-title");
    const confirmBody = document.getElementById("req-confirm-body");
    const confirmOk = document.getElementById("req-confirm-ok");
    const confirmCancel = document.getElementById("req-confirm-cancel");
  
    // Toast
    const toast = document.getElementById("req-toast");
  
    /* ── STATE ─────────────────────────────────────────────── */
    let activeTab = "all";
    let sortDir = "desc";
    let pendingAction = null;
    let activeAppId = null; // currently open application id
    let toastTimer = null;
  
    /* ── TOAST ─────────────────────────────────────────────── */
    function showToast(msg, type = "info") {
      clearTimeout(toastTimer);
      toast.innerHTML = escapeHtml(msg);
      toast.className = `req-toast req-toast--${type} req-toast--show`;
      toastTimer = setTimeout(
        () => toast.classList.remove("req-toast--show"),
        3400
      );
    }

    function showLoadingToast(msg) {
      clearTimeout(toastTimer);
      toast.innerHTML = `
        <span class="req-toast-spinner"></span>
        <span>${escapeHtml(msg)}</span>`;
      toast.className = "req-toast req-toast--loading req-toast--show";
    }

    function hideLoadingToast() {
      toast.classList.remove("req-toast--show");
    }
  
    /* ── ROWS HELPER ───────────────────────────────────────── */
    function getRows() {
      return Array.from(tbody.querySelectorAll("tr"));
    }
  
    /* ── FILTER ────────────────────────────────────────────── */
    function applyFilters() {
      const q = searchInput.value.toLowerCase().trim();
      const category = selCategory.value;
      let visible = 0;
  
      getRows().forEach((row) => {
        const matchTab = activeTab === "all" || row.dataset.status === activeTab;
        const matchCat = category === "all" || row.dataset.category === category;
        const matchSearch =
          !q ||
          (row.dataset.resident || "").includes(q) ||
          (row.dataset.service || "").includes(q);
  
        const show = matchTab && matchCat && matchSearch;
        row.style.display = show ? "" : "none";
        if (show) visible++;
      });
  
      countLabel.textContent = `Showing ${visible} request${
        visible !== 1 ? "s" : ""
      }`;
      noResults.style.display = visible === 0 ? "flex" : "none";
    }
  
    searchInput.addEventListener("input", applyFilters);
    selCategory.addEventListener("change", applyFilters);
  
    /* ── TABS ──────────────────────────────────────────────── */
    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        activeTab = tab.dataset.status;
        applyFilters();
      });
    });
  
    /* ── SORT ──────────────────────────────────────────────── */
    selSort.addEventListener("change", () => {
      sortDir = selSort.value === "newest" ? "desc" : "asc";
      sortRows();
    });
  
    document.querySelectorAll(".req-table thead th.sortable").forEach((th) => {
      th.addEventListener("click", () => {
        sortDir = sortDir === "desc" ? "asc" : "desc";
        document
          .querySelectorAll(".req-table thead th.sortable")
          .forEach((h) => h.classList.remove("sort-asc", "sort-desc"));
        th.classList.add(sortDir === "asc" ? "sort-asc" : "sort-desc");
        sortRows();
      });
    });
  
    function sortRows() {
      const rows = getRows();
      rows.sort((a, b) => {
        const da = new Date(a.dataset.date || 0).getTime();
        const db = new Date(b.dataset.date || 0).getTime();
        return sortDir === "desc" ? db - da : da - db;
      });
      rows.forEach((r) => tbody.appendChild(r));
      applyFilters();
    }
  
    /* ── DRAWER — open & fetch full details from API ───────── */
    tbody.addEventListener("click", (e) => {
      const viewBtn = e.target.closest(".req-btn-view");
      if (!viewBtn) return;
      const row = viewBtn.closest("tr");
      openDrawer(row);
    });
  
    async function openDrawer(row) {
      activeAppId = row.dataset.id;
  
      const resident =
        row.querySelector(".req-resident-name")?.textContent || "—";
      drawerTitle.textContent = resident;
      drawerSubtitle.textContent = `Request #${String(activeAppId).padStart(
        4,
        "0"
      )}`;
  
      drawerLoading.style.display = "block";
      drawerContent.style.display = "none";
      drawerFooter.innerHTML = "";
      drawerOverlay.style.display = "flex";
  
      await fetchAndPopulate(activeAppId);
    }
  
    /**
     * Fetch fresh data from the server and re-populate the drawer.
     * Called on open AND after every successful mutation so the UI
     * always reflects what's actually in the database.
     */
    async function fetchAndPopulate(id) {
      try {
        const res = await fetch(`${API}?action=view&id=${id}`);
        const json = await res.json();
  
        if (!json.success) {
          showToast(json.message || "Failed to load request details.", "error");
          closeDrawer();
          return;
        }
  
        populateDrawer(json.data);
      } catch (err) {
        showToast("Network error loading request details.", "error");
        closeDrawer();
      }
    }
  
    function populateDrawer(app) {
      const fullName = (
        app.full_name || `${app.first_name} ${app.last_name}`
      ).trim();
      const initials = fullName
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || "")
        .join("");
      const statusDb = app.status;
      const statusCls = dbStatusToCss(statusDb);
      const statusLbl = dbStatusToLabel(statusDb);
      const finalized = statusDb === "approved" || statusDb === "rejected" || statusDb === "cancelled";
  
      drawerTitle.textContent = fullName;
      drawerSubtitle.textContent = `Request #${String(app.id).padStart(4, "0")}`;
      drawerAvatar.textContent = initials;
      drawerResident.textContent = fullName;
      drawerResidentSub.textContent = `${app.first_name} ${app.last_name} · Barangay Resident`;
      drawerContact.textContent = app.contact || "—";
      drawerEmail.textContent = app.email || "—";
      drawerAddress.textContent = app.address || "—";
      drawerService.textContent = app.service_name || "—";
      drawerCategory.textContent = capitalize(app.service_category || "—");
      drawerPurpose.textContent = app.purpose || "No details provided.";
      drawerDate.textContent = formatDate(app.submitted_at);
  
      drawerStatusWrap.innerHTML = `<span class="req-status-pill status-${statusCls}">${statusLbl}</span>`;
  
      // Notes thread
      renderNotesThread(app.notes || [], finalized);
  
      // Documents
      renderDocuments(app.documents || []);

      // Fulfillment file (approved applications only)
      renderFulfillmentFile(app.fulfillment_file || null, app.status);
  
      // Note textarea — hide when finalized
      drawerNoteInput.style.display = finalized ? "none" : "";
      drawerResponse.value = "";
  
      // Footer action buttons
      buildDrawerFooter(statusDb, app.id);
  
      // Also sync the table row so the list stays accurate
      syncTableRow(app.id, statusDb);
  
      drawerLoading.style.display = "none";
      drawerContent.style.display = "block";
    }
  
    /* ── NOTES THREAD RENDERER ─────────────────────────────── */
    function renderNotesThread(notes, finalized) {
      if (!notes || notes.length === 0) {
        drawerNotesSection.style.display = "none";
        return;
      }
  
      drawerNotesSection.style.display = "";
  
      const items = notes
        .map((n) => {
          const officerName = escapeHtml(n.officer_name || "SK Officer");
          const noteText = escapeHtml(n.note);
          const when = formatDate(n.created_at);
          return `
                  <div class="drawer-note-entry">
                      <div class="drawer-note-header">
                          <span class="drawer-note-author">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;vertical-align:-2px;margin-right:4px;">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                              </svg>
                              ${officerName}
                          </span>
                          <span class="drawer-note-date">${when}</span>
                      </div>
                      <p class="drawer-note-body">${noteText}</p>
                  </div>`;
        })
        .join("");
  
      drawerNotesThread.innerHTML = `<div class="drawer-notes-list">${items}</div>`;
    }
  
    /* ── DOCUMENTS RENDERER ────────────────────────────────── */
    const MAX_DOC_NAME = 42;
  
    function truncateDocName(name) {
      return name.length > MAX_DOC_NAME ? name.slice(0, MAX_DOC_NAME - 1) + "…" : name;
    }
  
    function renderDocuments(docs) {
      if (!docs || docs.length === 0) {
        drawerFiles.innerHTML = '<p class="drawer-no-files">No documents submitted.</p>';
        return;
      }
  
      const extIcon = (mime) => {
        if (mime && mime.startsWith("image/")) return "🖼️";
        if (mime === "application/pdf") return "📕";
        if (mime && mime.includes("word")) return "📘";
        if (mime && mime.includes("sheet")) return "📗";
        return "📄";
      };
  
      const formatSize = (bytes) => {
        if (!bytes) return "";
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / (1024 * 1024)).toFixed(1) + " MB";
      };
  
      const items = docs.map((doc) => {
        const icon      = extIcon(doc.mime_type);
        const fullName  = doc.file_name || "";
        const shortName = escapeHtml(truncateDocName(fullName));
        const titleAttr = escapeHtml(fullName);
        const size      = formatSize(doc.file_size);
        const metaParts = [formatDate(doc.uploaded_at), size].filter(Boolean);
        const basePath = "/SKonnect/" + doc.file_path.replace(/^\/+/, "");
        const isImage   = doc.mime_type && doc.mime_type.startsWith("image/");
        const isPdf     = doc.mime_type === "application/pdf";
  
        const previewBtn = (isImage || isPdf) ? `
          <button class="drawer-file-preview-btn" data-path="${escapeHtml(basePath)}" data-name="${titleAttr}" data-type="${doc.mime_type}" title="Quick View">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
          </button>` : "";
  
        return `
          <div class="drawer-file-item">
            <span class="drawer-file-icon">${icon}</span>
            <div class="drawer-file-info">
              <a href="${escapeHtml(basePath)}" class="drawer-file-link" download="${titleAttr}" title="${titleAttr}">
                ${shortName}
              </a>
              ${metaParts.length ? `<span class="drawer-file-meta">${metaParts.join(" · ")}</span>` : ""}
            </div>
            ${previewBtn}
          </div>`;
      }).join("");
  
      drawerFiles.innerHTML = `<div class="drawer-files-list">${items}</div>`;
    }
  
    /* ── FULFILLMENT FILE RENDERER ─────────────────────────── */
    function renderFulfillmentFile(filePath, status) {
      // Only show for approved applications that have a fulfillment file attached
      if (status !== "approved" || !filePath) {
        drawerFulfillmentSection.style.display = "none";
        drawerFulfillmentFile.innerHTML = "—";
        return;
      }

      drawerFulfillmentSection.style.display = "";

      const basePath   = "/SKonnect/" + filePath.replace(/^\/+/, "");
      const fileName   = filePath.split("/").pop() || "fulfillment_file";
      const ext        = fileName.split(".").pop().toLowerCase();

      // Infer mime from extension for preview button
      const mimeMap = {
        pdf: "application/pdf",
        jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png",
        gif: "image/gif",  webp: "image/webp",
      };
      const mime     = mimeMap[ext] || "";
      const isImage  = mime.startsWith("image/");
      const isPdf    = mime === "application/pdf";

      const icon = isImage ? "🖼️" : isPdf ? "📕" : "📄";

      const previewBtn = (isImage || isPdf) ? `
        <button class="drawer-file-preview-btn drawer-fulfillment-preview-btn"
                data-path="${escapeHtml(basePath)}"
                data-name="${escapeHtml(fileName)}"
                data-type="${escapeHtml(mime)}"
                title="Quick View">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
          </svg>
        </button>` : "";

      drawerFulfillmentFile.innerHTML = `
        <div class="drawer-files-list">
          <div class="drawer-file-item drawer-file-item--fulfillment">
            <span class="drawer-file-icon">${icon}</span>
            <div class="drawer-file-info">
              <a href="${escapeHtml(basePath)}" class="drawer-file-link" download="${escapeHtml(fileName)}" title="${escapeHtml(fileName)}">
                ${escapeHtml(truncateDocName(fileName))}
              </a>
              <span class="drawer-file-meta drawer-fulfillment-meta">Officer-issued document</span>
            </div>
            ${previewBtn}
          </div>
        </div>`;
    }

    /* ── DRAWER FOOTER BUTTONS ─────────────────────────────── */
    function buildDrawerFooter(statusDb, id) {
      drawerFooter.innerHTML = "";

      if (statusDb === "approved" || statusDb === "rejected" || statusDb === "cancelled") {
        const modifierMap = { approved: "approved", rejected: "declined", cancelled: "cancelled" };
        const iconMap     = { approved: "✅", rejected: "❌", cancelled: "🚫" };
        const labelMap    = {
          approved:  "This request has been approved. No further actions are available.",
          rejected:  "This request has been declined. No further actions are available.",
          cancelled: "This request was cancelled by the resident. No further actions are available.",
        };
        drawerFooter.innerHTML = `
          <div class="drawer-finalized-banner drawer-finalized-banner--${modifierMap[statusDb]}">
            <span>${iconMap[statusDb]}</span>
            ${labelMap[statusDb]}
          </div>`;
        return;
      }

      drawerFooter.appendChild(
        makeDrawerBtn(
          "drawer-btn-respond",
          id,
          "add_note",
          `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.127 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>Action Require`
        )
      );

      drawerFooter.appendChild(
        makeDrawerBtn(
          "drawer-btn-approve",
          id,
          "approve",
          `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Approve`
        )
      );
      drawerFooter.appendChild(
        makeDrawerBtn(
          "drawer-btn-decline",
          id,
          "decline",
          `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>Decline`
        )
      );
    }
  
    function makeDrawerBtn(cls, id, action, innerHTML) {
      const btn = document.createElement("button");
      btn.className = cls;
      btn.dataset.id = id;
      btn.dataset.action = action;
      btn.innerHTML = innerHTML;
      return btn;
    }
  
    /* ── DRAWER FOOTER DELEGATION ──────────────────────────── */
    drawerFooter.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      handleAction(btn.dataset.action, btn.dataset.id);
    });
  
    /* ── ACTION HANDLER ────────────────────────────────────── */
    function handleAction(action, id) {
      const row = tbody.querySelector(`tr[data-id="${id}"]`);
      const residentName =
        row?.querySelector(".req-resident-name")?.textContent || "this resident";
      const serviceName =
        row?.querySelector(".req-service-badge")?.textContent?.trim() ||
        "this request";
      const note = drawerResponse.value.trim();

      if (action === "add_note") {
        if (!note) {
          showToast("Please write a note before setting status.", "error");
          drawerResponse.focus();
          return;
        }
        pendingAction = async () => submitNote(id, note);
        confirmIcon.textContent = "💬";
        confirmTitle.textContent = "Send Officer Note";
        confirmBody.textContent = `Send a note to ${residentName}'s application? Status will be set to "Action Required".`;
        confirmOk.textContent = "Send Note";
        confirmOk.className = "req-confirm-ok";
        confirmOverlay.style.display = "flex";
        return;
      }

      if (action === "approve") {
        openApproveModal(id, residentName, serviceName);
        return;
      }

      if (action === "decline") {
        openDeclineModal(id, residentName, serviceName);
        return;
      }
    }
  
    /* ── SUBMIT: ADD NOTE ──────────────────────────────────── */
    async function submitNote(id, note) {
      const fd = new FormData();
      fd.append("action", "add_note");
      fd.append("id", id);
      fd.append("note", note);

      showLoadingToast("Sending…");
  
      try {
        const res = await fetch(API, { method: "POST", body: fd });
        const json = await res.json();

        hideLoadingToast();
  
        if (!json.success) {
          showToast(json.message || "Failed to send note.", "error");
          return;
        }
  
        showToast('Note sent. Status set to "Action Required".', "success");
  
        // Re-fetch from server to get updated notes thread + status
        if (activeAppId == id) {
          drawerLoading.style.display = "block";
          drawerContent.style.display = "none";
          await fetchAndPopulate(id);
        }
      } catch (err) {
        hideLoadingToast();
        showToast("Network error. Please try again.", "error");
      }
    }
  
    /* ── SUBMIT: STATUS UPDATE (approve / reject only) ─────── */
    async function submitStatusUpdate(id, newStatus, note = "", fileInput = null) {
      const fd = new FormData();
      fd.append("action", "update_status");
      fd.append("id", id);
      fd.append("status", newStatus);
      fd.append("note", note);

      if (fileInput && fileInput.files && fileInput.files.length > 0) {
        fd.append("fulfillment_file", fileInput.files[0]);
      }

      const loadingMsg = newStatus === "approved"
        ? "Approving application…"
        : "Declining application…";
      showLoadingToast(loadingMsg);

      try {
        const res = await fetch(API, { method: "POST", body: fd });
        const json = await res.json();

        hideLoadingToast();

        if (!json.success) {
          showToast(json.message || "Update failed.", "error");
          return false;
        }

        const label = newStatus === "approved" ? "approved" : "declined";
        showToast(
          `Application ${label} successfully.`,
          newStatus === "approved" ? "success" : "info"
        );

        // Re-fetch to get authoritative data, then close
        if (activeAppId == id) {
          drawerLoading.style.display = "block";
          drawerContent.style.display = "none";
          await fetchAndPopulate(id);
        }

        // Close the drawer after finalization
        closeDrawer();
        return true;
      } catch (err) {
        hideLoadingToast();
        showToast("Network error. Please try again.", "error");
        return false;
      }
    }

    /* ── APPROVE MODAL ─────────────────────────────────────── */
    const approveModalOverlay = document.getElementById("req-approve-modal-overlay");
    const approveModalClose   = document.getElementById("req-approve-modal-close");
    const approveNoteTextarea = document.getElementById("approve-modal-note");
    const approveFileInput    = document.getElementById("approve-modal-file");
    const approveFileLabel    = document.getElementById("approve-modal-file-label");
    const approveSubmitBtn    = document.getElementById("approve-modal-submit");
    const approveModalLoading = document.getElementById("approve-modal-loading");
    let approveTargetId = null;

    async function openApproveModal(id, residentName, serviceName) {
      approveTargetId = id;
      approveNoteTextarea.value = "";
      approveFileInput.value = "";
      approveFileLabel.textContent = "No file chosen";
      approveModalLoading.style.display = "block";
      approveNoteTextarea.disabled = true;
      approveSubmitBtn.disabled = true;
      approveModalOverlay.style.display = "flex";

      document.getElementById("approve-modal-title").textContent =
        `Approve: ${residentName}'s ${serviceName}`;

      try {
        const res  = await fetch(`${API}?action=get_approval_message&id=${id}`);
        const json = await res.json();
        approveNoteTextarea.value = json.success ? (json.approval_message || "") : "";
      } catch (_) {
        approveNoteTextarea.value = "";
      } finally {
        approveModalLoading.style.display = "none";
        approveNoteTextarea.disabled = false;
        approveSubmitBtn.disabled = false;
      }
    }

    function closeApproveModal() {
      approveModalOverlay.style.display = "none";
      approveTargetId = null;
    }

    approveModalClose.addEventListener("click", closeApproveModal);
    approveModalOverlay.addEventListener("click", (e) => {
      if (e.target === approveModalOverlay) closeApproveModal();
    });

    approveFileInput.addEventListener("change", () => {
      approveFileLabel.textContent = approveFileInput.files[0]
        ? approveFileInput.files[0].name
        : "No file chosen";
    });

    approveSubmitBtn.addEventListener("click", async () => {
      if (!approveTargetId) return;
      const note = approveNoteTextarea.value.trim();
      approveSubmitBtn.disabled = true;
      approveSubmitBtn.textContent = "Approving…";
      const ok = await submitStatusUpdate(approveTargetId, "approved", note, approveFileInput);
      if (ok) closeApproveModal();
      else {
        approveSubmitBtn.disabled = false;
        approveSubmitBtn.textContent = "Approve Application";
      }
    });

    /* ── DECLINE MODAL ─────────────────────────────────────── */
    const declineModalOverlay  = document.getElementById("req-decline-modal-overlay");
    const declineModalClose    = document.getElementById("req-decline-modal-close");
    const declineNoteTextarea  = document.getElementById("decline-modal-note");
    const declineSubmitBtn     = document.getElementById("decline-modal-submit");
    let declineTargetId = null;

    function openDeclineModal(id, residentName, serviceName) {
      declineTargetId = id;
      declineNoteTextarea.value = "";
      declineModalOverlay.style.display = "flex";
      document.getElementById("decline-modal-title").textContent =
        `Decline: ${residentName}'s ${serviceName}`;
      setTimeout(() => declineNoteTextarea.focus(), 80);
    }

    function closeDeclineModal() {
      declineModalOverlay.style.display = "none";
      declineTargetId = null;
    }

    declineModalClose.addEventListener("click", closeDeclineModal);
    declineModalOverlay.addEventListener("click", (e) => {
      if (e.target === declineModalOverlay) closeDeclineModal();
    });

    declineSubmitBtn.addEventListener("click", async () => {
      if (!declineTargetId) return;
      const note = declineNoteTextarea.value.trim();
      if (!note) {
        declineNoteTextarea.classList.add("textarea--error");
        declineNoteTextarea.focus();
        showToast("A reason is required before declining.", "error");
        return;
      }
      declineNoteTextarea.classList.remove("textarea--error");
      declineSubmitBtn.disabled = true;
      declineSubmitBtn.textContent = "Declining…";
      const ok = await submitStatusUpdate(declineTargetId, "rejected", note);
      if (ok) closeDeclineModal();
      else {
        declineSubmitBtn.disabled = false;
        declineSubmitBtn.textContent = "Decline Application";
      }
    });

    declineNoteTextarea.addEventListener("input", () => {
      if (declineNoteTextarea.value.trim()) {
        declineNoteTextarea.classList.remove("textarea--error");
      }
    });
  
    /* ── SYNC TABLE ROW FROM SERVER DATA ───────────────────── */
    /**
     * After any mutation, we get fresh data from the server.
     * This updates the corresponding table row so the list stays
     * in sync without a full page reload.
     */
    function syncTableRow(id, statusDb) {
      const row = tbody.querySelector(`tr[data-id="${id}"]`);
      if (!row) return;
  
      const css = dbStatusToCss(statusDb);
      const label = dbStatusToLabel(statusDb);
  
      row.dataset.status = css;
  
      const pill = row.querySelector(".req-status-pill");
      if (pill) {
        pill.className = `req-status-pill status-${css}`;
        pill.textContent = label;
      }
  
      updateTabCounts();
      applyFilters();
    }
  
    /* ── UPDATE TAB COUNTS ─────────────────────────────────── */
    function updateTabCounts() {
      const rows = getRows();
      const countMap = {
        all: rows.length,
        pending: 0,
        "action-required": 0,
        approved: 0,
        declined: 0,
        cancelled: 0,
      };
      rows.forEach((r) => {
        const s = r.dataset.status;
        if (s in countMap) countMap[s]++;
      });
      tabs.forEach((tab) => {
        const status = tab.dataset.status;
        const badge = tab.querySelector(".req-tab-count");
        if (badge && status in countMap) badge.textContent = countMap[status];
      });
    }
  
    /* ── CLOSE DRAWER ──────────────────────────────────────── */
    function closeDrawer() {
      drawerOverlay.style.display = "none";
      activeAppId = null;
    }
  
    drawerClose.addEventListener("click", closeDrawer);
    drawerOverlay.addEventListener("click", (e) => {
      if (e.target === drawerOverlay) closeDrawer();
    });
  
    /* ── CONFIRM MODAL ─────────────────────────────────────── */
    confirmOk.addEventListener("click", async () => {
      // 1. Save the function to a local constant so it survives the reset
      const actionToExecute = pendingAction;
  
      // 2. Now it's safe to close the modal and reset the global variable
      closeConfirm();
  
      // 3. Execute the saved function
      if (typeof actionToExecute === "function") {
        await actionToExecute();
      }
    });
  
    confirmCancel.addEventListener("click", closeConfirm);
    confirmOverlay.addEventListener("click", (e) => {
      if (e.target === confirmOverlay) closeConfirm();
    });
  
    function closeConfirm() {
      confirmOverlay.style.display = "none";
      pendingAction = null;
    }
  
    /* ── HELPERS ───────────────────────────────────────────── */
  
    // Map DB status value → CSS class used in the stylesheet
    function dbStatusToCss(status) {
      const map = {
        action_required: "action-required",
        rejected: "declined",
        cancelled: "cancelled",
      };
      return map[status] ?? status;
    }
  
    // Map DB status value → human label
    function dbStatusToLabel(status) {
      const map = {
        pending: "Pending",
        action_required: "Action Required",
        approved: "Approved",
        rejected: "Declined",
        cancelled: "Cancelled",
      };
      return map[status] ?? capitalize(status.replace(/_/g, " "));
    }
  
    function capitalize(str) {
      return str ? str.charAt(0).toUpperCase() + str.slice(1) : str;
    }
  
    function escapeHtml(str) {
      if (!str) return "";
      return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
    }
  
    function formatDate(dateStr) {
      if (!dateStr) return "—";
      const d = new Date(dateStr);
      return d.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    }

    /* ── FILE PREVIEW MODAL ────────────────────────────────── */
    const filePreviewOverlay = document.getElementById("req-file-preview-overlay");
    const filePreviewClose = document.getElementById("req-file-preview-close");
    const filePreviewName = document.getElementById("file-preview-name");
    const filePreviewBody = document.getElementById("file-preview-body");

    drawerFiles.addEventListener("click", (e) => {
      const previewBtn = e.target.closest(".drawer-file-preview-btn");
      if (!previewBtn) return;
      e.preventDefault();
      
      const filePath = previewBtn.dataset.path;
      const fileName = previewBtn.dataset.name;
      const fileType = previewBtn.dataset.type;
      
      openFilePreview(filePath, fileName, fileType);
    });

    drawerFulfillmentFile.addEventListener("click", (e) => {
      const previewBtn = e.target.closest(".drawer-file-preview-btn");
      if (!previewBtn) return;
      e.preventDefault();
      openFilePreview(previewBtn.dataset.path, previewBtn.dataset.name, previewBtn.dataset.type);
    });

    function openFilePreview(path, name, type) {
      filePreviewName.textContent = name;
      filePreviewBody.innerHTML = '<div class="req-file-preview-loading">Loading...</div>';
      filePreviewOverlay.style.display = "flex";

      setTimeout(() => {
        if (type && type.startsWith("image/")) {
          filePreviewBody.innerHTML = `<img src="${path}" alt="${name}" class="req-file-preview-image">`;
        } else if (type === "application/pdf") {
          filePreviewBody.innerHTML = `<iframe src="${path}" class="req-file-preview-pdf" frameborder="0"></iframe>`;
        } else {
          filePreviewBody.innerHTML = '<p class="req-file-preview-error">Preview not available for this file type.</p>';
        }
      }, 100);
    }

    function closeFilePreview() {
      filePreviewOverlay.style.display = "none";
      filePreviewBody.innerHTML = "";
    }

    filePreviewClose.addEventListener("click", closeFilePreview);
    filePreviewOverlay.addEventListener("click", (e) => {
      if (e.target === filePreviewOverlay) closeFilePreview();
    });

    /* ── KEYBOARD ──────────────────────────────────────────── */
    document.addEventListener("keydown", (e) => {
      if (e.key !== "Escape") return;
      // Close modals from innermost outward
      if (filePreviewOverlay.style.display !== "none") { closeFilePreview(); return; }
      if (approveModalOverlay.style.display !== "none") { closeApproveModal(); return; }
      if (declineModalOverlay.style.display !== "none") { closeDeclineModal(); return; }
      closeConfirm();
      closeDrawer();
    });

    /* ── INIT ──────────────────────────────────────────────── */
    sortRows();
    applyFilters();
    updateTabCounts();
  });