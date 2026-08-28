<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../../backend/config/database.php';
RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

// Fetch all applications with service + resident info
$stmt = $conn->query("
    SELECT
        sa.id,
        sa.full_name   AS resident,
        sa.contact,
        sa.email,
        sa.address,
        sa.purpose,
        sa.status,
        sa.submitted_at AS date,
        sa.updated_at,
        s.name         AS service,
        s.category,
        s.id           AS service_id
    FROM service_applications sa
    JOIN services s ON sa.service_id = s.id
    ORDER BY
        FIELD(sa.status, 'pending', 'action_required', 'approved', 'rejected', 'cancelled'),
        sa.submitted_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Latest note per application
$noteStmt = $conn->query("
    SELECT an.application_id, an.note
    FROM application_notes an
    INNER JOIN (
        SELECT application_id, MAX(created_at) AS latest
        FROM application_notes
        GROUP BY application_id
    ) latest_notes ON an.application_id = latest_notes.application_id
                   AND an.created_at    = latest_notes.latest
");
$notesMap = [];
foreach ($noteStmt->fetchAll(PDO::FETCH_ASSOC) as $n) {
    $notesMap[$n['application_id']] = $n['note'];
}

// Attach notes + icon to each request
$categoryIcons = [
    'medical'    => '🏥',
    'education'  => '🎓',
    'scholarship'=> '🏅',
    'livelihood' => '🛠️',
    'assistance' => '🤝',
    'legal'      => '⚖️',
    'other'      => '📋',
];
foreach ($requests as &$r) {
    $r['admin_remarks'] = $notesMap[$r['id']] ?? '';
    $r['icon']          = $categoryIcons[$r['category']] ?? '📋';
}
unset($r);

// Counts per status
$counts = [
    'pending'         => 0,
    'action_required' => 0,
    'approved'        => 0,
    'rejected'        => 0,
    'cancelled'       => 0,
];
foreach ($requests as $req) {
    if (isset($counts[$req['status']])) $counts[$req['status']]++;
}

// If ?id= is passed, highlight that card via JS
$focusId = isset($_GET['id']) ? (int) $_GET['id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Service Requests</title>
    <link rel="stylesheet" href="../../../styles/management/admin/admin_dashboard.css">
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_service_requests.css">
</head>
<body>

<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php
        $pageTitle      = 'Service Requests';
        $pageBreadcrumb = [['Home', '#'], ['Services', '#'], ['Service Requests', null]];
        $adminName      = $_SESSION['user_name'] ?? 'Admin';
        $adminRole      = 'System Admin';
        $notifCount     = 7;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>

        <!-- Controls -->
        <div class="svc-controls">
            <div class="svc-controls-left">
                <div class="svc-search-wrap">
                    <span class="svc-search-icon">🔍</span>
                    <input type="text" id="req-search" class="svc-search-input" placeholder="Search by name or service...">
                </div>
                <select id="req-category" class="svc-select">
                    <option value="all">All Categories</option>
                    <option value="medical">Medical</option>
                    <option value="education">Education</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="assistance">Assistance</option>
                    <option value="legal">Legal</option>
                    <option value="other">Other</option>
                </select>
                <select id="req-status" class="svc-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="action_required">Action Required</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="svc-stats-strip">
            <div class="svc-stat-pill stat-pending">
                <span class="svc-stat-num"><?= $counts['pending'] ?></span>
                <span>Pending</span>
            </div>
            <div class="svc-stat-pill" style="color:var(--ap-text-muted)">
                <span class="svc-stat-num" style="color:#1d4ed8"><?= $counts['action_required'] ?></span>
                <span>Action Required</span>
            </div>
            <div class="svc-stat-pill stat-approved">
                <span class="svc-stat-num"><?= $counts['approved'] ?></span>
                <span>Approved</span>
            </div>
            <div class="svc-stat-pill stat-rejected">
                <span class="svc-stat-num"><?= $counts['rejected'] ?></span>
                <span>Rejected</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num"><?= count($requests) ?></span>
                <span>Total</span>
            </div>
        </div>

        <!-- Requests Table -->
        <p class="svc-section-label">All Service Requests</p>

        <div class="req-table-wrap">
            <table class="req-table" id="req-grid">
                <thead>
                    <tr>
                        <th class="col-service">Service</th>
                        <th class="col-resident">Resident</th>
                        <th class="col-contact">Contact</th>
                        <th class="col-category">Category</th>
                        <th class="col-submitted">Submitted</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="req-tbody">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="req-empty-row">No service requests found.</td>
                    </tr>
                <?php else: ?>
                <?php foreach ($requests as $req): ?>
                <?php
                    $statusLabel = match($req['status']) {
                        'pending'         => 'Pending',
                        'action_required' => 'Action Required',
                        'approved'        => 'Approved',
                        'rejected'        => 'Rejected',
                        'cancelled'       => 'Cancelled',
                        default           => ucfirst($req['status']),
                    };
                    $badgeClass = match($req['status']) {
                        'pending'         => 'badge-pending',
                        'action_required' => 'badge-completed',
                        'approved'        => 'badge-approved',
                        'rejected'        => 'badge-rejected',
                        'cancelled'       => 'badge-rejected',
                        default           => 'badge-pending',
                    };
                ?>
                <tr class="req-row"
                    id="req-card-<?= $req['id'] ?>"
                    data-category="<?= htmlspecialchars($req['category']) ?>"
                    data-status="<?= htmlspecialchars($req['status']) ?>"
                    data-name="<?= strtolower(htmlspecialchars($req['resident'])) ?>"
                    data-service="<?= strtolower(htmlspecialchars($req['service'])) ?>">

                    <td class="col-service">
                        <div class="req-service-cell">
                            <div class="req-icon svc-icon-<?= $req['category'] ?>"><?= $req['icon'] ?></div>
                            <div class="req-service-info">
                                <div class="req-service-name"><?= htmlspecialchars($req['service']) ?></div>
                            </div>
                        </div>
                    </td>

                    <td class="col-resident">
                        <span class="req-resident-name"><?= htmlspecialchars($req['resident']) ?></span>
                    </td>

                    <td class="col-contact">
                        <span class="req-meta"><?= htmlspecialchars($req['contact']) ?></span>
                    </td>

                    <td class="col-category">
                        <span class="svc-cat-tag tag-<?= $req['category'] ?>"><?= ucfirst($req['category']) ?></span>
                    </td>

                    <td class="col-submitted">
                        <span class="req-date"><?= date('M d, Y', strtotime($req['date'])) ?></span>
                        <span class="req-time"><?= date('h:i A', strtotime($req['date'])) ?></span>
                    </td>

                    <td class="col-status">
                        <span class="svc-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </td>

                    <td class="col-actions">
                        <button class="btn-svc-primary btn-view-act"
                            onclick='openRequestModal(<?= htmlspecialchars(json_encode($req), ENT_QUOTES) ?>)'>
                            👁️ View & Act
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="svc-no-results" id="no-results" style="display:none;">
            <p>No requests found matching your search.</p>
        </div>

    </main>
</div>

<!-- VIEW / ACTION MODAL -->
<div class="svc-modal-overlay" id="req-modal-overlay">
    <div class="svc-modal-box">

        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon" id="req-modal-icon">🏥</div>
                <div>
                    <h3 class="svc-modal-title" id="req-modal-title">Request Details</h3>
                    <p class="svc-modal-subtitle" id="req-modal-subtitle">Service Request</p>
                </div>
            </div>
            <button class="svc-modal-close" onclick="closeRequestModal()">×</button>
        </div>

        <div class="svc-modal-summary">
            <div class="svc-summary-item">
                <span class="svc-summary-label">Resident</span>
                <span class="svc-summary-value" id="req-modal-resident">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Contact</span>
                <span class="svc-summary-value" id="req-modal-contact">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Status</span>
                <span class="svc-summary-value" id="req-modal-status">—</span>
            </div>
        </div>

        <div class="svc-modal-body">
            <div class="svc-form-group">
                <label class="svc-label">Address</label>
                <p id="req-modal-address" style="font-size:13px;color:var(--ap-text-body);font-family:'Poppins',sans-serif;"></p>
            </div>
            <div class="svc-form-group">
                <label class="svc-label">Email</label>
                <p id="req-modal-email" style="font-size:13px;color:var(--ap-text-body);font-family:'Poppins',sans-serif;"></p>
            </div>
            <div class="svc-form-group">
                <label class="svc-label">Date Submitted</label>
                <p id="req-modal-date" style="font-size:13px;color:var(--ap-text-body);font-family:'Poppins',sans-serif;"></p>
            </div>
            <div class="svc-form-group">
                <label class="svc-label">Purpose / Details</label>
                <p id="req-modal-purpose" style="font-size:13px;color:var(--ap-text-body);font-family:'Poppins',sans-serif;line-height:1.6;"></p>
            </div>
            <div class="svc-form-group">
                <label class="svc-label">Admin Remarks / Note</label>
                <textarea class="svc-textarea" id="req-modal-remarks" placeholder="Add remarks or notes (will be saved)..."></textarea>
            </div>
        </div>

        <div class="svc-modal-footer">
            <button class="btn-svc-secondary" onclick="closeRequestModal()">Close</button>
            <button class="btn-svc-primary btn-svc-approve"         onclick="updateStatus('approved')">✅ Approve</button>
            <button class="btn-svc-primary btn-svc-reject"          onclick="updateStatus('rejected')">❌ Reject</button>
            <button class="btn-svc-primary" style="background:#1d4ed8" onclick="updateStatus('action_required')">📋 Action Required</button>
        </div>

    </div>
</div>

<!-- Pass focus ID to JS -->
<script>
    window.FOCUS_REQUEST_ID = <?= $focusId ? $focusId : 'null' ?>;
</script>
<script src="../../../scripts/management/admin/admin_service_requests.js?v=<?= time() ?>"></script>
</body>
</html>