<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');

require_once __DIR__ . '/../../../backend/controllers/ServiceRequestController.php';

$controller = new ServiceRequestController();
$requests   = $controller->getAll();   // all applications, newest first
$counts     = $controller->getStatusCounts();

// Helper: build initials from full_name or first+last name
function getInitials(array $req): string {
    $name = trim($req['full_name'] ?? '');
    if (!$name) $name = trim(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? ''));
    $parts = preg_split('/\s+/', $name);
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out ?: '?';
}

// Map DB status → display label
function statusLabel(string $status): string {
    return match($status) {
        'pending'          => 'Pending',
        'action_required'  => 'Action Required',
        'approved'         => 'Approved',
        'rejected'         => 'Declined',
        'cancelled'        => 'Cancelled',
        default            => ucfirst(str_replace('_', ' ', $status)),
    };
}

// Map DB status → CSS class used in the stylesheet
function statusCss(string $status): string {
    return match($status) {
        'action_required' => 'action-required',
        'rejected'        => 'declined',
        'cancelled'       => 'cancelled',
        default           => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Requests — SK Officer</title>
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_requests.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <main class="off-content">

    <?php
    $pageTitle      = 'Requests';
    $pageBreadcrumb = [['Home', '#'], ['Operations', null], ['Requests', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- STAT WIDGETS -->
        <section class="off-widgets">

            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Pending</span>
                    <p class="widget-number"><?= $counts['pending'] ?></p>
                    <span class="widget-trend warning">&#9650; Needs attention</span>
                </div>
            </div>

            <div class="off-widget-card widget-orange">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Action Required</span>
                    <p class="widget-number"><?= $counts['action_required'] ?></p>
                    <span class="widget-trend await">Awaiting resident</span>
                </div>
            </div>

            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Approved</span>
                    <p class="widget-number"><?= $counts['approved'] ?></p>
                    <span class="widget-trend up">This month</span>
                </div>
            </div>

            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Declined</span>
                    <p class="widget-number"><?= $counts['rejected'] ?></p>
                    <span class="widget-trend danger">This month</span>
                </div>
            </div>

        </section>

        <!-- STATUS TABS + CONTROLS -->
        <div class="req-controls-wrap">

            <div class="req-tabs" role="tablist">
                <button class="req-tab active" data-status="all"              role="tab">All <span class="req-tab-count"><?= count($requests) ?></span></button>
                <button class="req-tab"        data-status="pending"          role="tab">Pending <span class="req-tab-count"><?= $counts['pending'] ?></span></button>
                <button class="req-tab"        data-status="action-required"  role="tab">Action Required <span class="req-tab-count"><?= $counts['action_required'] ?></span></button>
                <button class="req-tab"        data-status="approved"         role="tab">Approved <span class="req-tab-count"><?= $counts['approved'] ?></span></button>
                <button class="req-tab"        data-status="declined"         role="tab">Declined <span class="req-tab-count"><?= $counts['rejected'] ?></span></button>
                <button class="req-tab"        data-status="cancelled"        role="tab">Cancelled <span class="req-tab-count"><?= $counts['cancelled'] ?? 0 ?></span></button>
            </div>

            <div class="req-filters">
                <div class="req-search-wrap">
                    <svg class="req-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" id="req-search" class="req-search-input" placeholder="Search by resident or service…">
                </div>
                <select id="req-category" class="req-select">
                    <option value="all">All Categories</option>
                    <option value="medical">Medical</option>
                    <option value="education">Education</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="assistance">Assistance</option>
                    <option value="legal">Legal</option>
                    <option value="other">Other</option>
                </select>
                <select id="req-sort" class="req-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>

        </div>

        <!-- REQUESTS TABLE -->
        <div class="req-table-panel">

            <div class="panel-header">
                <h2 class="section-label">All Requests</h2>
                <span class="req-count" id="req-count">Showing <?= count($requests) ?> request<?= count($requests) !== 1 ? 's' : '' ?></span>
            </div>

            <div class="req-table-wrap">
                <table class="req-table" id="req-table">
                    <thead>
                        <tr>
                            <th class="col-resident">Resident</th>
                            <th class="col-service">Service</th>
                            <th class="col-category">Category</th>
                            <th class="col-purpose">Purpose / Details</th>
                            <th class="col-date sortable" data-col="date">Date Submitted <span class="sort-icon">↕</span></th>
                            <th class="col-files">Files</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="req-tbody">

                    <?php foreach ($requests as $req):
                        $initials   = getInitials($req);
                        $fullName   = trim($req['full_name'] ?: ($req['first_name'] . ' ' . $req['last_name']));
                        $statusDb   = $req['status'];
                        $statusCls  = statusCss($statusDb);
                        $statusLbl  = statusLabel($statusDb);
                        $submittedF = date('M j, Y', strtotime($req['submitted_at']));
                        $submittedD = date('Y-m-d', strtotime($req['submitted_at']));
                        $docCount   = (int)($req['doc_count'] ?? 0);
                        $purpose    = htmlspecialchars($req['purpose'] ?? '', ENT_QUOTES);
                    ?>
                        <tr data-id="<?= $req['id'] ?>"
                            data-status="<?= $statusCls ?>"
                            data-category="<?= htmlspecialchars($req['service_category']) ?>"
                            data-date="<?= $submittedD ?>"
                            data-resident="<?= strtolower(htmlspecialchars($fullName)) ?>"
                            data-service="<?= strtolower(htmlspecialchars($req['service_name'])) ?>"
                            data-purpose="<?= $purpose ?>"
                            data-submitted-f="<?= htmlspecialchars($submittedF) ?>"
                            data-has-files="<?= $docCount > 0 ? 'true' : 'false' ?>"
                            data-file-count="<?= $docCount ?>">

                            <td class="col-resident">
                                <div class="req-resident-cell">
                                    <div class="req-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <span class="req-resident-name" title="<?= htmlspecialchars($fullName) ?>"><?= htmlspecialchars($fullName) ?></span>
                                </div>
                            </td>

                            <td class="col-service">
                                <span class="req-service-badge badge-<?= htmlspecialchars($req['service_category']) ?>">
                                    <?= htmlspecialchars($req['service_name']) ?>
                                </span>
                            </td>

                            <td class="col-category">
                                <span class="req-category-text"><?= htmlspecialchars(ucfirst($req['service_category'])) ?></span>
                            </td>

                            <td class="col-purpose">
                                <span class="req-purpose-text"><?= htmlspecialchars($req['purpose'] ?? '—') ?></span>
                            </td>

                            <td class="col-date">
                                <time datetime="<?= $submittedD ?>"><?= $submittedF ?></time>
                            </td>

                            <td class="col-files">
                                <?php if ($docCount > 0): ?>
                                    <span class="req-files-badge">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                                        <?= $docCount ?> file<?= $docCount !== 1 ? 's' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="req-no-files">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="col-status">
                                <span class="req-status-pill status-<?= $statusCls ?>">
                                    <?= $statusLbl ?>
                                </span>
                            </td>

                            <td class="col-actions">
                                <div class="req-action-group">
                                    <button class="req-btn-view" data-id="<?= $req['id'] ?>" title="View full request">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        View
                                    </button>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <div class="req-no-results" id="req-no-results" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <p>No requests match your current filters.</p>
            </div>

        </div>

        <!-- PAGINATION -->
        <section class="off-pagination">
            <button class="off-page-btn" id="req-prev-btn" disabled>&#8249; Previous</button>
            <div class="off-page-numbers" id="req-page-numbers">
                <button class="off-page-num active">1</button>
            </div>
            <button class="off-page-btn" id="req-next-btn" disabled>Next &#8250;</button>
        </section>

    </main>
</div>

<!-- ── VIEW / DETAIL MODAL ──────────────────────────────────── -->
<div class="req-modal-overlay" id="req-drawer-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-modal" id="req-drawer">

        <div class="req-modal-header">
            <div class="req-drawer-header-left">
                <div class="req-drawer-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <div>
                    <h2 class="req-drawer-title" id="drawer-title">Request Details</h2>
                    <p class="req-drawer-subtitle" id="drawer-subtitle">Request #—</p>
                </div>
            </div>
            <button class="req-drawer-close" id="req-drawer-close" aria-label="Close modal">&times;</button>
        </div>

        <!-- Loading state -->
        <div class="req-modal-body req-drawer-body" id="drawer-loading" style="display:none;">
            <p style="text-align:center;padding:40px 0;color:var(--text-muted);">Loading details…</p>
        </div>

        <div class="req-modal-body req-drawer-body" id="drawer-content">

            <!-- Resident info -->
            <div class="drawer-section">
                <p class="drawer-section-label">Resident</p>
                <div class="drawer-resident-row">
                    <div class="drawer-avatar" id="drawer-avatar">—</div>
                    <div>
                        <p class="drawer-resident-name" id="drawer-resident-name">—</p>
                        <p class="drawer-resident-sub" id="drawer-resident-sub">Barangay Resident</p>
                    </div>
                </div>
            </div>

            <!-- Contact details row -->
            <div class="drawer-row-2">
                <div class="drawer-section">
                    <p class="drawer-section-label">Contact Number</p>
                    <p class="drawer-value" id="drawer-contact">—</p>
                </div>
                <div class="drawer-section">
                    <p class="drawer-section-label">Email Address</p>
                    <p class="drawer-value" id="drawer-email">—</p>
                </div>
            </div>

            <div class="drawer-section">
                <p class="drawer-section-label">Home Address</p>
                <p class="drawer-value" id="drawer-address">—</p>
            </div>

            <!-- Service + Status -->
            <div class="drawer-row-2">
                <div class="drawer-section">
                    <p class="drawer-section-label">Service Requested</p>
                    <p class="drawer-value" id="drawer-service">—</p>
                </div>
                <div class="drawer-section">
                    <p class="drawer-section-label">Category</p>
                    <p class="drawer-value" id="drawer-category">—</p>
                </div>
            </div>

            <div class="drawer-row-2">
                <div class="drawer-section">
                    <p class="drawer-section-label">Current Status</p>
                    <p class="drawer-value" id="drawer-status-wrap">—</p>
                </div>
                <div class="drawer-section">
                    <p class="drawer-section-label">Date Submitted</p>
                    <p class="drawer-value" id="drawer-date">—</p>
                </div>
            </div>

            <!-- Purpose -->
            <div class="drawer-section">
                <p class="drawer-section-label">Purpose / Details</p>
                <p class="drawer-value drawer-value--purpose" id="drawer-purpose">—</p>
            </div>

            <!-- Submitted Documents -->
            <div class="drawer-section" id="drawer-files-section">
                <p class="drawer-section-label">Submitted Documents</p>
                <div id="drawer-files">—</div>
            </div>

            <!-- Fulfillment File (shown only when approved and a file was attached) -->
            <div class="drawer-section" id="drawer-fulfillment-section" style="display:none;">
                <p class="drawer-section-label">
                    Fulfillment File
                </p>
                <div id="drawer-fulfillment-file">—</div>
            </div>

            <!-- Officer Notes Thread -->
            <div class="drawer-section" id="drawer-notes-thread-section" style="display:none;">
                <p class="drawer-section-label">Officer Notes Thread</p>
                <div id="drawer-notes-thread"></div>
            </div>

            <!-- Add Note textarea (hidden once approved/rejected) -->
            <div class="drawer-section drawer-section--response" id="drawer-note-input-section">
                <p class="drawer-section-label">
                    Add Officer Note
                    <span class="drawer-optional"> — sending a note sets status to "Action Required"</span>
                </p>
                <textarea id="drawer-response" class="drawer-textarea" rows="3"
                    placeholder="Write a note or request additional information from the resident…"></textarea>
            </div>

        </div>

        <div class="req-modal-footer req-drawer-footer" id="req-drawer-footer">
            <!-- Buttons injected by JS based on status -->
        </div>

    </div>
</div>

<!-- CONFIRM MODAL -->
<div class="req-confirm-overlay" id="req-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-confirm-box">
        <div class="req-confirm-icon" id="req-confirm-icon">⚠️</div>
        <h3 class="req-confirm-title" id="req-confirm-title">Confirm Action</h3>
        <p class="req-confirm-body" id="req-confirm-body">Are you sure?</p>
        <div class="req-confirm-footer">
            <button class="btn-off-sm" id="req-confirm-cancel">Cancel</button>
            <button class="req-confirm-ok" id="req-confirm-ok">Confirm</button>
        </div>
    </div>
</div>

<!-- APPROVE MODAL -->
<div class="req-action-modal-overlay" id="req-approve-modal-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-action-modal">
        <div class="req-action-modal-header">
            <div class="req-action-modal-header-left">
                <span class="req-action-modal-icon req-action-modal-icon--approve">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </span>
                <h3 class="req-action-modal-title" id="approve-modal-title">Approve Application</h3>
            </div>
            <button class="req-action-modal-close" id="req-approve-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="req-action-modal-body">
            <p class="req-action-modal-desc">
                Review and edit the approval message below. This will be added to the resident's request thread and the application will be marked as <strong>Approved</strong>.
            </p>
            <div class="req-action-modal-loading" id="approve-modal-loading" style="display:none;">
                <span>Loading approval message…</span>
            </div>
            <label class="req-action-modal-label" for="approve-modal-note">Approval Message <span class="req-action-modal-required">(optional — edit as needed)</span></label>
            <textarea id="approve-modal-note" class="req-action-modal-textarea" rows="4"
                placeholder="Write a message for the resident, e.g. pickup instructions, schedule, etc."></textarea>

            <label class="req-action-modal-label" style="margin-top:14px;" for="approve-modal-file">
                Attach Fulfillment File
                <span class="req-action-modal-required">(optional — e.g. certificate, clearance)</span>
            </label>
            <label class="req-action-modal-file-wrap" for="approve-modal-file">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                <span id="approve-modal-file-label">No file chosen</span>
                <input type="file" id="approve-modal-file" class="req-action-modal-file-input"
                    accept=".pdf,.doc,.docx,.xlsx,.jpg,.jpeg,.png,.gif,.webp">
            </label>
        </div>
        <div class="req-action-modal-footer">
            <button class="req-action-modal-cancel" id="req-approve-modal-close-btn"
                onclick="document.getElementById('req-approve-modal-overlay').style.display='none'">Cancel</button>
            <button class="req-action-modal-submit req-action-modal-submit--approve" id="approve-modal-submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Approve Application
            </button>
        </div>
    </div>
</div>

<!-- DECLINE MODAL -->
<div class="req-action-modal-overlay" id="req-decline-modal-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-action-modal">
        <div class="req-action-modal-header">
            <div class="req-action-modal-header-left">
                <span class="req-action-modal-icon req-action-modal-icon--decline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </span>
                <h3 class="req-action-modal-title" id="decline-modal-title">Decline Application</h3>
            </div>
            <button class="req-action-modal-close" id="req-decline-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="req-action-modal-body">
            <p class="req-action-modal-desc">
                You must provide a reason. This will be saved to the resident's request thread and the application will be marked as <strong>Declined</strong>. This action is final.
            </p>
            <label class="req-action-modal-label" for="decline-modal-note">
                Reason for Declining
                <span class="req-action-modal-required req-action-modal-required--mandatory">* Required</span>
            </label>
            <textarea id="decline-modal-note" class="req-action-modal-textarea" rows="4"
                placeholder="Explain why this application is being declined (e.g. incomplete documents, ineligible, duplicate request)…"></textarea>
        </div>
        <div class="req-action-modal-footer">
            <button class="req-action-modal-cancel" id="req-decline-modal-close-btn"
                onclick="document.getElementById('req-decline-modal-overlay').style.display='none'">Cancel</button>
            <button class="req-action-modal-submit req-action-modal-submit--decline" id="decline-modal-submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                Decline Application
            </button>
        </div>
    </div>
</div>

<!-- FILE PREVIEW MODAL -->
<div class="req-file-preview-overlay" id="req-file-preview-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-file-preview-container">
        <div class="req-file-preview-header">
            <span class="req-file-preview-name" id="file-preview-name">—</span>
            <button class="req-file-preview-close" id="req-file-preview-close" aria-label="Close preview">&times;</button>
        </div>
        <div class="req-file-preview-body" id="file-preview-body">
            <div class="req-file-preview-loading">Loading...</div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="req-toast" id="req-toast" aria-live="polite"></div>

<script src="../../../scripts/management/officer/officer_requests.js"></script>

</body>
</html>