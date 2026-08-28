<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Announcements</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/public/announcements.css">
    <link rel="stylesheet" href="../../styles/public/header.css">
    <link rel="stylesheet" href="../../styles/public/footer.css">
</head>
<body>

<?php
require_once __DIR__ . '/../../backend/models/AnnouncementModel.php';
$annModel = new AnnouncementModel();
$annModel->archiveExpired();
$featured = $annModel->getFeatured();
$annList  = $annModel->getActive();
?>

<?php include __DIR__ . '/../../components/public/navbar.php'; ?>

<main class="announcements-page">

    <!-- HEADER SECTION -->
    <section class="announcements-header">
        <div class="header-text">
            <h1>Community Announcements</h1>
            <p>Stay updated with official announcements from the SK of Barangay Sauyo.</p>
        </div>

        <div class="header-controls">
            <input type="text" id="pub-search" placeholder="Search announcements..." class="search-input">

            <select id="pub-category" class="filter-category">
                <option value="all">All Categories</option>
                <option value="program">Programs</option>
                <option value="event">Events</option>
                <option value="urgent">Urgent</option>
                <option value="meeting">Meetings</option>
                <option value="notice">Public Notices</option>
            </select>

            <select id="pub-sort" class="sort-order">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
            </select>
        </div>
    </section>

    <!-- FEATURED ANNOUNCEMENT -->
    <?php if ($featured): ?>
    <section class="featured-section">
        <article class="featured-announcement">
            <div class="featured-badges">
                <span class="badge featured-badge">FEATURED</span>
                <span class="badge <?= htmlspecialchars($featured['category']) ?>">
                    <?= ucfirst(htmlspecialchars($featured['category'])) ?>
                </span>
            </div>

            <h2><?= htmlspecialchars($featured['title']) ?></h2>

            <!-- Featured Excerpt -->
            <p><?= htmlspecialchars(mb_substr(strip_tags($featured['content']), 0, 150)) ?>…</p>

            <div class="meta">
                <span>Posted by: <?= htmlspecialchars($featured['author_name']) ?></span>
                <time datetime="<?= date('Y-m-d', strtotime($featured['published_at'])) ?>">
                    <?= date('F j, Y', strtotime($featured['published_at'])) ?>
                </time>
            </div>

            <a href="announcement_view.php?id=<?= (int)$featured['id'] ?>" class="btn-primary">View Full Details</a>
        </article>
    </section>
    <?php endif; ?>

    <!-- ANNOUNCEMENTS GRID -->
    <section class="announcements-list">
        <div class="announcements-grid" id="pub-grid">

            <?php if (empty($annList)): ?>
            <p class="no-results-msg" style="grid-column:1/-1;padding:2rem;text-align:center;color:#64748b;">
                No announcements available at this time.
            </p>
            <?php else: ?>

            <?php foreach ($annList as $ann): ?>
            <article class="announcement-card"
                     data-category="<?= htmlspecialchars($ann['category']) ?>"
                     data-title="<?= htmlspecialchars(strtolower($ann['title'])) ?>"
                     data-date="<?= htmlspecialchars($ann['published_at']) ?>">

                <div class="card-image">
                    <?php if ($ann['banner_img']): ?>
                        <img src="<?= htmlspecialchars($ann['banner_img']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">
                    <?php else: ?>
                        <div class="card-image-placeholder"></div>
                    <?php endif; ?>
                </div>

                <div class="card-content">
                    <div class="card-badges">
                        <span class="badge <?= htmlspecialchars($ann['category']) ?>">
                            <?= ucfirst(htmlspecialchars($ann['category'])) ?>
                        </span>
                        <?php if ($ann['featured']): ?>
                        <span class="badge featured-badge">Featured</span>
                        <?php endif; ?>
                    </div>

                    <h3><?= htmlspecialchars($ann['title']) ?></h3>

                    <p class="excerpt">
                        <?= htmlspecialchars(mb_substr(strip_tags($ann['content']), 0, 150)) ?>…
                    </p>

                    <div class="card-meta">
                        <span>By: <?= htmlspecialchars($ann['author_name']) ?></span>
                        <time datetime="<?= date('Y-m-d', strtotime($ann['published_at'])) ?>">
                            <?= date('M j, Y', strtotime($ann['published_at'])) ?>
                        </time>
                    </div>

                    <div class="card-actions">
                        <a href="announcement_view.php?id=<?= (int)$ann['id'] ?>" class="btn-secondary">Read More</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- No results message -->
        <div id="pub-no-results" style="display:none; text-align:center; padding:2rem; color:#64748b;">
            No announcements match your search.
        </div>
    </section>

    <!-- PAGINATION -->
    <section class="pagination">
        <button class="page-btn" id="pub-prev" disabled>&#8249; Previous</button>
        <div id="pub-page-numbers" style="display:flex;gap:.5rem;align-items:center;"></div>
        <button class="page-btn" id="pub-next">Next &#8250;</button>
    </section>

</main>

<?php include __DIR__ . '/../../components/public/footer.php'; ?>

<script src="../../scripts/public/announcements.js"></script>
</body>
</html>