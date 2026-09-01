<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Services</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/services_page.css">

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php
            $pageTitle      = 'Services';
            $pageBreadcrumb = [['Home', '#'], ['Services', null]];
            $userName       = $_SESSION['user_name'] ?? 'Guest';
            $userRole       = 'Resident';
            include __DIR__ . '/../../components/portal/topbar.php';
            ?>

            <?php
            /* ── LOAD SERVICES FROM DATABASE ────────────────────────── */
            require_once __DIR__ . '/../../backend/config/database.php';
            require_once __DIR__ . '/../../backend/controllers/ServiceController.php';

            $serviceController = new ServiceController();
            $services = $serviceController->getAll();

            // Sort services: active/open first, then limited, then closed/inactive
            usort($services, function ($a, $b) {
                $getPriority = function ($service) {
                    $isActive = $service['status'] === 'active';
                    $hasCapacity = $service['max_capacity'] !== null;
                    $capacityFull = $hasCapacity && $service['current_count'] >= $service['max_capacity'];

                    if ($isActive && !$capacityFull) return 1;
                    if (
                        $isActive && $hasCapacity && !$capacityFull &&
                        ($service['current_count'] / $service['max_capacity']) >= 0.7
                    ) return 2;
                    return 3;
                };

                $priorityA = $getPriority($a);
                $priorityB = $getPriority($b);

                if ($priorityA === $priorityB) {
                    // If same priority, sort by name alphabetically
                    return strcmp($a['name'], $b['name']);
                }

                return $priorityA - $priorityB;
            });

            $categoryMeta = [
                'medical'    => ['label' => 'Medical',     'emoji' => '🏥'],
                'education'  => ['label' => 'Education',   'emoji' => '🎓'],
                'scholarship' => ['label' => 'Scholarship', 'emoji' => '🏅'],
                'livelihood' => ['label' => 'Livelihood',  'emoji' => '🛠️'],
                'assistance' => ['label' => 'Assistance',  'emoji' => '🤝'],
                'legal'      => ['label' => 'Legal',       'emoji' => '⚖️'],
                'other'      => ['label' => 'Other',       'emoji' => '📋'],
            ];

            $categoryIcons = [
                'medical'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
                'education'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>',
                'scholarship' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/></svg>',
                'livelihood' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>',
                'assistance' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"/></svg>',
                'legal'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99.203 1.99.377 3 .52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.491Z"/></svg>',
                'other'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            ];

            // service_type → display label mapping (from DB COMMENT)
            $serviceTypeLabels = [
                'document'    => 'Online Application',
                'appointment' => 'Request-based',
                'info'        => 'Information & Contact',
            ];

            $totalActive   = count(array_filter($services, fn ($s) => $s['status'] === 'active'));
            $totalInactive = count(array_filter($services, fn ($s) => $s['status'] === 'inactive'));

            // Pre-fetch resident's existing requests to show "already applied" state
            $residentId = (int)($_SESSION['user_id'] ?? 0);
            $appliedServiceIds = [];
            $userEmail = '';
            if ($residentId) {
                try {
                    $db   = (new Database())->getConnection();
                    $stmt = $db->prepare("SELECT DISTINCT service_id FROM service_applications WHERE resident_id = :rid AND status NOT IN ('rejected','cancelled')");
                    $stmt->execute([':rid' => $residentId]);
                    $appliedServiceIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'service_id');

                    // Fetch the user's email for auto-filling the apply modal
                    $emailStmt = $db->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
                    $emailStmt->execute([':id' => $residentId]);
                    $userEmail = $emailStmt->fetch(PDO::FETCH_ASSOC)['email'] ?? '';
                } catch (Throwable $e) { /* silently ignore if table doesn't exist yet */
                }
            }
            ?>

            <!-- HOW IT WORKS -->
            <section class="how-it-works-section">
                <h2 class="section-label">How It Works</h2>
                <div class="steps-row">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <strong>Browse Services</strong>
                            <p>Explore available SK programs and find one you qualify for.</p>
                        </div>
                    </div>
                    <div class="step-arrow">›</div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <strong>Submit a Request</strong>
                            <p>Fill out the form and upload your required documents.</p>
                        </div>
                    </div>
                    <div class="step-arrow">›</div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <strong>SK Review</strong>
                            <p>An SK officer reviews and verifies your submission.</p>
                        </div>
                    </div>
                    <div class="step-arrow">›</div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-body">
                            <strong>Get Notified</strong>
                            <p>Receive a notification once your request has been processed.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTROLS BAR -->
            <section class="announcements-controls">
                <div class="controls-left">
                    <div class="search-wrap">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="svc-search" placeholder="Search services…" class="ann-search-input">
                    </div>
                </div>
                <div class="controls-right">
                    <select id="svc-category" class="ann-select">
                        <option value="all">All Categories</option>
                        <option value="medical">Medical</option>
                        <option value="education">Education</option>
                        <option value="scholarship">Scholarship</option>
                        <option value="livelihood">Livelihood</option>
                        <option value="assistance">Assistance</option>
                        <option value="legal">Legal</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="svc-type" class="ann-select">
                        <option value="all">All Types</option>
                        <option value="document">Online Application</option>
                        <option value="appointment">Request-based</option>
                        <option value="info">Info &amp; Contact</option>
                    </select>
                    <select id="svc-status" class="ann-select">
                        <option value="all">All Statuses</option>
                        <option value="active">Open</option>
                        <option value="inactive">Closed</option>
                    </select>
                </div>
            </section>

            <!-- SERVICES GRID -->
            <section class="announcements-section">
                <div class="panel-header" style="margin-bottom: 16px;">
                    <h2 class="section-label">Available Services</h2>
                    <span class="svc-count" id="svc-count">
                        Showing <?= count($services) ?> service<?= count($services) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="announcements-grid" id="svc-grid">

                    <?php if (empty($services)) : ?>
                        <p class="no-results" style="display:block;">No services are currently listed.</p>
                    <?php else : ?>
                        <?php foreach ($services as $svc) :
                            $meta      = $categoryMeta[$svc['category']] ?? ['label' => ucfirst($svc['category']), 'emoji' => '📋'];
                            $icon      = $categoryIcons[$svc['category']] ?? $categoryIcons['other'];
                            $isActive  = $svc['status'] === 'active';
                            $hasCapacity  = $svc['max_capacity'] !== null;
                            $capacityFull = $hasCapacity && $svc['current_count'] >= $svc['max_capacity'];
                            $isOpen       = $isActive && !$capacityFull;
                            $isLimited    = $isActive && $hasCapacity && !$capacityFull
                                && ($svc['current_count'] / $svc['max_capacity']) >= 0.7;

                            // Determine display status
                            if (!$isActive || $capacityFull)    $displayStatus = 'closed';
                            elseif ($isLimited)                  $displayStatus = 'limited';
                            else                                 $displayStatus = 'open';

                            $typeLabel  = $serviceTypeLabels[$svc['service_type']] ?? 'Service';
                            $reqLines   = array_filter(array_map('trim', explode("\n", $svc['requirements'] ?? '')));
                            $attNames   = $svc['attachment_name'] ? array_filter(array_map('trim', explode(',', $svc['attachment_name']))) : [];
                            $attPaths   = $svc['attachment_path'] ? array_values(array_filter(array_map('trim', explode(',', $svc['attachment_path'])))) : [];
                            $alreadyApplied = in_array($svc['id'], $appliedServiceIds);

                            // Button label & type
                            $btnLabel = match ($svc['service_type']) {
                                'document'    => 'Apply Now',
                                'appointment' => 'Request Service',
                                default       => 'View Details',
                            };
                            $isInfoOnly = $svc['service_type'] === 'info';
                        ?>
                            <article class="ann-card svc-card res-svc-card" data-id="<?= $svc['id'] ?>" data-category="<?= htmlspecialchars($svc['category']) ?>" data-type="<?= htmlspecialchars($svc['service_type']) ?>" data-status="<?= $displayStatus ?>">

                                <div class="ann-card-body">

                                    <!-- Top row: icon + category badge | status badge -->
                                    <div class="svc-card-top">
                                        <div class="svc-card-top-left">
                                            <div class="svc-icon-wrap svc-icon-<?= $svc['category'] ?>">
                                                <?= $icon ?>
                                            </div>
                                            <span class="res-cat-tag res-cat-<?= $svc['category'] ?>"><?= $meta['label'] ?></span>
                                        </div>
                                        <div class="svc-card-badges">
                                            <?php if ($displayStatus === 'open') : ?>
                                                <span class="feed-badge status-open">
                                                    <span class="feed-badge-dot"></span>Open
                                                </span>
                                            <?php elseif ($displayStatus === 'limited') : ?>
                                                <span class="feed-badge status-limited">
                                                    <span class="feed-badge-dot"></span>Limited Slots
                                                </span>
                                            <?php else : ?>
                                                <span class="feed-badge status-closed">
                                                    <span class="feed-badge-dot"></span>Closed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Title & description -->
                                    <h3 class="ann-card-title"><?= htmlspecialchars($svc['name']) ?></h3>
                                    <p class="svc-card-excerpt"><?= htmlspecialchars($svc['description']) ?></p>

                                    <!-- Details list -->
                                    <ul class="svc-details-list">
                                        <li class="svc-detail-type">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                            </svg>
                                            <span class="svc-detail-label">Type</span>
                                            <span class="res-type-pill res-type-<?= $svc['service_type'] ?>"><?= htmlspecialchars($typeLabel) ?></span>
                                        </li>
                                        <?php if ($svc['eligibility']) : ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                                <span class="svc-detail-label">Eligibility</span>
                                                <span class="svc-detail-value"><?= htmlspecialchars($svc['eligibility']) ?></span>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($svc['processing_time']) : ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                <span class="svc-detail-label">Processing</span>
                                                <span class="svc-detail-value"><?= htmlspecialchars($svc['processing_time']) ?></span>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($reqLines)) : ?>
                                            <li class="svc-detail-req">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                                <div class="svc-detail-req-content">
                                                    <span class="svc-detail-label">Requirements</span>
                                                    <?php
                                                    $totalReqs = count($reqLines);
                                                    $limit = $totalReqs > 3 ? 2 : 3;
                                                    ?>
                                                    <ul class="svc-req-list">
                                                        <?php foreach (array_slice($reqLines, 0, $limit) as $req) : ?>
                                                            <li><?= htmlspecialchars(ltrim($req, '-• ')) ?></li>
                                                        <?php endforeach; ?>
                                                        <?php if ($totalReqs > 3) : ?>
                                                            <li class="svc-req-more">+<?= $totalReqs - 2 ?> more...</li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($isInfoOnly && !empty($svc['contact_info'])) : ?>
                                            <li class="svc-detail-contact-row">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                                </svg>
                                                <div class="svc-detail-contact">
                                                    <span class="svc-detail-label">Contact</span>
                                                    <?php
                                                    $contactLines = array_filter(array_map('trim', explode("\n", $svc['contact_info'])));
                                                    ?>
                                                    <ul class="svc-contact-list">
                                                        <?php foreach ($contactLines as $line) : ?>
                                                            <li><?= htmlspecialchars($line) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($attNames)) : ?>
                                            <?php $totalAtts = count($attNames); ?>
                                            <div class="res-forms-row">
                                                <span class="svc-detail-label">
                                                    Attachment<?= $totalAtts > 1 ? 's' : '' ?>:
                                                </span>
                                                <a href="<?= htmlspecialchars($attPaths[0] ?? '#') ?>" class="res-form-link" target="_blank" download title="<?= htmlspecialchars($attNames[0]) ?>">
                                                    📎 <span class="res-form-link-name"><?= htmlspecialchars($attNames[0]) ?></span>
                                                </a>
                                                <?php if ($totalAtts > 1) : ?>
                                                    <span class="svc-req-more">+<?= $totalAtts - 1 ?> more...</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>


                                        <?php if ($hasCapacity) : ?>
                                            <li class="res-capacity-bar-header">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                </svg>
                                                <div class="res-capacity-bar-label">
                                                    <span class="svc-detail-label">Slots</span>

                                                    <span class="svc-detail-value res-slots-text <?= $capacityFull ? 'slots-full' : ($isLimited ? 'slots-limited' : '') ?>">
                                                        <?= $svc['current_count'] ?> / <?= $svc['max_capacity'] ?> filled
                                                    </span>
                                                </div>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Capacity bar -->
                                        <?php if ($hasCapacity) : ?>
                                            <div class="res-capacity-bar-wrap">

                                                <?php $pct = min(100, round(($svc['current_count'] / $svc['max_capacity']) * 100)); ?>

                                                <div class="res-capacity-bar">
                                                    <div class="res-capacity-fill <?= $pct >= 100 ? 'bar-full' : ($pct >= 70 ? 'bar-warn' : '') ?>" style="width:<?= $pct ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </ul>

                                    <!-- Card actions -->
                                    <div class="ann-card-actions res-card-actions">
                                        <!-- Always: View Details -->
                                        <button class="btn-ghost-portal res-view-btn" data-id="<?= $svc['id'] ?>" data-service="<?= htmlspecialchars($svc['name']) ?>" data-icon="<?= htmlspecialchars($icon) ?>" data-category="<?= htmlspecialchars($meta['label']) ?>" data-category-key="<?= htmlspecialchars($svc['category']) ?>" data-type="<?= htmlspecialchars($svc['service_type']) ?>" data-type-label="<?= htmlspecialchars($typeLabel) ?>" data-status="<?= $displayStatus ?>" data-description="<?= htmlspecialchars($svc['description']) ?>" data-eligibility="<?= htmlspecialchars($svc['eligibility'] ?? '—') ?>" data-processing="<?= htmlspecialchars($svc['processing_time'] ?? '—') ?>" data-requirements="<?= htmlspecialchars($svc['requirements'] ?? '') ?>" data-contact="<?= htmlspecialchars($svc['contact_info'] ?? '') ?>" data-capacity="<?= $hasCapacity ? $svc['current_count'] . '/' . $svc['max_capacity'] : '' ?>" data-attachment-names="<?= htmlspecialchars(implode(',', $attNames)) ?>" data-attachment-paths="<?= htmlspecialchars(implode(',', $attPaths)) ?>" aria-label="View details for <?= htmlspecialchars($svc['name']) ?>">
                                            View Details
                                        </button>

                                        <?php if (!$isInfoOnly) : ?>
                                            <?php if ($alreadyApplied) : ?>
                                                <button class="btn-primary-portal res-action-btn" disabled>
                                                    ✓ Already Applied
                                                </button>
                                            <?php elseif ($isOpen) : ?>
                                                <button class="btn-primary-portal res-action-btn svc-apply-btn" data-id="<?= $svc['id'] ?>" data-service="<?= htmlspecialchars($svc['name']) ?>" data-icon="<?= htmlspecialchars($icon) ?>" data-category-key="<?= htmlspecialchars($svc['category']) ?>" data-type="<?= htmlspecialchars($svc['service_type']) ?>" data-eligibility="<?= htmlspecialchars($svc['eligibility'] ?? '—') ?>" data-processing="<?= htmlspecialchars($svc['processing_time'] ?? '—') ?>" data-requirements="<?= htmlspecialchars($svc['requirements'] ?? '') ?>">
                                                    <?= $btnLabel ?>
                                                </button>
                                            <?php else : ?>
                                                <button class="btn-primary-portal res-action-btn" disabled>
                                                    <?= $capacityFull ? 'Slots Full' : 'Currently Closed' ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                    </div>

                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

                <div class="no-results" id="no-results" style="display:none;">
                    <p>No services match your search.</p>
                </div>
            </section>

        </main>
    </div>

    <!-- ═══════════════════════════════════════════════════════
     DETAILS MODAL (Info & Direct Contact — read-only view)
     ═══════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="details-modal-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="details-modal-title">
        <div class="modal-box modal-box--wide">

            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-icon" id="details-modal-icon">📋</div>
                    <div>
                        <h3 id="details-modal-title">Service Details</h3>
                        <p class="modal-subtitle" id="details-modal-type-label">—</p>
                    </div>
                </div>
                <button class="modal-close" id="details-modal-close" aria-label="Close">&times;</button>
            </div>

            <div class="modal-body">
                <!-- Status strip -->
                <div class="details-status-strip" id="details-status-strip"></div>

                <!-- Description -->
                <div class="details-section">
                    <span class="details-section-label">About This Service</span>
                    <p class="details-description" id="details-description">—</p>
                </div>

                <!-- Meta grid -->
                <div class="details-meta-grid">
                    <div class="details-meta-item" id="details-elig-wrap">
                        <span class="details-meta-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Eligibility
                        </span>
                        <span class="details-meta-value" id="details-eligibility">—</span>
                    </div>
                    <div class="details-meta-item" id="details-proc-wrap">
                        <span class="details-meta-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Processing Time
                        </span>
                        <span class="details-meta-value" id="details-processing">—</span>
                    </div>
                    <div class="details-meta-item" id="details-cap-wrap" style="display:none;">
                        <span class="details-meta-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            Capacity
                        </span>
                        <span class="details-meta-value" id="details-capacity">—</span>
                    </div>
                </div>

                <!-- Requirements -->
                <div class="details-section" id="details-req-section" style="display:none;">
                    <span class="details-section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Requirements
                    </span>
                    <ul class="details-req-list" id="details-req-list"></ul>
                </div>

                <!-- Contact info (info type only) -->
                <div class="details-section details-contact-section" id="details-contact-section" style="display:none;">
                    <span class="details-section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        How to Reach Us
                    </span>
                    <p class="details-contact-text" id="details-contact"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-close-portal" id="details-modal-cancel">Close</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
     APPLICATION / REQUEST MODAL
     ═══════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="apply-modal-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="apply-modal-title">
        <div class="modal-box">

            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-icon" id="apply-modal-icon">📋</div>
                    <div>
                        <h3 id="apply-modal-title">Service Request</h3>
                        <p class="modal-subtitle">All fields marked <span class="required-star">*</span> are required.</p>
                    </div>
                </div>
                <button class="modal-close" id="apply-modal-close" aria-label="Close">&times;</button>
            </div>

            <!-- Service summary strip -->
            <div class="modal-svc-summary">
                <div class="svc-summary-item">
                    <span class="svc-summary-label">Eligibility</span>
                    <span id="apply-sum-eligibility">—</span>
                </div>
                <div class="svc-summary-item">
                    <span class="svc-summary-label">Processing Time</span>
                    <span id="apply-sum-processing">—</span>
                </div>
                <div class="svc-summary-item">
                    <span class="svc-summary-label">Requirements</span>
                    <span id="apply-sum-requirements">—</span>
                </div>
            </div>

            <div class="modal-body">
                <form class="concern-form" id="apply-form" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="apply-service-id" name="service_id">

                    <!-- Row 1: Name + Contact -->
                    <div class="modal-row">
                        <div class="form-group">
                            <label class="modal-label" for="r-name">Full Name <span class="required-star">*</span></label>
                            <input type="text" class="modal-input" id="r-name" name="full_name" placeholder="e.g. Juan Dela Cruz" autocomplete="name" required>
                            <span class="field-error" id="err-name"></span>
                        </div>
                        <div class="form-group">
                            <label class="modal-label" for="r-contact">Contact Number <span class="required-star">*</span></label>
                            <input type="tel" class="modal-input" id="r-contact" name="contact" placeholder="e.g. 09XX XXX XXXX" autocomplete="tel" required>
                            <span class="field-error" id="err-contact"></span>
                        </div>
                    </div>

                    <!-- Row 2: Email + Address -->
                    <div class="modal-row">
                        <div class="form-group">
                            <label class="modal-label" for="r-email">Email Address <span class="required-star">*</span></label>
                            <input type="email" class="modal-input modal-input--readonly" id="r-email" name="email" autocomplete="off" readonly>
                            <span class="field-hint">Auto-filled from your account.</span>
                            <span class="field-error" id="err-email"></span>
                        </div>
                        <div class="form-group">
                            <label class="modal-label" for="r-address">Home Address <span class="required-star">*</span></label>
                            <input type="text" class="modal-input" id="r-address" name="address" placeholder="Purok/Street, Barangay Sauyo" autocomplete="street-address" required>
                            <span class="field-error" id="err-address"></span>
                        </div>
                    </div>

                    <!-- Purpose / Notes -->
                    <div class="form-group">
                        <label class="modal-label" for="r-purpose">
                            Purpose / Notes
                            <span class="modal-label-optional">(Optional)</span>
                        </label>
                        <textarea class="modal-input modal-textarea" id="r-purpose" name="purpose" rows="3" placeholder="Briefly explain why you are applying for this service…" maxlength="1000"></textarea>
                    </div>

                    <!-- Upload Documents -->
                    <div class="form-group">
                        <label class="modal-label">Upload Required Documents <span class="required-star">*</span></label>
                        <div class="file-drop-zone" id="file-drop-zone">
                            <input type="file" id="r-docs" name="documents[]" multiple class="file-input-hidden" accept="image/*,.pdf,.doc,.docx">
                            <div class="file-drop-inner">
                                <span class="file-drop-icon">📎</span>
                                <span class="file-drop-text">
                                    Drag & drop files here or
                                    <button type="button" class="file-browse-btn" id="file-browse-btn">browse</button>
                                </span>
                                <span class="file-drop-hint">Accepted: Images, PDF, DOC — Max 5 MB each</span>
                            </div>
                            <ul class="file-list" id="file-list"></ul>
                        </div>
                        <span class="field-error" id="err-docs"></span>
                    </div>

                    <!-- Acknowledgement -->
                    <div class="form-group svc-acknowledge">
                        <label class="acknowledge-wrap">
                            <input type="checkbox" id="r-agree" name="agree" required>
                            <span>I confirm that all information provided is accurate and that I meet the eligibility requirements for this service. <span class="required-star">*</span></span>
                        </label>
                        <span class="field-error" id="err-agree"></span>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button class="btn-close-portal" id="apply-modal-cancel" type="button">Cancel</button>
                <button class="btn-primary-portal" id="apply-modal-submit" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Submit Request
                </button>
            </div>
        </div>
    </div>

    <script>const SESSION_USER_EMAIL = <?= json_encode($userEmail) ?>;</script>
    <script src="../../scripts/portal/services_page.js"></script>

</body>

</html>