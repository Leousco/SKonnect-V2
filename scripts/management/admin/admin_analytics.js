/**
 * admin_analytics.js
 * Chart.js charts — all data from analytics_stats.php
 * FIXED: All statistics panels now properly populated
 */

 document.addEventListener("DOMContentLoaded", function () {
    const API_URL = "../../../backend/routes/analytics_stats.php";
  
    /* ── Chart defaults ──────────────────────────────────── */
    Chart.defaults.font.family = "'Segoe UI', Tahoma, sans-serif";
    Chart.defaults.color = "#64748b";
  
    const VIOLET = "#7c3aed";
    const VIOLET_LT = "#8b5cf6";
    const AMBER = "#f59e0b";
    const INDIGO = "#6366f1";
    const TEAL = "#0d9488";
    const SLATE = "#94a3b8";
    const GREEN = "#10b981";
    const RED = "#ef4444";
    const PALETTE = [VIOLET, AMBER, INDIGO, TEAL, SLATE, GREEN, RED];
  
    const CAT_LABELS = {
      medical: "Medical Assist.",
      education: "Education",
      scholarship: "Scholarship",
      livelihood: "Livelihood",
      assistance: "Assistance",
      legal: "Legal Aid",
      other: "Others",
    };
  
    const MONTH_LABELS = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
  
    /* ── Chart instances ─────────────────────────────── */
    let barChart = null;
    let donutChart = null;
    let growthChart = null;
    let activeChart = null;
    let selectedYear = new Date().getFullYear();
  
    /* ── Count-up helper ─────────────────────────────────── */
    function countUp(el, target, suffix = "") {
      if (!el) return;
      let current = 0;
      const step = Math.max(1, Math.ceil(target / (900 / 16)));
      const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString() + suffix;
        if (current >= target) clearInterval(timer);
      }, 16);
    }
  
    /* ── Load data ───────────────────────────────────────── */
    function load(year) {
      fetch(`${API_URL}?year=${year}`)
        .then((r) => r.json())
        .then((json) => {
          if (json.status !== "success") {
            console.error("Analytics API:", json.message);
            return;
          }
          const d = json.data;
          renderStatCards(d);
          renderYearFilter(d.availableYears, d.selectedYear);
          renderBarChart(d.requestsByMonth, d.selectedYear);
          renderDonutChart(d.serviceBreakdown);
          renderGrowthChart(d.growthLabels, d.growthData);
          renderActiveChart(d.activeUsers, d.inactiveUsers, d.activePct);
  
          /* ── NEW: Render all missing sections ── */
          renderUserRoles(d.usersByRole);
          renderAnnouncementStats(d.announcementStats);
          renderEventStats(d.eventStats);
          renderThreadStats(d.threadStats);
          renderReportStats(d.reportStats);
          renderServicesTable(d.requestsByService);
          renderRequestStatus(d.requestStatusCounts);
          renderServiceTypes(d.requestsByType);
        })
        .catch((err) => console.error("Analytics fetch failed:", err));
    }
  
    /* ── Stat Cards (already worked) ─────────────────────── */
    function renderStatCards(d) {
      const totalEl = document.getElementById("stat-total-users");
      countUp(totalEl, d.totalUsers);
      const newEl = document.getElementById("stat-new-this-month");
      if (newEl) newEl.textContent = `▲ ${d.newThisMonth} this month`;
  
      const reqEl = document.getElementById("stat-total-requests");
      countUp(reqEl, d.totalRequests);
      const reqMonthEl = document.getElementById("stat-requests-month");
      if (reqMonthEl)
        reqMonthEl.textContent = `▲ ${d.requestsThisMonth} this month`;
  
      const topEl = document.getElementById("stat-top-service");
      if (topEl) topEl.textContent = d.topService.name || "N/A";
      const topCntEl = document.getElementById("stat-top-service-cnt");
      if (topCntEl)
        topCntEl.textContent =
          d.topService.cnt > 0
            ? `${d.topService.cnt} requests this month`
            : "No requests this month";
  
      const activeEl = document.getElementById("stat-active-users");
      countUp(activeEl, d.activeUsers);
      const inactEl = document.getElementById("stat-inactive-users");
      if (inactEl) inactEl.textContent = `${d.inactiveUsers} inactive`;
    }
  
    /* ── User Roles Panel (NEW) ─────────────────────────── */
    function renderUserRoles(roles) {
      const roleResident = document.getElementById("role-resident");
      const roleSkOfficer = document.getElementById("role-sk_officer");
      const roleModerator = document.getElementById("role-moderator");
      const roleAdmin = document.getElementById("role-admin");
  
      if (roleResident)
        roleResident.textContent = roles?.resident?.toLocaleString() || "0";
      if (roleSkOfficer)
        roleSkOfficer.textContent = roles?.sk_officer?.toLocaleString() || "0";
      if (roleModerator)
        roleModerator.textContent = roles?.moderator?.toLocaleString() || "0";
      if (roleAdmin)
        roleAdmin.textContent = roles?.admin?.toLocaleString() || "0";
    }
  
    /* ── Announcement Stats Panel (NEW) ─────────────────── */
    function renderAnnouncementStats(stats) {
      if (!stats) return;
      const total = document.getElementById("ann-total");
      const published = document.getElementById("ann-published");
      const drafts = document.getElementById("ann-drafts");
      const archived = document.getElementById("ann-archived");
      const urgent = document.getElementById("ann-urgent");
      const featured = document.getElementById("ann-featured");
  
      if (total) total.textContent = stats.total?.toLocaleString() || "0";
      if (published)
        published.textContent = stats.published?.toLocaleString() || "0";
      if (drafts) drafts.textContent = stats.drafts?.toLocaleString() || "0";
      if (archived)
        archived.textContent = stats.archived?.toLocaleString() || "0";
      if (urgent) urgent.textContent = stats.urgent?.toLocaleString() || "0";
      if (featured)
        featured.textContent = stats.featured?.toLocaleString() || "0";
    }
  
    /* ── Event Stats Panel (NEW) ────────────────────────── */
    function renderEventStats(stats) {
      if (!stats) return;
      const total = document.getElementById("evt-total");
      const upcoming = document.getElementById("evt-upcoming");
      const past = document.getElementById("evt-past");
      const thisMonth = document.getElementById("evt-this-month");
  
      if (total) total.textContent = stats.total?.toLocaleString() || "0";
      if (upcoming)
        upcoming.textContent = stats.upcoming?.toLocaleString() || "0";
      if (past) past.textContent = stats.past?.toLocaleString() || "0";
      if (thisMonth)
        thisMonth.textContent = stats.this_month?.toLocaleString() || "0";
    }
  
    /* ── Thread Stats Panel (NEW) ───────────────────────── */
    function renderThreadStats(stats) {
      if (!stats) return;
      const total = document.getElementById("thr-total");
      const published = document.getElementById("thr-published");
      const removed = document.getElementById("thr-removed");
      const pending = document.getElementById("thr-pending");
      const responded = document.getElementById("thr-responded");
      const resolved = document.getElementById("thr-resolved");
  
      if (total) total.textContent = stats.total?.toLocaleString() || "0";
      if (published)
        published.textContent = stats.published?.toLocaleString() || "0";
      if (removed) removed.textContent = stats.removed?.toLocaleString() || "0";
      if (pending) pending.textContent = stats.pending?.toLocaleString() || "0";
      if (responded)
        responded.textContent = stats.responded?.toLocaleString() || "0";
      if (resolved)
        resolved.textContent = stats.resolved?.toLocaleString() || "0";
    }
  
    /* ── Report Stats Panels (NEW) ──────────────────────── */
    function renderReportStats(reportStats) {
      if (!reportStats) return;
  
      // Thread Reports
      const threadReports = reportStats.threads || {};
      const threadTotal = document.getElementById("thread-report-total");
      const threadPending = document.getElementById("thread-report-pending");
      const threadReviewed = document.getElementById("thread-report-reviewed");
      const threadDismissed = document.getElementById("thread-report-dismissed");
  
      if (threadTotal)
        threadTotal.textContent = threadReports.total?.toLocaleString() || "0";
      if (threadPending)
        threadPending.textContent =
          threadReports.pending?.toLocaleString() || "0";
      if (threadReviewed)
        threadReviewed.textContent =
          threadReports.reviewed?.toLocaleString() || "0";
      if (threadDismissed)
        threadDismissed.textContent =
          threadReports.dismissed?.toLocaleString() || "0";
  
      // Comment Reports
      const commentReports = reportStats.comments || {};
      const commentTotal = document.getElementById("comment-report-total");
      const commentPending = document.getElementById("comment-report-pending");
      const commentReviewed = document.getElementById("comment-report-reviewed");
      const commentDismissed = document.getElementById(
        "comment-report-dismissed"
      );
  
      if (commentTotal)
        commentTotal.textContent = commentReports.total?.toLocaleString() || "0";
      if (commentPending)
        commentPending.textContent =
          commentReports.pending?.toLocaleString() || "0";
      if (commentReviewed)
        commentReviewed.textContent =
          commentReports.reviewed?.toLocaleString() || "0";
      if (commentDismissed)
        commentDismissed.textContent =
          commentReports.dismissed?.toLocaleString() || "0";
    }
  
    /* ── Services Table (NEW) ───────────────────────────── */
    function renderServicesTable(services) {
      const tbody = document.getElementById("servicesTableBody");
      if (!tbody) return;
  
      if (!services || services.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="5" class="an-table-empty">No service data available.</td></tr>';
        return;
      }
  
      tbody.innerHTML = services
        .map((svc, idx) => {
          const categoryLabel =
            CAT_LABELS[svc.category] || svc.category || "Other";
          const typeLabel =
            svc.service_type === "document"
              ? "Document"
              : svc.service_type === "appointment"
              ? "Appointment"
              : "Info";
          return `
                  <tr>
                      <td>${idx + 1}</td>
                      <td><strong>${escapeHtml(svc.name)}</strong></td>
                      <td>${escapeHtml(categoryLabel)}</td>
                      <td>${typeLabel}</td>
                      <td>${parseInt(svc.cnt).toLocaleString()}</td>
                  </tr>
              `;
        })
        .join("");
    }
  
    /* ── Request Status Grid (NEW) ──────────────────────── */
    function renderRequestStatus(statusCounts) {
      const container = document.getElementById("statusGrid");
      if (!container) return;
  
      const statusLabels = {
        pending: "Pending",
        action_required: "Action Required",
        approved: "Approved",
        rejected: "Rejected",
        cancelled: "Cancelled",
      };
  
      const statusColors = {
        pending: "#f59e0b",
        action_required: "#ef4444",
        approved: "#10b981",
        rejected: "#dc2626",
        cancelled: "#94a3b8",
      };
  
      if (!statusCounts || Object.keys(statusCounts).length === 0) {
        container.innerHTML =
          '<div class="an-status-empty">No data available</div>';
        return;
      }
  
      const maxValue = Math.max(...Object.values(statusCounts)) || 1;
  
      let html = "";
      for (const [key, value] of Object.entries(statusCounts)) {
        const width = Math.min(100, (value / maxValue) * 100);
        const label = statusLabels[key] || key;
        const color = statusColors[key] || "#7c3aed";
        const formattedValue = parseInt(value).toLocaleString();
  
        html += `
              <div class="an-status-item">
                  <div class="an-status-bar" style="background: ${color}; width: ${width}%;"></div>
                  <div class="an-status-content">
                      <span class="an-status-label">${label}</span>
                      <span class="an-status-number">${formattedValue}</span>
                  </div>
              </div>
          `;
      }
  
      container.innerHTML = html;
    }
  
    /* ── Service Type Grid (NEW) ────────────────────────── */
    function renderServiceTypes(typeCounts) {
      const container = document.getElementById("typeGrid");
      if (!container) return;
  
      const typeLabels = {
        document: "Document Applications",
        appointment: "Appointment Requests",
        info: "Information Inquiries",
      };
  
      const typeColors = {
        document: "#7c3aed",
        appointment: "#f59e0b",
        info: "#0d9488",
      };
  
      if (!typeCounts || Object.keys(typeCounts).length === 0) {
        container.innerHTML =
          '<div class="an-type-empty">No data available</div>';
        return;
      }
  
      const maxValue = Math.max(...Object.values(typeCounts)) || 1;
  
      let html = "";
      for (const [key, value] of Object.entries(typeCounts)) {
        const width = Math.min(100, (value / maxValue) * 100);
        const label = typeLabels[key] || key;
        const color = typeColors[key] || "#7c3aed";
        const formattedValue = parseInt(value).toLocaleString();
  
        html += `
              <div class="an-type-item">
                  <div class="an-type-bar" style="background: ${color}; width: ${width}%;"></div>
                  <div class="an-type-content">
                      <span class="an-type-label">${label}</span>
                      <span class="an-type-number">${formattedValue}</span>
                  </div>
              </div>
          `;
      }
  
      container.innerHTML = html;
    }
  
    /* ── Year filter ─────────────────────────────────────── */
    function renderYearFilter(years, selected) {
      const sel = document.getElementById("reqYearFilter");
      if (!sel) return;
  
      // Clear existing options first
      sel.innerHTML = "";
  
      years.forEach((yr) => {
        const opt = document.createElement("option");
        opt.value = yr;
        opt.textContent = yr;
        opt.selected = yr == selected;
        sel.appendChild(opt);
      });
  
      // Remove old listener and add new one
      const newSel = sel.cloneNode(true);
      sel.parentNode.replaceChild(newSel, sel);
  
      newSel.addEventListener("change", function () {
        selectedYear = parseInt(this.value);
        load(selectedYear);
      });
    }
  
    /* ── Bar chart ───────────────────────────────────────── */
    function renderBarChart(data, year) {
      const currentMonth =
        new Date().getFullYear() == year ? new Date().getMonth() : 11;
      const colors = data.map((_, i) =>
        i <= currentMonth ? VIOLET : "rgba(124,58,237,0.15)"
      );
  
      if (barChart) {
        barChart.data.datasets[0].data = data;
        barChart.data.datasets[0].backgroundColor = colors;
        barChart.update();
        return;
      }
  
      const canvas = document.getElementById("requestsBarChart");
      if (!canvas) return;
  
      barChart = new Chart(canvas, {
        type: "bar",
        data: {
          labels: MONTH_LABELS,
          datasets: [
            {
              label: "Requests",
              data: data,
              backgroundColor: colors,
              borderRadius: 5,
              borderSkipped: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: { label: (ctx) => ` ${ctx.parsed.y} requests` },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              border: { display: false },
              ticks: { font: { size: 11 } },
            },
            y: {
              beginAtZero: true,
              border: { display: false, dash: [4, 4] },
              grid: { color: "rgba(0,0,0,0.06)" },
              ticks: { font: { size: 11 }, stepSize: 1 },
            },
          },
        },
      });
    }
  
    /* ── Service donut ───────────────────────────────────── */
    function renderDonutChart(breakdown) {
      const labels = breakdown.map((b) => CAT_LABELS[b.category] || b.category);
      const values = breakdown.map((b) => parseInt(b.cnt));
      const colors = breakdown.map((_, i) => PALETTE[i % PALETTE.length]);
  
      const legend = document.getElementById("service-legend");
      if (legend && breakdown.length) {
        legend.innerHTML = breakdown
          .map(
            (b, i) => `
                  <li>
                      <span class="an-legend-dot" style="background:${
                        PALETTE[i % PALETTE.length]
                      }"></span>
                      ${CAT_LABELS[b.category] || b.category}
                      <strong>${parseInt(b.cnt).toLocaleString()}</strong>
                  </li>
              `
          )
          .join("");
      } else if (legend) {
        legend.innerHTML =
          '<li style="color:var(--admin-text-muted);font-size:12px;">No data this month.</li>';
      }
  
      if (donutChart) {
        donutChart.data.labels = labels;
        donutChart.data.datasets[0].data = values;
        donutChart.data.datasets[0].backgroundColor = colors;
        donutChart.update();
        return;
      }
  
      const canvas = document.getElementById("serviceDonutChart");
      if (!canvas || !breakdown.length) return;
  
      donutChart = new Chart(canvas, {
        type: "doughnut",
        data: {
          labels,
          datasets: [
            {
              data: values,
              backgroundColor: colors,
              borderWidth: 0,
              hoverOffset: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "68%",
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ` ${ctx.label}: ${ctx.parsed} requests`,
              },
            },
          },
        },
      });
    }
  
    /* ── Growth line chart ───────────────────────────────── */
    function renderGrowthChart(labels, data) {
      if (!labels || !labels.length) return;
  
      if (growthChart) {
        growthChart.data.labels = labels;
        growthChart.data.datasets[0].data = data;
        growthChart.update();
        return;
      }
  
      const canvas = document.getElementById("userGrowthChart");
      if (!canvas) return;
  
      growthChart = new Chart(canvas, {
        type: "line",
        data: {
          labels,
          datasets: [
            {
              label: "Total Users",
              data,
              borderColor: VIOLET_LT,
              backgroundColor: "rgba(124,58,237,0.08)",
              borderWidth: 2.5,
              pointRadius: 3,
              pointBackgroundColor: VIOLET,
              fill: true,
              tension: 0.4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: { label: (ctx) => ` ${ctx.parsed.y} total users` },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              border: { display: false },
              ticks: { font: { size: 11 } },
            },
            y: {
              beginAtZero: false,
              border: { display: false, dash: [4, 4] },
              grid: { color: "rgba(0,0,0,0.06)" },
              ticks: { font: { size: 11 } },
            },
          },
        },
      });
    }
  
    /* ── Active donut ────────────────────────────────────── */
    function renderActiveChart(active, inactive, pct) {
      const pctEl = document.getElementById("active-pct");
      const activeEl = document.getElementById("active-count");
      const inactiveEl = document.getElementById("inactive-count");
  
      if (pctEl) pctEl.textContent = pct + "%";
      if (activeEl) countUp(activeEl, active);
      if (inactiveEl) countUp(inactiveEl, inactive);
  
      if (activeChart) {
        activeChart.data.datasets[0].data = [active, inactive];
        activeChart.update();
        return;
      }
  
      const canvas = document.getElementById("activeDonutChart");
      if (!canvas) return;
  
      activeChart = new Chart(canvas, {
        type: "doughnut",
        data: {
          labels: ["Active", "Inactive"],
          datasets: [
            {
              data: [active, inactive],
              backgroundColor: [GREEN, RED],
              borderWidth: 0,
              hoverOffset: 4,
            },
          ],
        },
        options: {
          responsive: false,
          cutout: "72%",
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.parsed}` },
            },
          },
        },
      });
    }
  
    /* ── Helper: Escape HTML ────────────────────────────── */
    function escapeHtml(str) {
      if (!str) return "";
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
    }
  
    /* ── Script loader ───────────────────────────────────── */
    function loadScript(src) {
      return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) return resolve();
        const s = document.createElement("script");
        s.src = src;
        s.onload = resolve;
        s.onerror = () => reject(new Error(`Failed to load: ${src}`));
        document.head.appendChild(s);
      });
    }
  
    /* ── PDF Export ──────────────────────────────────────── */
    async function exportToPDF() {
      const btn = document.getElementById("exportPdfBtn");
      const origHTML = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;animation:spin 1s linear infinite">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Generating…`;
  
      try {
        await loadScript("https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js");
        await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js");
  
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF("p", "mm", "a4");
        const pageW = pdf.internal.pageSize.getWidth();
        const pageH = pdf.internal.pageSize.getHeight();
        const margin = 12;
  
        // Header
        pdf.setFont("helvetica", "bold");
        pdf.setFontSize(18);
        pdf.setTextColor(30, 30, 60);
        pdf.text("SKonnect Analytics Report", margin, 16);
  
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(9);
        pdf.setTextColor(100, 116, 139);
        const now = new Date().toLocaleDateString("en-PH", {
          year: "numeric", month: "long", day: "numeric",
          hour: "2-digit", minute: "2-digit",
        });
        pdf.text(`Generated: ${now}`, margin, 22);
  
        pdf.setDrawColor(220, 220, 235);
        pdf.line(margin, 26, pageW - margin, 26);
  
        let yPos = 32;
  
        const sections = document.querySelectorAll(
          ".an-stats, .an-charts-row, .an-info-row, .an-service-row, .an-reports-row"
        );
  
        for (const section of sections) {
          const canvas = await html2canvas(section, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: "#ffffff",
          });
  
          const imgData = canvas.toDataURL("image/png");
          const imgW = pageW - margin * 2;
          const imgH = (canvas.height / canvas.width) * imgW;
  
          if (yPos + imgH > pageH - margin - 8) {
            pdf.addPage();
            yPos = margin;
          }
  
          pdf.addImage(imgData, "PNG", margin, yPos, imgW, imgH);
          yPos += imgH + 5;
        }
  
        // Footer on every page
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
          pdf.setPage(i);
          pdf.setFontSize(8);
          pdf.setTextColor(160, 160, 180);
          pdf.text(
            `SKonnect · Sangguniang Kabataan · Page ${i} of ${totalPages}`,
            pageW / 2,
            pageH - 5,
            { align: "center" }
          );
        }
  
        const dateStr = new Date().toISOString().split("T")[0];
        pdf.save(`skonnect-analytics-${dateStr}.pdf`);
      } catch (err) {
        console.error("PDF export failed:", err);
        alert("Export failed. Please try again.");
      }
  
      btn.disabled = false;
      btn.innerHTML = origHTML;
    }
  
    document.getElementById("exportPdfBtn")?.addEventListener("click", exportToPDF);
  
    /* ── Init ────────────────────────────────────────────── */
    load(selectedYear);
  });