<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Events Management</title>
    <link rel="stylesheet" href="../../../styles/management/officer/officer_events.css">
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="off-content">

    <?php
    $pageTitle      = 'Events Management';
    $pageBreadcrumb = [['Home', '#'], ['Events', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- PAGE HEADER -->
        <div class="evmgmt-page-header">
            <div class="evmgmt-header-left">
                <h1 class="evmgmt-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    Events Management
                </h1>
                <p class="evmgmt-page-subtitle">Schedule and manage community events visible to all residents.</p>
            </div>
            <button class="evmgmt-btn-primary" id="openAddModal">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Event
            </button>
        </div>

        <!-- STAT STRIP -->
        <div class="evmgmt-stat-strip">
            <div class="evmgmt-stat-card" id="stat-total">
                <span class="evmgmt-stat-num" id="stat-num-total">0</span>
                <span class="evmgmt-stat-lbl">Total Events</span>
            </div>
            <div class="evmgmt-stat-card" id="stat-upcoming">
                <span class="evmgmt-stat-num" id="stat-num-upcoming">0</span>
                <span class="evmgmt-stat-lbl">Upcoming</span>
            </div>
            <div class="evmgmt-stat-card" id="stat-this-month">
                <span class="evmgmt-stat-num" id="stat-num-month">0</span>
                <span class="evmgmt-stat-lbl">This Month</span>
            </div>
            <div class="evmgmt-stat-card" id="stat-past">
                <span class="evmgmt-stat-num" id="stat-num-past">0</span>
                <span class="evmgmt-stat-lbl">Past Events</span>
            </div>
        </div>

        <!-- MAIN GRID: CALENDAR + EVENT LIST -->
        <div class="evmgmt-main-grid">

            <!-- LEFT: CALENDAR -->
            <section class="evmgmt-calendar-panel">
                <div class="evmgmt-panel-header">
                    <h2 class="section-label">Calendar</h2>
                    <div class="evmgmt-cal-nav">
                        <button class="evmgmt-nav-btn prev-month" aria-label="Previous month">&#8249;</button>
                        <span class="evmgmt-month-year"></span>
                        <button class="evmgmt-nav-btn next-month" aria-label="Next month">&#8250;</button>
                    </div>
                </div>

                <div class="evmgmt-calendar">
                    <div class="evmgmt-cal-days">
                        <div>Sun</div><div>Mon</div><div>Tue</div>
                        <div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                    </div>
                    <div class="evmgmt-cal-dates" id="cal-dates">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- CALENDAR LEGEND -->
                <div class="evmgmt-cal-legend">
                    <div class="evmgmt-legend-item">
                        <div class="evmgmt-legend-dot evmgmt-legend-today"></div>
                        <span>Today</span>
                    </div>
                    <div class="evmgmt-legend-item">
                        <div class="evmgmt-legend-dot evmgmt-legend-event"></div>
                        <span>Event</span>
                    </div>
                    <div class="evmgmt-legend-item">
                        <div class="evmgmt-legend-dot evmgmt-legend-past"></div>
                        <span>Past Event</span>
                    </div>
                </div>
            </section>

            <!-- RIGHT: EVENT LIST -->
            <section class="evmgmt-list-panel">
                <div class="evmgmt-panel-header">
                    <h2 class="section-label">All Events</h2>
                    <div class="evmgmt-filter-row">
                        <button class="evmgmt-filter-btn active" data-filter="all">All</button>
                        <button class="evmgmt-filter-btn" data-filter="upcoming">Upcoming</button>
                        <button class="evmgmt-filter-btn" data-filter="past">Past</button>
                    </div>
                </div>

                <ul class="evmgmt-event-list" id="event-list">
                    <!-- Populated by JS -->
                </ul>
                <p class="evmgmt-list-empty" id="list-empty" style="display:none;">No events found.</p>
            </section>

        </div>

    </main>
</div>

<!-- ===================== ADD / EDIT MODAL ===================== -->
<div class="evmgmt-modal-overlay" id="eventModal">
    <div class="evmgmt-modal">
        <div class="evmgmt-modal-header">
            <h3 class="evmgmt-modal-title" id="modalTitle">Add New Event</h3>
            <button class="evmgmt-modal-close" id="closeModal" aria-label="Close">&times;</button>
        </div>
        <div class="evmgmt-modal-body">
            <input type="hidden" id="editIndex" value="">

            <div class="evmgmt-field-group evmgmt-field-full">
                <label class="evmgmt-label" for="ev-title">Event Title <span class="evmgmt-required">*</span></label>
                <input class="evmgmt-input" type="text" id="ev-title" placeholder="e.g. Barangay Youth Summit 2026" maxlength="100">
                <span class="evmgmt-field-error" id="err-title"></span>
            </div>

            <div class="evmgmt-field-row evmgmt-field-row--3">
                <div class="evmgmt-field-group">
                    <label class="evmgmt-label" for="ev-date">Date <span class="evmgmt-required">*</span></label>
                    <input class="evmgmt-input" type="date" id="ev-date">
                    <span class="evmgmt-field-error" id="err-date"></span>
                </div>
                <div class="evmgmt-field-group">
                    <label class="evmgmt-label" for="ev-time">Start Time</label>
                    <input class="evmgmt-input" type="time" id="ev-time">
                </div>
                <div class="evmgmt-field-group">
                    <label class="evmgmt-label" for="ev-time-end">End Time</label>
                    <input class="evmgmt-input" type="time" id="ev-time-end">
                </div>
            </div>

            <div class="evmgmt-field-group evmgmt-field-full">
                <label class="evmgmt-label" for="ev-location">Location</label>
                <input class="evmgmt-input" type="text" id="ev-location" placeholder="e.g. Barangay Hall, Quezon City" maxlength="120">
            </div>

            <div class="evmgmt-field-group evmgmt-field-full">
                <label class="evmgmt-label" for="ev-desc">Description / Notes</label>
                <textarea class="evmgmt-textarea" id="ev-desc" rows="4" placeholder="Add details about this event..."></textarea>
            </div>
        </div>
        <div class="evmgmt-modal-footer">
            <button class="evmgmt-btn-ghost" id="cancelModal">Cancel</button>
            <button class="evmgmt-btn-primary" id="saveEvent">Save Event</button>
        </div>
    </div>
</div>

<!-- ===================== VIEW MODAL ===================== -->
<div class="evmgmt-modal-overlay" id="viewModal">
    <div class="evmgmt-modal evmgmt-modal--view">
        <div class="evmgmt-modal-header">
            <h3 class="evmgmt-modal-title" id="viewModalTitle">Event Details</h3>
            <button class="evmgmt-modal-close" id="closeViewModal" aria-label="Close">&times;</button>
        </div>
        <div class="evmgmt-modal-body" id="viewModalBody">
            <!-- Populated by JS -->
        </div>
        <div class="evmgmt-modal-footer">
            <button class="evmgmt-btn-ghost" id="closeViewModalBtn">Close</button>
            <button class="evmgmt-btn-primary" id="editFromView">Edit Event</button>
        </div>
    </div>
</div>

<!-- ===================== DELETE CONFIRM MODAL ===================== -->
<div class="evmgmt-modal-overlay" id="deleteModal">
    <div class="evmgmt-modal evmgmt-modal--sm">
        <div class="evmgmt-modal-header">
            <h3 class="evmgmt-modal-title">Delete Event</h3>
            <button class="evmgmt-modal-close" id="closeDeleteModal" aria-label="Close">&times;</button>
        </div>
        <div class="evmgmt-modal-body">
            <p class="evmgmt-delete-msg">Are you sure you want to delete <strong id="deleteEventName"></strong>? This action cannot be undone.</p>
        </div>
        <div class="evmgmt-modal-footer">
            <button class="evmgmt-btn-ghost" id="cancelDelete">Cancel</button>
            <button class="evmgmt-btn-danger" id="confirmDelete">Delete</button>
        </div>
    </div>
</div>

<script src="../../../scripts/management/officer/officer_events.js"></script>

</body>
</html>