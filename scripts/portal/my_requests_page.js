/* my_requests_page.js — Portal My Requests */

document.addEventListener("DOMContentLoaded", () => {
  /* ─────────────────────────────────────────────
       FILTER & SORT
    ───────────────────────────────────────────── */

  const searchInput = document.getElementById("req-search");
  const statusSelect = document.getElementById("req-status");
  const categorySelect = document.getElementById("req-category");
  const sortSelect = document.getElementById("req-sort");
  const rows = Array.from(document.querySelectorAll(".req-row"));
  const noResults = document.getElementById("no-results");
  const tbody = document.getElementById("req-tbody");

  function filterRows() {
    const query = searchInput.value.toLowerCase().trim();
    const status = statusSelect.value;
    const category = categorySelect.value;
    let visible = 0;

    rows.forEach((row) => {
      const service = (row.dataset.service || "").toLowerCase();
      const rowSts = row.dataset.status || "";
      const rowCat = row.dataset.category || "";

      const matchesSearch = !query || service.includes(query);
      const matchesStatus = status === "all" || rowSts === status;
      const matchesCategory = category === "all" || rowCat === category;

      const show = matchesSearch && matchesStatus && matchesCategory;
      row.style.display = show ? "" : "none";
      if (show) visible++;
    });

    noResults.style.display = visible === 0 ? "block" : "none";
  }

  function sortRows() {
    const order = sortSelect.value;

    const getDate = (row) => {
      const cell = row.querySelectorAll("td")[2];
      return cell ? new Date(cell.textContent.trim()) : new Date(0);
    };

    const sorted = [...rows].sort((a, b) => {
      const da = getDate(a),
        db = getDate(b);
      return order === "oldest" ? da - db : db - da;
    });

    sorted.forEach((row) => tbody.appendChild(row));
    filterRows();
  }

  searchInput.addEventListener("input", filterRows);
  statusSelect.addEventListener("change", filterRows);
  categorySelect.addEventListener("change", filterRows);
  sortSelect.addEventListener("change", sortRows);

  /* ─────────────────────────────────────────────
       MODAL ELEMENTS
    ───────────────────────────────────────────── */

  const modalOverlay = document.getElementById("modal-overlay");
  const modalClose = document.getElementById("modal-close");
  const modalCloseBtn = document.getElementById("modal-close-btn");
  const modalFooter = document.getElementById("modal-footer");

  const modalTitle = document.getElementById("modal-title");
  const modalIcon = document.getElementById("modal-svc-icon");
  const stripStatus = document.getElementById("strip-status");
  const stripSubmitted = document.getElementById("strip-submitted");
  const stripUpdated = document.getElementById("strip-updated");
  const stripDocs = document.getElementById("strip-docs");

  // Read view elements
  const detailFullName = document.getElementById("detail-full-name");
  const detailContact = document.getElementById("detail-contact");
  const detailEmail = document.getElementById("detail-email");
  const detailAddress = document.getElementById("detail-address");
  const detailPurpose = document.getElementById("detail-purpose");
  const detailDocsList = document.getElementById("detail-documents-list");

  // Timeline / notes
  const reqTimeline = document.getElementById("req-timeline");
  const skBlock = document.getElementById("sk-response-block");
  const noRespBlock = document.getElementById("no-response-block");
  const skNotesThread = document.getElementById("sk-notes-thread");

  // Action required banner
  const actionRequiredBanner = document.getElementById(
    "action-required-banner"
  );

  // Fulfillment file block
  const fulfillmentBlock = document.getElementById("fulfillment-block");
  const fulfillmentFileWrap = document.getElementById("fulfillment-file-wrap");

  // Edit view elements
  const submissionReadView = document.getElementById("submission-read-view");
  const submissionEditView = document.getElementById("submission-edit-view");
  const editFullName = document.getElementById("edit-full-name");
  const editContact = document.getElementById("edit-contact");
  const editEmail = document.getElementById("edit-email");
  const editAddress = document.getElementById("edit-address");
  const editPurpose = document.getElementById("edit-purpose");
  const existingDocsList = document.getElementById("existing-docs-list");
  const editFileInput = document.getElementById("edit-docs-input");
  const editFileBrowseBtn = document.getElementById("edit-file-browse-btn");
  const editFileList = document.getElementById("edit-file-list");
  const editFileDropZone = document.getElementById("edit-file-drop-zone");

  // Resubmit confirm modal
  const resubmitConfirmOverlay = document.getElementById(
    "resubmit-confirm-overlay"
  );
  const resubmitCancelBtn = document.getElementById("resubmit-cancel-btn");
  const resubmitConfirmBtn = document.getElementById("resubmit-confirm-btn");

  // Toast
  const toast = document.getElementById("req-toast");

  // State
  let currentRow = null;
  let isEditMode = false;
  let newFiles = []; // DataTransfer-style list of new File objects
  let removedDocIds = []; // IDs of existing docs the user wants removed

  /* ─────────────────────────────────────────────
       HELPERS
    ───────────────────────────────────────────── */

  const iconMap = {
    medical: "🏥",
    dental: "🩺",
    scholarship: "🏅",
    education: "🎓",
    livelihood: "🛠️",
    skills: "🔧",
    legal: "⚖️",
    assistance: "🤝",
  };

  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str ?? "";
    return div.innerHTML;
  }

  function formatStatus(status) {
    const map = {
      pending: "Pending",
      "under-review": "Under Review",
      "action-required": "Action Required",
      approved: "Approved",
      rejected: "Rejected",
      cancelled: "Cancelled",
    };
    return map[status] || status;
  }

  function showToast(msg, type = "success") {
    toast.textContent = msg;
    toast.className = `req-toast req-toast--${type}`;
    toast.style.display = "block";
    setTimeout(() => {
      toast.style.display = "none";
    }, 4000);
  }

  function showLoadingToast(msg) {
    // Inject spinner keyframe once
    if (!document.getElementById("req-toast-spin-style")) {
      const s = document.createElement("style");
      s.id = "req-toast-spin-style";
      s.textContent = `@keyframes req-spin{to{transform:rotate(360deg)}}`;
      document.head.appendChild(s);
    }
    toast.className = "req-toast req-toast--loading";
    toast.innerHTML = `
      <span style="display:inline-block;width:15px;height:15px;border:2px solid #93c5fd;
            border-top-color:#1d4ed8;border-radius:50%;vertical-align:middle;
            animation:req-spin 0.7s linear infinite;margin-right:8px;flex-shrink:0;"></span>
      <span>${msg}</span>`;
    toast.style.display = "flex";
    toast.style.alignItems = "center";
  }

  function dismissToast() {
    toast.style.display = "none";
    toast.innerHTML = "";
  }

  function formatFileSize(bytes) {
    if (!bytes) return "";
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
  }

  function fileIcon(mime) {
    if (!mime) return "📄";
    if (mime.startsWith("image/")) return "🖼️";
    if (mime === "application/pdf") return "📕";
    if (mime.includes("word")) return "📘";
    if (mime.includes("spreadsheet") || mime.includes("excel")) return "📗";
    return "📄";
  }

  /* ─────────────────────────────────────────────
       TIMELINE BUILDER
    ───────────────────────────────────────────── */

  const timelineConfig = {
    pending: [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-pending",
        icon: "🕐",
        label: "Awaiting SK Review",
        isPending: true,
      },
    ],
    "under-review": [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-review",
        icon: "🔍",
        label: "Under SK Review",
        isPending: false,
      },
      {
        dot: "dot-pending",
        icon: "🕐",
        label: "Awaiting Decision",
        isPending: true,
      },
    ],
    "action-required": [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-review",
        icon: "🔍",
        label: "Reviewed by SK",
        isPending: false,
      },
      {
        dot: "dot-pending",
        icon: "⚠️",
        label: "Action Required",
        isPending: true,
      },
    ],
    approved: [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-review",
        icon: "🔍",
        label: "Under SK Review",
        isPending: false,
      },
      {
        dot: "dot-approved",
        icon: "✅",
        label: "Request Approved",
        isPending: false,
      },
    ],
    rejected: [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-review",
        icon: "🔍",
        label: "Under SK Review",
        isPending: false,
      },
      {
        dot: "dot-rejected",
        icon: "❌",
        label: "Request Rejected",
        isPending: false,
      },
    ],
    cancelled: [
      {
        dot: "dot-submitted",
        icon: "📨",
        label: "Request Submitted",
        isPending: false,
      },
      {
        dot: "dot-rejected",
        icon: "🚫",
        label: "Request Cancelled",
        isPending: false,
      },
    ],
  };

  function buildTimeline(status, submittedDate, updatedDate) {
    const steps = timelineConfig[status] || timelineConfig["pending"];
    reqTimeline.innerHTML = "";

    steps.forEach((step, i) => {
      let dateStr = "";
      if (i === 0) dateStr = submittedDate;
      else if (i === steps.length - 1 && !step.isPending) dateStr = updatedDate;

      const item = document.createElement("div");
      item.className = "timeline-item";
      item.innerHTML = `
                <div class="timeline-dot ${step.dot}">${step.icon}</div>
                <div class="timeline-content">
                    <div class="timeline-label">${step.label}</div>
                    ${
                      dateStr
                        ? `<div class="timeline-date">${escapeHtml(
                            dateStr
                          )}</div>`
                        : `<div class="timeline-date" style="font-style:italic;opacity:.6;">Pending</div>`
                    }
                </div>
            `;
      reqTimeline.appendChild(item);
    });
  }

  /* ─────────────────────────────────────────────
       DOCUMENTS LIST (read view)
    ───────────────────────────────────────────── */

  const MAX_DOC_NAME = 40;

  function truncateName(name) {
    return name.length > MAX_DOC_NAME
      ? name.slice(0, MAX_DOC_NAME - 1) + "…"
      : name;
  }

  function renderDocumentsList(documents) {
    if (!documents || documents.length === 0) {
      detailDocsList.innerHTML =
        '<span style="color:var(--text-muted);font-size:13px;">No documents uploaded.</span>';
      return;
    }

    detailDocsList.innerHTML = documents
      .map((doc) => {
        const icon = fileIcon(doc.mime_type);
        const size = formatFileSize(doc.file_size);
        const fullName = doc.file_name || "";
        const name = escapeHtml(truncateName(fullName));
        const title = escapeHtml(fullName);
        const rawPath = doc.file_path || "";
        const basePath = rawPath ? "/SKonnect/" + rawPath.replace(/^\/+/, "") : "";
        const path = escapeHtml(basePath);
        const isPreviewable = doc.mime_type && (doc.mime_type.startsWith("image/") || doc.mime_type === "application/pdf");

        const previewBtn = (path && isPreviewable)
          ? `<button class="doc-preview-btn" data-path="${path}" data-name="${title}" data-type="${escapeHtml(doc.mime_type)}" title="Quick View">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
             </button>`
          : "";

        const downloadLink = path
          ? `<a class="doc-link" href="${path}" download="${title}" title="Download ${title}">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
               </svg>
             </a>`
          : "";

        return `
          <div class="doc-item">
            <span class="doc-icon">${icon}</span>
            <div class="doc-info">
              <span class="doc-name" title="${title}">${name}</span>
              ${size ? `<span class="doc-size">${size}</span>` : ""}
            </div>
            ${previewBtn}
            ${downloadLink}
          </div>`;
      })
      .join("");
  }

  /* ─────────────────────────────────────────────
       SK NOTES THREAD
    ───────────────────────────────────────────── */

  function renderNotesThread(notes) {
    if (!notes || notes.length === 0) {
      skNotesThread.innerHTML = "";
      return;
    }

    skNotesThread.innerHTML = notes
      .map((note) => {
        const officerName = escapeHtml(note.officer_name || "SK Officer");
        const noteText = escapeHtml(note.note || "");
        const rawNote = note.note || "";
        const createdAt = note.created_at
          ? new Date(note.created_at).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
              hour: "numeric",
              minute: "2-digit",
            })
          : "—";

        let cardModifier = "";
        if (rawNote.startsWith("Request Approved")) {
          cardModifier = " sk-response-card--approved";
        } else if (rawNote.startsWith("Request Declined")) {
          cardModifier = " sk-response-card--rejected";
        }

        return `
                <div class="sk-response-card${cardModifier}">
                    <div class="sk-response-header">
                        <div class="sk-avatar">SK</div>
                        <div>
                            <strong>${officerName}</strong>
                            <span class="resp-date">${createdAt}</span>
                        </div>
                    </div>
                    <p class="sk-response-text">${noteText}</p>
                </div>
            `;
      })
      .join("");
  }

  function renderFulfillmentFile(filePath) {
    if (!filePath) {
      fulfillmentBlock.style.display = "none";
      fulfillmentFileWrap.innerHTML = "";
      return;
    }

    const basePath = "/SKonnect/" + filePath.replace(/^\/+/, "");
    const fileName = filePath.split("/").pop();
    const ext = fileName.split(".").pop().toLowerCase();
    const isImage = ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);
    const isPdf = ext === "pdf";
    const icon = isImage ? "🖼️" : isPdf ? "📕" : "📄";
    const isPreviewable = isImage || isPdf;

    const previewBtn = isPreviewable
      ? `<button class="doc-preview-btn" data-path="${escapeHtml(basePath)}" data-name="${escapeHtml(fileName)}" data-type="${isImage ? "image/" + ext : "application/pdf"}" title="Quick View">
           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
         </button>`
      : "";

    fulfillmentFileWrap.innerHTML = `
      <div class="doc-item">
        <span class="doc-icon">${icon}</span>
        <div class="doc-info">
          <span class="doc-name" title="${escapeHtml(fileName)}">${escapeHtml(fileName)}</span>
          <span class="doc-size" style="color:var(--success,#16a34a);font-size:11px;">Sent by SK Officer</span>
        </div>
        ${previewBtn}
        <a class="doc-link" href="${escapeHtml(basePath)}" download="${escapeHtml(fileName)}" title="Download">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        </a>
      </div>`;

    fulfillmentBlock.style.display = "block";

    fulfillmentFileWrap.querySelectorAll(".doc-preview-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        openFilePreview(btn.dataset.path, btn.dataset.name, btn.dataset.type);
      });
    });
  }

  function renderExistingDocs(documents) {
    if (!documents || documents.length === 0) {
      existingDocsList.innerHTML =
        '<span style="color:var(--text-muted);font-size:.85rem;">No documents on file.</span>';
      return;
    }

    existingDocsList.innerHTML = documents
      .map((doc) => {
        const icon = fileIcon(doc.mime_type);
        const size = formatFileSize(doc.file_size);
        const name = escapeHtml(doc.file_name);
        const id = doc.id;
        return `
          <label class="existing-doc-item">
            <input type="checkbox" class="existing-doc-checkbox existing-doc-check" value="${id}" checked>
            <span class="doc-icon">${icon}</span>
            <div class="existing-doc-info">
              <span class="existing-doc-name" title="${name}">${escapeHtml(truncateName(doc.file_name))}</span>
              ${size ? `<span class="existing-doc-size">${size}</span>` : ""}
            </div>
          </label>
        `;
      })
      .join("");
  }

  /* ─────────────────────────────────────────────
       NEW FILES PICKER (edit mode)
    ───────────────────────────────────────────── */

  function renderNewFileList() {
    editFileList.innerHTML = "";
    newFiles.forEach((file, i) => {
      const li = document.createElement("li");
      li.className = "file-list-item";
      li.innerHTML = `
        <div class="file-list-icon">${fileIcon(file.type)}</div>
        <div class="file-list-info">
          <span class="file-list-name" title="${escapeHtml(file.name)}">${escapeHtml(truncateName(file.name))}</span>
          <span class="file-list-meta">${formatFileSize(file.size)}</span>
        </div>
        <span class="file-list-new-badge">New</span>
        <button type="button" class="file-remove-btn file-item-remove" data-index="${i}" aria-label="Remove file">&times;</button>
      `;
      editFileList.appendChild(li);
    });
  }

  editFileBrowseBtn?.addEventListener("click", () => editFileInput.click());

  editFileInput?.addEventListener("change", () => {
    Array.from(editFileInput.files).forEach((f) => {
      if (f.size > 5 * 1024 * 1024) {
        showToast(`"${f.name}" exceeds 5 MB and was skipped.`, "error");
        return;
      }
      newFiles.push(f);
    });
    editFileInput.value = "";
    renderNewFileList();
  });

  editFileDropZone?.addEventListener("dragover", (e) => {
    e.preventDefault();
    editFileDropZone.classList.add("dragover");
  });
  editFileDropZone?.addEventListener("dragleave", () =>
    editFileDropZone.classList.remove("dragover")
  );
  editFileDropZone?.addEventListener("drop", (e) => {
    e.preventDefault();
    editFileDropZone.classList.remove("dragover");
    Array.from(e.dataTransfer.files).forEach((f) => {
      if (f.size > 5 * 1024 * 1024) {
        showToast(`"${f.name}" exceeds 5 MB.`, "error");
        return;
      }
      newFiles.push(f);
    });
    renderNewFileList();
  });

  editFileList?.addEventListener("click", (e) => {
    const btn = e.target.closest(".file-item-remove");
    if (!btn) return;
    const idx = parseInt(btn.dataset.index, 10);
    newFiles.splice(idx, 1);
    renderNewFileList();
  });

  /* ─────────────────────────────────────────────
       MODAL FOOTER BUTTONS
    ───────────────────────────────────────────── */

  function renderFooterButtons(status) {
    modalFooter.innerHTML = "";

    const closeBtn = document.createElement("button");
    closeBtn.className = "btn-ghost-portal";
    closeBtn.id = "modal-close-btn";
    closeBtn.type = "button";
    closeBtn.textContent = "Close";
    closeBtn.addEventListener("click", closeModal);

    const cancellableStatuses = ["pending", "action-required"];

    if (status === "action-required") {
      if (!isEditMode) {
        const cancelBtn = document.createElement("button");
        cancelBtn.className = "btn-danger-portal";
        cancelBtn.type = "button";
        cancelBtn.textContent = "Cancel Request";
        cancelBtn.addEventListener("click", showCancelConfirm);

        const editBtn = document.createElement("button");
        editBtn.className = "btn-primary-portal";
        editBtn.type = "button";
        editBtn.innerHTML = "Edit Submission";
        editBtn.addEventListener("click", enterEditMode);

        modalFooter.appendChild(closeBtn);
        modalFooter.appendChild(cancelBtn);
        modalFooter.appendChild(editBtn);
      } else {
        const cancelEditBtn = document.createElement("button");
        cancelEditBtn.className = "btn-ghost-portal";
        cancelEditBtn.type = "button";
        cancelEditBtn.textContent = "Cancel Edit";
        cancelEditBtn.addEventListener("click", exitEditMode);

        const resubmitBtn = document.createElement("button");
        resubmitBtn.className = "btn-primary-portal";
        resubmitBtn.type = "button";
        resubmitBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>&nbsp;
                <span> Resubmit Application</span>`;
        resubmitBtn.addEventListener("click", showResubmitConfirm);

        modalFooter.appendChild(cancelEditBtn);
        modalFooter.appendChild(resubmitBtn);
      }
    } else if (status === "pending") {
      const cancelBtn = document.createElement("button");
      cancelBtn.className = "btn-danger-portal";
      cancelBtn.type = "button";
      cancelBtn.textContent = "Cancel Request";
      cancelBtn.addEventListener("click", showCancelConfirm);

      modalFooter.appendChild(closeBtn);
      modalFooter.appendChild(cancelBtn);
    } else {
      modalFooter.appendChild(closeBtn);
    }
  }

  /* ─────────────────────────────────────────────
       EDIT MODE TOGGLE
    ───────────────────────────────────────────── */

  function enterEditMode() {
    isEditMode = true;
    newFiles = [];
    removedDocIds = [];

    const row = currentRow;
    editFullName.value = row.dataset.fullName || "";
    editContact.value = row.dataset.contact || "";
    editEmail.value = row.dataset.email || "";
    editAddress.value = row.dataset.address || "";
    editPurpose.value = row.dataset.purpose || "";

    let documents = [];
    try {
      documents = JSON.parse(row.dataset.documents || "[]");
    } catch (e) {}
    renderExistingDocs(documents);
    renderNewFileList();

    submissionReadView.style.display = "none";
    submissionEditView.style.display = "block";

    renderFooterButtons("action-required");
  }

  function exitEditMode() {
    isEditMode = false;
    newFiles = [];
    removedDocIds = [];
    editFileList.innerHTML = "";

    submissionEditView.style.display = "none";
    submissionReadView.style.display = "block";

    renderFooterButtons("action-required");
  }

  /* ─────────────────────────────────────────────
       RESUBMIT FLOW
    ───────────────────────────────────────────── */

  function showResubmitConfirm() {
    // Basic validation first
    const errors = validateEditForm();
    if (errors.length > 0) return; // errors already displayed inline

    resubmitConfirmOverlay.style.display = "flex";
  }

  resubmitCancelBtn?.addEventListener("click", () => {
    resubmitConfirmOverlay.style.display = "none";
  });

  resubmitConfirmBtn?.addEventListener("click", () => {
    resubmitConfirmOverlay.style.display = "none";
    doResubmit();
  });

  function validateEditForm() {
    const errors = [];

    const clearErr = (id) => {
      const el = document.getElementById(id);
      if (el) el.textContent = "";
    };
    const setErr = (id, msg) => {
      const el = document.getElementById(id);
      if (el) el.textContent = msg;
    };

    clearErr("edit-err-name");
    clearErr("edit-err-contact");
    clearErr("edit-err-email");
    clearErr("edit-err-address");

    if (!editFullName.value.trim()) {
      setErr("edit-err-name", "Full name is required.");
      errors.push("name");
    }
    if (!editContact.value.trim()) {
      setErr("edit-err-contact", "Contact number is required.");
      errors.push("contact");
    } else if (
      !/^(09|\+639)\d{9}$/.test(editContact.value.trim().replace(/\s/g, ""))
    ) {
      setErr(
        "edit-err-contact",
        "Enter a valid PH mobile number (e.g. 09XX XXX XXXX)."
      );
      errors.push("contact");
    }
    if (!editEmail.value.trim()) {
      setErr("edit-err-email", "Email address is required.");
      errors.push("email");
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(editEmail.value.trim())) {
      setErr("edit-err-email", "Enter a valid email address.");
      errors.push("email");
    }
    if (!editAddress.value.trim()) {
      setErr("edit-err-address", "Home address is required.");
      errors.push("address");
    }

    return errors;
  }

  async function doResubmit() {
    const row = currentRow;
    if (!row) return;

    const appId = row.dataset.id;

    // Collect IDs of docs to remove (unchecked boxes)
    removedDocIds = [];
    document.querySelectorAll(".existing-doc-check").forEach((cb) => {
      if (!cb.checked) removedDocIds.push(parseInt(cb.value, 10));
    });

    const formData = new FormData();
    formData.append("action", "reapply");
    formData.append("id", appId);
    formData.append("full_name", editFullName.value.trim());
    formData.append("contact", editContact.value.trim());
    formData.append("email", editEmail.value.trim());
    formData.append("address", editAddress.value.trim());
    formData.append("purpose", editPurpose.value.trim());
    formData.append("remove_docs", JSON.stringify(removedDocIds));

    newFiles.forEach((file) => formData.append("documents[]", file));

    // Disable the resubmit confirm button to prevent double-submit
    resubmitConfirmBtn.disabled = true;
    resubmitConfirmBtn.textContent = "Submitting…";

    try {
      const resp = await fetch("../../backend/routes/service_requests.php", {
        method: "POST",
        body: formData,
      });
      const data = await resp.json();

      if (data.success) {
        showToast("✅ Application resubmitted successfully!", "success");
        closeModal();
        // Reload the page after a short delay so the table reflects the updated status
        setTimeout(() => window.location.reload(), 1200);
      } else {
        const msg = Array.isArray(data.errors)
          ? data.errors.join(" ")
          : data.message || "Resubmission failed. Please try again.";
        showToast("❌ " + msg, "error");
      }
    } catch (err) {
      console.error("Resubmit error:", err);
      showToast("❌ Network error. Please try again.", "error");
    } finally {
      resubmitConfirmBtn.disabled = false;
      resubmitConfirmBtn.textContent = "Yes, Resubmit";
    }
  }

  /* ─────────────────────────────────────────────
       CANCEL FLOW
    ───────────────────────────────────────────── */

  const cancelConfirmOverlay = document.getElementById("cancel-confirm-overlay");
  const cancelConfirmBackBtn = document.getElementById("cancel-confirm-back-btn");
  const cancelConfirmProceedBtn = document.getElementById("cancel-confirm-proceed-btn");

  function showCancelConfirm() {
    cancelConfirmOverlay.style.display = "flex";
  }

  cancelConfirmBackBtn?.addEventListener("click", () => {
    cancelConfirmOverlay.style.display = "none";
  });

  cancelConfirmProceedBtn?.addEventListener("click", () => {
    cancelConfirmOverlay.style.display = "none";
    doCancel();
  });

  async function doCancel() {
    const row = currentRow;
    if (!row) return;

    cancelConfirmProceedBtn.disabled = true;
    cancelConfirmProceedBtn.textContent = "Cancelling…";
    showLoadingToast("Cancelling your request…");

    const formData = new FormData();
    formData.append("action", "cancel");
    formData.append("id", row.dataset.id);

    try {
      const resp = await fetch("../../backend/routes/service_requests.php", {
        method: "POST",
        body: formData,
      });
      const data = await resp.json();
      dismissToast();

      if (data.success) {
        showToast("✅ Request cancelled. A confirmation has been sent to your email.", "success");
        closeModal();
        setTimeout(() => window.location.reload(), 1800);
      } else {
        showToast("❌ " + (data.message || "Failed to cancel. Please try again."), "error");
      }
    } catch (err) {
      console.error("Cancel error:", err);
      dismissToast();
      showToast("❌ Network error. Please try again.", "error");
    } finally {
      cancelConfirmProceedBtn.disabled = false;
      cancelConfirmProceedBtn.textContent = "Yes, Cancel Request";
    }
  }

  /* ─────────────────────────────────────────────
       FILE PREVIEW MODAL
    ───────────────────────────────────────────── */

  const filePreviewOverlay = document.getElementById("req-file-preview-overlay");
  const filePreviewClose = document.getElementById("req-file-preview-close");
  const filePreviewName = document.getElementById("file-preview-name");
  const filePreviewBody = document.getElementById("file-preview-body");

  detailDocsList.addEventListener("click", (e) => {
    const btn = e.target.closest(".doc-preview-btn");
    if (!btn) return;
    e.preventDefault();
    openFilePreview(btn.dataset.path, btn.dataset.name, btn.dataset.type);
  });

  function openFilePreview(path, name, type) {
    filePreviewName.textContent = name;
    filePreviewBody.innerHTML = '<div class="req-file-preview-loading">Loading...</div>';
    filePreviewOverlay.style.display = "flex";

    setTimeout(() => {
      if (type && type.startsWith("image/")) {
        filePreviewBody.innerHTML = `<img src="${path}" alt="${escapeHtml(name)}" class="req-file-preview-image">`;
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

  filePreviewClose?.addEventListener("click", closeFilePreview);
  filePreviewOverlay?.addEventListener("click", (e) => {
    if (e.target === filePreviewOverlay) closeFilePreview();
  });

  /* ─────────────────────────────────────────────
       OPEN / CLOSE MODAL
    ───────────────────────────────────────────── */

  function openModal(row) {
    currentRow = row;
    isEditMode = false;
    newFiles = [];
    removedDocIds = [];

    const service = row.dataset.service || "Service Request";
    const category = row.dataset.category || "";
    const status = row.dataset.status || "pending";
    const submitted = row.dataset.submitted || "—";
    const updated = row.dataset.updated || "—";
    const purpose = row.dataset.purpose || "";
    const fullName = row.dataset.fullName || "—";
    const contact = row.dataset.contact || "—";
    const email = row.dataset.email || "—";
    const address = row.dataset.address || "—";
    const docs = row.dataset.docs || "—";
    const fulfillmentFile = row.dataset.fulfillmentFile || "";

    let notes = [];
    try {
      notes = JSON.parse(row.dataset.notes || "[]");
    } catch (e) {}

    let documents = [];
    try {
      documents = JSON.parse(row.dataset.documents || "[]");
    } catch (e) {}

    // Header
    modalTitle.textContent = service;
    modalIcon.textContent = iconMap[category] || "📋";

    // Status strip
    stripStatus.innerHTML = `<span class="req-status-badge status-${status}">${formatStatus(
      status
    )}</span>`;
    stripSubmitted.textContent = submitted;
    stripUpdated.textContent = updated;
    stripDocs.textContent = docs;

    // Action required banner
    actionRequiredBanner.style.display =
      status === "action-required" ? "flex" : "none";

    // My Submission (read view)
    submissionReadView.style.display = "block";
    submissionEditView.style.display = "none";

    detailFullName.textContent = fullName || "—";
    detailContact.textContent = contact || "—";
    detailEmail.textContent = email || "—";
    detailAddress.textContent = address || "—";
    detailPurpose.textContent = purpose || "(No description provided)";

    renderDocumentsList(documents);

    // Fulfillment file
    renderFulfillmentFile(fulfillmentFile);

    // Timeline
    buildTimeline(status, submitted, updated);

    // SK Notes Thread
    if (notes && notes.length > 0) {
      skBlock.style.display = "block";
      noRespBlock.style.display = "none";
      renderNotesThread(notes);
    } else {
      skBlock.style.display = "none";
      noRespBlock.style.display = "block";
    }

    // Footer buttons
    renderFooterButtons(status);

    modalOverlay.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeModal() {
    modalOverlay.style.display = "none";
    document.body.style.overflow = "";
    isEditMode = false;
    newFiles = [];
    removedDocIds = [];
    currentRow = null;

    // Reset edit form
    submissionEditView.style.display = "none";
    submissionReadView.style.display = "block";
    editFileList.innerHTML = "";
  }

  // Attach open to all view buttons
  document.querySelectorAll(".btn-view-req").forEach((btn) => {
    btn.addEventListener("click", () => {
      const row = btn.closest(".req-row");
      if (row) openModal(row);
    });
  });

  modalClose?.addEventListener("click", closeModal);
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (filePreviewOverlay.style.display !== "none") {
        closeFilePreview();
        return;
      }
      if (cancelConfirmOverlay.style.display !== "none") {
        cancelConfirmOverlay.style.display = "none";
        return;
      }
      closeModal();
      resubmitConfirmOverlay.style.display = "none";
    }
  });

  /* ─────────────────────────────────────────────
       PAGINATION
    ───────────────────────────────────────────── */

  const ROWS_PER_PAGE = 10;
  const pageNumbers = document.getElementById("page-numbers");
  const prevBtn = document.getElementById("prev-btn");
  const nextBtn = document.getElementById("next-btn");
  let currentPage = 1;

  function applyPagination() {
    const visible = rows.filter((r) => r.style.display !== "none");
    const total = Math.max(1, Math.ceil(visible.length / ROWS_PER_PAGE));
    currentPage = Math.min(currentPage, total);

    visible.forEach((row, i) => {
      const pageOfRow = Math.floor(i / ROWS_PER_PAGE) + 1;
      row.style.display = pageOfRow === currentPage ? "" : "none";
    });

    pageNumbers.innerHTML = "";
    for (let i = 1; i <= total; i++) {
      const btn = document.createElement("button");
      btn.className = "page-num" + (i === currentPage ? " active" : "");
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        applyPagination();
      });
      pageNumbers.appendChild(btn);
    }

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === total;
  }

  prevBtn?.addEventListener("click", () => {
    currentPage--;
    applyPagination();
  });
  nextBtn?.addEventListener("click", () => {
    currentPage++;
    applyPagination();
  });

  function filterAndPage() {
    filterRows();
    currentPage = 1;
    applyPagination();
  }

  searchInput.removeEventListener("input", filterRows);
  statusSelect.removeEventListener("change", filterRows);
  categorySelect.removeEventListener("change", filterRows);

  searchInput.addEventListener("input", filterAndPage);
  statusSelect.addEventListener("change", filterAndPage);
  categorySelect.addEventListener("change", filterAndPage);

  applyPagination();
});