<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/ServiceRequestModel.php';

$residentId = (int)($_SESSION['user_id'] ?? 0);
$model      = new ServiceRequestModel();
$requests   = $model->getByResident($residentId);

$counts = ['total' => count($requests), 'under_review' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($requests as $r) {
    if ($r['status'] === 'under_review') $counts['under_review']++;
    elseif ($r['status'] === 'approved')  $counts['approved']++;
    elseif ($r['status'] === 'rejected')  $counts['rejected']++;
}

$categoryLabels = [
    'medical'    => 'Medical',
    'education'  => 'Education',
    'scholarship'=> 'Scholarship',
    'livelihood' => 'Livelihood',
    'assistance' => 'Assistance',
    'legal'      => 'Legal',
    'other'      => 'Other',
];
$catIcons = [
    'medical'    => '🏥',
    'education'  => '🎓',
    'scholarship'=> '🏅',
    'livelihood' => '🛠️',
    'assistance' => '🤝',
    'legal'      => '⚖️',
    'other'      => '📋',
];

function resStatusLabel(string $s): string {
    return match($s) {
        'pending'          => 'Pending',
        'under_review'     => 'Under Review',
        'action_required'  => 'Action Required',
        'approved'         => 'Approved',
        'rejected'         => 'Rejected',
        'cancelled'        => 'Cancelled',
        default            => ucfirst($s),
    };
}
function resStatusCss(string $s): string {
    return match($s) {
        'under_review'    => 'under-review',
        'action_required' => 'action-required',
        default           => $s,
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | My Requests</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/my_requests_page.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>
    <main class="dashboard-content">
    <?php
    $pageTitle      = 'My Requests';
    $pageBreadcrumb = [['Home', '#'], ['My Requests', null]];
    $userName       = $_SESSION['user_name']  ?? 'Guest';
    $userRole       = 'Resident';
    // $notifCount     = 3;
    include __DIR__ . '/../../components/portal/topbar.php';
    ?>

        <!-- STAT WIDGETS -->
        <section class="dashboard-widgets">
            <div class="widget-card">
                <h3>Total Requests</h3>
                <p class="widget-number"><?= $counts['total'] ?></p>
                <span class="widget-sub">All submissions</span>
            </div>
            <div class="widget-card">
                <h3>Under Review</h3>
                <p class="widget-number"><?= $counts['under_review'] ?></p>
                <span class="widget-sub">Awaiting SK decision</span>
            </div>
            <div class="widget-card">
                <h3>Approved</h3>
                <p class="widget-number"><?= $counts['approved'] ?></p>
                <span class="widget-sub">Ready for claiming</span>
            </div>
            <div class="widget-card">
                <h3>Rejected</h3>
                <p class="widget-number"><?= $counts['rejected'] ?></p>
                <span class="widget-sub">See details for reason</span>
            </div>
        </section>

        <!-- CONTROLS -->
        <section class="announcements-controls">
            <div class="controls-left">
                <div class="search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" id="req-search" placeholder="Search requests..." class="ann-search-input">
                </div>
            </div>
            <div class="controls-right">
                <select id="req-status" class="ann-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="under-review">Under Review</option>
                    <option value="action-required">Action Required</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="req-category" class="ann-select">
                    <option value="all">All Categories</option>
                    <option value="medical">Medical</option>
                    <option value="education">Education</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="assistance">Assistance</option>
                    <option value="legal">Legal</option>
                    <option value="other">Other</option>
                </select>
                <select id="req-sort" class="ann-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>
        </section>

        <!-- REQUESTS TABLE -->
        <section class="announcements-section">
            <h2 class="section-label">My Service Requests</h2>
            <div class="req-table-wrap">
                <table class="req-table" id="req-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Category</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody id="req-tbody">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px 0; color:var(--text-muted);">
                                You have no service requests yet. <a href="services_page.php" style="color:var(--primary);">Browse services →</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req):
                            $statusDb  = $req['status'];
                            $statusCss = resStatusCss($statusDb);
                            $statusLbl = resStatusLabel($statusDb);
                            $catKey    = strtolower($req['service_category'] ?? 'other');
                            $catLabel  = $categoryLabels[$catKey] ?? ucfirst($catKey);
                            $icon      = $catIcons[$catKey] ?? '📋';
                            $svcName   = htmlspecialchars($req['service_name'] ?? '');
                            $submitted = date('M j, Y', strtotime($req['submitted_at']));
                            $updated   = date('M j, Y', strtotime($req['updated_at']));
                            $purpose   = htmlspecialchars($req['purpose'] ?? '', ENT_QUOTES);
                            $fullName  = htmlspecialchars($req['full_name']  ?? '', ENT_QUOTES);
                            $contact   = htmlspecialchars($req['contact']    ?? '', ENT_QUOTES);
                            $email     = htmlspecialchars($req['email']      ?? '', ENT_QUOTES);
                            $address   = htmlspecialchars($req['address']    ?? '', ENT_QUOTES);
                            $docCount  = (int)($req['doc_count'] ?? 0);
                            $docLabel  = $docCount > 0 ? $docCount . ' file' . ($docCount !== 1 ? 's' : '') : '—';
                            $noteCount = (int)($req['note_count'] ?? 0);
                            $appId     = (int)($req['id'] ?? 0);

                            $notesJson = htmlspecialchars(json_encode($req['notes'] ?? []), ENT_QUOTES);

                            // Encode documents as JSON for JavaScript access
                            $docsJson  = htmlspecialchars(json_encode($req['documents'] ?? []), ENT_QUOTES);

                            // Fulfillment file (optional, for approved digital services)
                            $fulfillmentFile = htmlspecialchars($req['fulfillment_file'] ?? '', ENT_QUOTES);
                        ?>
                        <tr class="req-row"
                            data-id="<?= $appId ?>"
                            data-service-id="<?= (int)($req['service_id'] ?? 0) ?>"
                            data-status="<?= $statusCss ?>"
                            data-category="<?= htmlspecialchars($catKey, ENT_QUOTES) ?>"
                            data-service="<?= $svcName ?>"
                            data-submitted="<?= $submitted ?>"
                            data-updated="<?= $updated ?>"
                            data-purpose="<?= $purpose ?>"
                            data-full-name="<?= $fullName ?>"
                            data-contact="<?= $contact ?>"
                            data-email="<?= $email ?>"
                            data-address="<?= $address ?>"
                            data-docs="<?= $docLabel ?>"
                            data-note-count="<?= $noteCount ?>"
                            data-notes="<?= $notesJson ?>"
                            data-documents="<?= $docsJson ?>"
                            data-fulfillment-file="<?= $fulfillmentFile ?>">
                            <td><span class="svc-name"><?= $icon ?> <?= $svcName ?></span></td>
                            <td><span class="req-cat-tag tag-<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></span></td>
                            <td><?= $submitted ?></td>
                            <td><span class="req-status-badge status-<?= $statusCss ?>"><?= $statusLbl ?></span></td>
                            <td class="update-col"><?= $updated ?></td>
                            <td><button class="btn-view-req">View Details</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="no-results" id="no-results" style="display:none;">
                    <p>No requests found matching your search.</p>
                </div>
            </div>

            <!-- PAGINATION -->
            <div class="pagination-section" style="margin-top: 24px;">
                <button class="page-btn" id="prev-btn" disabled>&#8249; Previous</button>
                <div class="page-numbers" id="page-numbers"></div>
                <button class="page-btn" id="next-btn" disabled>Next &#8250;</button>
            </div>
        </section>
    </main>
</div>

<!-- VIEW DETAILS MODAL -->
<div class="modal-overlay" id="modal-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="modal-title">
    <div class="modal-box modal-box-lg">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon" id="modal-svc-icon">📋</div>
                <div>
                    <h3 id="modal-title">Request Details</h3>
                </div>
            </div>
            <button class="modal-close" id="modal-close" aria-label="Close">&times;</button>
        </div>

        <!-- STATUS STRIP -->
        <div class="modal-status-strip" id="modal-status-strip">
            <div class="strip-item">
                <span class="strip-label">Status</span>
                <span id="strip-status">—</span>
            </div>
            <div class="strip-item">
                <span class="strip-label">Date Submitted</span>
                <span id="strip-submitted">—</span>
            </div>
            <div class="strip-item">
                <span class="strip-label">Last Updated</span>
                <span id="strip-updated">—</span>
            </div>
            <div class="strip-item">
                <span class="strip-label">Documents Uploaded</span>
                <span id="strip-docs">—</span>
            </div>
        </div>

        <!-- ACTION REQUIRED BANNER -->
        <div class="action-required-banner" id="action-required-banner" style="display:none;">
            <span class="ar-banner-icon">⚠️</span>
            <div class="ar-banner-body">
                <strong>Action Required</strong>
                <p>An SK Officer has reviewed your request and left notes below. Please review their feedback, update your submission if needed, then resubmit.</p>
            </div>
        </div>

        <div class="modal-body" id="modal-body-content">

            <!-- MY SUBMISSION (READ VIEW) -->
            <div id="submission-read-view">
                <div class="req-detail-block">
                    <h4 class="req-detail-heading">📝 My Submission</h4>
                    <div class="submission-details-grid">
                        <div class="submission-field">
                            <span class="submission-label">Full Name</span>
                            <span class="submission-value" id="detail-full-name">—</span>
                        </div>
                        <div class="submission-field">
                            <span class="submission-label">Contact Number</span>
                            <span class="submission-value" id="detail-contact">—</span>
                        </div>
                        <div class="submission-field">
                            <span class="submission-label">Email Address</span>
                            <span class="submission-value" id="detail-email">—</span>
                        </div>
                        <div class="submission-field">
                            <span class="submission-label">Home Address</span>
                            <span class="submission-value" id="detail-address">—</span>
                        </div>
                    </div>
                    <div class="submission-field submission-field--full" style="margin-top:12px;">
                        <span class="submission-label">Purpose / Details</span>
                        <p class="req-detail-text" id="detail-purpose">—</p>
                    </div>

                    <!-- DOCUMENTS LIST -->
                    <div class="submission-field submission-field--full" style="margin-top:12px;" id="docs-read-block">
                        <span class="submission-label">Submitted Documents</span>
                        <div id="detail-documents-list" class="detail-docs-list">—</div>
                    </div>
                </div>
            </div>

            <!-- EDIT FORM (only shown when status = action_required and user clicks Edit) -->
            <div id="submission-edit-view" style="display:none;">
                <div class="req-detail-block">
                    <h4 class="req-detail-heading"> Edit Your Submission</h4>
                    <p class="edit-form-hint">Update the details below and click <strong>Resubmit</strong> when ready.</p>

                    <div class="modal-row">
                        <div class="form-group">
                            <label class="modal-label" for="edit-full-name">Full Name <span class="required-star">*</span></label>
                            <input type="text" class="modal-input" id="edit-full-name" placeholder="e.g. Juan Dela Cruz">
                            <span class="field-error" id="edit-err-name"></span>
                        </div>
                        <div class="form-group">
                            <label class="modal-label" for="edit-contact">Contact Number <span class="required-star">*</span></label>
                            <input type="tel" class="modal-input" id="edit-contact" placeholder="e.g. 09XX XXX XXXX">
                            <span class="field-error" id="edit-err-contact"></span>
                        </div>
                    </div>
                    <div class="modal-row">
                        <div class="form-group">
                            <label class="modal-label" for="edit-email">Email Address <span class="required-star">*</span></label>
                            <input type="email" class="modal-input" id="edit-email" placeholder="e.g. juan@email.com">
                            <span class="field-error" id="edit-err-email"></span>
                        </div>
                        <div class="form-group">
                            <label class="modal-label" for="edit-address">Home Address <span class="required-star">*</span></label>
                            <input type="text" class="modal-input" id="edit-address" placeholder="Purok/Street, Barangay Sauyo">
                            <span class="field-error" id="edit-err-address"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="modal-label" for="edit-purpose">Purpose / Details</label>
                        <textarea class="modal-input modal-textarea" id="edit-purpose" rows="3" placeholder="Describe the reason for your request…"></textarea>
                    </div>

                    <!-- EXISTING DOCUMENTS -->
                    <div class="form-group" id="existing-docs-section">
                        <label class="modal-label">Current Documents</label>
                        <div id="existing-docs-list" class="existing-docs-list"></div>
                        <p class="edit-form-hint" style="margin-top:6px;">Uncheck a file to remove it upon resubmission.</p>
                    </div>

                    <!-- ADD NEW DOCUMENTS -->
                    <div class="form-group">
                        <label class="modal-label">Add New Documents</label>
                        <div class="file-drop-zone" id="edit-file-drop-zone">
                            <input type="file" id="edit-docs-input" multiple class="file-input-hidden" accept="image/*,.pdf,.doc,.docx">
                            <div class="file-drop-inner">
                                <span class="file-drop-icon">📎</span>
                                <span class="file-drop-text">
                                    Drag & drop files here or
                                    <button type="button" class="file-browse-btn" id="edit-file-browse-btn">browse</button>
                                </span>
                                <span class="file-drop-hint">Accepted: Images, PDF, DOC — Max 5 MB each</span>
                            </div>
                            <ul class="file-list" id="edit-file-list"></ul>
                        </div>
                        <span class="field-error" id="edit-err-docs"></span>
                    </div>
                </div>
            </div>

            <!-- TIMELINE -->
            <div class="req-detail-block">
                <h4 class="req-detail-heading">📋 Request Timeline</h4>
                <div class="req-timeline" id="req-timeline"></div>
            </div>

            <!-- FULFILLMENT FILE (shown for approved digital services) -->
            <div class="req-detail-block" id="fulfillment-block" style="display:none;">
                <h4 class="req-detail-heading">📎 Attached File from SK Officer</h4>
                <div id="fulfillment-file-wrap"></div>
            </div>

            <!-- SK RESPONSE THREAD (shown if notes exist) -->
            <div class="req-detail-block" id="sk-response-block" style="display:none;">
                <h4 class="req-detail-heading">💬 SK Officer Updates</h4>
                <div id="sk-notes-thread"></div>
            </div>

            <!-- NO RESPONSE YET -->
            <div class="req-detail-block" id="no-response-block" style="display:none;">
                <h4 class="req-detail-heading">💬 SK Officer Updates</h4>
                <div class="no-response-yet">
                    <span>🕐</span>
                    <p>No updates yet. You will be notified once an SK officer reviews your request.</p>
                </div>
            </div>
        </div>

        <div class="modal-footer" id="modal-footer">
            <!-- Buttons rendered by JS depending on status + edit mode -->
            <button class="btn-secondary-portal" id="modal-close-btn" type="button">Close</button>
        </div>
    </div>
</div>

<!-- CANCEL CONFIRM MODAL -->
<div class="modal-overlay" id="cancel-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon">🚫</div>
                <div><h3>Cancel Request</h3></div>
            </div>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-secondary);line-height:1.6;">
                Are you sure you want to cancel this request? This action cannot be undone.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-ghost-portal" id="cancel-confirm-back-btn" type="button">Go Back</button>
            <button class="btn-danger-portal" id="cancel-confirm-proceed-btn" type="button">Yes, Cancel Request</button>
        </div>
    </div>
</div>

<!-- RESUBMIT CONFIRM MODAL -->
<div class="modal-overlay" id="resubmit-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon">🔄</div>
                <div><h3>Confirm Resubmission</h3></div>
            </div>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-secondary);line-height:1.6;">
                Are you sure you want to resubmit your updated application? The SK officer will be notified to review it again.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary-portal" id="resubmit-cancel-btn" type="button">Cancel</button>
            <button class="btn-primary-portal" id="resubmit-confirm-btn" type="button">Yes, Resubmit</button>
        </div>
    </div>
</div>

<!-- FILE PREVIEW MODAL -->
<div class="req-file-preview-overlay" id="req-file-preview-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="req-file-preview-container">
        <div class="req-file-preview-header">
            <span class="req-file-preview-name" id="file-preview-name"></span>
            <button class="req-file-preview-close" id="req-file-preview-close" aria-label="Close">&times;</button>
        </div>
        <div class="req-file-preview-body" id="file-preview-body"></div>
    </div>
</div>

<!-- TOAST -->
<div id="req-toast" class="req-toast" aria-live="polite" style="display:none;"></div>

<script src="../../scripts/portal/my_requests_page.js"></script>
</body>
</html>