<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Services — SK Officer</title>
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_services.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="off-content">

    <?php
    $pageTitle      = 'Services';
    $pageBreadcrumb = [['Home', '#'], ['Operations', null], ['Services', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

    <?php
    /* ── LOAD SERVICES FROM DATABASE ────────────────────────── */
    require_once __DIR__ . '/../../../backend/config/Database.php';
    require_once __DIR__ . '/../../../backend/controllers/ServiceController.php';

    $serviceController = new ServiceController();
    $services = $serviceController->getAll();

    $categoryMeta = [
        'medical'    => ['label' => 'Medical',    'icon' => 'svc-icon-medical'],
        'education'  => ['label' => 'Education',  'icon' => 'svc-icon-education'],
        'scholarship'=> ['label' => 'Scholarship','icon' => 'svc-icon-scholarship'],
        'livelihood' => ['label' => 'Livelihood', 'icon' => 'svc-icon-livelihood'],
        'assistance' => ['label' => 'Assistance', 'icon' => 'svc-icon-assistance'],
        'legal'      => ['label' => 'Legal',      'icon' => 'svc-icon-legal'],
        'other'      => ['label' => 'Other',      'icon' => 'svc-icon-other'],
    ];

    $categoryIcons = [
        'medical'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
        'education'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84 51.39 51.39 0 0 0-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>',
        'scholarship'=> '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/></svg>',
        'livelihood' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>',
        'assistance' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"/></svg>',
        'legal'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99.203 1.99.377 3 .52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.491Z"/></svg>',
        'other'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
    ];

    $totalActive   = count(array_filter($services, fn($s) => $s['status'] === 'active'));
    $totalInactive = count(array_filter($services, fn($s) => $s['status'] === 'inactive'));
    $totalServices = count($services);
    $atCapacity    = count(array_filter($services, fn($s) => $s['max_capacity'] !== null && $s['current_count'] >= $s['max_capacity']));
    ?>

        <!-- STAT WIDGETS -->
        <section class="off-widgets">

            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Active Services</span>
                    <p class="widget-number"><?= $totalActive ?></p>
                    <span class="widget-trend up">&#9650; Open for requests</span>
                </div>
            </div>

            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Inactive Services</span>
                    <p class="widget-number"><?= $totalInactive ?></p>
                    <span class="widget-trend warning">Closed to requests</span>
                </div>
            </div>

            <div class="off-widget-card widget-cyan">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Services</span>
                    <p class="widget-number"><?= $totalServices ?></p>
                    <span class="widget-trend neutral">All categories</span>
                </div>
            </div>

            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Pending Requests</span>
                    <p class="widget-number">8</p>
                    <span class="widget-trend warning">&#9650; Needs attention</span>
                </div>
            </div>

        </section>

        <!-- CONTROLS BAR -->
        <div class="svc-controls">
            <div class="svc-controls-left">
                <div class="svc-search-wrap">
                    <svg class="svc-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" id="svc-search" class="svc-search-input" placeholder="Search services…">
                </div>
                <select id="svc-category" class="svc-select">
                    <option value="all">All Categories</option>
                    <option value="medical">Medical</option>
                    <option value="education">Education</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="assistance">Assistance</option>
                    <option value="legal">Legal</option>
                    <option value="other">Other</option>
                </select>
                <select id="svc-type" class="svc-select">
                    <option value="all">All Types</option>
                    <option value="document">Online Application</option>
                    <option value="appointment">Request-based Service</option>
                    <option value="info">Information &amp; Direct Contact</option>
                </select>
                <select id="svc-status" class="svc-select">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="svc-controls-right">
                <button class="svc-add-btn" id="svc-add-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Service
                </button>
            </div>
        </div>

        <!-- SERVICES GRID -->
        <div class="panel-header" style="margin-bottom: 16px;">
            <h2 class="section-label">All Services</h2>
            <span class="svc-count" id="svc-count">Showing <?= $totalServices ?> services</span>
        </div>

        <div class="svc-grid" id="svc-grid">
            <?php foreach ($services as $svc):
                $meta = $categoryMeta[$svc['category']] ?? ['label' => ucfirst($svc['category']), 'icon' => ''];
                $icon = $categoryIcons[$svc['category']] ?? $categoryIcons['other'];
                $hasCapacity   = $svc['max_capacity'] !== null;
                $capacityFull  = $hasCapacity && $svc['current_count'] >= $svc['max_capacity'];
                $capacityPct   = $hasCapacity ? min(100, round(($svc['current_count'] / $svc['max_capacity']) * 100)) : 0;
                $reqLines      = array_filter(array_map('trim', explode("\n", $svc['requirements'] ?? '')));
            ?>
            <article class="svc-card"
                data-id="<?= $svc['id'] ?>"
                data-category="<?= $svc['category'] ?>"
                data-type="<?= $svc['service_type'] ?>"
                data-status="<?= $svc['status'] ?>"
                data-name="<?= strtolower(htmlspecialchars($svc['name'])) ?>">

                <div class="svc-card-body">

                    <!-- Top row: icon + category badge | status badge -->
                    <div class="svc-card-top">
                        <div class="svc-card-top-left">
                            <div class="svc-icon-wrap svc-icon-<?= $svc['category'] ?>">
                                <?= $icon ?>
                            </div>
                            <span class="svc-cat-tag svc-cat-<?= $svc['category'] ?>"><?= $meta['label'] ?></span>
                        </div>
                        <div class="svc-card-badges">
                            <?php if ($capacityFull): ?>
                            <span class="svc-status-badge svc-badge-full">
                                <span class="svc-status-dot"></span>
                                Full
                            </span>
                            <?php else: ?>
                            <span class="svc-status-badge svc-badge-<?= $svc['status'] ?>">
                                <span class="svc-status-dot"></span>
                                <?= $svc['status'] === 'active' ? 'Active' : 'Inactive' ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="svc-card-title"><?= htmlspecialchars($svc['name']) ?></h3>

                    <!-- Description -->
                    <p class="svc-card-desc"><?= htmlspecialchars($svc['description']) ?></p>

                    <!-- Details list -->
                    <ul class="svc-details-list">
                        <?php
                        $typeLabels = ['document' => 'Online Application', 'appointment' => 'Request-based Service', 'info' => 'Information & Contact'];
                        $typeLabelDisplay = $typeLabels[$svc['service_type']] ?? ucfirst($svc['service_type']);
                        ?>
                        <li class="svc-detail-type">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                            <span class="svc-detail-label">Service Type</span>
                            <span class="svc-detail-type-value"><?= htmlspecialchars($typeLabelDisplay) ?></span>
                        </li>
                        <?php if ($svc['eligibility']): ?>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            <span class="svc-detail-label-eleg">Eligibility</span>
                            <span class="svc-detail-type-value"><?= htmlspecialchars($svc['eligibility']) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($svc['processing_time']): ?>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <span class="svc-detail-label">Processing</span>
                            <span class="svc-detail-type-value"><?= htmlspecialchars($svc['processing_time']) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($reqLines)): ?>
                        <li class="svc-detail-req">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            <div class="svc-detail-req-content">
                                <span class="svc-detail-label">Requirements</span>
                                <ul class="svc-req-list">
                                    <?php 
                                    $displayReqs = array_slice($reqLines, 0, 2);
                                    foreach ($displayReqs as $req): 
                                    ?>
                                    <li><?= htmlspecialchars(ltrim($req, '-• ')) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($reqLines) > 2): ?>
                                <span class="svc-req-more">+<?= count($reqLines) - 2 ?> more...</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ($svc['service_type'] === 'info' && $svc['contact_info']): ?>
                        <li class="svc-detail-contact-row">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            <div class="svc-detail-contact">
                                <span class="svc-detail-label">Contact</span>
                                <span class="svc-contact-text"><?= nl2br(htmlspecialchars($svc['contact_info'])) ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php
                        $attNames = $svc['attachment_name'] ? array_filter(array_map('trim', explode(',', $svc['attachment_name']))) : [];
                        $attPaths = $svc['attachment_path'] ? array_filter(array_map('trim', explode(',', $svc['attachment_path']))) : [];
                        $attPaths = array_values($attPaths);
                        if (!empty($attNames)):
                        ?>
                        <li class="svc-attachment-row">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                            <div class="svc-attachment-multi">
                                <span class="svc-detail-label">Form<?= count($attNames) > 1 ? 's' : '' ?></span>
                                <?php 
                                $displayAtts = array_slice($attNames, 0, 1);
                                foreach ($displayAtts as $ai => $attName): 
                                ?>
                                <a href="<?= htmlspecialchars($attPaths[$ai] ?? '#') ?>" class="svc-attachment-link" target="_blank" download>
                                    <?= htmlspecialchars($attName) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if (count($attNames) > 1): ?>
                                <span class="svc-attachment-more">+<?= count($attNames) - 1 ?> more...</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Capacity bar (only if max_capacity is set) -->
                    <?php if ($hasCapacity): ?>
                    <div class="svc-capacity-wrap">
                        <div class="svc-capacity-header">
                            <span class="svc-capacity-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                                Capacity
                            </span>
                            <span class="svc-capacity-count <?= $capacityFull ? 'is-full' : '' ?>">
                                <?= $svc['current_count'] ?> / <?= $svc['max_capacity'] ?>
                            </span>
                        </div>
                        <div class="svc-capacity-bar">
                            <div class="svc-capacity-fill <?= $capacityPct >= 100 ? 'full' : ($capacityPct >= 80 ? 'warning' : '') ?>"
                                style="width: <?= $capacityPct ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Card Footer: actions -->
                <div class="svc-card-footer">
                    <!-- Toggle status -->
                    <button class="svc-toggle-btn svc-toggle-<?= $svc['status'] ?>"
                        data-id="<?= $svc['id'] ?>"
                        data-status="<?= $svc['status'] ?>"
                        title="<?= $svc['status'] === 'active' ? 'Deactivate' : 'Activate' ?> service">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg>
                        <?= $svc['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                    </button>
                    <div class="svc-card-actions-right">
                        <button class="svc-view-btn"
                            data-service='<?= htmlspecialchars(json_encode($svc), ENT_QUOTES) ?>'
                            title="View service details">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            View
                        </button>
                        <button class="svc-delete-btn"
                            data-id="<?= $svc['id'] ?>"
                            data-name="<?= htmlspecialchars($svc['name'], ENT_QUOTES) ?>"
                            title="Delete service">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            Remove
                        </button>
                    </div>
                </div>

            </article>
            <?php endforeach; ?>
        </div>

        <!-- NO RESULTS -->
        <div class="svc-no-results" id="svc-no-results" style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <p>No services match your current filters.</p>
        </div>

    </main>
</div>

<!-- ══════════════════════════════════════════════════
     ADD / EDIT MODAL
══════════════════════════════════════════════════ -->
<div class="svc-modal-overlay" id="svc-modal-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="svc-modal-title">
    <div class="svc-modal-box">

        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon" id="svc-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <div>
                    <h3 class="svc-modal-title" id="svc-modal-title">Add Service</h3>
                    <p class="svc-modal-subtitle">Fields marked <span class="svc-required">*</span> are required.</p>
                </div>
            </div>
            <button class="svc-modal-close" id="svc-modal-close" aria-label="Close">&times;</button>
        </div>

        <!-- STEP TABS -->
        <div class="svc-modal-tabs" role="tablist">
            <button class="svc-tab active" data-tab="1" role="tab">
                <span class="svc-tab-num">1</span>
                <span>Basic Info</span>
            </button>
            <div class="svc-tab-divider"></div>
            <button class="svc-tab" data-tab="2" role="tab">
                <span class="svc-tab-num">2</span>
                <span>Requirements</span>
            </button>
            <div class="svc-tab-divider"></div>
            <button class="svc-tab" data-tab="3" role="tab">
                <span class="svc-tab-num">3</span>
                <span>Settings</span>
            </button>
        </div>

        <div class="svc-modal-body">
            <input type="hidden" id="svc-id">

            <!-- ── TAB 1: BASIC INFO ── -->
            <div class="svc-tab-panel active" id="svc-panel-1">

                <!-- Service Name -->
                <div class="svc-form-group">
                    <label class="svc-label" for="svc-name">Service Name <span class="svc-required">*</span></label>
                    <input type="text" id="svc-name" class="svc-input" placeholder="e.g. Medical Assistance" maxlength="80">
                    <span class="svc-field-error" id="err-svc-name"></span>
                </div>

                <!-- Row: Category + Service Type -->
                <div class="svc-form-row">
                    <div class="svc-form-group">
                        <label class="svc-label" for="svc-category-field">Category <span class="svc-required">*</span></label>
                        <select id="svc-category-field" class="svc-select-input">
                            <option value="" disabled selected hidden>Select Category</option>
                            <option value="medical">Medical</option>
                            <option value="education">Education</option>
                            <option value="scholarship">Scholarship</option>
                            <option value="livelihood">Livelihood</option>
                            <option value="assistance">Assistance</option>
                            <option value="legal">Legal</option>
                            <option value="other">Other</option>
                        </select>
                        <span class="svc-field-error" id="err-svc-category"></span>
                    </div>
                    <div class="svc-form-group">
                        <label class="svc-label" for="svc-type-field">Service Type <span class="svc-required">*</span></label>
                        <select id="svc-type-field" class="svc-select-input">
                            <option value="" disabled selected hidden>Select Type</option>
                            <option value="document">Online Application</option>
                            <option value="appointment">Request-based Service</option>
                            <option value="info">Information & Contact</option>
                        </select>
                        <span class="svc-field-error" id="err-svc-type"></span>
                    </div>
                </div>

                <!-- Description -->
                <div class="svc-form-group">
                    <label class="svc-label" for="svc-desc">Description <span class="svc-required">*</span></label>
                    <textarea id="svc-desc" class="svc-textarea" rows="3" placeholder="Describe what this service offers…"></textarea>
                    <span class="svc-field-error" id="err-svc-desc"></span>
                </div>

                <!-- Approval Message (hidden for info/walk-in type) -->
                <div class="svc-form-group" id="svc-approval-group">
                    <label class="svc-label" for="svc-approval-msg">Approval Message <span class="svc-required">*</span></label>
                    <textarea id="svc-approval-msg" class="svc-textarea" rows="3" placeholder="e.g. Approved! Please visit the SK Hall this Friday with your school ID.&#10;&#10;This message will be shown to residents when their application is approved."></textarea>
                    <span class="svc-field-hint">Enter instructions or next steps shown to residents when approved.</span>
                    <span class="svc-field-error" id="err-svc-approval-msg"></span>
                </div>

                <!-- Row: Eligibility + Processing Time -->
                <div class="svc-form-row">
                    <div class="svc-form-group">
                        <label class="svc-label" for="svc-eligibility">Eligibility <span class="svc-optional">(optional)</span></label>
                        <input type="text" id="svc-eligibility" class="svc-input" placeholder="e.g. Registered youth resident">
                    </div>
                    <div class="svc-form-group">
                        <label class="svc-label" for="svc-time">Processing Time <span class="svc-optional">(optional)</span></label>
                        <input type="text" id="svc-time" class="svc-input" placeholder="e.g. 3–5 working days">
                    </div>
                </div>

                <!-- Contact Info (shown only for info/walk-in type) -->
                <div class="svc-form-group svc-contact-group" id="svc-contact-group" style="display:none;">
                    <label class="svc-label" for="svc-contact">Contact Information <span class="svc-required">*</span></label>
                    <textarea id="svc-contact" class="svc-textarea" rows="3" placeholder="e.g. SK Hotline: 0917-123-4567&#10;SK Office: Barangay Hall Room 2&#10;Available: Mon–Fri, 8AM–5PM"></textarea>
                    <span class="svc-field-hint">Include hotline numbers, office location, and available hours.</span>
                    <span class="svc-field-error" id="err-svc-contact"></span>
                </div>

                <!-- Status -->
                <div class="svc-form-group">
                    <label class="svc-label" for="svc-status-field">Initial Status <span class="svc-required">*</span></label>
                    <select id="svc-status-field" class="svc-select-input">
                        <option value="active">Active — open for requests</option>
                        <option value="inactive">Inactive — closed to requests</option>
                    </select>
                </div>

            </div><!-- /tab 1 -->

            <!-- ── TAB 2: REQUIREMENTS ── -->
            <div class="svc-tab-panel" id="svc-panel-2">        

                <!-- Requirements textarea -->
                <div class="svc-form-group">
                    <label class="svc-label" for="svc-requirements">
                        Document Requirements
                        <span class="svc-optional">(optional)</span>
                    </label>
                    <div class="svc-req-editor-wrap">
                        <textarea id="svc-requirements" class="svc-textarea svc-req-textarea" rows="6"
                            placeholder="- Valid ID&#10;- Medical Certificate&#10;- Barangay Certificate of Residency&#10;&#10;Tip: Start a line with a hyphen (-) to create a list item."></textarea>
                        <div class="svc-req-preview-wrap" id="svc-req-preview-wrap" style="display:none;">
                            <div class="svc-req-preview-label">Preview</div>
                            <div class="svc-req-preview" id="svc-req-preview"></div>
                        </div>
                    </div>
                    <div class="svc-req-toolbar">
                        <button type="button" class="svc-preview-toggle" id="svc-preview-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            Preview
                        </button>
                    </div>
                </div>

                <!-- Attachment upload -->
                <div class="svc-form-group" style="margin-top: 4px;">
                    <label class="svc-label">
                        Downloadable Forms / Attachments
                        <span class="svc-optional">(optional)</span>
                    </label>
                    <div class="svc-attachment-box" id="svc-attachment-box">
                        <div class="svc-attachment-empty" id="svc-attachment-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                            <p class="svc-attachment-hint">Drag &amp; drop files here, or</p>
                            <label class="svc-attachment-browse" for="svc-attachment-input">Browse files</label>
                            <input type="file" id="svc-attachment-input" class="svc-attachment-input" accept=".pdf,.doc,.docx,.xlsx" multiple>
                            <p class="svc-attachment-meta">PDF, DOC, DOCX, XLSX — max 10MB each</p>
                        </div>
                    </div>
                    <!-- Attachment list (filled dynamically) -->
                    <div class="svc-attachment-list" id="svc-attachment-list"></div>
                    <button type="button" class="svc-attachment-add-more" id="svc-attachment-add-more" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add another file
                        <input type="file" class="svc-attachment-add-more-input" id="svc-attachment-add-more-input" accept=".pdf,.doc,.docx,.xlsx" multiple>
                    </button>
                    <!-- existing attachment names (edit mode) -->
                    <input type="hidden" id="svc-existing-attachment" value="">
                </div>

            </div><!-- /tab 2 -->

            <!-- ── TAB 3: SETTINGS ── -->
            <div class="svc-tab-panel" id="svc-panel-3">

                <!-- Max Capacity toggle -->
                <div class="svc-settings-card">
                    <div class="svc-settings-card-header">
                        <div class="svc-settings-card-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            <div>
                                <p class="svc-settings-card-title">Set Maximum Capacity</p>
                                <p class="svc-settings-card-desc">When the limit is reached, the service will automatically close and show as full to residents.</p>
                            </div>
                        </div>
                        <label class="svc-toggle-switch">
                            <input type="checkbox" id="svc-capacity-toggle">
                            <span class="svc-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="svc-capacity-input-wrap" id="svc-capacity-input-wrap" style="display:none;">
                        <div class="svc-form-group" style="margin-bottom:0;">
                            <label class="svc-label" for="svc-max-capacity">Maximum Slots / Applicants</label>
                            <div class="svc-capacity-input-row">
                                <input type="number" id="svc-max-capacity" class="svc-input svc-capacity-num" min="1" max="9999" placeholder="e.g. 50">
                                <span class="svc-capacity-unit">slots</span>
                            </div>
                            <span class="svc-field-hint">The service automatically becomes inactive once this number of approved applicants is reached.</span>
                            <span class="svc-field-error" id="err-svc-capacity"></span>
                        </div>
                    </div>
                </div>

                <!-- Notification setting (future) -->
                <div class="svc-settings-card svc-settings-card--muted">
                    <div class="svc-settings-card-header">
                        <div class="svc-settings-card-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            <div>
                                <p class="svc-settings-card-title">Applicant Notifications</p>
                                <p class="svc-settings-card-desc">Residents will receive an in-app notification when their application status changes.</p>
                            </div>
                        </div>
                        <span class="svc-settings-badge">Always On</span>
                    </div>
                </div>

                <!-- Visibility setting (future) -->
                <div class="svc-settings-card svc-settings-card--muted">
                    <div class="svc-settings-card-header">
                        <div class="svc-settings-card-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <div>
                                <p class="svc-settings-card-title">Public Visibility</p>
                                <p class="svc-settings-card-desc">This service will be visible to all residents on the public portal.</p>
                            </div>
                        </div>
                        <span class="svc-settings-badge">Always On</span>
                    </div>
                </div>

            </div><!-- /tab 3 -->

        </div><!-- /modal-body -->

        <div class="svc-modal-footer">
            <button class="btn-off-sm" id="svc-modal-cancel">Cancel</button>
            <div class="svc-modal-footer-right">
                <button class="svc-nav-btn" id="svc-prev-btn" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    Back
                </button>
                <button class="svc-nav-btn svc-nav-btn--next" id="svc-next-btn">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
                <button class="svc-save-btn" id="svc-save-btn" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Save Service
                </button>
            </div>
        </div>

    </div>
</div>

<!-- VIEW SERVICE MODAL -->
<div class="svc-view-overlay" id="svc-view-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="svc-view-name">
    <div class="svc-view-box">

        <div class="svc-view-header">
            <div class="svc-view-header-left">
                <div class="svc-view-icon" id="svc-view-icon"></div>
                <div class="svc-view-title-wrap">
                    <h3 class="svc-view-name" id="svc-view-name"></h3>
                    <div class="svc-view-badges" id="svc-view-badges"></div>
                </div>
            </div>
            <button class="svc-view-close" id="svc-view-close" aria-label="Close">&times;</button>
        </div>

        <div class="svc-view-body" id="svc-view-body"></div>

        <div class="svc-view-footer">
            <button class="btn-off-sm" id="svc-view-edit-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:13px;height:13px;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                Edit Service
            </button>
            <button class="svc-save-btn" id="svc-view-close-btn" style="background:var(--off-dark-mid);">Close</button>
        </div>

    </div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div class="svc-confirm-overlay" id="svc-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
    <div class="svc-confirm-box">
        <div class="svc-confirm-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
        </div>
        <h3 class="svc-confirm-title">Remove Service</h3>
        <p class="svc-confirm-body" id="svc-confirm-body">Are you sure you want to remove this service? This cannot be undone.</p>
        <div class="svc-confirm-footer">
            <button class="btn-off-sm" id="svc-confirm-cancel">Cancel</button>
            <button class="svc-confirm-delete" id="svc-confirm-delete">Remove</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="svc-toast" id="svc-toast" aria-live="polite"></div>

<script src="../../../scripts/management/officer/officer_services.js"></script>

</body>
</html>