document.addEventListener("DOMContentLoaded", () => {

  const ROWS_PER_PAGE = 15;

  const tbody       = document.getElementById("log-tbody");
  const noResults   = document.getElementById("log-no-results");
  const countLabel  = document.getElementById("log-count");
  const searchInput = document.getElementById("log-search");
  const selAction   = document.getElementById("log-action-type");
  const selMod      = document.getElementById("log-moderator");
  const dateFrom    = document.getElementById("log-date-from");
  const dateTo      = document.getElementById("log-date-to");
  const clearBtn    = document.getElementById("log-clear-btn");
  const exportBtn   = document.getElementById("log-export-btn");
  const prevBtn     = document.getElementById("log-prev-btn");
  const nextBtn     = document.getElementById("log-next-btn");
  const pageNumbers = document.getElementById("log-page-numbers");

  let sortCol = "datetime";
  let sortDir = "desc";
  let currentPage = 1;
  let filteredRows = [];

  /* ── HELPERS ── */

  function getAllRows() {
    return Array.from(tbody.querySelectorAll("tr:not(#log-empty-row)"));
  }

  function rowText(row) {
    const target = row.querySelector(".col-target")?.textContent || "";
    const mod    = row.querySelector(".col-moderator")?.textContent || "";
    return (target + " " + mod).toLowerCase();
  }

  /* ── FILTER + PAGINATE ── */

  function applyFilters() {
    const q      = searchInput.value.toLowerCase().trim();
    const action = selAction.value;
    const mod    = selMod.value;
    const from   = dateFrom.value ? new Date(dateFrom.value) : null;
    const to     = dateTo.value   ? new Date(dateTo.value + "T23:59:59") : null;

    filteredRows = getAllRows().filter((row) => {
      const matchSearch = !q || rowText(row).includes(q);
      const matchAction = action === "all" || row.dataset.action === action;
      const matchMod    = mod === "all"    || row.dataset.moderator === mod;

      let matchDate = true;
      if (from || to) {
        const dt = new Date(row.dataset.datetime);
        if (from && dt < from) matchDate = false;
        if (to   && dt > to)   matchDate = false;
      }

      return matchSearch && matchAction && matchMod && matchDate;
    });

    currentPage = 1;
    renderPage();
  }

  function renderPage() {
    const total     = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * ROWS_PER_PAGE;
    const end   = start + ROWS_PER_PAGE;

    getAllRows().forEach((row) => { row.style.display = "none"; });
    filteredRows.slice(start, end).forEach((row) => { row.style.display = ""; });

    countLabel.textContent = `Showing ${Math.min(end, total)} of ${total} entr${total !== 1 ? "ies" : "y"}`;
    noResults.style.display = total === 0 ? "flex" : "none";

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    pageNumbers.innerHTML = "";

    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) {
      startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      const btn = document.createElement("button");
      btn.className = "mod-page-num" + (i === currentPage ? " active" : "");
      btn.textContent = i;
      btn.addEventListener("click", () => {
        currentPage = i;
        renderPage();
      });
      pageNumbers.appendChild(btn);
    }

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
  }

  searchInput.addEventListener("input",  applyFilters);
  selAction.addEventListener("change",   applyFilters);
  selMod.addEventListener("change",      applyFilters);
  dateFrom.addEventListener("change",    applyFilters);
  dateTo.addEventListener("change",      applyFilters);

  clearBtn.addEventListener("click", () => {
    searchInput.value = "";
    selAction.value   = "all";
    selMod.value      = "all";
    dateFrom.value    = "";
    dateTo.value      = "";
    applyFilters();
  });

  prevBtn.addEventListener("click", () => {
    if (currentPage > 1) { currentPage--; renderPage(); }
  });
  nextBtn.addEventListener("click", () => {
    const total = Math.ceil(filteredRows.length / ROWS_PER_PAGE);
    if (currentPage < total) { currentPage++; renderPage(); }
  });

  /* ── SORTING ── */

  document.querySelectorAll(".log-table thead th.sortable").forEach((th) => {
    th.addEventListener("click", () => {
      const col = th.dataset.col;
      sortDir = (sortCol === col && sortDir === "asc") ? "desc" : "asc";
      sortCol = col;

      document.querySelectorAll(".log-table thead th.sortable").forEach((h) => {
        h.classList.remove("sort-asc", "sort-desc");
      });
      th.classList.add(sortDir === "asc" ? "sort-asc" : "sort-desc");

      sortRows(col, sortDir);
    });
  });

  function cellValue(row, col) {
    switch (col) {
      case "id":        return parseInt(row.querySelector(".col-id")?.textContent || "0");
      case "datetime":  return new Date(row.dataset.datetime || 0).getTime();
      case "moderator": return row.querySelector(".log-mod-cell span")?.textContent?.toLowerCase() || "";
      default:          return "";
    }
  }

  function sortRows(col, dir) {
    const rows = getAllRows();
    rows.sort((a, b) => {
      const va = cellValue(a, col), vb = cellValue(b, col);
      if (va < vb) return dir === "asc" ? -1 : 1;
      if (va > vb) return dir === "asc" ?  1 : -1;
      return 0;
    });
    rows.forEach((r) => tbody.appendChild(r));
    applyFilters();
  }

  sortRows("datetime", "desc");
  document.querySelector('[data-col="datetime"]')?.classList.add("sort-desc");

  /* ── PDF EXPORT ── */

  exportBtn.addEventListener("click", () => {
    const { jsPDF } = window.jspdf;
    const doc       = new jsPDF({ orientation: "landscape", unit: "pt", format: "a4" });

    const TEAL      = [15, 118, 110];
    const DARK      = [2, 44, 34];
    const MUTED     = [107, 114, 128];
    const LIGHT_BG  = [240, 253, 250];
    const pageW     = doc.internal.pageSize.getWidth();
    const pageH     = doc.internal.pageSize.getHeight();

    /* header strip */
    doc.setFillColor(...TEAL);
    doc.rect(0, 0, pageW, 52, "F");

    doc.setFont("helvetica", "bold");
    doc.setFontSize(18);
    doc.setTextColor(255, 255, 255);
    doc.text("SKonnect — Moderation Activity Report", 36, 32);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.setTextColor(180, 240, 235);
    doc.text("Generated: " + new Date().toLocaleString("en-PH", {
      year: "numeric", month: "long", day: "numeric",
      hour: "2-digit", minute: "2-digit"
    }), 36, 46);

    /* active filter summary */
    const filterParts = [];
    if (selAction.value !== "all") filterParts.push("Action: " + selAction.options[selAction.selectedIndex].text);
    if (selMod.value    !== "all") filterParts.push("Moderator: " + selMod.options[selMod.selectedIndex].text);
    if (dateFrom.value)            filterParts.push("From: " + dateFrom.value);
    if (dateTo.value)              filterParts.push("To: " + dateTo.value);
    if (searchInput.value.trim())  filterParts.push('Search: "' + searchInput.value.trim() + '"');

    let yPos = 70;

    if (filterParts.length) {
      doc.setFont("helvetica", "italic");
      doc.setFontSize(8.5);
      doc.setTextColor(...MUTED);
      doc.text("Filters applied: " + filterParts.join("  •  "), 36, yPos);
      yPos += 16;
    }

    /* summary stat boxes */
    const statItems = [
      { label: "Visible Entries",    value: filteredRows.length },
      { label: "Thread Actions",     value: filteredRows.filter(r => r.dataset.action?.startsWith("thread_")).length },
      { label: "Sanctions",          value: filteredRows.filter(r => ["warning_issued","mute_issued","ban_issued"].includes(r.dataset.action)).length },
      { label: "Reports Handled",    value: filteredRows.filter(r => ["report_resolved","report_dismissed"].includes(r.dataset.action)).length },
    ];

    const boxW = (pageW - 72) / statItems.length;
    statItems.forEach((s, idx) => {
      const bx = 36 + idx * boxW;
      doc.setFillColor(...LIGHT_BG);
      doc.setDrawColor(...TEAL);
      doc.roundedRect(bx, yPos, boxW - 8, 42, 4, 4, "FD");
      doc.setFont("helvetica", "bold");
      doc.setFontSize(20);
      doc.setTextColor(...DARK);
      doc.text(String(s.value), bx + (boxW - 8) / 2, yPos + 22, { align: "center" });
      doc.setFont("helvetica", "normal");
      doc.setFontSize(7.5);
      doc.setTextColor(...MUTED);
      doc.text(s.label, bx + (boxW - 8) / 2, yPos + 35, { align: "center" });
    });

    yPos += 58;

    /* table */
    const headers = ["#", "Date & Time", "Moderator", "Action", "Target Type", "Target", "Posted By", "Notes"];

    const rows = filteredRows.map((row, i) => {
      const id         = String(i + 1).padStart(3, "0");
      const date       = row.querySelector(".log-date")?.textContent?.trim() || "";
      const time       = row.querySelector(".log-time")?.textContent?.trim() || "";
      const mod        = row.querySelector(".log-mod-cell span")?.textContent?.trim() || "";
      const action     = row.querySelector(".log-action-badge")?.textContent?.trim().replace(/\s+/g, " ") || "";
      const targetType = row.querySelector(".log-target-type")?.textContent?.trim() || "";
      const targetName = row.querySelector(".log-target-name")?.textContent?.trim() || "";
      const targetUser = row.querySelector(".log-target-user")?.textContent?.trim().replace(/^by /, "") || "";
      const notes      = row.querySelector(".log-notes-text")?.textContent?.trim() || "";
      return [id, date + "\n" + time, mod, action, targetType, targetName, targetUser, notes];
    });

    const actionColors = {
      "Remove Thread":    [254, 226, 226],
      "Remove Comment":   [254, 226, 226],
      "User Banned":      [254, 226, 226],
      "Warning Issued":   [254, 243, 199],
      "User Muted":       [252, 231, 243],
      "Flag Thread":      [255, 247, 237],
      "Report Reviewed":  [209, 250, 229],
      "Report Dismissed": [241, 245, 249],
      "Sanction Lifted":  [220, 252, 231],
      "Pin Thread":       [224, 231, 255],
      "Restore Thread":   [220, 252, 231],
    };

    doc.autoTable({
      startY: yPos,
      head:   [headers],
      body:   rows,
      margin: { left: 36, right: 36 },
      styles: {
        font:      "helvetica",
        fontSize:  8,
        cellPadding: 5,
        valign:    "middle",
        overflow:  "linebreak",
        lineColor: [209, 250, 229],
        lineWidth: 0.4,
      },
      headStyles: {
        fillColor:  TEAL,
        textColor:  255,
        fontStyle:  "bold",
        fontSize:   8.5,
        halign:     "left",
      },
      columnStyles: {
        0: { cellWidth: 28,  halign: "center", textColor: MUTED },
        1: { cellWidth: 70 },
        2: { cellWidth: 80 },
        3: { cellWidth: 82 },
        4: { cellWidth: 52,  halign: "center" },
        5: { cellWidth: 110 },
        6: { cellWidth: 72 },
        7: { cellWidth: "auto" },
      },
      alternateRowStyles: { fillColor: LIGHT_BG },
      didParseCell(data) {
        if (data.section === "body" && data.column.index === 3) {
          const label = data.cell.raw?.toString().trim() || "";
          const bg    = actionColors[label];
          if (bg) data.cell.styles.fillColor = bg;
        }
      },
      didDrawPage(data) {
        /* page footer */
        doc.setFont("helvetica", "normal");
        doc.setFontSize(7.5);
        doc.setTextColor(...MUTED);
        const pageStr = `Page ${data.pageNumber}`;
        doc.text(pageStr, pageW - 36, pageH - 14, { align: "right" });
        doc.text("SKonnect Moderation System — Confidential", 36, pageH - 14);
      },
    });

    const filename = `skonnect_activity_logs_${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(filename);
  });

  /* ── INIT ── */
  applyFilters();
});