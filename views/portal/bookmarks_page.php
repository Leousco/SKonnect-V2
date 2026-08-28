<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/ThreadBookmarkModel.php';

$db      = new Database();
$conn    = $db->getConnection();
$user_id = $_SESSION['user_id'] ?? null;

$bookmarkModel = new BookmarkModel($conn);
$threads       = $bookmarkModel->getBookmarkedThreads((int)$user_id);

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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | My Bookmarks</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/feed_page.css">
    <link rel="stylesheet" href="../../styles/portal/bookmarks_page.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php
            $pageTitle      = 'My Bookmarks';
            $pageBreadcrumb = [['Home', '#'], ['Community Feed', 'feed_page.php'], ['My Bookmarks', null]];
            $userName       = $_SESSION['user_name'] ?? 'Guest';
            $userRole       = 'Resident';
            $notifCount     = 3;
            include __DIR__ . '/../../components/portal/topbar.php';
            ?>

            <!-- CONTROLS -->
            <section class="announcements-controls bookmarks-controls">
                <div class="controls-left">
                    <a href="feed_page.php" class="btn-back-portal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Back to Feed
                    </a>
                    <div class="search-wrap">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="bm-search" placeholder="Search bookmarks…" class="ann-search-input">
                    </div>
                </div>
                <div class="controls-right">
                    <select id="bm-category" class="ann-select">
                        <option value="all">All Categories</option>
                        <option value="inquiry">Inquiry</option>
                        <option value="complaint">Complaint</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="event_question">Event Question</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="bm-status" class="ann-select">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="responded">Responded</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </section>

            <!-- BOOKMARKS GRID -->
            <section class="announcements-section">
                <h2 class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#facc15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bmp-icon">
                        <path d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg> My Bookmarked Threads
                    <span class="bookmarks-total-badge"><?= count($threads) ?></span>
                </h2>

                <?php if (empty($threads)) : ?>
                    <div class="bookmarks-empty">
                        <div class="bookmarks-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="#facc15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bmi-icon">
                                <path d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                        </div>
                        <h3>No bookmarks yet</h3>
                        <p>Threads you bookmark on the Community Feed will appear here.</p>
                        <a href="feed_page.php" class="btn-primary-portal">Browse Community Feed</a>
                    </div>
                <?php else : ?>

                    <div class="announcements-grid" id="bm-grid">
                        <?php foreach ($threads as $t) :
                            $cat_key        = $t['category'];
                            $cat_label      = $cat_labels[$cat_key] ?? 'Other';
                            $user_supported = (bool)$t['user_supported'];
                            $date_fmt       = date('M j, Y', strtotime($t['created_at']));
                            $bm_date_fmt    = date('M j, Y', strtotime($t['bookmarked_at']));
                        ?>
                            <article class="ann-card feed-card bm-card" onclick="if(!event.target.closest('button, a')){ window.location.href='thread_view.php?id=<?= (int)$t['id'] ?>'; }" style="cursor: pointer;" data-category="<?= htmlspecialchars($cat_key) ?>" data-status="<?= htmlspecialchars($t['status']) ?>">
                                <div class="ann-card-body">

                                    <!-- BADGES -->
                                    <div class="feed-card-badges">
                                        <span class="ann-badge category-<?= $cat_key ?>"><?= $cat_label ?></span>
                                        <span class="feed-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                    </div>

                                    <h3 class="ann-card-title"><?= htmlspecialchars($t['subject']) ?></h3>
                                    <p class="ann-card-excerpt"><?= htmlspecialchars(mb_substr($t['message'], 0, 160)) ?><?= mb_strlen($t['message']) > 160 ? '…' : '' ?></p>

                                    <div class="ann-card-meta">
                                        <span>By: <?= htmlspecialchars($t['author_name']) ?></span>
                                        <time datetime="<?= $t['created_at'] ?>"><?= $date_fmt ?></time>
                                        <span class="bm-saved-on">Saved on <?= $bm_date_fmt ?></span>
                                    </div>

                                    <div class="ann-card-actions">
                                        <!-- LEFT: SUPPORT BUTTON (icon + count only) -->
                                        <button class="support-btn <?= $user_supported ? 'active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $user_supported ? 'Remove support' : 'I support this' ?>">
                                            <img src="../../assets/img/handshake-icon.png" alt="Support" class="support-icon">
                                            <span class="support-count"><?= (int)$t['support_count'] ?></span>
                                        </button>

                                        <!-- RIGHT: COMMENT + BOOKMARK -->
                                        <div class="card-actions-right">
                                            <a href="thread_view.php?id=<?= (int)$t['id'] ?>" class="btn-secondary-portal">
                                                💬 <?= (int)$t['comment_count'] ?> <?= $t['comment_count'] == 1 ? 'Comment' : 'Comments' ?>
                                            </a>
                                            <button class="bookmark-btn active" data-thread-id="<?= (int)$t['id'] ?>" title="Remove bookmark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="bm-icon">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="no-results" id="bm-no-results" style="display:none;">
                        <p>No bookmarks match your search.</p>
                    </div>

                <?php endif; ?>

            </section>

        </main>
    </div>

    <!-- TOAST -->
    <div id="feed-toast" class="feed-toast" aria-live="polite"></div>

    <script>
        const FEED_USER_ID = <?= (int)$user_id ?>;
    </script>
    <script src="../../scripts/portal/bookmarks_page.js"></script>

</body>

</html>