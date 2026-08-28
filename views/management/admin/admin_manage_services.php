<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Services</title>
    <link rel="stylesheet" href="../../../styles/management/admin/admin_dashboard.css">
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_manage_services.css">
</head>
<body>

<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php
        $pageTitle      = 'Manage Services';
        $pageBreadcrumb = [['Home', '#'], ['Services', '#'], ['Manage Services', null]];
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
                    <input type="text" id="svc-search" class="svc-search-input" placeholder="Search services...">
                </div>
                <select id="svc-category" class="svc-select">
                    <option value="all">All Categories</option>
                    <option value="medical">Medical</option>
                    <option value="education">Education</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="assistance">Assistance</option>
                    <option value="legal">Legal</option>
                    <option value="other">Other</option>
                </select>
                <select id="svc-status" class="svc-select">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="svc-controls-right">
                <button class="btn-svc-add" onclick="openAddModal()">+ Add Service</button>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="svc-stats-strip">
            <div class="svc-stat-pill stat-active">
                <span class="svc-stat-num" id="stat-active">—</span>
                <span>Active</span>
            </div>
            <div class="svc-stat-pill stat-inactive">
                <span class="svc-stat-num" id="stat-inactive">—</span>
                <span>Inactive</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" id="stat-total">—</span>
                <span>Total Services</span>
            </div>
        </div>

        <!-- Services Grid -->
        <p class="svc-section-label">All Services</p>
        <div class="svc-grid" id="svc-grid">
            <!-- Populated by JS -->
            <div class="svc-loading" id="svc-loading">Loading services…</div>
        </div>

        <div class="svc-no-results" id="no-results" style="display:none;">
            <p>No services found matching your search.</p>
        </div>

    </main>
</div>

<!-- ADD / EDIT MODAL -->
<div class="svc-modal-overlay" id="svc-modal-overlay">
    <div class="svc-modal-box">

        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon" id="modal-icon">📋</div>
                <div>
                    <h3 class="svc-modal-title" id="modal-title">Add Service</h3>
                    <p class="svc-modal-subtitle">Fill in the service details below.</p>
                </div>
            </div>
            <button class="svc-modal-close" onclick="closeModal()">×</button>
        </div>

        <div class="svc-modal-body">
            <input type="hidden" id="serviceId">

            <div class="svc-form-row">
                <div class="svc-form-group">
                    <label class="svc-label">Service Name <span class="svc-required">*</span></label>
                    <input type="text" class="svc-input" id="serviceName" placeholder="e.g. Medical Assistance">
                    <span class="svc-field-error" id="err-name"></span>
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Category <span class="svc-required">*</span></label>
                    <select class="svc-select-input" id="serviceCategory">
                        <option value="medical">Medical</option>
                        <option value="education">Education</option>
                        <option value="scholarship">Scholarship</option>
                        <option value="livelihood">Livelihood</option>
                        <option value="assistance">Assistance</option>
                        <option value="legal">Legal</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="svc-form-group">
                <label class="svc-label">Service Type <span class="svc-required">*</span></label>
                <select class="svc-select-input" id="serviceType">
                    <option value="document">Document / Online Application</option>
                    <option value="appointment">Appointment / Request-based</option>
                    <option value="info">Information &amp; Direct Contact</option>
                </select>
            </div>

            <div class="svc-form-group">
                <label class="svc-label">Description</label>
                <textarea class="svc-textarea" id="serviceDescription" placeholder="Describe the service..."></textarea>
            </div>

            <div class="svc-form-group">
                <label class="svc-label">Approval Message</label>
                <textarea class="svc-textarea" id="serviceApprovalMessage"
                    placeholder="Instructions shown to residents when their application is approved..."></textarea>
                <span class="svc-field-hint">Use "N/A" if this service type is Info &amp; Direct Contact.</span>
            </div>

            <div class="svc-form-row">
                <div class="svc-form-group">
                    <label class="svc-label">Eligibility</label>
                    <input type="text" class="svc-input" id="serviceEligibility" placeholder="e.g. Registered youth resident">
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Processing Time</label>
                    <input type="text" class="svc-input" id="serviceTime" placeholder="e.g. 3-5 working days">
                </div>
            </div>

            <div class="svc-form-row">
                <div class="svc-form-group">
                    <label class="svc-label">Requirements</label>
                    <input type="text" class="svc-input" id="serviceRequirements" placeholder="e.g. Valid ID, Medical Certificate">
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Max Capacity</label>
                    <input type="number" class="svc-input" id="serviceMaxCapacity" placeholder="Leave blank for unlimited" min="1">
                </div>
            </div>

            <div class="svc-form-group">
                <label class="svc-label">Status</label>
                <select class="svc-select-input" id="serviceStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="svc-modal-footer">
            <button class="btn-svc-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn-svc-primary" id="btn-save" onclick="saveService()">💾 Save Service</button>
        </div>

    </div>
</div>

<script>
    // Pass the API base URL to JS
    const SVC_API = '../../../backend/routes/admin_services.php';
</script>
<script src="../../../scripts/management/admin/admin_manage_services.js"></script>
</body>
</html>