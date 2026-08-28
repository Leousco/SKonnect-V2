<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/ThreadModel.php';
require_once __DIR__ . '/../../backend/models/CommentModel.php';

$db      = new Database();
$conn    = $db->getConnection();
$user_id = $_SESSION['user_id'] ?? null;

$threadModel  = new ThreadModel($conn);
$commentModel = new CommentModel($conn);

$thread_id = (int)($_GET['id'] ?? 0);
if (!$thread_id) {
    header('Location: feed_page.php');
    exit;
}

// ── Sanction check ────────────────────────────────────────────
require_once __DIR__ . '/../../backend/models/SanctionModel.php';
$sanctionModel  = new SanctionModel($conn);
$sanction_level = $sanctionModel->getActiveLevel((int)$user_id);
$is_banned      = $sanction_level >= 2;
$sanction_meta  = ['reason' => null, 'expires_at' => null, 'issued_at' => null];
if ($is_banned) {
    $s_stmt = $conn->prepare(
        "SELECT reason, expires_at, created_at AS issued_at
         FROM user_sanctions
         WHERE user_id = :uid AND is_active = 1 AND level = :lvl
         ORDER BY created_at DESC LIMIT 1"
    );
    $s_stmt->execute([':uid' => $user_id, ':lvl' => $sanction_level]);
    $s_row = $s_stmt->fetch(PDO::FETCH_ASSOC);
    if ($s_row) $sanction_meta = $s_row;
}
// ─────────────────────────────────────────────────────────────

$thread = $threadModel->getThreadById($thread_id, (int)$user_id);
if (!$thread) {
    header('Location: feed_page.php');
    exit;
}

$images   = $threadModel->getThreadImages($thread_id);
$comments = $commentModel->getCommentsByThread($thread_id, (int)$user_id);

// Mod / SK Official comments always appear first; preserve original order within each group
usort($comments, function ($a, $b) {
    $a_mod = (int)!empty($a['is_mod_comment']);
    $b_mod = (int)!empty($b['is_mod_comment']);
    if ($b_mod !== $a_mod) return $b_mod - $a_mod;   // mod comments first
    return strtotime($b['created_at']) - strtotime($a['created_at']); // then newest first
});

// --- Helpers ---
$cat_labels = [
    'inquiry'        => 'Inquiry',
    'complaint'      => 'Complaint',
    'suggestion'     => 'Suggestion',
    'event_question' => 'Event',
    'other'          => 'Other',
];
$cat_key   = $thread['category'];
$cat_label = $cat_labels[$cat_key] ?? 'Other';
$date_fmt  = date('F j, Y · g:i A', strtotime($thread['created_at']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | <?= htmlspecialchars($thread['subject']) ?></title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/feed_page.css">
    <link rel="stylesheet" href="../../styles/portal/thread_view.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php
            $pageTitle      = 'Thread';
            $pageBreadcrumb = [['Home', '#'], ['Community Feed', 'feed_page.php'], [$thread['subject'], null]];
            $userName       = $_SESSION['user_name'] ?? 'Guest';
            $userRole       = 'Resident';
            $notifCount     = 3;
            include __DIR__ . '/../../components/portal/topbar.php';
            ?>

            <!-- THREAD CONTAINER -->
            <div class="thread-container">

                <!-- BACK LINK -->
                <a href="feed_page.php" class="thread-back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Back to Community Feed
                </a>

                <!-- THREAD CARD -->
                <article class="thread-main-card">

                    <!-- BADGES: category + status only (priority removed) -->
                    <div class="feed-card-badges">
                        <span class="ann-badge category-<?= $cat_key ?>"><?= $cat_label ?></span>
                        <span class="feed-badge status-<?= $thread['status'] ?>"><?= ucfirst($thread['status']) ?></span>
                    </div>

                    <!-- TITLE -->
                    <h1 class="thread-title"><?= htmlspecialchars($thread['subject']) ?></h1>

                    <!-- META -->
                    <div class="thread-meta">
                        <div class="thread-author-avatar">
                            <?= strtoupper(substr($thread['author_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="thread-author-name"><?= htmlspecialchars($thread['author_name']) ?></span>
                            <time class="thread-date" datetime="<?= $thread['created_at'] ?>"><?= $date_fmt ?></time>
                        </div>
                        <div class="thread-meta-counts">
                            <span>💬 <?= (int)$thread['comment_count'] ?> <?= $thread['comment_count'] == 1 ? 'comment' : 'comments' ?></span>
                        </div>
                    </div>

                    <!-- BODY -->
                    <div class="thread-body">
                        <?= nl2br(htmlspecialchars($thread['message'])) ?>
                    </div>

                    <!-- IMAGES (only shown on thread view, not on cards) -->
                    <?php if (!empty($images)) : ?>
                        <div class="thread-images-grid">
                            <?php foreach ($images as $img) : ?>
                                <div class="thread-image-item" data-src="../../<?= htmlspecialchars($img['file_path']) ?>">
                                    <img src="../../<?= htmlspecialchars($img['file_path']) ?>" alt="<?= htmlspecialchars($img['file_name']) ?>" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ACTIONS -->
                    <div class="thread-actions">
                        <button class="support-btn <?= $thread['user_supported'] ? 'active' : '' ?>" id="thread-support-btn" data-thread-id="<?= $thread_id ?>" title="<?= $thread['user_supported'] ? 'Remove support' : 'I support this' ?>">
                            <img src="../../assets/img/handshake-icon.png" alt="Support" class="support-icon">
                            <span id="thread-support-count"><?= (int)$thread['support_count'] ?></span>
                            <span><?= $thread['user_supported'] ? 'Supported' : 'Support' ?></span>
                        </button>

                        <button class="thread-bookmark-btn <?= $thread['is_bookmarked'] ? 'active' : '' ?>" id="thread-bookmark-btn" data-thread-id="<?= $thread_id ?>" title="<?= $thread['is_bookmarked'] ? 'Remove bookmark' : 'Bookmark this thread' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="bm-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span class="bm-label"><?= $thread['is_bookmarked'] ? 'Bookmarked' : 'Bookmark' ?></span>
                        </button>

                        <!-- THREAD REPORT BUTTON (only for others' threads) -->
                        <?php if ((int)$user_id !== (int)$thread['author_id']) : ?>
                            <button class="thread-report-btn" id="thread-report-btn" data-report-type="thread" data-target-id="<?= $thread_id ?>" title="Report this thread">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18m0-13.5h13.5a1.5 1.5 0 0 1 0 3H3" />
                                </svg>
                                Report
                            </button>
                        <?php endif; ?>

                        <!-- THREAD DELETE BUTTON (only for own threads) -->
                        <?php if ((int)$user_id === (int)$thread['author_id']) : ?>
                            <button class="thread-delete-btn" id="thread-delete-btn" data-thread-id="<?= $thread_id ?>" title="Delete this thread">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>

                </article>

                <!-- COMMENTS SECTION -->
                <section class="comments-section">
                    <h2 class="comments-heading">
                        Comments
                        <span class="comments-count" id="comments-count"><?= count($comments) ?></span>
                    </h2>

                    <!-- MAIN REPLY BOX -->
                    <?php if (!$is_banned) : ?>
                        <div class="reply-box">
                            <div class="reply-avatar">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="reply-input-wrap">
                                <textarea id="reply-textarea" class="concern-textarea reply-textarea" rows="3" placeholder="Write a comment…"></textarea>
                                <div class="reply-footer">
                                    <span class="reply-hint">Contribute to a helpful and inclusive conversation.</span>
                                    <button class="btn-primary-portal reply-submit-btn" id="reply-submit-btn" type="button">
                                        <span id="reply-label">Post Comment</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="ban-comment-notice">
                            <?php if ($sanction_level === 3) : ?>
                                🚫 <strong>Commenting is disabled.</strong> Your account has a permanent ban from the community feed.
                            <?php else : ?>
                                ⏳ <strong>Commenting is temporarily disabled.</strong> Your 7-day posting ban expires on <?= $sanction_meta['expires_at'] ? date('F j, Y 	 g:i A', strtotime($sanction_meta['expires_at'])) : '—' ?>.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- COMMENT LIST -->
                    <div class="comment-list" id="comment-list">

                        <?php if (empty($comments)) : ?>
                            <div class="no-comments" id="no-comments">
                                No comments yet. Be the first to reply!
                            </div>
                        <?php else : ?>

                            <?php foreach ($comments as $c) :
                                $c_date   = date('M j, Y · g:i A', strtotime($c['created_at']));
                                $initials = strtoupper(substr($c['author_name'], 0, 1));
                                $is_own_comment = ((int)$user_id === (int)$c['author_id']);
                            ?>
                                <div class="comment-item <?= !empty($c['is_mod_comment']) ? 'comment-item--mod' : '' ?> <?= !empty($c['removed_by_mod']) ? 'comment-item--removed' : '' ?>" id="comment-<?= (int)$c['id'] ?>">

                                    <?php if (!empty($c['removed_by_mod'])) : ?>
                                        <div class="comment-tombstone">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <span>This comment has been removed by a Moderator.</span>
                                        </div>
                                    <?php elseif (!empty($c['removed_by_user'])) : ?>
                                        <div class="comment-tombstone comment-tombstone--self">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z" />
                                            </svg>
                                            <span>This comment has been removed by the author.</span>
                                        </div>
                                    <?php else : ?>
                                        <div class="comment-avatar"><?= $initials ?></div>
                                        <div class="comment-body">
                                            <div class="comment-header">
                                                <span class="comment-author"><?= htmlspecialchars($c['author_name']) ?></span>
                                                <?php if (!empty($c['is_mod_comment'])) : ?>
                                                    <span class="mod-reply-badge">SK Official</span>
                                                <?php endif; ?>
                                                <time class="comment-date" datetime="<?= $c['created_at'] ?>"><?= $c_date ?></time>
                                            </div>
                                            <div class="comment-text"><?= nl2br(htmlspecialchars($c['message'])) ?></div>
                                            <div class="comment-actions">
                                                <button class="comment-support-btn <?= $c['user_supported'] ? 'active' : '' ?>" data-comment-id="<?= (int)$c['id'] ?>" title="Support this comment">
                                                    <img src="../../assets/img/handshake-icon.png" alt="Support" class="comment-support-icon"> <span class="comment-support-count"><?= (int)$c['support_count'] ?></span>
                                                </button>
                                                <button class="reply-toggle-btn" data-comment-id="<?= (int)$c['id'] ?>" title="Reply to this comment">
                                                    💬 Reply
                                                </button>
                                                <?php if (!$is_own_comment) : ?>
                                                    <button class="content-report-btn" data-report-type="comment" data-target-id="<?= (int)$c['id'] ?>" title="Report this comment">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18m0-13.5h13.5a1.5 1.5 0 0 1 0 3H3" />
                                                        </svg>
                                                        Report
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($is_own_comment) : ?>
                                                    <button class="content-delete-btn" data-delete-type="comment" data-target-id="<?= (int)$c['id'] ?>" title="Delete your comment">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- REPLIES -->
                                            <?php if (!empty($c['replies'])) : ?>
                                                <div class="reply-list" id="reply-list-<?= (int)$c['id'] ?>">
                                                    <?php foreach ($c['replies'] as $r) :
                                                        $r_date     = date('M j, Y · g:i A', strtotime($r['created_at']));
                                                        $r_initials = strtoupper(substr($r['author_name'], 0, 1));
                                                        $is_own_reply = ((int)$user_id === (int)$r['author_id']);
                                                    ?>
                                                        <div class="reply-item <?= !empty($r['is_mod_comment']) ? 'reply-item--mod' : '' ?> <?= !empty($r['removed_by_mod']) ? 'reply-item--removed' : '' ?>" id="reply-<?= (int)$r['id'] ?>">
                                                            <?php if (!empty($r['removed_by_mod'])) : ?>
                                                                <div class="comment-tombstone comment-tombstone--reply">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                                    </svg>
                                                                    <span>This reply has been removed by a Moderator.</span>
                                                                </div>
                                                            <?php elseif (!empty($r['removed_by_user'])) : ?>
                                                                <div class="comment-tombstone comment-tombstone--reply comment-tombstone--self">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z" />
                                                                    </svg>
                                                                    <span>This reply has been removed by the author.</span>
                                                                </div>
                                                            <?php else : ?>
                                                                <div class="reply-avatar"><?= $r_initials ?></div>
                                                                <div class="reply-body">
                                                                    <div class="comment-header">
                                                                        <span class="comment-author"><?= htmlspecialchars($r['author_name']) ?></span>
                                                                        <?php if (!empty($r['is_mod_comment'])) : ?>
                                                                            <span class="mod-reply-badge">SK Official</span>
                                                                        <?php endif; ?>
                                                                        <time class="comment-date" datetime="<?= $r['created_at'] ?>"><?= $r_date ?></time>
                                                                    </div>
                                                                    <div class="comment-text"><?= nl2br(htmlspecialchars($r['message'])) ?></div>
                                                                    <?php if (!$is_own_reply) : ?>
                                                                        <div class="comment-actions reply-actions">
                                                                            <button class="content-report-btn" data-report-type="reply" data-target-id="<?= (int)$r['id'] ?>" title="Report this reply">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18m0-13.5h13.5a1.5 1.5 0 0 1 0 3H3" />
                                                                                </svg>
                                                                                Report
                                                                            </button>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_own_reply) : ?>
                                                                        <div class="comment-actions reply-actions">
                                                                            <button class="content-delete-btn" data-delete-type="reply" data-target-id="<?= (int)$r['id'] ?>" title="Delete your reply">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                                </svg>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="reply-list" id="reply-list-<?= (int)$c['id'] ?>"></div>
                                            <?php endif; ?>

                                            <!-- INLINE REPLY BOX (hidden by default) -->
                                            <div class="inline-reply-box" id="reply-box-<?= (int)$c['id'] ?>" style="display:none;">
                                                <textarea class="concern-textarea reply-textarea inline-reply-textarea" rows="2" placeholder="Write a reply…" data-comment-id="<?= (int)$c['id'] ?>"></textarea>
                                                <div class="inline-reply-footer">
                                                    <button class="btn-cancel-reply" data-comment-id="<?= (int)$c['id'] ?>">Cancel</button>
                                                    <button class="btn-submit-reply btn-primary-portal" data-comment-id="<?= (int)$c['id'] ?>">
                                                        <span class="reply-submit-label">Post Reply</span>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>

                </section>

            </div><!-- /.thread-container -->

        </main>
    </div>

    <!-- LIGHTBOX -->
    <div class="lightbox-overlay" id="lightbox-overlay" style="display:none;">
        <button class="lightbox-close" id="lightbox-close">&times;</button>
        <img class="lightbox-img" id="lightbox-img" src="" alt="Image preview">
    </div>

    <!-- DELETE CONFIRM MODAL -->
    <div class="delete-modal-overlay" id="delete-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="delete-modal">
            <div class="delete-modal-icon">🗑️</div>
            <h3 class="delete-modal-title" id="delete-modal-title">Delete this?</h3>
            <p class="delete-modal-desc" id="delete-modal-desc">This action cannot be undone. Your content will be permanently hidden.</p>
            <div class="delete-modal-footer">
                <button class="btn-cancel-reply" id="delete-modal-cancel">Cancel</button>
                <button class="btn-danger-delete" id="delete-modal-confirm">
                    <span id="delete-confirm-label">Yes, Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- REPORT MODAL -->
    <div class="report-modal-overlay" id="report-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
        <div class="report-modal">
            <div class="report-modal-header">
                <h3 class="report-modal-title" id="report-modal-title">Report Content</h3>
                <button class="report-modal-close" id="report-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="report-modal-body">
                <p class="report-modal-desc">Help us understand what's wrong with this content. Select a reason below.</p>

                <!-- CATEGORY CHECKBOXES -->
                <div class="report-categories">
                    <label class="report-category-option">
                        <input type="radio" name="report-category" value="inappropriate">
                        <span class="report-category-label">
                            <span class="report-category-name">Inappropriate</span>
                            <span class="report-category-desc">Offensive, explicit, or violates community standards</span>
                        </span>
                    </label>
                    <label class="report-category-option">
                        <input type="radio" name="report-category" value="spam">
                        <span class="report-category-label">
                            <span class="report-category-name">Spam</span>
                            <span class="report-category-desc">Repetitive, promotional, or irrelevant content</span>
                        </span>
                    </label>
                    <label class="report-category-option">
                        <input type="radio" name="report-category" value="misinformation">
                        <span class="report-category-label">
                            <span class="report-category-name">Misinformation</span>
                            <span class="report-category-desc">False or misleading information</span>
                        </span>
                    </label>
                    <label class="report-category-option">
                        <input type="radio" name="report-category" value="harassment">
                        <span class="report-category-label">
                            <span class="report-category-name">Harassment</span>
                            <span class="report-category-desc">Bullying, threats, or targeted attacks</span>
                        </span>
                    </label>
                </div>
                <p class="report-category-error" id="report-category-error"></p>

                <!-- OPTIONAL NOTE -->
                <div class="report-note-wrap">
                    <label class="report-note-label" for="report-note">Additional details <span class="report-note-optional">(optional)</span></label>
                    <textarea id="report-note" class="concern-textarea report-note-textarea" rows="3" placeholder="Provide any extra context that may help the moderator…" maxlength="500"></textarea>
                </div>
            </div>
            <div class="report-modal-footer">
                <button class="btn-cancel-reply" id="report-modal-cancel">Cancel</button>
                <button class="btn-danger-report" id="report-modal-submit">
                    <span id="report-submit-label">Submit Report</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="feed-toast" class="feed-toast" aria-live="polite"></div>

    <!-- BAN NOTICE MODAL -->
    <?php if ($is_banned) :
        $ban_level_label = $sanction_level === 3 ? 'Permanent Ban' : '7-Day Posting Ban';
        $ban_icon        = $sanction_level === 3 ? '🚫' : '⏳';
        $ban_color_class = $sanction_level === 3 ? 'ban-modal--permanent' : 'ban-modal--temporary';
        $issued_fmt  = $sanction_meta['issued_at']  ? date('F j, Y', strtotime($sanction_meta['issued_at'])) : '—';
        $expires_fmt = $sanction_meta['expires_at'] ? date('F j, Y \at g:i A', strtotime($sanction_meta['expires_at'])) : ($sanction_level === 3 ? 'Never (permanent)' : '—');
        $display_reason = (!empty($sanction_meta['reason']) && $sanction_meta['reason'] !== '(No additional reason provided)') ? htmlspecialchars($sanction_meta['reason']) : null;
    ?>
        <div class="ban-modal-overlay" id="ban-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="ban-modal-title">
            <div class="ban-modal <?= $ban_color_class ?>">
                <div class="ban-modal-icon"><?= $ban_icon ?></div>
                <h2 class="ban-modal-title" id="ban-modal-title">Your account has a <?= $ban_level_label ?></h2>
                <p class="ban-modal-desc">
                    <?php if ($sanction_level === 3) : ?>
                        You have been permanently restricted from posting, commenting, or interacting with the community feed. You may still browse and read all threads.
                    <?php else : ?>
                        You have a temporary 7-day posting ban. You may still browse and read all threads, but cannot post, comment, or interact until the ban expires.
                    <?php endif; ?>
                </p>
                <div class="ban-modal-details">
                    <div class="ban-detail-row">
                        <span class="ban-detail-label">Sanction Level</span>
                        <span class="ban-detail-value">Level <?= $sanction_level ?> : <?= $ban_level_label ?></span>
                    </div>
                    <div class="ban-detail-row">
                        <span class="ban-detail-label">Issued On</span>
                        <span class="ban-detail-value"><?= $issued_fmt ?></span>
                    </div>
                    <div class="ban-detail-row">
                        <span class="ban-detail-label"><?= $sanction_level === 3 ? 'Lifted On' : 'Expires On' ?></span>
                        <span class="ban-detail-value <?= $sanction_level === 3 ? 'ban-detail-permanent' : 'ban-detail-expiry' ?>"><?= $expires_fmt ?></span>
                    </div>
                    <?php if ($display_reason) : ?>
                        <div class="ban-detail-row">
                            <span class="ban-detail-label">Reason</span>
                            <span class="ban-detail-value"><?= $display_reason ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="ban-modal-note">
                    <?php if ($sanction_level === 2) : ?>
                        ⏱ If you believe this is a mistake, please contact an SK administrator.
                    <?php else : ?>
                        If you believe this ban was issued in error, please contact an SK administrator.
                    <?php endif; ?>
                </p>
                <button class="ban-modal-dismiss" id="ban-modal-dismiss">I Understand — Continue Browsing</button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const THREAD_ID = <?= (int)$thread_id ?>;
        const FEED_USER_ID = <?= (int)$user_id ?>;
        const USER_BAN_LEVEL = <?= (int)$sanction_level ?>;
        const USER_IS_BANNED = <?= $is_banned ? 'true' : 'false' ?>;
    </script>
    <script src="../../scripts/portal/thread_view.js"></script>

</body>

</html>