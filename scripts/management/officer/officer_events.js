(function () {
  var ENDPOINT = "../../../backend/routes/events_routes.php";

  var eventsData = [];
  var activeFilter = "all";
  var pendingDeleteId = null;
  var viewingId = null;

  var calDatesEl = document.getElementById("cal-dates");
  var monthYearEl = document.querySelector(".evmgmt-month-year");
  var prevBtn = document.querySelector(".prev-month");
  var nextBtn = document.querySelector(".next-month");
  var eventListEl = document.getElementById("event-list");
  var listEmptyEl = document.getElementById("list-empty");

  var statTotal = document.getElementById("stat-num-total");
  var statUpcoming = document.getElementById("stat-num-upcoming");
  var statMonth = document.getElementById("stat-num-month");
  var statPast = document.getElementById("stat-num-past");

  var eventModal = document.getElementById("eventModal");
  var modalTitle = document.getElementById("modalTitle");
  var editIndexEl = document.getElementById("editIndex");
  var evTitle = document.getElementById("ev-title");
  var evDate = document.getElementById("ev-date");
  var evTime = document.getElementById("ev-time");
  var evTimeEnd = document.getElementById("ev-time-end");
  var evLocation = document.getElementById("ev-location");
  var evDesc = document.getElementById("ev-desc");
  var errTitle = document.getElementById("err-title");
  var errDate = document.getElementById("err-date");

  var viewModal = document.getElementById("viewModal");
  var viewModalBody = document.getElementById("viewModalBody");
  var deleteModal = document.getElementById("deleteModal");
  var deleteEventName = document.getElementById("deleteEventName");

  var MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  var MONTHS_SHORT = [
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

  var today = new Date();
  today.setHours(0, 0, 0, 0);
  var current = new Date(today.getFullYear(), today.getMonth(), 1);

  function parseDateStr(s) {
    var p = s.split("-").map(Number);
    return new Date(p[0], p[1] - 1, p[2]);
  }
  function formatDateDisplay(s) {
    var d = parseDateStr(s);
    return (
      MONTHS_SHORT[d.getMonth()] + " " + d.getDate() + ", " + d.getFullYear()
    );
  }
  function formatTime(t) {
    if (!t) return null;
    var p = t.split(":");
    var h = parseInt(p[0], 10),
      m = p[1];
    return (h % 12 || 12) + ":" + m + " " + (h >= 12 ? "PM" : "AM");
  }
  function formatTimeRange(start, end) {
    var s = formatTime(start);
    if (!s) return null;
    var e = formatTime(end);
    return e ? s + " – " + e : s;
  }
  function daysUntil(s) {
    var diff = Math.round((parseDateStr(s) - today) / 86400000);
    if (diff === 0) return "Today";
    if (diff === 1) return "Tomorrow";
    if (diff < 0) return Math.abs(diff) + "d ago";
    return "In " + diff + " days";
  }
  function isPast(s) {
    return parseDateStr(s) < today;
  }
  function isToday(s) {
    return parseDateStr(s).getTime() === today.getTime();
  }
  function isSameMonth(s, y, m) {
    var p = s.split("-").map(Number);
    return p[0] === y && p[1] - 1 === m;
  }
  function escHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }
  function findById(id) {
    return (
      eventsData.find(function (e) {
        return e.id === id;
      }) || null
    );
  }

  /* ── API ──────────────────────────────────────────────── */

  function apiFetch(params) {
    var fd = new FormData();
    Object.keys(params).forEach(function (k) {
      fd.append(k, params[k]);
    });
    return fetch(ENDPOINT, { method: "POST", body: fd }).then(function (r) {
      return r.json();
    });
  }

  function loadEvents() {
    return apiFetch({ action: "list" }).then(function (res) {
      if (res.success) {
        eventsData = res.data.map(function (e) {
          return {
            id: parseInt(e.id, 10),
            title: e.title,
            date: e.event_date,
            time: e.event_time || "",
            time_end: e.event_time_end || "",
            location: e.location || "",
            description: e.description || "",
            author: e.author_name || "",
          };
        });
      }
    });
  }

  /* ── STATS ─────────────────────────────────────────────── */

  function updateStats() {
    var now = today;
    statTotal.textContent = eventsData.length;
    statUpcoming.textContent = eventsData.filter(function (e) {
      return !isPast(e.date) && !isToday(e.date);
    }).length;
    statMonth.textContent = eventsData.filter(function (e) {
      var d = parseDateStr(e.date);
      return (
        d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth()
      );
    }).length;
    statPast.textContent = eventsData.filter(function (e) {
      return isPast(e.date);
    }).length;
  }

  /* ── CALENDAR ──────────────────────────────────────────── */

  function renderCalendar() {
    var year = current.getFullYear(),
      month = current.getMonth();
    monthYearEl.textContent = MONTHS[month] + " " + year;
    calDatesEl.innerHTML = "";

    var firstDay = new Date(year, month, 1).getDay();
    var lastDate = new Date(year, month + 1, 0).getDate();

    var lookup = {};
    eventsData.forEach(function (ev) {
      if (isSameMonth(ev.date, year, month)) {
        if (!lookup[ev.date]) lookup[ev.date] = [];
        lookup[ev.date].push(ev);
      }
    });

    for (var i = 0; i < firstDay; i++) {
      var blank = document.createElement("div");
      blank.className = "ecal-day empty";
      calDatesEl.appendChild(blank);
    }

    for (var d = 1; d <= lastDate; d++) {
      var dateStr =
        year +
        "-" +
        String(month + 1).padStart(2, "0") +
        "-" +
        String(d).padStart(2, "0");
      var cell = document.createElement("div");
      cell.className = "ecal-day";
      cell.textContent = d;

      if (isToday(dateStr)) cell.classList.add("today");
      if (lookup[dateStr]) {
        cell.classList.add("has-event");
        if (isPast(dateStr) && !isToday(dateStr))
          cell.classList.add("past-event");
        var dot = document.createElement("span");
        dot.className = "evmgmt-cal-dot";
        cell.appendChild(dot);
        (function (ds) {
          cell.addEventListener("click", function () {
            filterToDate(ds);
          });
        })(dateStr);
      }

      calDatesEl.appendChild(cell);
    }
  }

  function filterToDate(dateStr) {
    document.querySelectorAll(".evmgmt-filter-btn").forEach(function (b) {
      b.classList.remove("active");
    });
    activeFilter = "date:" + dateStr;
    renderEventList();
  }

  /* ── EVENT LIST ─────────────────────────────────────────── */

  function renderEventList() {
    var filtered = eventsData.filter(function (e) {
      if (activeFilter === "upcoming") return !isPast(e.date);
      if (activeFilter === "past") return isPast(e.date);
      if (activeFilter.startsWith("date:"))
        return e.date === activeFilter.slice(5);
      return true;
    });

    filtered.sort(function (a, b) {
      return parseDateStr(a.date) - parseDateStr(b.date);
    });

    eventListEl.innerHTML = "";
    listEmptyEl.style.display = filtered.length ? "none" : "block";

    filtered.forEach(function (ev) {
      var d = parseDateStr(ev.date);
      var past = isPast(ev.date);
      var tday = isToday(ev.date);
      var badge = tday
        ? '<span class="evmgmt-badge evmgmt-badge-today">Today</span>'
        : past
        ? '<span class="evmgmt-badge evmgmt-badge-past">Past</span>'
        : '<span class="evmgmt-badge evmgmt-badge-upcoming">Upcoming</span>';

      var timeMeta = ev.time
        ? '<span class="evmgmt-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>' +
          escHtml(formatTimeRange(ev.time, ev.time_end)) +
          "</span>"
        : "";
      var locMeta = ev.location
        ? '<span class="evmgmt-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>' +
          escHtml(ev.location) +
          "</span>"
        : "";

      var li = document.createElement("li");
      li.className = "evmgmt-event-item" + (past ? " is-past" : "");
      li.innerHTML =
        '<div class="evmgmt-date-badge">' +
        '<span class="evmgmt-date-day">' +
        d.getDate() +
        "</span>" +
        '<span class="evmgmt-date-mon">' +
        MONTHS_SHORT[d.getMonth()] +
        "</span>" +
        "</div>" +
        '<div class="evmgmt-event-info">' +
        '<div class="evmgmt-event-title-row">' +
        '<span class="evmgmt-event-name" title="' +
        escHtml(ev.title) +
        '">' +
        escHtml(ev.title) +
        "</span>" +
        badge +
        "</div>" +
        '<div class="evmgmt-event-meta">' +
        timeMeta +
        locMeta +
        '<span class="evmgmt-countdown">' +
        daysUntil(ev.date) +
        "</span>" +
        "</div>" +
        "</div>" +
        '<div class="evmgmt-event-actions">' +
        '<button class="evmgmt-action-btn btn-view" data-id="' +
        ev.id +
        '" title="View">' +
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>' +
        "</button>" +
        '<button class="evmgmt-action-btn btn-edit" data-id="' +
        ev.id +
        '" title="Edit">' +
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>' +
        "</button>" +
        '<button class="evmgmt-action-btn btn-delete" data-id="' +
        ev.id +
        '" title="Delete">' +
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
        "</button>" +
        "</div>";

      eventListEl.appendChild(li);
    });
  }

  /* ── ADD / EDIT MODAL ───────────────────────────────────── */

  function openAddModal() {
    editIndexEl.value = "";
    modalTitle.textContent = "Add New Event";
    evTitle.value = "";
    evDate.value = "";
    evTime.value = "";
    evTimeEnd.value = "";
    evLocation.value = "";
    evDesc.value = "";
    clearErrors();
    openModal(eventModal);
  }

  function openEditModal(id) {
    var ev = findById(id);
    if (!ev) return;
    closeModal(viewModal);
    editIndexEl.value = ev.id;
    modalTitle.textContent = "Edit Event";
    evTitle.value = ev.title;
    evDate.value = ev.date;
    evTime.value = ev.time;
    evTimeEnd.value = ev.time_end;
    evLocation.value = ev.location;
    evDesc.value = ev.description;
    clearErrors();
    openModal(eventModal);
  }

  function clearErrors() {
    errTitle.textContent = "";
    errDate.textContent = "";
    evTitle.classList.remove("is-error");
    evDate.classList.remove("is-error");
  }

  function validateForm() {
    var ok = true;
    clearErrors();
    if (!evTitle.value.trim()) {
      errTitle.textContent = "Event title is required.";
      evTitle.classList.add("is-error");
      ok = false;
    }
    if (!evDate.value) {
      errDate.textContent = "Date is required.";
      evDate.classList.add("is-error");
      ok = false;
    }
    return ok;
  }

  function saveEvent() {
    if (!validateForm()) return;

    var btn = document.getElementById("saveEvent");
    var idVal = editIndexEl.value;
    var params = {
      action: idVal ? "update" : "create",
      title: evTitle.value.trim(),
      event_date: evDate.value,
      event_time: evTime.value,
      event_time_end: evTimeEnd.value,
      location: evLocation.value.trim(),
      description: evDesc.value.trim(),
    };
    if (idVal) params.id = idVal;

    btn.disabled = true;
    btn.textContent = "Saving…";

    apiFetch(params)
      .then(function (res) {
        btn.disabled = false;
        btn.textContent = "Save Event";
        if (!res.success) {
          alert(res.message || "Save failed.");
          return;
        }
        closeModal(eventModal);
        loadEvents().then(refresh);
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = "Save Event";
        alert("Network error. Please try again.");
      });
  }

  /* ── VIEW MODAL ─────────────────────────────────────────── */

  function openViewModal(id) {
    var ev = findById(id);
    if (!ev) return;
    viewingId = id;

    var timeHtml = ev.time
      ? '<li class="evmgmt-view-detail-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg><span><strong>Time:</strong> ' +
        escHtml(formatTimeRange(ev.time, ev.time_end)) +
        "</span></li>"
      : "";
    var locHtml = ev.location
      ? '<li class="evmgmt-view-detail-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg><span><strong>Location:</strong> ' +
        escHtml(ev.location) +
        "</span></li>"
      : "";
    var descHtml = ev.description
      ? '<div class="evmgmt-view-desc">' + escHtml(ev.description) + "</div>"
      : "";

    viewModalBody.innerHTML =
      '<p class="evmgmt-view-title">' +
      escHtml(ev.title) +
      "</p>" +
      '<ul class="evmgmt-view-detail-list">' +
      '<li class="evmgmt-view-detail-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg><span><strong>Date:</strong> ' +
      formatDateDisplay(ev.date) +
      ' &nbsp;<em style="color:#d97706;font-size:11px;font-weight:600;">' +
      daysUntil(ev.date) +
      "</em></span></li>" +
      timeHtml +
      locHtml +
      "</ul>" +
      descHtml;

    openModal(viewModal);
  }

  /* ── DELETE MODAL ───────────────────────────────────────── */

  function openDeleteModal(id) {
    var ev = findById(id);
    if (!ev) return;
    pendingDeleteId = id;
    deleteEventName.textContent = '"' + ev.title + '"';
    openModal(deleteModal);
  }

  function confirmDelete() {
    if (pendingDeleteId === null) return;
    var btn = document.getElementById("confirmDelete");
    btn.disabled = true;
    btn.textContent = "Deleting…";

    apiFetch({ action: "delete", id: pendingDeleteId })
      .then(function (res) {
        btn.disabled = false;
        btn.textContent = "Delete";
        if (!res.success) {
          alert(res.message || "Delete failed.");
          return;
        }
        pendingDeleteId = null;
        closeModal(deleteModal);
        loadEvents().then(refresh);
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = "Delete";
        alert("Network error. Please try again.");
      });
  }

  /* ── MODAL HELPERS ──────────────────────────────────────── */

  function getScrollbarWidth() {
    return window.innerWidth - document.documentElement.clientWidth;
  }

  function setScrollbarVariable() {
    const scrollbarWidth = getScrollbarWidth();
    document.documentElement.style.setProperty(
      "--scrollbar-w",
      scrollbarWidth + "px"
    );
  }

  function openModal(el) {
    setScrollbarVariable();
    el.classList.add("is-open");
    document.documentElement.classList.add("modal-open");
  }

  function closeModal(el) {
    el.classList.remove("is-open");
    if (!document.querySelector(".evmgmt-modal-overlay.is-open")) {
      document.documentElement.classList.remove("modal-open");
    }
  }

  window.addEventListener("resize", function () {
    if (document.querySelector(".evmgmt-modal-overlay.is-open")) {
      setScrollbarVariable();
    }
  });

  /* ── REFRESH ────────────────────────────────────────────── */

  function refresh() {
    updateStats();
    renderCalendar();
    renderEventList();
  }

  /* ── EVENT BINDINGS ─────────────────────────────────────── */

  document
    .getElementById("openAddModal")
    .addEventListener("click", openAddModal);
  document.getElementById("closeModal").addEventListener("click", function () {
    closeModal(eventModal);
  });
  document.getElementById("cancelModal").addEventListener("click", function () {
    closeModal(eventModal);
  });
  document.getElementById("saveEvent").addEventListener("click", saveEvent);

  document
    .getElementById("closeViewModal")
    .addEventListener("click", function () {
      closeModal(viewModal);
    });
  document
    .getElementById("closeViewModalBtn")
    .addEventListener("click", function () {
      closeModal(viewModal);
    });
  document
    .getElementById("editFromView")
    .addEventListener("click", function () {
      if (viewingId !== null) openEditModal(viewingId);
    });

  document
    .getElementById("closeDeleteModal")
    .addEventListener("click", function () {
      closeModal(deleteModal);
    });
  document
    .getElementById("cancelDelete")
    .addEventListener("click", function () {
      closeModal(deleteModal);
    });
  document
    .getElementById("confirmDelete")
    .addEventListener("click", confirmDelete);

  [eventModal, viewModal, deleteModal].forEach(function (ov) {
    ov.addEventListener("click", function (e) {
      if (e.target === ov) closeModal(ov);
    });
  });

  eventListEl.addEventListener("click", function (e) {
    var btn = e.target.closest(".evmgmt-action-btn");
    if (!btn) return;
    var id = parseInt(btn.dataset.id, 10);
    if (btn.classList.contains("btn-view")) openViewModal(id);
    if (btn.classList.contains("btn-edit")) openEditModal(id);
    if (btn.classList.contains("btn-delete")) openDeleteModal(id);
  });

  document.querySelectorAll(".evmgmt-filter-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".evmgmt-filter-btn").forEach(function (b) {
        b.classList.remove("active");
      });
      btn.classList.add("active");
      activeFilter = btn.dataset.filter;
      renderEventList();
    });
  });

  prevBtn.addEventListener("click", function () {
    current = new Date(current.getFullYear(), current.getMonth() - 1, 1);
    renderCalendar();
  });
  nextBtn.addEventListener("click", function () {
    current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
    renderCalendar();
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape")
      [eventModal, viewModal, deleteModal].forEach(function (m) {
        if (m.classList.contains("is-open")) closeModal(m);
      });
  });

  /* ── INIT ───────────────────────────────────────────────── */
  loadEvents().then(refresh);
})();
