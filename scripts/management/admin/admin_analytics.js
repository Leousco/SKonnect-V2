document.addEventListener("DOMContentLoaded", function () {
  const API_URL = "../../../backend/routes/analytics_stats.php";

  const analyticsError = document.getElementById("analytics-error");
  const filterError = document.getElementById("an-filter-error");
  const hasChart = typeof Chart !== "undefined";
  if (hasChart) {
    Chart.defaults.font.family = "'Segoe UI', Tahoma, sans-serif";
    Chart.defaults.color = "#64748b";
  }
  const countUpTimers = new WeakMap();

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
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
  ];

  const GRANULARITY_LABELS = {
    hour: "hourly",
    day: "daily",
    week: "weekly",
    month: "monthly",
  };

  let barChart = null;
  let donutChart = null;
  let growthChart = null;
  let activeChart = null;
  let selectedYear = new Date().getFullYear();
  let loadSequence = 0;
  let currentData = null;

  // Filter state as it appears in the controls, and the filter that was
  // actually applied to the data currently on screen.
  const filterControls = { mode: "all", day: "", from: "", to: "" };
  let appliedFilter = { mode: "all", from: null, to: null, label: "All time" };

  const modeButtons = Array.from(document.querySelectorAll(".an-mode"));
  const dateFields = document.getElementById("an-date-fields");
  const dayField = document.querySelector('[data-mode-field="day"]');
  const rangeFields = Array.from(document.querySelectorAll('[data-mode-field="range"]'));
  const dayInput = document.getElementById("an-date");
  const fromInput = document.getElementById("an-date-from");
  const toInput = document.getElementById("an-date-to");
  const applyButton = document.getElementById("an-apply-filter");
  const periodLabelEl = document.getElementById("an-period-label");

  const skeletonTargets = [
    [".an-stats", "stats"],
    [".an-charts-row .an-chart-panel", "panel"],
    [".an-info-panel", "info"],
    [".an-service-table-panel", "table"],
    [".an-status-panel", "status"],
    [".an-report-panel", "report"],
  ];

  // ── Dates ─────────────────────────────────────────────────────────

  function pad(value) {
    return String(value).padStart(2, "0");
  }

  function toISODate(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  function isValidISODate(value) {
    if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const parsed = new Date(`${value}T00:00:00`);
    return !Number.isNaN(parsed.getTime()) && toISODate(parsed) === value;
  }

  function formatDayLabel(value) {
    const parsed = new Date(`${value}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) return value;
    return parsed.toLocaleDateString("en-PH", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
  }

  function seedFilterDefaults() {
    const today = new Date();
    const weekAgo = new Date(today);
    weekAgo.setDate(today.getDate() - 6);

    filterControls.day = toISODate(today);
    filterControls.from = toISODate(weekAgo);
    filterControls.to = toISODate(today);

    if (dayInput) dayInput.value = filterControls.day;
    if (fromInput) fromInput.value = filterControls.from;
    if (toInput) toInput.value = filterControls.to;
  }

  // ── Filter UI ─────────────────────────────────────────────────────

  function setMode(mode) {
    filterControls.mode = mode;
    modeButtons.forEach((button) => {
      const active = button.dataset.mode === mode;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", String(active));
    });
    syncDateFields();
    clearFilterError();

    if (mode === "all") {
      applyFilter();
    }
  }

  function syncDateFields() {
    const mode = filterControls.mode;
    if (dateFields) dateFields.hidden = mode === "all";
    if (dayField) dayField.hidden = mode !== "day";
    rangeFields.forEach((field) => {
      field.hidden = mode !== "range";
    });
  }

  function showFilterError(message) {
    if (!filterError) return;
    filterError.textContent = message;
    filterError.hidden = false;
  }

  function clearFilterError() {
    if (!filterError) return;
    filterError.textContent = "";
    filterError.hidden = true;
  }

  function applyFilter() {
    clearFilterError();
    const mode = filterControls.mode;

    if (mode === "all") {
      appliedFilter = { mode: "all", from: null, to: null, label: "All time" };
      load(selectedYear);
      return;
    }

    if (mode === "day") {
      const day = dayInput?.value || "";
      if (!isValidISODate(day)) {
        showFilterError("Choose a valid date to report on.");
        return;
      }
      appliedFilter = {
        mode: "day",
        from: day,
        to: day,
        label: formatDayLabel(day),
      };
      load(selectedYear);
      return;
    }

    const from = fromInput?.value || "";
    const to = toInput?.value || "";
    if (!isValidISODate(from) || !isValidISODate(to)) {
      showFilterError("Choose both a start date and an end date.");
      return;
    }
    if (from > to) {
      showFilterError("The start date must be on or before the end date.");
      return;
    }

    appliedFilter = {
      mode: from === to ? "day" : "range",
      from,
      to,
      label:
        from === to
          ? formatDayLabel(from)
          : `${formatDayLabel(from).replace(/, \d{4}$/, "")} – ${formatDayLabel(to)}`,
    };
    load(selectedYear);
  }

  function applyServerFilter(meta) {
    if (!meta || !meta.mode) return;
    appliedFilter = {
      mode: meta.mode,
      from: meta.from || null,
      to: meta.to || null,
      label: meta.label || "All time",
      days: meta.days ?? null,
    };

    filterControls.mode = meta.mode;
    modeButtons.forEach((button) => {
      const active = button.dataset.mode === meta.mode;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", String(active));
    });
    syncDateFields();

    if (periodLabelEl) periodLabelEl.textContent = appliedFilter.label;
  }

  // ── Loading ────────────────────────────────────────────────────────

  function createSkeletonLayer(type) {
    const layer = document.createElement("div");
    layer.className = `an-skeleton-layer an-skeleton-${type}`;
    layer.setAttribute("aria-hidden", "true");

    const templates = {
      stats: `
        <div class="an-skeleton-stat-card"><i class="an-skeleton-icon"></i><div class="an-skeleton-stat-body"><i></i><i></i><i></i></div></div>
        <div class="an-skeleton-stat-card"><i class="an-skeleton-icon"></i><div class="an-skeleton-stat-body"><i></i><i></i><i></i></div></div>
        <div class="an-skeleton-stat-card"><i class="an-skeleton-icon"></i><div class="an-skeleton-stat-body"><i></i><i></i><i></i></div></div>
        <div class="an-skeleton-stat-card"><i class="an-skeleton-icon"></i><div class="an-skeleton-stat-body"><i></i><i></i><i></i></div></div>`,
      panel: `
        <div class="an-skeleton-heading"><i></i><i></i></div>
        <div class="an-skeleton-chart">
          <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
        </div>`,
      info: `
        <div class="an-skeleton-heading"><i class="an-skeleton-icon"></i><i></i></div>
        <div class="an-skeleton-rows"><i></i><i></i><i></i></div>`,
      table: `
        <div class="an-skeleton-heading"><i></i><i></i></div>
        <div class="an-skeleton-table-head">
          <i></i><i></i><i></i><i></i><i></i>
        </div>
        <div class="an-skeleton-table-row">
          <span><i></i></span><span><i></i></span><span><i></i></span><span><i></i></span><span><i></i></span>
        </div>
        <div class="an-skeleton-table-row">
          <span><i></i></span><span><i></i></span><span><i></i></span><span><i></i></span><span><i></i></span>
        </div>`,
      status: `
        <div class="an-skeleton-heading"><i></i><i></i></div>
        <div class="an-skeleton-rows"><i></i><i></i><i></i></div>
        <div class="an-skeleton-divider"></div>
        <div class="an-skeleton-heading"><i></i><i></i></div>
        <div class="an-skeleton-rows"><i></i><i></i></div>`,
      report: `
        <div class="an-skeleton-heading"><i></i><i></i></div>
        <div class="an-skeleton-rows"><i></i><i></i><i></i></div>`,
    };

    layer.innerHTML = templates[type] || "";
    return layer;
  }

  function setLoadingState(isLoading) {
    skeletonTargets.forEach(([selector, type]) => {
      document.querySelectorAll(selector).forEach((target) => {
        if (isLoading) {
          if (target.classList.contains("an-loading")) return;
          target.classList.add("an-loading");
          target.setAttribute("aria-busy", "true");
          target.appendChild(createSkeletonLayer(type));
          return;
        }

        target.classList.remove("an-loading");
        target.removeAttribute("aria-busy");
        target.querySelector(":scope > .an-skeleton-layer")?.remove();
      });
    });

    if (applyButton) applyButton.disabled = isLoading;
  }

  async function load(year) {
    const sequence = ++loadSequence;
    setLoadingState(true);

    const params = new URLSearchParams({ year: String(year) });
    if (appliedFilter.mode !== "all" && appliedFilter.from && appliedFilter.to) {
      params.set("from", appliedFilter.from);
      params.set("to", appliedFilter.to);
    }

    try {
      const response = await fetch(`${API_URL}?${params.toString()}`, {
        headers: { Accept: "application/json" },
      });
      const json = await response.json();
      if (sequence !== loadSequence) return;
      if (!response.ok || json.status !== "success") {
        throw new Error(json.message || `Analytics request failed (${response.status}).`);
      }

      const d = json.data;
      currentData = d;
      clearError();
      applyServerFilter(d.filter);
      renderStatCards(d);
      renderYearFilter(d.availableYears, d.selectedYear);
      renderPeriodSubtitles(d);

      if (hasChart) {
        renderTimelineChart(d.requestsTimeline, d.filter);
        renderDonutChart(d.serviceBreakdown, d.filter);
        renderGrowthChart(d.growthLabels, d.growthData, d.growthGranularity, d.filter);
        renderActiveChart(d.activeUsers, d.inactiveUsers, d.activePct);
      } else {
        showError(
          "Analytics data loaded, but charts could not be displayed because the chart library did not load."
        );
      }

      renderUserRoles(d.usersByRole);
      renderAnnouncementStats(d.announcementStats);
      renderEventStats(d.eventStats);
      renderThreadStats(d.threadStats);
      renderReportStats(d.reportStats);
      renderServicesTable(d.requestsByService);
      renderRequestStatus(d.requestStatusCounts);
      renderServiceTypes(d.requestsByType);
    } catch (err) {
      if (sequence !== loadSequence) return;
      console.error("Analytics load failed:", err);
      showError(`Unable to load analytics data. ${err.message || "Please try again."}`);
    } finally {
      if (sequence === loadSequence) setLoadingState(false);
    }
  }

  function showError(message) {
    if (!analyticsError) return;
    analyticsError.textContent = message;
    analyticsError.hidden = false;
  }

  function clearError() {
    if (!analyticsError) return;
    analyticsError.textContent = "";
    analyticsError.hidden = true;
  }

  // ── Panels ─────────────────────────────────────────────────────────

  function isFiltered() {
    return appliedFilter.mode !== "all";
  }

  function periodPhrase() {
    return isFiltered() ? "in the selected period" : "this month";
  }

  function countUp(el, target, suffix = "") {
    if (!el) return;
    const previousTimer = countUpTimers.get(el);
    if (previousTimer) clearInterval(previousTimer);

    target = Number(target) || 0;
    let current = 0;
    const step = Math.max(1, Math.ceil(target / (900 / 16)));
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current.toLocaleString() + suffix;
      if (current >= target) {
        clearInterval(timer);
        countUpTimers.delete(el);
      }
    }, 16);
    countUpTimers.set(el, timer);
  }

  function renderStatCards(d) {
    const filtered = isFiltered();

    const usersLabel = document.getElementById("stat-users-label");
    if (usersLabel) usersLabel.textContent = filtered ? "New users" : "Total users";
    countUp(document.getElementById("stat-total-users"), d.totalUsers);

    const newEl = document.getElementById("stat-new-this-month");
    if (newEl) {
      newEl.textContent = filtered
        ? `${(d.activeUsers || 0).toLocaleString()} currently active`
        : `▲ ${(d.newUsers || 0).toLocaleString()} this month`;
    }

    const reqLabel = document.getElementById("stat-requests-label");
    if (reqLabel) reqLabel.textContent = "Service requests";
    countUp(document.getElementById("stat-total-requests"), d.totalRequests);

    const reqPeriodEl = document.getElementById("stat-requests-month");
    if (reqPeriodEl) {
      reqPeriodEl.textContent = filtered
        ? "submitted in the selected period"
        : `▲ ${(d.requestsInPeriod || 0).toLocaleString()} this month`;
    }

    const topEl = document.getElementById("stat-top-service");
    if (topEl) topEl.textContent = d.topService?.name || "N/A";

    const topCntEl = document.getElementById("stat-top-service-cnt");
    if (topCntEl) {
      const count = Number(d.topService?.cnt) || 0;
      topCntEl.textContent =
        count > 0
          ? `${count.toLocaleString()} requests ${periodPhrase()}`
          : `No requests ${periodPhrase()}`;
    }

    countUp(document.getElementById("stat-active-users"), d.activeUsers);
    const inactEl = document.getElementById("stat-inactive-users");
    if (inactEl) inactEl.textContent = `${(d.inactiveUsers || 0).toLocaleString()} inactive`;
  }

  function renderPeriodSubtitles(d) {
    const filtered = isFiltered();
    const label = appliedFilter.label;

    const categorySub = document.getElementById("category-sub");
    if (categorySub) {
      categorySub.textContent = filtered
        ? `Category mix · ${label}`
        : "Current month breakdown";
    }

    const growthSub = document.getElementById("growth-sub");
    if (growthSub) {
      const cadence = GRANULARITY_LABELS[d.growthGranularity] || "monthly";
      growthSub.textContent = filtered
        ? `Cumulative registered users · ${cadence} within period`
        : "Cumulative registered users · last 12 months";
    }

    const activeSub = document.getElementById("active-sub");
    if (activeSub) {
      activeSub.textContent = filtered
        ? "Status of users registered in period"
        : "Current user status breakdown";
    }

    const servicesSub = document.getElementById("services-sub");
    if (servicesSub) {
      servicesSub.textContent = filtered
        ? `Ranked by volume · ${label}`
        : "All-time, ranked by volume";
    }

    const statusSub = document.getElementById("status-sub");
    if (statusSub) statusSub.textContent = filtered ? `Counts · ${label}` : "All-time counts";

    const typeSub = document.getElementById("type-sub");
    if (typeSub) typeSub.textContent = filtered ? `Counts · ${label}` : "All-time counts";

    const threadReportsSub = document.getElementById("thread-reports-sub");
    if (threadReportsSub) {
      threadReportsSub.textContent = filtered
        ? `Moderation queue · ${label}`
        : "All-time moderation queue";
    }

    const commentReportsSub = document.getElementById("comment-reports-sub");
    if (commentReportsSub) {
      commentReportsSub.textContent = filtered
        ? `Moderation queue · ${label}`
        : "All-time moderation queue";
    }
  }

  function renderUserRoles(roles) {
    const map = {
      "role-resident": "resident",
      "role-sk_officer": "sk_officer",
      "role-moderator": "moderator",
      "role-admin": "admin",
    };
    Object.entries(map).forEach(([id, key]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (roles?.[key] || 0).toLocaleString();
    });
  }

  function renderAnnouncementStats(stats) {
    if (!stats) return;
    const map = {
      "ann-total": "total",
      "ann-published": "published",
      "ann-drafts": "drafts",
      "ann-archived": "archived",
      "ann-urgent": "urgent",
      "ann-featured": "featured",
    };
    Object.entries(map).forEach(([id, key]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (stats[key] || 0).toLocaleString();
    });
  }

  function renderEventStats(stats) {
    if (!stats) return;
    const map = {
      "evt-total": "total",
      "evt-upcoming": "upcoming",
      "evt-past": "past",
      "evt-this-month": "this_month",
      "evt-next-30": "next_30",
      "evt-this-year": "this_year",
    };
    Object.entries(map).forEach(([id, key]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (stats[key] || 0).toLocaleString();
    });
  }

  function renderThreadStats(stats) {
    if (!stats) return;
    const map = {
      "thr-total": "total",
      "thr-published": "published",
      "thr-removed": "removed",
      "thr-pending": "pending",
      "thr-responded": "responded",
      "thr-resolved": "resolved",
    };
    Object.entries(map).forEach(([id, key]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (stats[key] || 0).toLocaleString();
    });
  }

  function renderReportStats(reportStats) {
    if (!reportStats) return;
    renderReportGroup("thread-report", reportStats.threads || {});
    renderReportGroup("comment-report", reportStats.comments || {});
  }

  function renderReportGroup(prefix, stats) {
    const map = {
      [`${prefix}-total`]: "total",
      [`${prefix}-pending`]: "pending",
      [`${prefix}-reviewed`]: "reviewed",
      [`${prefix}-dismissed`]: "dismissed",
    };
    Object.entries(map).forEach(([id, key]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (stats[key] || 0).toLocaleString();
    });
  }

  function renderServicesTable(services) {
    const tbody = document.getElementById("servicesTableBody");
    if (!tbody) return;

    if (!services || services.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="5" class="an-table-empty">No service data for this period.</td></tr>';
      return;
    }

    tbody.innerHTML = services
      .map((svc, idx) => {
        const categoryLabel = CAT_LABELS[svc.category] || svc.category || "Other";
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
            <td>${parseInt(svc.cnt, 10).toLocaleString()}</td>
          </tr>
        `;
      })
      .join("");
  }

  function renderBars(container, counts, labels, colors) {
    if (!container) return;
    const entries = Object.entries(counts || {});
    if (!entries.length) {
      container.innerHTML = '<div class="an-status-empty">No data available</div>';
      return;
    }

    const maxValue = Math.max(...entries.map(([, value]) => Number(value) || 0)) || 1;

    container.innerHTML = entries
      .map(([key, value]) => {
        const width = Math.min(100, ((Number(value) || 0) / maxValue) * 100);
        const label = labels[key] || key;
        const color = colors[key] || VIOLET;
        return `
          <div class="an-status-item">
            <div class="an-status-bar" style="background: ${color}; width: ${width}%;"></div>
            <div class="an-status-content">
              <span class="an-status-label">${label}</span>
              <span class="an-status-number">${(Number(value) || 0).toLocaleString()}</span>
            </div>
          </div>
        `;
      })
      .join("");
  }

  function renderRequestStatus(statusCounts) {
    renderBars(
      document.getElementById("statusGrid"),
      statusCounts,
      {
        pending: "Pending",
        action_required: "Action Required",
        approved: "Approved",
        rejected: "Rejected",
        cancelled: "Cancelled",
      },
      {
        pending: AMBER,
        action_required: RED,
        approved: GREEN,
        rejected: "#dc2626",
        cancelled: SLATE,
      }
    );
  }

  function renderServiceTypes(typeCounts) {
    renderBars(
      document.getElementById("typeGrid"),
      typeCounts,
      {
        document: "Document Applications",
        appointment: "Appointment Requests",
        info: "Information Inquiries",
      },
      {
        document: VIOLET,
        appointment: AMBER,
        info: TEAL,
      }
    );
  }

  function renderYearFilter(years, selected) {
    const sel = document.getElementById("reqYearFilter");
    if (!sel || !Array.isArray(years)) return;

    sel.innerHTML = "";
    years.forEach((yr) => {
      const opt = document.createElement("option");
      opt.value = yr;
      opt.textContent = yr;
      opt.selected = Number(yr) === Number(selected);
      sel.appendChild(opt);
    });

    const newSel = sel.cloneNode(true);
    sel.parentNode.replaceChild(newSel, sel);

    newSel.hidden = isFiltered();
    newSel.addEventListener("change", function () {
      selectedYear = parseInt(this.value, 10);
      load(selectedYear);
    });
  }

  // ── Charts ─────────────────────────────────────────────────────────

  function renderTimelineChart(timeline, filter) {
    const labels = timeline?.labels || MONTH_LABELS;
    const data = timeline?.data || [];
    const filtered = filter?.mode && filter.mode !== "all";
    const year = timeline?.year ?? selectedYear;

    const titleEl = document.getElementById("timeline-title");
    if (titleEl) titleEl.textContent = timeline?.title || "Service requests per month";

    const subEl = document.getElementById("timeline-sub");
    if (subEl) {
      if (filtered) {
        subEl.textContent = `Requests within ${filter.label}`;
      } else {
        const activeYear = Number.isFinite(Number(year)) ? Number(year) : selectedYear;
        subEl.textContent = `Monthly volume across all service types · ${activeYear}`;
      }
    }

    let colors;
    if (filtered) {
      colors = data.map(() => VIOLET);
    } else {
      const activeYear = Number.isFinite(Number(year)) ? Number(year) : selectedYear;
      const currentMonth =
        new Date().getFullYear() === activeYear ? new Date().getMonth() : 11;
      colors = data.map((_, i) =>
        i <= currentMonth ? VIOLET : "rgba(124,58,237,0.15)"
      );
    }

    if (barChart) {
      barChart.data.labels = labels;
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
        labels,
        datasets: [
          {
            label: "Requests",
            data,
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
            ticks: { font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
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

  function renderDonutChart(breakdown, filter) {
    const rows = Array.isArray(breakdown) ? breakdown : [];
    const canvas = document.getElementById("serviceDonutChart");
    const chartWrap = canvas?.parentElement;
    const emptyText =
      filter?.mode && filter.mode !== "all"
        ? "No requests in this period."
        : "No requests this month.";

    if (!rows.length) {
      if (donutChart) {
        donutChart.destroy();
        donutChart = null;
      }
      if (canvas) canvas.hidden = true;
      if (chartWrap && !chartWrap.querySelector(".an-chart-empty")) {
        const empty = document.createElement("p");
        empty.className = "an-chart-empty";
        empty.textContent = emptyText;
        chartWrap.appendChild(empty);
      }
    } else {
      chartWrap?.querySelector(".an-chart-empty")?.remove();
      if (canvas) canvas.hidden = false;
    }

    const labels = rows.map((b) => CAT_LABELS[b.category] || b.category);
    const values = rows.map((b) => parseInt(b.cnt, 10));
    const colors = rows.map((_, i) => PALETTE[i % PALETTE.length]);

    const legend = document.getElementById("service-legend");
    if (legend) {
      legend.innerHTML = rows.length
        ? rows
            .map(
              (b, i) => `
                <li>
                  <span class="an-legend-dot" style="background:${PALETTE[i % PALETTE.length]}"></span>
                  ${CAT_LABELS[b.category] || b.category}
                  <strong>${parseInt(b.cnt, 10).toLocaleString()}</strong>
                </li>
              `
            )
            .join("")
        : '<li style="color:var(--admin-text-muted);font-size:12px;">No data for this period.</li>';
    }

    if (donutChart) {
      donutChart.data.labels = labels;
      donutChart.data.datasets[0].data = values;
      donutChart.data.datasets[0].backgroundColor = colors;
      donutChart.update();
      return;
    }

    if (!canvas || !rows.length) return;

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

  function renderGrowthChart(labels, data, granularity, filter) {
    if (!labels || !labels.length) return;

    const subEl = document.getElementById("growth-sub");
    if (subEl) {
      if (filter?.mode && filter.mode !== "all") {
        const cadence = GRANULARITY_LABELS[granularity] || "monthly";
        subEl.textContent = `Cumulative registered users · ${cadence} within period`;
      } else {
        subEl.textContent = "Cumulative registered users · last 12 months";
      }
    }

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
            ticks: { font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
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

  function renderActiveChart(active, inactive, pct) {
    const pctEl = document.getElementById("active-pct");
    const activeEl = document.getElementById("active-count");
    const inactiveEl = document.getElementById("inactive-count");

    if (pctEl) pctEl.textContent = `${pct}%`;
    countUp(activeEl, active);
    countUp(inactiveEl, inactive);

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

  // ── Utilities ──────────────────────────────────────────────────────

  function escapeHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

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

  // ── PDF export ─────────────────────────────────────────────────────

  async function exportToPDF() {
    const btn = document.getElementById("exportPdfBtn");
    if (!btn) return;
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
      const periodText = appliedFilter.label || "All time";

      pdf.setFont("helvetica", "bold");
      pdf.setFontSize(18);
      pdf.setTextColor(30, 30, 60);
      pdf.text("SKonnect Analytics Report", margin, 16);

      pdf.setFont("helvetica", "normal");
      pdf.setFontSize(9);
      pdf.setTextColor(100, 116, 139);
      pdf.text(`Period: ${periodText}`, margin, 22);

      const now = new Date().toLocaleDateString("en-PH", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
      pdf.text(`Generated: ${now}`, margin, 27);

      pdf.setDrawColor(220, 220, 235);
      pdf.line(margin, 31, pageW - margin, 31);

      let yPos = 36;

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

      const totalPages = pdf.internal.getNumberOfPages();
      for (let i = 1; i <= totalPages; i++) {
        pdf.setPage(i);
        pdf.setFontSize(8);
        pdf.setTextColor(160, 160, 180);
        pdf.text(
          `SKonnect · Sangguniang Kabataan · ${periodText} · Page ${i} of ${totalPages}`,
          pageW / 2,
          pageH - 5,
          { align: "center" }
        );
      }

      const dateStr = new Date().toISOString().split("T")[0];
      const periodSlug =
        appliedFilter.mode !== "all" && appliedFilter.from && appliedFilter.to
          ? `${appliedFilter.from}_to_${appliedFilter.to}`
          : "all-time";
      pdf.save(`skonnect-analytics-${periodSlug}-${dateStr}.pdf`);
    } catch (err) {
      console.error("PDF export failed:", err);
      alert("Export failed. Please try again.");
    }

    btn.disabled = false;
    btn.innerHTML = origHTML;
  }

  // ── Wiring ─────────────────────────────────────────────────────────

  modeButtons.forEach((button) => {
    button.addEventListener("click", () => setMode(button.dataset.mode));
  });

  applyButton?.addEventListener("click", applyFilter);

  [dayInput, fromInput, toInput].forEach((input) => {
    input?.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        applyFilter();
      }
    });
  });

  document.getElementById("exportPdfBtn")?.addEventListener("click", exportToPDF);

  seedFilterDefaults();
  syncDateFields();
  load(selectedYear);
});
