<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Users</title>
    <link rel="stylesheet" href="../../../styles/management/officer/officer_users.css">
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
    $pageTitle      = 'Users';
    $pageBreadcrumb = [['Home', '#'], ['Users', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- STAT WIDGETS -->
        <section class="off-widgets">

            <div class="off-widget-card widget-cyan">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Residents</span>
                    <p class="widget-number" id="stat-total">0</p>
                    <span class="widget-trend up">&#9650; Active members</span>
                </div>
            </div>

            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Verified Users</span>
                    <p class="widget-number" id="stat-verified">0</p>
                    <span class="widget-trend up">&#9650; ID confirmed</span>
                </div>
            </div>

            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">New This Month</span>
                    <p class="widget-number" id="stat-new">0</p>
                    <span class="widget-trend neutral">Registered recently</span>
                </div>
            </div>

            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Active Requestors</span>
                    <p class="widget-number" id="stat-active">0</p>
                    <span class="widget-trend neutral">Have open requests</span>
                </div>
            </div>

        </section>

        <!-- CONTROLS -->
        <div class="usr-controls-wrap">
            <div class="usr-search-wrap">
                <svg class="usr-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" class="usr-search-input" id="usr-search" placeholder="Search by name, email, or address…">
            </div>

            <div class="usr-filters">
                <select class="usr-select" id="usr-filter-status">
                    <option value="all">All Statuses</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                </select>

                <select class="usr-select" id="usr-filter-age">
                    <option value="all">All Age Groups</option>
                    <option value="15-17">15–17</option>
                    <option value="18-24">18–24</option>
                    <option value="25-30">25–30</option>
                </select>

                <select class="usr-select" id="usr-sort">
                    <option value="name-asc">Name A–Z</option>
                    <option value="name-desc">Name Z–A</option>
                    <option value="date-desc">Newest First</option>
                    <option value="date-asc">Oldest First</option>
                </select>
            </div>

            <span class="usr-count" id="usr-count"></span>
        </div>

        <!-- USER TABLE PANEL -->
        <section class="usr-table-panel">
            <div class="usr-table-wrap">
                <table class="usr-table" id="usr-table">
                    <thead>
                        <tr>
                            <th class="col-usr-name">Resident</th>
                            <th class="col-usr-contact">Contact</th>
                            <th class="col-usr-address">Address</th>
                            <th class="col-usr-age">Age</th>
                            <th class="col-usr-status">Status</th>
                            <th class="col-usr-joined">Joined</th>
                            <th class="col-usr-requests">Requests</th>
                            <th class="col-usr-action">Details</th>
                        </tr>
                    </thead>
                    <tbody id="usr-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <p class="usr-empty" id="usr-empty" style="display:none;">No residents match your search.</p>
        </section>

    </main>
</div>

<!-- ===================== USER DETAIL DRAWER ===================== -->
<div class="usr-drawer-overlay" id="usrDrawerOverlay">
    <aside class="usr-drawer" id="usrDrawer" role="dialog" aria-modal="true" aria-label="User details">

        <div class="usr-drawer-header">
            <div class="usr-drawer-title-wrap">
                <div class="usr-drawer-avatar" id="drawerAvatar"></div>
                <div>
                    <p class="usr-drawer-name" id="drawerName"></p>
                    <span class="usr-drawer-role">Resident</span>
                </div>
            </div>
            <button class="usr-drawer-close" id="usrDrawerClose" aria-label="Close">&times;</button>
        </div>

        <div class="usr-drawer-body">

            <div class="usr-drawer-section">
                <p class="usr-drawer-section-label">Profile Information</p>
                <div class="usr-info-grid">
                    <div class="usr-info-item">
                        <span class="usr-info-key">Email</span>
                        <span class="usr-info-val" id="drawerEmail">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Phone</span>
                        <span class="usr-info-val" id="drawerPhone">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Date of Birth</span>
                        <span class="usr-info-val" id="drawerDob">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Age</span>
                        <span class="usr-info-val" id="drawerAge">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Purok</span>
                        <span class="usr-info-val" id="drawerPurok">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Street Address</span>
                        <span class="usr-info-val" id="drawerAddress">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Civil Status</span>
                        <span class="usr-info-val" id="drawerCivil">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Nationality</span>
                        <span class="usr-info-val" id="drawerNat">—</span>
                    </div>
                    <div class="usr-info-item usr-info-item--full">
                        <span class="usr-info-key">Religion</span>
                        <span class="usr-info-val" id="drawerReligion">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Verification</span>
                        <span class="usr-info-val" id="drawerVerified">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Member Since</span>
                        <span class="usr-info-val" id="drawerJoined">—</span>
                    </div>
                </div>
            </div>

            <div class="usr-drawer-section">
                <p class="usr-drawer-section-label">Membership</p>
                <div class="usr-info-grid">
                    <div class="usr-info-item">
                        <span class="usr-info-key">Education</span>
                        <span class="usr-info-val" id="drawerEdu">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Employment</span>
                        <span class="usr-info-val" id="drawerEmp">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Registered Voter</span>
                        <span class="usr-info-val" id="drawerVoter">—</span>
                    </div>
                    <div class="usr-info-item">
                        <span class="usr-info-key">Gender</span>
                        <span class="usr-info-val" id="drawerGender">—</span>
                    </div>
                </div>
            </div>

            <div class="usr-drawer-section">
                <p class="usr-drawer-section-label">Request Summary</p>
                <div class="usr-req-summary" id="drawerReqSummary"></div>
            </div>

            <div class="usr-drawer-section">
                <p class="usr-drawer-section-label">Recent Requests</p>
                <ul class="usr-activity-list" id="drawerActivity"></ul>
            </div>

        </div>
    </aside>
</div>

<script src="../../../scripts/management/officer/officer_users.js"></script>

</body>
</html>