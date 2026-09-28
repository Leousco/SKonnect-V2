<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Users</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_manage_users.css">
</head>
<body>
<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">
        <?php
        $pageTitle      = 'Manage Users';
        $pageBreadcrumb = [['Home', '#'], ['Users', '#'], ['User Management', null]];
        $adminName      = $_SESSION['user_name'] ?? 'Admin';
        $adminRole      = 'System Admin';
        $notifCount     = 7;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>

        <div class="svc-controls">
            <div class="svc-controls-left">
                <div class="svc-search-wrap">
                    <span class="svc-search-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg></span>
                    <input type="text" id="user-search" class="svc-search-input" placeholder="Search by name or email…">
                </div>
                <select id="user-role" class="svc-select">
                    <option value="all">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="moderator">Moderator</option>
                    <option value="sk_officer">SK Officer</option>
                    <option value="resident">Resident</option>
                </select>
                <select id="user-gender" class="svc-select">
                    <option value="all">All Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
                <select id="user-verified" class="svc-select">
                    <option value="all">All Status</option>
                    <option value="1">Verified</option>
                    <option value="0">Unverified</option>
                </select>
            </div>
            <div class="svc-controls-right">
                <button id="btn-add-user" class="btn-svc-primary btn-svc-approve"><svg class="mu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>Add User</button>
            </div>
        </div>

        <div class="svc-stats-strip">
            <div class="svc-stat-pill">
                <span class="svc-stat-num" data-stat="total">—</span>
                <span>Total Users</span>
            </div>
            <div class="svc-stat-pill stat-approved">
                <span class="svc-stat-num" data-stat="verified">—</span>
                <span>Verified</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" data-stat="admin">—</span>
                <span>Admins</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" data-stat="moderator">—</span>
                <span>Moderators</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" data-stat="sk_officer">—</span>
                <span>SK Officers</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" data-stat="resident">—</span>
                <span>Residents</span>
            </div>
        </div>

        <p class="svc-section-label">All Users</p>
        <div class="user-table-wrap">
            <table class="user-table" id="user-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="user-tbody">
                    <tr><td colspan="7" class="mu-loading-row">Loading users…</td></tr>
                </tbody>
            </table>
        </div>

        <div class="svc-no-results" id="no-results" style="display:none;">
            <p>No users found matching your search.</p>
        </div>
    </main>
</div>


<div class="svc-modal-overlay" id="add-user-modal-overlay">
    <div class="svc-modal-box">
        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon mu-icon-success"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg></div>
                <div>
                    <h3 class="svc-modal-title">Add New User</h3>
                    <p class="svc-modal-subtitle">Fill in the details to create an account.</p>
                </div>
            </div>
            <button class="svc-modal-close" id="add-user-close">×</button>
        </div>
        <div class="svc-modal-body">
            <form id="add-user-form" onsubmit="return false;">
                <div class="svc-form-row">
                    <div class="svc-form-group">
                        <label class="svc-label">First Name <span class="mu-required">*</span></label>
                        <input type="text" id="add-first-name" class="svc-select-input" placeholder="Juan">
                    </div>
                    <div class="svc-form-group">
                        <label class="svc-label">Last Name <span class="mu-required">*</span></label>
                        <input type="text" id="add-last-name" class="svc-select-input" placeholder="Dela Cruz">
                    </div>
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Middle Name</label>
                    <input type="text" id="add-middle-name" class="svc-select-input" placeholder="Optional">
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Email <span class="mu-required">*</span></label>
                    <input type="email" id="add-email" class="svc-select-input" placeholder="user@email.com">
                </div>
                <div class="svc-form-row">
                    <div class="svc-form-group">
                        <label class="svc-label">Password <span class="mu-required">*</span></label>
                        <input type="password" id="add-password" class="svc-select-input" placeholder="Min. 8 characters">
                    </div>
                    <div class="svc-form-group">
                        <label class="svc-label">Confirm Password <span class="mu-required">*</span></label>
                        <input type="password" id="add-password-confirm" class="svc-select-input" placeholder="Re-enter password">
                        <span class="mu-pw-match-hint" id="pw-match-hint"></span>
                    </div>
                </div>
                <div class="svc-form-row">
                    <div class="svc-form-group">
                        <label class="svc-label">Role <span class="mu-required">*</span></label>
                        <select id="add-role" class="svc-select-input">
                            <option value="">Select role…</option>
                            <option value="admin">Admin</option>
                            <option value="moderator">Moderator</option>
                            <option value="sk_officer">SK Officer</option>
                            <option value="resident">Resident</option>
                        </select>
                    </div>
                    <div class="svc-form-group">
                        <label class="svc-label">Gender <span class="mu-required">*</span></label>
                        <select id="add-gender" class="svc-select-input">
                            <option value="">Select gender…</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="svc-form-group">
                    <label class="svc-label">Birth Date <span class="mu-required">*</span></label>
                    <input type="date" id="add-birth-date" class="svc-select-input">
                </div>
            </form>
        </div>
        <div class="svc-modal-footer">
            <button class="btn-svc-secondary" id="add-user-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-approve" id="add-user-submit"><svg class="mu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>Create User</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay" id="user-modal-overlay">
    <div class="svc-modal-box">
        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon" id="user-modal-avatar">US</div>
                <div>
                    <h3 class="svc-modal-title" id="user-modal-name">User Details</h3>
                    <p class="svc-modal-subtitle" id="user-modal-email-disp">—</p>
                </div>
            </div>
            <button class="svc-modal-close" id="user-modal-close">×</button>
        </div>

        <div class="svc-modal-summary">
            <div class="svc-summary-item">
                <span class="svc-summary-label">Role</span>
                <span class="svc-summary-value" id="user-modal-role-disp">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Gender</span>
                <span class="svc-summary-value" id="user-modal-gender-disp">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Status</span>
                <span class="svc-summary-value" id="user-modal-status-disp">—</span>
            </div>
        </div>

        <div class="svc-modal-body mu-profile-body" id="user-modal-body">
            <div class="mu-edit-warning">
                <svg class="mu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378a2.25 2.25 0 0 0-3.898 0L2.697 16.126a2.25 2.25 0 0 0 1.948 3.374Z"/></svg>
                <span><strong>Edit mode:</strong> Update the user details below, then save.</span>
            </div>

            <section class="mu-detail-section">
                <h4 class="mu-detail-heading">Personal Information</h4>
                <div class="mu-detail-grid">
                    <div class="mu-detail-field"><label class="svc-label" for="edit-first-name">First Name</label><p id="user-modal-first-name" class="user-detail-val mu-view-value"></p><input type="text" id="edit-first-name" class="svc-select-input mu-edit-input" maxlength="50"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-middle-name">Middle Name</label><p id="user-modal-middle-name" class="user-detail-val mu-view-value"></p><input type="text" id="edit-middle-name" class="svc-select-input mu-edit-input" maxlength="50"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-last-name">Last Name</label><p id="user-modal-last-name" class="user-detail-val mu-view-value"></p><input type="text" id="edit-last-name" class="svc-select-input mu-edit-input" maxlength="50"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-email">Email</label><p id="user-modal-email" class="user-detail-val mu-view-value"></p><input type="email" id="edit-email" class="svc-select-input mu-edit-input" maxlength="100"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-gender">Gender</label><p id="user-modal-gender" class="user-detail-val mu-view-value"></p><select id="edit-gender" class="svc-select-input mu-edit-input"><option value="male">Male</option><option value="female">Female</option><option value="other">Prefer not to say</option></select></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-birth-date">Birth Date</label><p id="user-modal-birth-date" class="user-detail-val mu-view-value"></p><input type="date" id="edit-birth-date" class="svc-select-input mu-edit-input"></div>
                    <div class="mu-detail-field"><label class="svc-label">Age</label><p id="user-modal-age" class="user-detail-val"></p></div>
                    <div class="mu-detail-field"><label class="svc-label">Date Joined</label><p id="user-modal-joined" class="user-detail-val"></p></div>
                    <div class="mu-detail-field"><label class="svc-label">User ID</label><p id="user-modal-id" class="user-detail-val"></p></div>
                    <div class="mu-detail-field mu-role-field">
                        <label class="svc-label" for="user-modal-role-select">Change Role</label>
                        <select class="svc-select-input" id="user-modal-role-select">
                            <option value="admin">Admin</option><option value="moderator">Moderator</option><option value="sk_officer">SK Officer</option><option value="resident">Resident</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="mu-detail-section">
                <h4 class="mu-detail-heading">Contact & Address</h4>
                <div class="mu-detail-grid">
                    <div class="mu-detail-field"><label class="svc-label" for="edit-mobile-number">Contact Number</label><p id="user-modal-mobile" class="user-detail-val mu-view-value"></p><input type="tel" id="edit-mobile-number" class="svc-select-input mu-edit-input" maxlength="20"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-purok">Purok / Zone</label><p id="user-modal-purok" class="user-detail-val mu-view-value"></p><input type="text" id="edit-purok" class="svc-select-input mu-edit-input" maxlength="100"></div>
                    <div class="mu-detail-field mu-detail-field--wide"><label class="svc-label" for="edit-street-address">Street Address</label><p id="user-modal-street-address" class="user-detail-val mu-view-value"></p><input type="text" id="edit-street-address" class="svc-select-input mu-edit-input" maxlength="255"></div>
                </div>
            </section>

            <section class="mu-detail-section">
                <h4 class="mu-detail-heading">Additional Profile Details</h4>
                <div class="mu-detail-grid">
                    <div class="mu-detail-field"><label class="svc-label" for="edit-civil-status">Civil Status</label><p id="user-modal-civil-status" class="user-detail-val mu-view-value"></p><select id="edit-civil-status" class="svc-select-input mu-edit-input"><option value="">Not provided</option><option value="single">Single</option><option value="married">Married</option><option value="widowed">Widowed</option><option value="separated">Separated</option><option value="annulled">Annulled</option></select></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-nationality">Nationality</label><p id="user-modal-nationality" class="user-detail-val mu-view-value"></p><input type="text" id="edit-nationality" class="svc-select-input mu-edit-input" maxlength="100"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-religion">Religion</label><p id="user-modal-religion" class="user-detail-val mu-view-value"></p><input type="text" id="edit-religion" class="svc-select-input mu-edit-input" maxlength="100"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-education">Educational Attainment</label><p id="user-modal-education" class="user-detail-val mu-view-value"></p><select id="edit-education" class="svc-select-input mu-edit-input"><option value="">Not provided</option><option value="elementary">Elementary</option><option value="high_school">High School Graduate</option><option value="senior_high">Senior High School Graduate</option><option value="vocational">Vocational / Technical</option><option value="college_level">College Level</option><option value="college_graduate">College Graduate</option><option value="post_graduate">Post-Graduate</option></select></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-employment">Employment Status</label><p id="user-modal-employment" class="user-detail-val mu-view-value"></p><select id="edit-employment" class="svc-select-input mu-edit-input"><option value="">Not provided</option><option value="student">Student</option><option value="employed">Employed</option><option value="unemployed">Unemployed</option><option value="self_employed">Self-Employed</option></select></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-registered-voter">Registered Voter</label><p id="user-modal-voter" class="user-detail-val mu-view-value"></p><select id="edit-registered-voter" class="svc-select-input mu-edit-input"><option value="1">Yes</option><option value="0">No</option></select></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-school">School / Institution</label><p id="user-modal-school" class="user-detail-val mu-view-value"></p><input type="text" id="edit-school" class="svc-select-input mu-edit-input" maxlength="255"></div>
                    <div class="mu-detail-field"><label class="svc-label" for="edit-course">Course / Strand</label><p id="user-modal-course" class="user-detail-val mu-view-value"></p><input type="text" id="edit-course" class="svc-select-input mu-edit-input" maxlength="255"></div>
                </div>
            </section>
        </div>

        
        <div class="svc-modal-footer" id="footer-default">
            <div class="mu-footer-left">
                <button class="btn-svc-primary btn-svc-edit" id="btn-toggle-edit">Edit Info</button>
            </div>
            <div class="mu-footer-right">
                <button class="btn-svc-primary" id="btn-save-role"><svg class="mu-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.7 1.7 0 1 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.3a1.7 1.7 0 1 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 1 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 1 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 1 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V4a1.7 1.7 0 1 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 1 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 1 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2.9Z"/></svg>Save Role</button>
                <button class="btn-svc-primary btn-svc-danger" id="user-modal-ban">Ban User</button>
                <button class="btn-svc-primary btn-svc-delete" id="user-modal-delete">Delete</button>
            </div>
        </div>

        
        <div class="svc-modal-footer" id="footer-edit" style="display:none;">
            <div class="mu-footer-left">
                <button class="btn-svc-secondary" id="btn-cancel-edit">Cancel</button>
            </div>
            <div class="mu-footer-right">
                <button class="btn-svc-primary btn-svc-approve" id="btn-save-edit">Save Changes</button>
            </div>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-role-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--info"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15l.1.1a1.7 1.7 0 1 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.3a1.7 1.7 0 1 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 1 1-2.4-2.4l.1-.1"/></svg></div>
        <h3 class="mu-confirm-title">Change Role</h3>
        <p class="mu-confirm-body" id="confirm-role-body">—</p>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-role-cancel">Cancel</button>
            <button class="btn-svc-primary" id="confirm-role-ok">Save Role</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-edit-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--warn"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12V15.75Zm-8.4 3h16.8a1.5 1.5 0 0 0 1.3-2.25L13.3 2.7a1.5 1.5 0 0 0-2.6 0L2.3 16.5A1.5 1.5 0 0 0 3.6 18.75Z"/></svg></div>
        <h3 class="mu-confirm-title">Edit User Info</h3>
        <p class="mu-confirm-body">You are about to edit this user's personal information. Please ensure all changes are correct before saving.</p>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-edit-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-edit" id="confirm-edit-ok">Save Changes</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-ban-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--danger"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg></div>
        <h3 class="mu-confirm-title">Ban User</h3>
        <p class="mu-confirm-body" id="confirm-ban-body">—</p>
        <div class="svc-form-group mu-confirm-reason-wrap">
            <label class="svc-label">Reason <span class="mu-confirm-optional">(optional)</span></label>
            <input type="text" id="confirm-ban-reason" class="svc-select-input" placeholder="Leave blank for default reason">
        </div>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-ban-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-danger" id="confirm-ban-ok">Ban User</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-unban-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--success"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><rect width="13" height="10" x="5.5" y="10.5" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5V7a4 4 0 1 1 8 0"/></svg></div>
        <h3 class="mu-confirm-title">Unban User</h3>
        <p class="mu-confirm-body" id="confirm-unban-body">—</p>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-unban-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-approve" id="confirm-unban-ok">Unban User</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-delete-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--danger"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-10 0V5h8v2m-9 0 1 13h8l1-13M10 11v5m4-5v5"/></svg></div>
        <h3 class="mu-confirm-title">Delete Account</h3>
        <p class="mu-confirm-body" id="confirm-delete-body">—</p>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-delete-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-delete" id="confirm-delete-ok">Delete Account</button>
        </div>
    </div>
</div>


<div class="svc-modal-overlay mu-confirm-overlay" id="confirm-delete2-overlay">
    <div class="svc-modal-box mu-confirm-box">
        <div class="mu-confirm-icon mu-confirm-icon--danger"><svg class="mu-icon mu-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 9 .01 0M11 12h1v4h1m-1-13a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg></div>
        <h3 class="mu-confirm-title">Final Confirmation</h3>
        <p class="mu-confirm-body" id="confirm-delete2-body">—</p>
        <div class="mu-confirm-warning-chip">This action is permanent and cannot be undone.</div>
        <div class="mu-confirm-footer">
            <button class="btn-svc-secondary" id="confirm-delete2-cancel">Cancel</button>
            <button class="btn-svc-primary btn-svc-delete" id="confirm-delete2-ok">Yes, Permanently Delete</button>
        </div>
    </div>
</div>

<script src="../../../scripts/management/admin/admin_manage_users.js"></script>
</body>
</html>