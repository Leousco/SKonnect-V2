/**
 * officer_services.js
 * scripts/management/officer/officer_services.js
 *
 * Handles: search/filter, multi-step Add/Edit modal, requirements live preview,
 * attachment drag & drop, capacity toggle, service type conditional fields,
 * toggle active/inactive, delete confirmation, toast notifications.
 */

 document.addEventListener("DOMContentLoaded", () => {
  /* ══════════════════════════════════════════════════════════
     ELEMENT REFS
  ══════════════════════════════════════════════════════════ */

  const grid = document.getElementById("svc-grid");
  const noResults = document.getElementById("svc-no-results");
  const countLabel = document.getElementById("svc-count");

  const searchInput = document.getElementById("svc-search");
  const selCategory = document.getElementById("svc-category");
  const selType = document.getElementById("svc-type");
  const selStatus = document.getElementById("svc-status");

  // Modal
  const modalOverlay = document.getElementById("svc-modal-overlay");
  const modalTitle = document.getElementById("svc-modal-title");
  const modalClose = document.getElementById("svc-modal-close");
  const modalCancel = document.getElementById("svc-modal-cancel");
  const saveBtn = document.getElementById("svc-save-btn");
  const nextBtn = document.getElementById("svc-next-btn");
  const prevBtn = document.getElementById("svc-prev-btn");

  // Tab elements
  const tabs = document.querySelectorAll(".svc-tab");
  const panels = document.querySelectorAll(".svc-tab-panel");

  // Tab 1 fields
  const fieldId = document.getElementById("svc-id");
  const fieldName = document.getElementById("svc-name");
  const fieldCategory = document.getElementById("svc-category-field");
  const fieldTypeSelect = document.getElementById("svc-type-field");
  const fieldDesc = document.getElementById("svc-desc");
  const fieldApprovalMsg = document.getElementById("svc-approval-msg");
  const fieldEligibility = document.getElementById("svc-eligibility");
  const fieldTime = document.getElementById("svc-time");
  const fieldContact = document.getElementById("svc-contact");
  const fieldStatus = document.getElementById("svc-status-field");
  const contactGroup = document.getElementById("svc-contact-group");
  const approvalGroup = document.getElementById("svc-approval-group");

  // Tab 2 fields
  const fieldRequirements = document.getElementById("svc-requirements");
  const previewToggle = document.getElementById("svc-preview-toggle");
  const reqPreviewWrap = document.getElementById("svc-req-preview-wrap");
  const reqPreview = document.getElementById("svc-req-preview");
  const attachmentBox = document.getElementById("svc-attachment-box");
  const attachmentInput = document.getElementById("svc-attachment-input");
  const attachmentEmpty = document.getElementById("svc-attachment-empty");
  const attachmentList = document.getElementById("svc-attachment-list");
  const attachmentAddMore = document.getElementById("svc-attachment-add-more");
  const attachmentAddMoreInput = document.getElementById(
    "svc-attachment-add-more-input"
  );
  const existingAttachment = document.getElementById("svc-existing-attachment");

  // Tab 3 fields
  const capacityToggle = document.getElementById("svc-capacity-toggle");
  const capacityInputWrap = document.getElementById("svc-capacity-input-wrap");
  const fieldMaxCapacity = document.getElementById("svc-max-capacity");

  // Error spans
  const errName = document.getElementById("err-svc-name");
  const errCategory = document.getElementById("err-svc-category");
  const errType = document.getElementById("err-svc-type");
  const errDesc = document.getElementById("err-svc-desc");
  const errApprovalMsg = document.getElementById("err-svc-approval-msg");
  const errContact = document.getElementById("err-svc-contact");
  const errCapacity = document.getElementById("err-svc-capacity");

  // Confirm delete modal
  const confirmOverlay = document.getElementById("svc-confirm-overlay");
  const confirmBody = document.getElementById("svc-confirm-body");
  const confirmDelete = document.getElementById("svc-confirm-delete");
  const confirmCancel = document.getElementById("svc-confirm-cancel");

  // Toast
  const toast = document.getElementById("svc-toast");

  /* ══════════════════════════════════════════════════════════
     TOAST
  ══════════════════════════════════════════════════════════ */

  let toastTimer = null;

  function showToast(message, type = "success") {
    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.className = `svc-toast svc-toast--${type} svc-toast--show`;
    toastTimer = setTimeout(
      () => toast.classList.remove("svc-toast--show"),
      3400
    );
  }

  /* ══════════════════════════════════════════════════════════
     FILTER / SEARCH
  ══════════════════════════════════════════════════════════ */

  function getCards() {
    return Array.from(grid.querySelectorAll(".svc-card"));
  }

  function applyFilters() {
    const q = searchInput.value.toLowerCase().trim();
    const category = selCategory.value;
    const type = selType.value;
    const status = selStatus.value;

    let visible = 0;

    getCards().forEach((card) => {
      const name = card.dataset.name || "";
      const matchQ = !q || name.includes(q);
      const matchCat = category === "all" || card.dataset.category === category;
      const matchTyp = type === "all" || card.dataset.type === type;
      const matchSt = status === "all" || card.dataset.status === status;

      const show = matchQ && matchCat && matchTyp && matchSt;
      card.style.display = show ? "" : "none";
      if (show) visible++;
    });

    countLabel.textContent = `Showing ${visible} service${
      visible !== 1 ? "s" : ""
    }`;
    noResults.style.display = visible === 0 ? "flex" : "none";
  }

  searchInput.addEventListener("input", applyFilters);
  selCategory.addEventListener("change", applyFilters);
  selType.addEventListener("change", applyFilters);
  selStatus.addEventListener("change", applyFilters);

  /* ══════════════════════════════════════════════════════════
     TAB NAVIGATION
  ══════════════════════════════════════════════════════════ */

  let currentTab = 1;
  const TOTAL_TABS = 3;

  function goToTab(n) {
    currentTab = n;

    tabs.forEach((tab, i) => {
      tab.classList.toggle("active", i + 1 === n);
      if (i + 1 < n) tab.classList.add("completed");
      else tab.classList.remove("completed");
    });

    panels.forEach((panel, i) => {
      panel.classList.toggle("active", i + 1 === n);
    });

    prevBtn.style.display = n > 1 ? "" : "none";
    nextBtn.style.display = n < TOTAL_TABS ? "" : "none";
    saveBtn.style.display = n === TOTAL_TABS ? "" : "none";
  }

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = parseInt(tab.dataset.tab);
      if (target < currentTab) {
        goToTab(target);
        return;
      }
      // Validate before jumping forward
      if (validateTab(currentTab)) goToTab(target);
    });
  });

  nextBtn.addEventListener("click", () => {
    if (validateTab(currentTab)) goToTab(currentTab + 1);
  });

  prevBtn.addEventListener("click", () => {
    goToTab(currentTab - 1);
  });

  /* ══════════════════════════════════════════════════════════
     VALIDATION
  ══════════════════════════════════════════════════════════ */

  function clearErrors() {
    [errName, errCategory, errType, errDesc, errApprovalMsg, errContact, errCapacity].forEach(
      (el) => {
        if (el) el.textContent = "";
      }
    );
    document
      .querySelectorAll(
        ".svc-input.error, .svc-select-input.error, .svc-textarea.error"
      )
      .forEach((el) => el.classList.remove("error"));
  }

  function setError(el, errEl, msg) {
    if (errEl) errEl.textContent = msg;
    if (el) el.classList.add("error");
  }

  function validateTab(tabNum) {
    let valid = true;

    if (tabNum === 1) {
      if (!fieldName.value.trim()) {
        setError(fieldName, errName, "Service name is required.");
        valid = false;
      }
      if (!fieldCategory.value) {
        setError(fieldCategory, errCategory, "Please select a category.");
        valid = false;
      }
      if (!fieldTypeSelect.value) {
        setError(fieldTypeSelect, errType, "Please select a service type.");
        valid = false;
      }
      if (!fieldDesc.value.trim()) {
        setError(fieldDesc, errDesc, "Description is required.");
        valid = false;
      }
      if (!fieldApprovalMsg.value.trim() && fieldTypeSelect.value !== "info") {
        setError(fieldApprovalMsg, errApprovalMsg, "Approval message is required.");
        valid = false;
      }
      if (fieldTypeSelect.value === "info" && !fieldContact.value.trim()) {
        setError(
          fieldContact,
          errContact,
          "Contact info is required for Information & Contact services."
        );
        valid = false;
      }
    }

    if (tabNum === 3) {
      if (capacityToggle.checked) {
        const cap = parseInt(fieldMaxCapacity.value);
        if (!fieldMaxCapacity.value || isNaN(cap) || cap < 1) {
          setError(
            fieldMaxCapacity,
            errCapacity,
            "Please enter a valid capacity (minimum 1)."
          );
          valid = false;
        }
      }
    }

    return valid;
  }

  function validateAll() {
    return validateTab(1) && validateTab(3);
  }

  /* ══════════════════════════════════════════════════════════
     SERVICE TYPE → CONDITIONAL FIELDS
  ══════════════════════════════════════════════════════════ */

  function onTypeChange() {
    const type = fieldTypeSelect.value;
    const isInfo = type === "info";

    contactGroup.style.display = isInfo ? "" : "none";
    if (!isInfo && errContact) errContact.textContent = "";

    approvalGroup.style.display = isInfo ? "none" : "";
    if (isInfo) {
      fieldApprovalMsg.value = "";
      if (errApprovalMsg) errApprovalMsg.textContent = "";
      fieldApprovalMsg.classList.remove("error");
    }
  }

  fieldTypeSelect.addEventListener("change", onTypeChange);

  /* ══════════════════════════════════════════════════════════
     REQUIREMENTS PREVIEW
  ══════════════════════════════════════════════════════════ */

  function buildReqPreview(raw) {
    if (!raw.trim())
      return '<em style="color:var(--off-text-muted);font-size:12px;">Nothing entered yet.</em>';

    const lines = raw.split("\n");
    let html = "";
    let inList = false;

    lines.forEach((line) => {
      const trimmed = line.trim();
      if (!trimmed) {
        if (inList) {
          html += "</ul>";
          inList = false;
        }
        return;
      }
      if (/^[-•*]/.test(trimmed)) {
        if (!inList) {
          html += "<ul>";
          inList = true;
        }
        html += `<li>${escHtml(trimmed.replace(/^[-•*]\s*/, ""))}</li>`;
      } else {
        if (inList) {
          html += "</ul>";
          inList = false;
        }
        html += `<p>${escHtml(trimmed)}</p>`;
      }
    });

    if (inList) html += "</ul>";
    return html;
  }

  function updatePreview() {
    reqPreview.innerHTML = buildReqPreview(fieldRequirements.value);
  }

  fieldRequirements.addEventListener("input", () => {
    if (reqPreviewWrap.style.display !== "none") updatePreview();
  });

  previewToggle.addEventListener("click", () => {
    const showing = reqPreviewWrap.style.display !== "none";
    reqPreviewWrap.style.display = showing ? "none" : "";
    if (!showing) updatePreview();
    previewToggle.innerHTML = showing
      ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg> Preview`
      : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg> Hide`;
  });

  /* ══════════════════════════════════════════════════════════
     MULTI-ATTACHMENT HANDLING
  ══════════════════════════════════════════════════════════ */

  let selectedFiles = [];
  let existingFiles = [];

  const FILE_ICON_SVG = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>`;
  const REMOVE_ICON_SVG = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>`;

  const ALLOWED_TYPES = [
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  ];

  function isAllowedFile(file) {
    return (
      ALLOWED_TYPES.includes(file.type) ||
      file.name.match(/\.(pdf|doc|docx|xlsx)$/i)
    );
  }

  function renderAttachmentList() {
    attachmentList.innerHTML = "";

    const hasItems = selectedFiles.length > 0 || existingFiles.length > 0;
    attachmentEmpty.style.display = hasItems ? "none" : "";
    attachmentAddMore.style.display = hasItems ? "" : "none";

    existingFiles.forEach((name, idx) => {
      const item = document.createElement("div");
      item.className = "svc-attachment-item";
      item.innerHTML = `
        <div class="svc-attachment-item-icon">${FILE_ICON_SVG}</div>
        <div class="svc-attachment-item-info">
          <span class="svc-attachment-item-name">${escHtml(name)}</span>
          <span class="svc-attachment-item-size">Existing file</span>
        </div>
        <button type="button" class="svc-attachment-item-remove" data-existing="${idx}" title="Remove">${REMOVE_ICON_SVG}</button>`;
      item
        .querySelector(".svc-attachment-item-remove")
        .addEventListener("click", () => {
          existingFiles.splice(idx, 1);
          renderAttachmentList();
        });
      attachmentList.appendChild(item);
    });

    selectedFiles.forEach((file, idx) => {
      const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
      const item = document.createElement("div");
      item.className = "svc-attachment-item";
      item.innerHTML = `
        <div class="svc-attachment-item-icon">${FILE_ICON_SVG}</div>
        <div class="svc-attachment-item-info">
          <span class="svc-attachment-item-name">${escHtml(file.name)}</span>
          <span class="svc-attachment-item-size">${sizeMB} MB</span>
        </div>
        <button type="button" class="svc-attachment-item-remove" title="Remove">${REMOVE_ICON_SVG}</button>`;
      item
        .querySelector(".svc-attachment-item-remove")
        .addEventListener("click", () => {
          selectedFiles.splice(idx, 1);
          renderAttachmentList();
        });
      attachmentList.appendChild(item);
    });
  }

  function addFiles(fileList) {
    let rejected = 0;
    Array.from(fileList).forEach((file) => {
      if (!isAllowedFile(file)) {
        rejected++;
        return;
      }
      if (file.size > 10 * 1024 * 1024) {
        showToast(`"${file.name}" exceeds 10MB and was skipped.`, "warning");
        return;
      }
      const duplicate = selectedFiles.some(
        (f) => f.name === file.name && f.size === file.size
      );
      if (!duplicate) selectedFiles.push(file);
    });
    if (rejected)
      showToast("Some files were skipped (unsupported type).", "warning");
    renderAttachmentList();
  }

  function clearAttachment() {
    selectedFiles = [];
    existingFiles = [];
    attachmentInput.value = "";
    if (attachmentAddMoreInput) attachmentAddMoreInput.value = "";
    renderAttachmentList();
  }

  attachmentInput.addEventListener("change", () => {
    if (attachmentInput.files.length) addFiles(attachmentInput.files);
    attachmentInput.value = "";
  });

  if (attachmentAddMoreInput) {
    attachmentAddMoreInput.addEventListener("change", () => {
      if (attachmentAddMoreInput.files.length)
        addFiles(attachmentAddMoreInput.files);
      attachmentAddMoreInput.value = "";
    });
  }

  attachmentAddMore.addEventListener("click", (e) => {
    if (!e.target.closest("label") && e.target !== attachmentAddMoreInput) {
      attachmentAddMoreInput.click();
    }
  });

  attachmentEmpty.addEventListener("click", (e) => {
    if (e.target !== attachmentInput && !e.target.closest("label")) {
      attachmentInput.click();
    }
  });

  attachmentBox.addEventListener("dragover", (e) => {
    e.preventDefault();
    attachmentBox.classList.add("drag-over");
  });

  attachmentBox.addEventListener("dragleave", () => {
    attachmentBox.classList.remove("drag-over");
  });

  attachmentBox.addEventListener("drop", (e) => {
    e.preventDefault();
    attachmentBox.classList.remove("drag-over");
    if (e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
  });

  /* ══════════════════════════════════════════════════════════
     CAPACITY TOGGLE
  ══════════════════════════════════════════════════════════ */

  capacityToggle.addEventListener("change", () => {
    capacityInputWrap.style.display = capacityToggle.checked ? "" : "none";
    if (!capacityToggle.checked) {
      fieldMaxCapacity.value = "";
      if (errCapacity) errCapacity.textContent = "";
    }
  });

  /* ══════════════════════════════════════════════════════════
     MODAL OPEN / CLOSE
  ══════════════════════════════════════════════════════════ */

  function resetModal() {
    clearErrors();
    goToTab(1);

    fieldId.value = "";
    fieldName.value = "";
    fieldCategory.value = "";
    fieldTypeSelect.value = "";
    fieldDesc.value = "";
    fieldApprovalMsg.value = "";
    fieldEligibility.value = "";
    fieldTime.value = "";
    fieldContact.value = "";
    fieldStatus.value = "active";
    fieldRequirements.value = "";

    contactGroup.style.display = "none";
    approvalGroup.style.display = "";
    reqPreviewWrap.style.display = "none";
    capacityToggle.checked = false;
    capacityInputWrap.style.display = "none";
    fieldMaxCapacity.value = "";

    clearAttachment();
    existingAttachment.value = "";
  }

  function openModal(mode = "add", data = null) {
    resetModal();

    if (mode === "edit" && data) {
      modalTitle.textContent = "Edit Service";

      fieldId.value = data.id || "";
      fieldName.value = data.name || "";
      fieldCategory.value = data.category || "";
      fieldTypeSelect.value = data.service_type || "";
      fieldDesc.value = data.description || "";
      fieldApprovalMsg.value = data.approval_message || "";
      fieldEligibility.value = data.eligibility || "";
      fieldTime.value = data.processing_time || "";
      fieldContact.value = data.contact_info || "";
      fieldStatus.value = data.status || "active";
      fieldRequirements.value = data.requirements || "";

      if (data.service_type === "info") {
        contactGroup.style.display = "";
        approvalGroup.style.display = "none";
      }

      if (data.max_capacity) {
        capacityToggle.checked = true;
        capacityInputWrap.style.display = "";
        fieldMaxCapacity.value = data.max_capacity;
      }

      if (data.attachment_name) {
        const names = Array.isArray(data.attachment_name)
          ? data.attachment_name
          : data.attachment_name
              .split(",")
              .map((n) => n.trim())
              .filter(Boolean);
        existingFiles = names;
        existingAttachment.value = names.join(",");
        renderAttachmentList();
      }
    } else {
      modalTitle.textContent = "Add Service";
    }

    modalOverlay.style.display = "flex";
    fieldName.focus();
  }

  setTimeout(() => {
    initAutoResizeTextarea(fieldDesc);
    initAutoResizeTextarea(fieldContact);  
  }, 10);

  function closeModal() {
    modalOverlay.style.display = "none";
  }

  document
    .getElementById("svc-add-btn")
    .addEventListener("click", () => openModal("add"));
  modalClose.addEventListener("click", closeModal);
  modalCancel.addEventListener("click", closeModal);
  modalOverlay.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
  });

  // (card-level edit button removed; use Edit Service in the view modal)

  /* ══════════════════════════════════════════════════════════
     SAVE SERVICE
  ══════════════════════════════════════════════════════════ */

  saveBtn.addEventListener("click", async () => {
    clearErrors();
    if (!validateAll()) {
      if (!validateTab(1)) {
        goToTab(1);
        return;
      }
      if (!validateTab(3)) {
        goToTab(3);
        return;
      }
      return;
    }

    const isEdit = !!fieldId.value;

    // Build FormData so the file upload is included
    const fd = new FormData();
    fd.append("action", isEdit ? "update" : "create");
    if (isEdit) fd.append("id", fieldId.value);
    fd.append("name", fieldName.value.trim());
    fd.append("category", fieldCategory.value);
    fd.append("service_type", fieldTypeSelect.value);
    fd.append("description", fieldDesc.value.trim());
    fd.append("approval_message", fieldApprovalMsg.value.trim());
    fd.append("eligibility", fieldEligibility.value.trim());
    fd.append("processing_time", fieldTime.value.trim());
    fd.append("requirements", fieldRequirements.value.trim());
    fd.append("contact_info", fieldContact.value.trim());
    fd.append("status", fieldStatus.value);
    fd.append(
      "max_capacity",
      capacityToggle.checked && fieldMaxCapacity.value
        ? fieldMaxCapacity.value
        : ""
    );

    // Attach new files (key must match $_FILES['attachments'])
    selectedFiles.forEach((file) => {
      fd.append("attachments[]", file);
    });

    // Track existing kept files
    if (existingFiles.length > 0) {
      fd.append("existing_attachments", existingFiles.join(","));
    } else if (isEdit) {
      fd.append("clear_attachment", "1");
    }

    saveBtn.disabled = true;
    saveBtn.textContent = "Saving…";

    try {
      const resp = await fetch("../../../backend/routes/services.php", {
        method: "POST",
        body: fd,
      });

      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();

      if (!json.success) {
        // Show server-side validation errors on the correct fields
        const errs = json.errors || [json.message || "Save failed."];
        errs.forEach((msg) => {
          const lower = msg.toLowerCase();
          if (lower.includes("name")) setError(fieldName, errName, msg);
          else if (lower.includes("category")) setError(fieldCategory, errCategory, msg);
          else if (lower.includes("type")) setError(fieldTypeSelect, errType, msg);
          else if (lower.includes("desc")) setError(fieldDesc, errDesc, msg);
          else if (lower.includes("approval")) setError(fieldApprovalMsg, errApprovalMsg, msg);
          else if (lower.includes("contact")) setError(fieldContact, errContact, msg);
          else if (lower.includes("capacity")) setError(fieldMaxCapacity, errCapacity, msg);
          else showToast(msg, "danger");
        });
        // Jump to the tab that has an error
        if (!validateTab(1)) goToTab(1);
        return;
      }

      // Success — update the card DOM from the server response
      const svc = json.service;
      svc.current_count = svc.current_count ?? 0;

      if (isEdit) {
        const card = grid.querySelector(`.svc-card[data-id="${svc.id}"]`);
        if (card) card.replaceWith(buildCard(svc));
        showToast(`"${svc.name}" updated successfully.`, "success");
      } else {
        grid.prepend(buildCard(svc));
        showToast(`"${svc.name}" has been added.`, "success");
      }

      applyFilters();
      closeModal();
    } catch (err) {
      showToast("Network error — please try again.", "danger");
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg> Save Service`;
    }
  });

  /* ══════════════════════════════════════════════════════════
     TOGGLE ACTIVE / INACTIVE
  ══════════════════════════════════════════════════════════ */

  grid.addEventListener("click", (e) => {
    const toggleBtn = e.target.closest(".svc-toggle-btn");
    if (!toggleBtn) return;

    const card = toggleBtn.closest(".svc-card");
    const id = toggleBtn.dataset.id;
    const status = toggleBtn.dataset.status;
    const newStatus = status === "active" ? "inactive" : "active";
    const name =
      card.querySelector(".svc-card-title")?.textContent || "Service";

    // Update card data attribute
    card.dataset.status = newStatus;

    // Update top border accent and badge
    const badge = card.querySelector(".svc-status-badge");
    if (badge && !badge.classList.contains("svc-badge-full")) {
      badge.className = `svc-status-badge svc-badge-${newStatus}`;
      badge.innerHTML =
        newStatus === "active"
          ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg><span class="svc-status-dot"></span>Active`
          : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg><span class="svc-status-dot"></span>Inactive`;
    }

    // Update toggle button
    toggleBtn.dataset.status = newStatus;
    toggleBtn.className = `svc-toggle-btn svc-toggle-${newStatus}`;
    toggleBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg> ${
      newStatus === "active" ? "Deactivate" : "Activate"
    }`;

    applyFilters();
    showToast(
      `"${name}" is now ${newStatus}.`,
      newStatus === "active" ? "success" : "warning"
    );

    // Persist to DB
    const fd = new FormData();
    fd.append("action", "toggle");
    fd.append("id", id);
    fetch("../../../backend/routes/services.php", {
      method: "POST",
      body: fd,
    })
      .then((r) => r.json())
      .then((json) => {
        if (!json.success) showToast("Could not update status.", "danger");
      })
      .catch(() => showToast("Network error on status update.", "danger"));
  });

  /* ══════════════════════════════════════════════════════════
     DELETE
  ══════════════════════════════════════════════════════════ */

  let pendingDeleteId = null;
  let pendingDeleteCard = null;

  grid.addEventListener("click", (e) => {
    const deleteBtn = e.target.closest(".svc-delete-btn");
    if (!deleteBtn) return;

    pendingDeleteId = deleteBtn.dataset.id;
    pendingDeleteCard = deleteBtn.closest(".svc-card");
    const name = deleteBtn.dataset.name || "this service";

    confirmBody.textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
    confirmOverlay.style.display = "flex";
  });

  confirmDelete.addEventListener("click", () => {
    if (pendingDeleteCard) {
      const name =
        pendingDeleteCard.querySelector(".svc-card-title")?.textContent ||
        "Service";
      pendingDeleteCard.style.transition = "opacity 0.3s, transform 0.3s";
      pendingDeleteCard.style.opacity = "0";
      pendingDeleteCard.style.transform = "scale(0.95)";
      setTimeout(() => {
        pendingDeleteCard.remove();
        applyFilters();
      }, 300);
      showToast(`"${name}" has been deleted.`, "danger");

      // Persist to DB
      const fd = new FormData();
      fd.append("action", "delete");
      fd.append("id", pendingDeleteId);
      fetch("../../../backend/routes/services.php", {
        method: "POST",
        body: fd,
      })
        .then((r) => r.json())
        .then((json) => {
          if (!json.success)
            showToast(json.message || "Delete failed.", "danger");
        })
        .catch(() => showToast("Network error on delete.", "danger"));
    }
    closeConfirm();
  });

  confirmCancel.addEventListener("click", closeConfirm);
  confirmOverlay.addEventListener("click", (e) => {
    if (e.target === confirmOverlay) closeConfirm();
  });

  function closeConfirm() {
    confirmOverlay.style.display = "none";
    pendingDeleteId = null;
    pendingDeleteCard = null;
  }

  /* ══════════════════════════════════════════════════════════
     BUILD NEW CARD (client-side for Add/Edit)
  ══════════════════════════════════════════════════════════ */

  const categoryIcons = {
    medical: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>`,
    education: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>`,
    scholarship: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/></svg>`,
    livelihood: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>`,
    assistance: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"/></svg>`,
    legal: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99.203 1.99.377 3 .52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.491Z"/></svg>`,
    other: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>`,
  };

  const categoryLabels = {
    medical: "Medical",
    education: "Education",
    scholarship: "Scholarship",
    livelihood: "Livelihood",
    assistance: "Assistance",
    legal: "Legal",
    other: "Other",
  };

  const typeLabels = {
    document: "Online Application",
    appointment: "Request-based Service",
    info: "Information & Contact",
  };

  function buildReqHtml(raw) {
    if (!raw || !raw.trim()) return "";
    const lines = raw.split("\n").filter((l) => l.trim());
    const items = lines.filter((l) => /^[-•*]/.test(l.trim()));
    if (!items.length) return `<span>${escHtml(raw.trim())}</span>`;
    return `<ul class="svc-req-list">${items
      .map((l) => `<li>${escHtml(l.trim().replace(/^[-•*]\s*/, ""))}</li>`)
      .join("")}</ul>`;
  }

  function buildCard(d) {
    const icon = categoryIcons[d.category] || categoryIcons.other;
    const catLabel = categoryLabels[d.category] || d.category;
    const typLabel = typeLabels[d.service_type] || d.service_type;
    const hasCapacity = d.max_capacity !== null && d.max_capacity !== undefined;
    const capPct = hasCapacity
      ? Math.min(100, Math.round((d.current_count / d.max_capacity) * 100))
      : 0;
    const capFull = hasCapacity && d.current_count >= d.max_capacity;

    const reqLines = d.requirements
      ? d.requirements
          .split("\n")
          .filter((l) => l.trim() && /^[-•*]/.test(l.trim()))
      : [];

    const article = document.createElement("article");
    article.className = "svc-card";
    article.dataset.id = d.id;
    article.dataset.category = d.category;
    article.dataset.type = d.service_type;
    article.dataset.status = d.status;
    article.dataset.name = d.name.toLowerCase();

    article.innerHTML = `
      <div class="svc-card-body">
        <div class="svc-card-top">
          <div class="svc-card-top-left">
            <div class="svc-icon-wrap svc-icon-${escHtml(
              d.category
            )}">${icon}</div>
            <span class="svc-cat-tag svc-cat-${escHtml(d.category)}">${escHtml(
      catLabel
    )}</span>
          </div>
          <div class="svc-card-badges">
            ${
              capFull
                ? `<span class="svc-status-badge svc-badge-full"><span class="svc-status-dot"></span>Full</span>`
                : `<span class="svc-status-badge svc-badge-${
                    d.status
                  }"><span class="svc-status-dot"></span>${
                    d.status === "active" ? "Active" : "Inactive"
                  }</span>`
            }
          </div>
        </div>
        <h3 class="svc-card-title">${escHtml(d.name)}</h3>
        <p class="svc-card-desc">${escHtml(d.description)}</p>
        <ul class="svc-details-list">
          <li class="svc-detail-type">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
            <span class="svc-detail-label">Service Type</span>
            <span class="svc-detail-type-value">${escHtml(typLabel)}</span>
          </li>
          ${
            d.eligibility
              ? `<li>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            <span class="svc-detail-label">Eligibility</span>
            <span class="svc-detail-type-value">${escHtml(d.eligibility)}</span>
          </li>`
              : ""
          }
          ${
            d.processing_time
              ? `<li>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span class="svc-detail-label">Processing</span>
            <span class="svc-detail-type-value">${escHtml(
              d.processing_time
            )}</span>
          </li>`
              : ""
          }
          ${
            reqLines.length
              ? `<li class="svc-detail-req">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            <div class="svc-detail-req-content">
              <span class="svc-detail-label">Requirements</span>
              <ul class="svc-req-list">${reqLines
                .map(
                  (l) =>
                    `<li>${escHtml(l.trim().replace(/^[-•*]\s*/, ""))}</li>`
                )
                .join("")}
              </ul>
            </div>
          </li>`
              : ""
          }
          ${
            d.service_type === "info" && d.contact_info
              ? `<li class="svc-detail-contact-row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
            <div class="svc-detail-contact">
              <span class="svc-detail-label">Contact</span>
              <span class="svc-contact-text">${escHtml(d.contact_info).replace(
                /\n/g,
                "<br>"
              )}</span>
            </div>
          </li>`
              : ""
          }
          ${(() => {
            const attNames = d.attachment_name
              ? Array.isArray(d.attachment_name)
                ? d.attachment_name
                : d.attachment_name
                    .split(",")
                    .map((n) => n.trim())
                    .filter(Boolean)
              : [];
            const attPaths = d.attachment_path
              ? Array.isArray(d.attachment_path)
                ? d.attachment_path
                : d.attachment_path
                    .split(",")
                    .map((p) => p.trim())
                    .filter(Boolean)
              : [];
            if (!attNames.length) return "";
            const links = attNames
              .map(
                (name, i) =>
                  `<a href="${escHtml(
                    attPaths[i] || "#"
                  )}" class="svc-attachment-link" target="_blank" download>${escHtml(
                    name
                  )}</a>`
              )
              .join("");
            return `<li class="svc-attachment-row">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
            <div class="svc-attachment-multi">
              <span class="svc-detail-label">Form${
                attNames.length > 1 ? "s" : ""
              }</span>
              ${links}
            </div>
          </li>`;
          })()}
        </ul>
        ${
          hasCapacity
            ? `
        <div class="svc-capacity-wrap">
          <div class="svc-capacity-header">
            <span class="svc-capacity-label">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
              Capacity
            </span>
            <span class="svc-capacity-count ${capFull ? "is-full" : ""}">${
                d.current_count
              } / ${d.max_capacity}</span>
          </div>
          <div class="svc-capacity-bar">
            <div class="svc-capacity-fill ${
              capPct >= 100 ? "full" : capPct >= 80 ? "warning" : ""
            }" style="width:${capPct}%"></div>
          </div>
        </div>`
            : ""
        }
      </div>
      <div class="svc-card-footer">
        <button class="svc-toggle-btn svc-toggle-${d.status}" data-id="${
      d.id
    }" data-status="${d.status}">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg>
          ${d.status === "active" ? "Deactivate" : "Activate"}
        </button>
        <div class="svc-card-actions-right">
          <button class="svc-view-btn" data-service='${escAttr(
            JSON.stringify(d)
          )}' title="View service details">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            View
          </button>
          <button class="svc-delete-btn" data-id="${d.id}" data-name="${escAttr(
      d.name
    )}" title="Delete service">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            Remove
          </button>
        </div>
      </div>`;

    return article;
  }

  /* ══════════════════════════════════════════════════════════
     ESCAPE HELPERS
  ══════════════════════════════════════════════════════════ */

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function escAttr(str) {
    return String(str).replace(/'/g, "&#39;");
  }

  /* ══════════════════════════════════════════════════════════
     VIEW SERVICE MODAL
  ══════════════════════════════════════════════════════════ */

  const viewOverlay = document.getElementById("svc-view-overlay");
  const viewClose = document.getElementById("svc-view-close");
  const viewCloseBtn = document.getElementById("svc-view-close-btn");
  const viewEditBtn = document.getElementById("svc-view-edit-btn");
  const viewIcon = document.getElementById("svc-view-icon");
  const viewName = document.getElementById("svc-view-name");
  const viewBadges = document.getElementById("svc-view-badges");
  const viewBody = document.getElementById("svc-view-body");

  let viewCurrentService = null;

  function openViewModal(data) {
    viewCurrentService = data;

    const icon = categoryIcons[data.category] || categoryIcons.other;
    const catLabel = categoryLabels[data.category] || data.category;
    const typLabel = typeLabels[data.service_type] || data.service_type;
    const hasCapacity =
      data.max_capacity !== null &&
      data.max_capacity !== undefined &&
      data.max_capacity !== "";
    const capPct = hasCapacity
      ? Math.min(
          100,
          Math.round(((data.current_count || 0) / data.max_capacity) * 100)
        )
      : 0;
    const capFull =
      hasCapacity && (data.current_count || 0) >= data.max_capacity;

    const iconClassMap = {
      medical: "svc-icon-medical",
      education: "svc-icon-education",
      scholarship: "svc-icon-scholarship",
      livelihood: "svc-icon-livelihood",
      assistance: "svc-icon-assistance",
      legal: "svc-icon-legal",
      other: "svc-icon-other",
    };
    viewIcon.className = `svc-view-icon ${
      iconClassMap[data.category] || "svc-icon-other"
    }`;
    viewIcon.innerHTML = icon;
    viewName.textContent = data.name;

    const statusBadge = capFull
      ? `<span class="svc-status-badge-modal svc-badge-full"><span class="svc-status-dot"></span>Full</span>`
      : `<span class="svc-status-badge-modal svc-badge-${
          data.status
        }"><span class="svc-status-dot"></span>${
          data.status === "active" ? "Active" : "Inactive"
        }</span>`;
    const catBadge = `<span class="svc-cat-tag svc-cat-${escHtml(
      data.category
    )}">${escHtml(catLabel)}</span>`;
    viewBadges.innerHTML = catBadge + statusBadge;

    let bodyHtml = "";

    // Description
    bodyHtml += `
      <div class="svc-view-section">
        <span class="svc-view-section-label">Description</span>
        <p class="svc-view-section-text">${escHtml(data.description)}</p>
      </div>`;

    // Meta grid: type + eligibility + processing time + status
    let metaItems = `
      <div class="svc-view-meta-item">
        <span class="svc-view-meta-key">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
          Service Type
        </span>
        <span class="svc-view-meta-val">${escHtml(typLabel)}</span>
      </div>`;

    if (data.eligibility) {
      metaItems += `
        <div class="svc-view-meta-item">
          <span class="svc-view-meta-key">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            Eligibility
          </span>
          <span class="svc-view-meta-val">${escHtml(data.eligibility)}</span>
        </div>`;
    }

    if (data.processing_time) {
      metaItems += `
        <div class="svc-view-meta-item">
          <span class="svc-view-meta-key">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Processing Time
          </span>
          <span class="svc-view-meta-val">${escHtml(
            data.processing_time
          )}</span>
        </div>`;
    }

    bodyHtml += `<div class="svc-view-meta-grid">${metaItems}</div>`;

    // Requirements
    if (data.requirements && data.requirements.trim()) {
      const lines = data.requirements
        .split("\n")
        .map((l) => l.trim())
        .filter((l) => l && /^[-•*]/.test(l));
      if (lines.length) {
        const items = lines
          .map((l) => `<li>${escHtml(l.replace(/^[-•*]\s*/, ""))}</li>`)
          .join("");
        bodyHtml += `
          <div class="svc-view-section">
            <span class="svc-view-section-label">Requirements</span>
            <ul class="svc-view-req-list">${items}</ul>
          </div>`;
      } else {
        bodyHtml += `
          <div class="svc-view-section">
            <span class="svc-view-section-label">Requirements</span>
            <p class="svc-view-section-text">${escHtml(
              data.requirements.trim()
            )}</p>
          </div>`;
      }
    }

    // Contact info
    if (data.service_type === "info" && data.contact_info) {
      bodyHtml += `
        <div class="svc-view-section">
          <span class="svc-view-section-label">Contact Information</span>
          <p class="svc-view-section-text">${escHtml(data.contact_info).replace(
            /\n/g,
            "<br>"
          )}</p>
        </div>`;
    }

    // Attachments
    const attachNames = data.attachment_name
      ? Array.isArray(data.attachment_name)
        ? data.attachment_name
        : data.attachment_name
            .split(",")
            .map((n) => n.trim())
            .filter(Boolean)
      : [];
    const attachPaths = data.attachment_path
      ? Array.isArray(data.attachment_path)
        ? data.attachment_path
        : data.attachment_path
            .split(",")
            .map((p) => p.trim())
            .filter(Boolean)
      : [];

    if (attachNames.length) {
      const attachLinks = attachNames
        .map((name, i) => {
          const path = attachPaths[i] || "#";
          return `
          <a href="${escHtml(
            path
          )}" class="svc-view-attachment" target="_blank" download>
            <div class="svc-view-attachment-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            </div>
            <div class="svc-view-attachment-info">
              <span class="svc-view-attachment-name">${escHtml(name)}</span>
              <span class="svc-view-attachment-sub">Click to download</span>
            </div>
            <div class="svc-view-attachment-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            </div>
          </a>`;
        })
        .join("");
      bodyHtml += `
        <div class="svc-view-section">
          <span class="svc-view-section-label">Downloadable Form${
            attachNames.length > 1 ? "s" : ""
          } / Attachment${attachNames.length > 1 ? "s" : ""}</span>
          <div class="svc-view-attachments">${attachLinks}</div>
        </div>`;
    }

    // Capacity bar
    if (hasCapacity) {
      const fillClass = capPct >= 100 ? "full" : capPct >= 80 ? "warning" : "";
      bodyHtml += `
        <div class="svc-view-capacity">
          <div class="svc-view-capacity-header">
            <span class="svc-view-capacity-label">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
              Capacity
            </span>
            <span class="svc-view-capacity-count ${capFull ? "is-full" : ""}">${
        data.current_count || 0
      } / ${data.max_capacity} slots</span>
          </div>
          <div class="svc-capacity-bar">
            <div class="svc-capacity-fill ${fillClass}" style="width:${capPct}%"></div>
          </div>
        </div>`;
    }

    viewBody.innerHTML = bodyHtml;
    viewOverlay.style.display = "flex";
  }

  function closeViewModal() {
    viewOverlay.style.display = "none";
    viewCurrentService = null;
  }

  viewClose.addEventListener("click", closeViewModal);
  viewCloseBtn.addEventListener("click", closeViewModal);
  viewOverlay.addEventListener("click", (e) => {
    if (e.target === viewOverlay) closeViewModal();
  });

  viewEditBtn.addEventListener("click", () => {
    if (viewCurrentService) {
      const serviceToEdit = viewCurrentService;
      closeViewModal();
      openModal("edit", serviceToEdit);
    }
  });

  // Delegated click for view buttons on cards
  grid.addEventListener("click", (e) => {
    const viewBtn = e.target.closest(".svc-view-btn");
    if (viewBtn) {
      try {
        const data = JSON.parse(viewBtn.dataset.service);
        openViewModal(data);
      } catch {
        showToast("Could not load service data.", "danger");
      }
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal();
      closeConfirm();
      closeViewModal();
    }
  });

  /* ══════════════════════════════════════════════════════════
     INIT
  ══════════════════════════════════════════════════════════ */

  applyFilters();
  goToTab(1);
});


function initAutoResizeTextarea(id) {
  const tx = typeof id === 'string' ? document.getElementById(id) : id;
  if (!tx) return;

  // 1. Function to perform the resize
  const resize = () => {
      tx.style.height = 'auto'; // Reset height
      tx.style.height = tx.scrollHeight + 'px'; // Set to content height
  };

  // 2. Run whenever the user types
  tx.addEventListener('input', resize);

  // 3. Run immediately (in case there is existing text, like when editing)
  resize();
}