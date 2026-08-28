<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/ThreadModel.php';

$db   = new Database();
$conn = $db->getConnection();

// Public viewer has no user_id — pass 0 so no bookmark/support state is loaded
$threadModel = new ThreadModel($conn);
$threads     = $threadModel->getFeedThreads(0);

$cat_labels = [
    'inquiry'        => 'Inquiry',
    'complaint'      => 'Complaint',
    'suggestion'     => 'Suggestion',
    'event_question' => 'Event',
    'other'          => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Community Feed</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/public/community.css">
    <link rel="stylesheet" href="../../styles/public/header.css">
    <link rel="stylesheet" href="../../styles/public/footer.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/public/navbar.php'; ?>

    <main class="community-feed-page">

        <section class="feed-header">
            <h1>Community Concerns &amp; Inquiries</h1>
            <p>View and track concerns from all members of the SK community.</p>
            <button class="new-thread-btn" onclick="window.location.href='../auth/login.php'">
                Post a Thread
            </button>
        </section>

        <!-- CONTROLS -->
        <section class="community-controls">
            <div class="community-search-wrap">
                <span class="community-search-icon">🔍</span>
                <input type="text" id="pub-search" placeholder="Search threads…" class="community-search-input">
            </div>
            <div class="community-filters">
                <select id="pub-category" class="community-select">
                    <option value="all">All Categories</option>
                    <option value="inquiry">Inquiry</option>
                    <option value="complaint">Complaint</option>
                    <option value="suggestion">Suggestion</option>
                    <option value="event_question">Event Question</option>
                    <option value="other">Other</option>
                </select>
                <select id="pub-status" class="community-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="responded">Responded</option>
                    <option value="resolved">Resolved</option>
                </select>
                <select id="pub-sort" class="community-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="comments">Most Comments</option>
                    <option value="supports">Most Supported</option>
                </select>
            </div>
        </section>

        <!-- COMMUNITY FEED -->
        <section class="community-feed" id="pub-feed-grid">

            <?php if (empty($threads)) : ?>
                <div class="pub-no-results" id="pub-no-results" style="display:block;">
                    <p>No threads yet. Be the first to post one!</p>
                </div>
            <?php else : ?>
                <?php foreach ($threads as $t) :
                    $cat_key   = $t['category'];
                    $cat_label = $cat_labels[$cat_key] ?? 'Other';
                    $date_fmt  = date('M j, Y', strtotime($t['created_at']));
                ?>
                    <article class="thread-card pub-feed-card" data-category="<?= htmlspecialchars($cat_key) ?>" data-status="<?= htmlspecialchars($t['status']) ?>" data-date="<?= $t['created_at'] ?>" data-comments="<?= (int)$t['comment_count'] ?>" data-supports="<?= (int)$t['support_count'] ?>" data-pinned="<?= !empty($t['is_pinned']) ? '1' : '0' ?>" onclick="window.location.href='public_thread_view.php?id=<?= (int)$t['id'] ?>'" style="cursor:pointer;">

                        <div class="thread-header">
                            <?php if (!empty($t['is_pinned'])) : ?>
                                <span class="pub-pin-badge">📌 Pinned</span>
                            <?php endif; ?>
                            <span class="category-badge <?= $cat_key ?>"><?= $cat_label ?></span>
                            <span class="status-badge <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                        </div>

                        <h3 class="thread-title"><?= htmlspecialchars($t['subject']) ?></h3>
                        <p class="thread-snippet"><?= htmlspecialchars(mb_substr($t['message'], 0, 160)) ?><?= mb_strlen($t['message']) > 160 ? '…' : '' ?></p>

                        <div class="thread-meta">
                            <span>By: <?= htmlspecialchars($t['author_name']) ?></span>
                            <time datetime="<?= $t['created_at'] ?>"><?= $date_fmt ?></time>
                            <span><img src="../../assets/img/handshake-icon.png" alt="Support" class="support-icon"> <?= (int)$t['support_count'] ?></span>
                            <span>💬 <?= (int)$t['comment_count'] ?> <?= $t['comment_count'] == 1 ? 'comment' : 'comments' ?></span>
                        </div>

                        <button class="view-thread-btn" onclick="event.stopPropagation(); window.location.href='public_thread_view.php?id=<?= (int)$t['id'] ?>'">
                            View &amp; Read
                        </button>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>

        <div class="pub-no-results" id="pub-no-results-filter" style="display:none;">
            <p>No threads found matching your search.</p>
        </div>

        <!-- PAGINATION -->
        <div class="pagination-wrapper">
            <section class="pagination">
                <button class="page-btn" id="pub-prev-btn" disabled>&#8249; Previous</button>
                <div id="pub-page-numbers"></div>
                <button class="page-btn" id="pub-next-btn">Next &#8250;</button>
            </section>
        </div>

    </main>

    <?php include __DIR__ . '/../../components/public/footer.php'; ?>

    <script src="../../scripts/public/community.js"></script>

</body>

</html>