<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../backend/models/UserProfileModel.php';
require_once __DIR__ . '/../../backend/models/NotificationModel.php';

RoleMiddleware::requireAuth();

$userId     = (int)$_SESSION['user_id'];
$model      = new UserProfileModel();
$p          = $model->getProfile($userId);
$incomplete = !$model->isProfileComplete($userId);

if (!$p) {
    header('Location: ../../views/auth/login.php');
    exit;
}

function val(mixed $v, string $fallback = '—'): string {
    return (!empty($v) && trim((string)$v) !== '') ? htmlspecialchars((string)$v) : $fallback;
}
function mapGender(string $g): string {
    return match ($g) { 'male' => 'Male', 'female' => 'Female', default => 'Prefer not to say' };
}
function mapCivil(string $c): string {
    return match ($c) {
        'single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed',
        'separated' => 'Separated', 'annulled' => 'Annulled', default => '—',
    };
}
function mapEdu(string $e): string {
    return match ($e) {
        'elementary' => 'Elementary', 'high_school' => 'High School Graduate',
        'senior_high' => 'Senior High School Graduate', 'vocational' => 'Vocational / Technical',
        'college_level' => 'College Level', 'college_graduate' => 'College Graduate',
        'post_graduate' => 'Post-Graduate', default => '—',
    };
}
function mapEmp(string $e): string {
    return match ($e) {
        'student' => 'Student', 'employed' => 'Employed',
        'unemployed' => 'Unemployed', 'self_employed' => 'Self-Employed', default => '—',
    };
}

$firstName  = $p['first_name']  ?? '';
$lastName   = $p['last_name']   ?? '';
$middleName = $p['middle_name'] ?? '';
$fullName   = trim("$firstName $middleName $lastName");
$initials   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

$dobFormatted = '—';
if (!empty($p['birth_date'])) {
    $dobFormatted = (new DateTime($p['birth_date']))->format('F j, Y');
}

$memberYear  = !empty($p['created_at']) ? (new DateTime($p['created_at']))->format('Y') : date('Y');
$memberSince = !empty($p['created_at']) ? (new DateTime($p['created_at']))->format('F Y') : '—';
$memberId    = 'SKN-' . $memberYear . '-' . str_pad($p['id'], 5, '0', STR_PAD_LEFT);

$genderDisplay = mapGender($p['gender'] ?? '');
$civilDisplay  = mapCivil($p['civil_status'] ?? '');
$eduDisplay    = mapEdu($p['educational_attainment'] ?? '');
$empDisplay    = mapEmp($p['employment_status'] ?? '');
$isVoter       = (bool)($p['is_registered_voter'] ?? false);

$profileJson = json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$pageTitle      = 'My Profile';
$pageBreadcrumb = [['Home', '#'], ['Profile', null]];
$userName       = $_SESSION['user_name'] ?? $fullName;
$userRole       = 'Resident';
$notifModel     = new NotificationModel();
$notifStats     = $notifModel->getStats($userId);
$notifCount     = $notifStats['unread'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Profile</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/profile_page.css">
</head>
<body>

<div class="dashboard-layout">

    <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

    <main class="dashboard-content">

        <?php include __DIR__ . '/../../components/portal/topbar.php'; ?>

        
        <section class="profile-hero-card">
            <div class="profile-hero-bg"></div>
            <div class="profile-hero-body">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar" id="profile-avatar">
                        <?php if (!empty($p['avatar_path'])): ?>
                            <img src="<?= htmlspecialchars($p['avatar_path']) ?>"
                                 alt="<?= htmlspecialchars($fullName) ?>"
                                 class="profile-avatar-img" id="profile-avatar-img">
                            <span class="profile-initials" id="profile-initials" style="display:none;"><?= $initials ?></span>
                        <?php else: ?>
                            <span class="profile-initials" id="profile-initials"><?= $initials ?></span>
                            <img src="" alt="" class="profile-avatar-img" id="profile-avatar-img" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <button class="avatar-change-btn" id="avatar-change-btn" title="Change photo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </button>
                    <input type="file" id="avatar-file-input" accept="image/*" style="display:none;">
                </div>

                <div class="profile-hero-info">
                    <div class="profile-hero-name-row">
                        <h2 class="profile-hero-name" id="hero-fullname"><?= htmlspecialchars($fullName) ?></h2>
                        <span class="profile-role-badge">SK Member</span>
                        <span class="profile-verified-badge" title="Verified account">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                            Verified
                        </span>
                        <?php if ($incomplete): ?>
                            <span class="profile-incomplete-badge" id="incomplete-badge" title="Profile setup incomplete">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                    <path d="M12 9v4m0 4h.01" />
                                </svg>
                                Incomplete Profile
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="profile-hero-sub" id="hero-sub">
                        Barangay Sauyo, Novaliches, Quezon City &nbsp;·&nbsp; Member since <?= $memberSince ?>
                    </p>
                    <div class="profile-hero-tags" id="hero-tags">
                        <?php if ($isVoter): ?>
                            <span class="hero-tag">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 21V5m0 0c5-4 11 4 16 0v11c-5 4-11-4-16 0" />
                                    <path d="M4 5v11" />
                                </svg>
                                Registered Voter
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($p['employment_status'])): ?>
                            <span class="hero-tag">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="7" width="18" height="13" rx="2" />
                                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2" />
                                </svg>
                                <?= htmlspecialchars($empDisplay) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($p['purok'])): ?>
                            <span class="hero-tag">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                                <?= htmlspecialchars($p['purok']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!$isVoter && empty($p['employment_status']) && empty($p['purok'])): ?>
                            <span class="hero-tag hero-tag-empty">Complete your profile to show tags</span>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="btn-primary-portal profile-edit-trigger" id="profile-edit-trigger" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m16 4 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z" />
                    </svg>
                    Edit Profile
                </button>
            </div>
        </section>

        <section class="profile-card activity-summary-card" aria-labelledby="activity-summary-title">
            <div class="profile-card-header activity-summary-header">
                <h2 class="section-label" id="activity-summary-title">Activity Summary</h2>
                <a class="profile-inline-link" href="my_requests_page.php">View my requests</a>
            </div>
            <div class="activity-summary-grid">
                <div class="activity-summary-item act-summary-service">
                    <span class="act-label">Service Requests</span>
                    <span class="act-value" id="sum-requests">—</span>
                    <span class="act-sub">Total submitted</span>
                </div>
                <div class="activity-summary-item act-summary-approved">
                    <span class="act-label">Approved</span>
                    <span class="act-value" id="sum-approved">—</span>
                    <span class="act-sub">Assistance received</span>
                </div>
                <div class="activity-summary-item act-summary-pending">
                    <span class="act-label">Pending</span>
                    <span class="act-value" id="sum-pending">—</span>
                    <span class="act-sub">Awaiting review</span>
                </div>
                <div class="activity-summary-item act-summary-rejected">
                    <span class="act-label">Rejected</span>
                    <span class="act-value" id="sum-rejected">—</span>
                    <span class="act-sub">Request updates</span>
                </div>
                <div class="activity-summary-item act-summary-thread">
                    <span class="act-label">Threads</span>
                    <span class="act-value" id="sum-threads">—</span>
                    <span class="act-sub">Posted by you</span>
                </div>
                <div class="activity-summary-item act-summary-notif">
                    <span class="act-label">Alerts</span>
                    <span class="act-value" id="sum-notifs">—</span>
                    <span class="act-sub">Notifications</span>
                </div>
                <div class="activity-summary-item act-summary-member">
                    <span class="act-label">Member Since</span>
                    <span class="act-member-value"><?= !empty($p['created_at']) ? htmlspecialchars((new DateTime($p['created_at']))->format("M 'y")) : '—' ?></span>
                    <span class="act-sub">Portal member</span>
                </div>
            </div>
            <div class="profile-recent-header">
                <h3 class="profile-sub-heading">Recent Service Requests</h3>
                <a class="profile-inline-link profile-browse-link" href="services_page.php">Browse services</a>
            </div>
            <div class="profile-scroll-wrap" data-scroll-area>
                <ul class="profile-recent-list profile-scroll-content" id="recent-requests">
                    <li class="recent-list-placeholder">Loading…</li>
                </ul>
                <button class="profile-scroll-more" type="button" aria-label="Scroll for more service requests" hidden>
                    Scroll for more
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>
        </section>

        <div class="profile-lower">

                
                <section class="profile-card" id="card-personal">
                    <div class="profile-card-header">
                        <h2 class="section-label">Personal Information</h2>
                        <button class="card-edit-btn" type="button" data-target="personal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m16 4 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z" />
                            </svg>
                            Edit
                        </button>
                    </div>

                    <div class="profile-fields" id="view-personal">
                        <div class="profile-field-row">
                            <span class="field-label">Full Name</span>
                            <span class="field-value" id="pf-fullname"><?= htmlspecialchars($fullName) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Date of Birth</span>
                            <span class="field-value" id="pf-dob"><?= $dobFormatted ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Age</span>
                            <span class="field-value" id="pf-age"><?= val($p['age'] ? $p['age'] . ' years old' : '') ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Sex</span>
                            <span class="field-value" id="pf-sex"><?= $genderDisplay ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Civil Status</span>
                            <span class="field-value" id="pf-civil"><?= $civilDisplay ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Nationality</span>
                            <span class="field-value" id="pf-nationality"><?= val($p['nationality']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Religion</span>
                            <span class="field-value" id="pf-religion"><?= val($p['religion']) ?></span>
                        </div>
                    </div>

                    <form class="profile-edit-form" id="form-personal" style="display:none;" novalidate>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label class="modal-label">First Name <span class="required-star">*</span></label>
                                <input type="text" class="ann-search-input" id="e-firstname"
                                       value="<?= htmlspecialchars($firstName) ?>">
                                <span class="field-error" id="err-firstname"></span>
                            </div>
                            <div class="form-group">
                                <label class="modal-label">Last Name <span class="required-star">*</span></label>
                                <input type="text" class="ann-search-input" id="e-lastname"
                                       value="<?= htmlspecialchars($lastName) ?>">
                                <span class="field-error" id="err-lastname"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Middle Name</label>
                            <input type="text" class="ann-search-input" id="e-middlename"
                                   value="<?= htmlspecialchars($middleName) ?>">
                        </div>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label class="modal-label">Date of Birth <span class="required-star">*</span></label>
                                <input type="date" class="ann-search-input" id="e-dob"
                                       value="<?= htmlspecialchars($p['birth_date'] ?? '') ?>">
                                <span class="field-error" id="err-dob"></span>
                            </div>
                            <div class="form-group">
                                <label class="modal-label">Sex <span class="required-star">*</span></label>
                                <select class="ann-select" id="e-gender" style="width:100%">
                                    <option value="male"   <?= ($p['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($p['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other"  <?= ($p['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label class="modal-label">Civil Status</label>
                                <select class="ann-select" id="e-civil" style="width:100%">
                                    <option value="">— Select —</option>
                                    <option value="single"    <?= ($p['civil_status'] ?? '') === 'single'    ? 'selected' : '' ?>>Single</option>
                                    <option value="married"   <?= ($p['civil_status'] ?? '') === 'married'   ? 'selected' : '' ?>>Married</option>
                                    <option value="widowed"   <?= ($p['civil_status'] ?? '') === 'widowed'   ? 'selected' : '' ?>>Widowed</option>
                                    <option value="separated" <?= ($p['civil_status'] ?? '') === 'separated' ? 'selected' : '' ?>>Separated</option>
                                    <option value="annulled"  <?= ($p['civil_status'] ?? '') === 'annulled'  ? 'selected' : '' ?>>Annulled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="modal-label">Nationality</label>
                                <input type="text" class="ann-search-input" id="e-nationality"
                                       value="<?= htmlspecialchars($p['nationality'] ?? '') ?>"
                                       placeholder="e.g. Filipino">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Religion</label>
                            <input type="text" class="ann-search-input" id="e-religion"
                                   value="<?= htmlspecialchars($p['religion'] ?? '') ?>"
                                   placeholder="e.g. Roman Catholic">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary-portal cancel-edit-btn" data-target="personal">Cancel</button>
                            <button type="button" class="btn-primary-portal save-edit-btn" data-target="personal">Save Changes</button>
                        </div>
                    </form>
                </section>

                
                <section class="profile-card" id="card-contact">
                    <div class="profile-card-header">
                        <h2 class="section-label">Contact & Address</h2>
                        <button class="card-edit-btn" type="button" data-target="contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m16 4 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z" />
                            </svg>
                            Edit
                        </button>
                    </div>

                    <div class="profile-fields" id="view-contact">
                        <div class="profile-field-row">
                            <span class="field-label">Email Address</span>
                            <span class="field-value" id="pf-email"><?= htmlspecialchars($p['email']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Mobile Number
                                <?php if (empty($p['mobile_number'])): ?>
                                    <span class="field-required-tag">Required</span>
                                <?php endif; ?>
                            </span>
                            <span class="field-value" id="pf-mobile"><?= val($p['mobile_number']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Purok / Zone
                                <?php if (empty($p['purok'])): ?>
                                    <span class="field-required-tag">Required</span>
                                <?php endif; ?>
                            </span>
                            <span class="field-value" id="pf-purok"><?= val($p['purok']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Street Address</span>
                            <span class="field-value" id="pf-street"><?= val($p['street_address']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Barangay</span>
                            <span class="field-value">Barangay Sauyo</span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">City / Municipality</span>
                            <span class="field-value">Novaliches, Quezon City</span>
                        </div>
                    </div>

                    <form class="profile-edit-form" id="form-contact" style="display:none;" novalidate>
                        <div class="form-group">
                            <label class="modal-label">Email Address <span class="required-star">*</span></label>
                            <input type="email" class="ann-search-input" id="e-email"
                                   value="<?= htmlspecialchars($p['email']) ?>">
                            <span class="field-error" id="err-email"></span>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Mobile Number <span class="required-star">*</span></label>
                            <input type="tel" class="ann-search-input" id="e-mobile"
                                   value="<?= htmlspecialchars($p['mobile_number'] ?? '') ?>"
                                   placeholder="09XX XXX XXXX">
                            <span class="field-error" id="err-mobile"></span>
                        </div>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label class="modal-label">Purok / Zone <span class="required-star">*</span></label>
                                <input type="text" class="ann-search-input" id="e-purok"
                                       value="<?= htmlspecialchars($p['purok'] ?? '') ?>"
                                       placeholder="e.g. Purok 3">
                                <span class="field-error" id="err-purok"></span>
                            </div>
                            <div class="form-group">
                                <label class="modal-label">Street Address</label>
                                <input type="text" class="ann-search-input" id="e-street"
                                       value="<?= htmlspecialchars($p['street_address'] ?? '') ?>"
                                       placeholder="e.g. 123 Sampaguita St.">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary-portal cancel-edit-btn" data-target="contact">Cancel</button>
                            <button type="button" class="btn-primary-portal save-edit-btn" data-target="contact">Save Changes</button>
                        </div>
                    </form>
                </section>

                
                <section class="profile-card" id="card-membership">
                    <div class="profile-card-header">
                        <h2 class="section-label">SK Membership</h2>
                        <button class="card-edit-btn" type="button" data-target="membership">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m16 4 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z" />
                            </svg>
                            Edit
                        </button>
                    </div>

                    <div class="profile-fields" id="view-membership">
                        <div class="profile-field-row">
                            <span class="field-label">Member ID</span>
                            <span class="field-value mono"><?= htmlspecialchars($memberId) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Date Joined</span>
                            <span class="field-value"><?= !empty($p['created_at']) ? htmlspecialchars((new DateTime($p['created_at']))->format('F j, Y')) : '—' ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Member Status</span>
                            <span class="field-value"><span class="req-status-badge status-approved">Active</span></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Registered Voter</span>
                            <span class="field-value" id="pf-voter">
                                <?php if ($isVoter): ?>
                                    <span class="req-status-badge status-approved">Yes</span>
                                <?php else: ?>
                                    <span class="req-status-badge status-pending">No / Not Set</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Educational Attainment</span>
                            <span class="field-value" id="pf-edu"><?= $eduDisplay ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">School / Institution</span>
                            <span class="field-value" id="pf-school"><?= val($p['school_institution']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Course / Strand</span>
                            <span class="field-value" id="pf-course"><?= val($p['course_strand']) ?></span>
                        </div>
                        <div class="profile-field-row">
                            <span class="field-label">Employment Status</span>
                            <span class="field-value" id="pf-employment"><?= $empDisplay ?></span>
                        </div>
                    </div>

                    <form class="profile-edit-form" id="form-membership" style="display:none;" novalidate>
                        <div class="form-row-half">
                            <div class="form-group">
                                <label class="modal-label">Educational Attainment</label>
                                <select class="ann-select" id="e-edu" style="width:100%">
                                    <option value="">— Select —</option>
                                    <option value="elementary"       <?= ($p['educational_attainment'] ?? '') === 'elementary'       ? 'selected' : '' ?>>Elementary</option>
                                    <option value="high_school"      <?= ($p['educational_attainment'] ?? '') === 'high_school'      ? 'selected' : '' ?>>High School Graduate</option>
                                    <option value="senior_high"      <?= ($p['educational_attainment'] ?? '') === 'senior_high'      ? 'selected' : '' ?>>Senior High School Graduate</option>
                                    <option value="vocational"       <?= ($p['educational_attainment'] ?? '') === 'vocational'       ? 'selected' : '' ?>>Vocational / Technical</option>
                                    <option value="college_level"    <?= ($p['educational_attainment'] ?? '') === 'college_level'    ? 'selected' : '' ?>>College Level</option>
                                    <option value="college_graduate" <?= ($p['educational_attainment'] ?? '') === 'college_graduate' ? 'selected' : '' ?>>College Graduate</option>
                                    <option value="post_graduate"    <?= ($p['educational_attainment'] ?? '') === 'post_graduate'    ? 'selected' : '' ?>>Post-Graduate</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="modal-label">Employment Status</label>
                                <select class="ann-select" id="e-employment" style="width:100%">
                                    <option value="">— Select —</option>
                                    <option value="student"       <?= ($p['employment_status'] ?? '') === 'student'       ? 'selected' : '' ?>>Student</option>
                                    <option value="employed"      <?= ($p['employment_status'] ?? '') === 'employed'      ? 'selected' : '' ?>>Employed</option>
                                    <option value="unemployed"    <?= ($p['employment_status'] ?? '') === 'unemployed'    ? 'selected' : '' ?>>Unemployed</option>
                                    <option value="self_employed" <?= ($p['employment_status'] ?? '') === 'self_employed' ? 'selected' : '' ?>>Self-Employed</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">School / Institution</label>
                            <input type="text" class="ann-search-input" id="e-school"
                                   value="<?= htmlspecialchars($p['school_institution'] ?? '') ?>"
                                   placeholder="e.g. Quezon City University">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Course / Strand</label>
                            <input type="text" class="ann-search-input" id="e-course"
                                   value="<?= htmlspecialchars($p['course_strand'] ?? '') ?>"
                                   placeholder="e.g. BS Information Technology">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Registered Voter</label>
                            <div style="display:flex; align-items:center; gap:10px; margin-top:4px;">
                                <label style="display:flex; align-items:center; gap:6px; font-size:13px; font-weight:500; cursor:pointer;">
                                    <input type="radio" name="voter" id="e-voter-yes" value="1"
                                           <?= $isVoter ? 'checked' : '' ?>>
                                    Yes
                                </label>
                                <label style="display:flex; align-items:center; gap:6px; font-size:13px; font-weight:500; cursor:pointer;">
                                    <input type="radio" name="voter" id="e-voter-no" value="0"
                                           <?= !$isVoter ? 'checked' : '' ?>>
                                    No
                                </label>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary-portal cancel-edit-btn" data-target="membership">Cancel</button>
                            <button type="button" class="btn-primary-portal save-edit-btn" data-target="membership">Save Changes</button>
                        </div>
                    </form>
                </section>

                <section class="profile-card" id="card-threads">
                    <div class="profile-card-header">
                        <h2 class="section-label">Posted Threads</h2>
                        <span class="profile-thread-total" id="thread-total">—</span>
                    </div>
                    <div class="profile-scroll-wrap threads-scroll-body" data-scroll-area>
                        <ul class="profile-recent-list profile-scroll-content" id="user-threads-list">
                            <li class="recent-list-placeholder">Loading…</li>
                        </ul>
                        <button class="profile-scroll-more" type="button" aria-label="Scroll for more posted threads" hidden>
                            Scroll for more
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                    </div>
                    <a class="profile-card-footer-link" href="feed_page.php">View all threads</a>
                </section>

        </div>

    </main>
</div>


<div class="modal-overlay setup-modal-overlay" id="setup-overlay"
     style="display:none;" aria-modal="true" role="dialog">
    <div class="modal-box" style="max-width:520px;">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon setup-modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M5 21a7 7 0 0 1 14 0m-2-7 2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3>Let's finish setting up your profile</h3>
                    <p class="modal-subtitle">A few more details help the SK office serve you better.</p>
                </div>
            </div>
        </div>

        <div class="setup-required-banner">
            <span class="setup-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                    <path d="M12 9v4m0 4h.01" />
                </svg>
            </span>
            <span>
                <strong>Mobile Number</strong> and <strong>Purok / Zone</strong> are required.
                This modal will continue to appear until these are filled in.
            </span>
        </div>

        <div class="modal-body" style="padding:20px 24px; overflow-y:auto; max-height:60vh;">
            <div class="form-row-half">
                <div class="form-group">
                    <label class="modal-label">Mobile Number <span class="required-star">*</span></label>
                    <input type="tel" class="ann-search-input" id="setup-mobile" placeholder="09XX XXX XXXX">
                    <span class="field-error" id="setup-err-mobile"></span>
                </div>
                <div class="form-group">
                    <label class="modal-label">Purok / Zone <span class="required-star">*</span></label>
                    <input type="text" class="ann-search-input" id="setup-purok" placeholder="e.g. Purok 3">
                    <span class="field-error" id="setup-err-purok"></span>
                </div>
            </div>
            <div class="form-group">
                <label class="modal-label">Street Address</label>
                <input type="text" class="ann-search-input" id="setup-street" placeholder="e.g. 123 Sampaguita Street">
            </div>
            <div class="form-row-half">
                <div class="form-group">
                    <label class="modal-label">Nationality</label>
                    <input type="text" class="ann-search-input" id="setup-nationality" placeholder="e.g. Filipino">
                </div>
                <div class="form-group">
                    <label class="modal-label">Religion</label>
                    <input type="text" class="ann-search-input" id="setup-religion" placeholder="e.g. Roman Catholic">
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary-portal" id="setup-remind-later">Remind me later</button>
            <button class="btn-primary-portal" id="setup-save-btn">Save &amp; Complete Profile</button>
        </div>
    </div>
</div>


<div class="profile-toast" id="profile-toast" style="display:none;">
    <span class="toast-icon" id="toast-icon" aria-hidden="true"></span>
    <span class="toast-text" id="toast-text">Changes saved.</span>
</div>



























<script>
    window.profileData       = <?= $profileJson ?>;
    window.profileIncomplete = <?= $incomplete ? 'true' : 'false' ?>;
    window.PROFILE_CTRL      = '../../backend/controllers/ProfileController.php';
    window.NOTIF_CTRL        = '../../backend/controllers/NotificationController.php';
    window.notifStats        = <?= json_encode($notifStats) ?>;
</script>
<script src="../../scripts/portal/profile_page.js"></script>

</body>
</html>