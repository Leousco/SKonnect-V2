<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/ThreadModel.php';
require_once __DIR__ . '/../../backend/models/CommentModel.php';

$db   = new Database();
$conn = $db->getConnection();

$threadModel  = new ThreadModel($conn);
$commentModel = new CommentModel($conn);

$thread_id = (int)($_GET['id'] ?? 0);
if (!$thread_id) {
    header('Location: community.php');
    exit;
}

// Pass user_id = 0 (no login state needed)
$thread = $threadModel->getThreadById($thread_id, 0);
if (!$thread) {
    header('Location: community.php');
    exit;
}

$images   = $threadModel->getThreadImages($thread_id);
$comments = $commentModel->getCommentsByThread($thread_id, 0);

// Mod / SK Official comments always appear first; within each group, newest first
usort($comments, function ($a, $b) {
    $a_mod = (int)!empty($a['is_mod_comment']);
    $b_mod = (int)!empty($b['is_mod_comment']);
    if ($b_mod !== $a_mod) return $b_mod - $a_mod;
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | <?= htmlspecialchars($thread['subject']) ?></title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/public/community.css">
    <link rel="stylesheet" href="../../styles/public/public_thread_view.css">
    <link rel="stylesheet" href="../../styles/public/header.css">
    <link rel="stylesheet" href="../../styles/public/footer.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/public/navbar.php'; ?>

    <main class="pub-thread-page">
        <div class="pub-thread-container">

            <!-- BACK LINK -->
            <a href="community.php" class="pub-thread-back-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Community Feed
            </a>

            <!-- THREAD CARD -->
            <article class="pub-thread-main-card">

                <!-- BADGES -->
                <div class="pub-thread-badges">
                    <?php if (!empty($thread['is_pinned'])) : ?>
                        <span class="pub-pin-badge">📌 Pinned</span>
                    <?php endif; ?>
                    <span class="category-badge <?= $cat_key ?>"><?= $cat_label ?></span>
                    <span class="status-badge <?= $thread['status'] ?>"><?= ucfirst($thread['status']) ?></span>
                </div>

                <!-- TITLE -->
                <h1 class="pub-thread-title"><?= htmlspecialchars($thread['subject']) ?></h1>

                <!-- META -->
                <div class="pub-thread-meta">
                    <div class="pub-thread-avatar">
                        <?= strtoupper(substr($thread['author_name'], 0, 1)) ?>
                    </div>
                    <div class="pub-thread-author-info">
                        <span class="pub-thread-author-name"><?= htmlspecialchars($thread['author_name']) ?></span>
                        <time class="pub-thread-date" datetime="<?= $thread['created_at'] ?>"><?= $date_fmt ?></time>
                    </div>
                    <div class="pub-thread-meta-counts">
                        <span>
                            <img src="../../assets/img/handshake-icon.png" alt="Support" class="pub-meta-icon">
                            <?= (int)$thread['support_count'] ?> <?= $thread['support_count'] == 1 ? 'support' : 'supports' ?>
                        </span>
                        <span>💬 <?= (int)$thread['comment_count'] ?> <?= $thread['comment_count'] == 1 ? 'comment' : 'comments' ?></span>
                    </div>
                </div>

                <!-- BODY -->
                <div class="pub-thread-body">
                    <?= nl2br(htmlspecialchars($thread['message'])) ?>
                </div>

                <!--
                    IMAGES — hidden data grid.
                    JS (community.js → initCarousel) reads these items and
                    builds the carousel identically to thread_view.js.
                -->
                <?php if (!empty($images)) : ?>
                    <div class="thread-images-grid">
                        <?php foreach ($images as $img) : ?>
                            <div class="thread-image-item" data-src="../../<?= htmlspecialchars($img['file_path']) ?>">
                                <img src="../../<?= htmlspecialchars($img['file_path']) ?>" alt="<?= htmlspecialchars($img['file_name']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- READ-ONLY NOTICE -->
                <div class="pub-readonly-notice">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <span>
                        You're viewing this as a guest.
                        <a href="../auth/login.php">Sign in</a> or <a href="../auth/register.php">create an account</a> to support, comment, and participate.
                    </span>
                </div>

            </article>

            <!-- COMMENTS SECTION -->
            <section class="pub-comments-section">
                <h2 class="pub-comments-heading">
                    Comments
                    <span class="pub-comments-count"><?= count($comments) ?></span>
                </h2>

                <!-- COMMENT LIST -->
                <div class="pub-comment-list">

                    <?php if (empty($comments)) : ?>
                        <div class="pub-no-comments">
                            No comments yet.
                        </div>
                    <?php else : ?>

                        <?php foreach ($comments as $c) :
                            $c_date   = date('M j, Y · g:i A', strtotime($c['created_at']));
                            $initials = strtoupper(substr($c['author_name'], 0, 1));
                        ?>
                            <div class="pub-comment-item <?= !empty($c['is_mod_comment']) ? 'pub-comment-item--mod' : '' ?> <?= (!empty($c['removed_by_mod']) || !empty($c['removed_by_user'])) ? 'pub-comment-item--removed' : '' ?>">

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
                                <div class="pub-comment-avatar"><?= $initials ?></div>
                                <div class="pub-comment-body">
                                    <div class="pub-comment-header">
                                        <span class="pub-comment-author"><?= htmlspecialchars($c['author_name']) ?></span>
                                        <?php if (!empty($c['is_mod_comment'])) : ?>
                                            <span class="pub-mod-badge">SK Official</span>
                                        <?php endif; ?>
                                        <time class="pub-comment-date" datetime="<?= $c['created_at'] ?>"><?= $c_date ?></time>
                                        <span class="pub-comment-support-count">
                                            <img src="../../assets/img/handshake-icon.png" alt="Support" class="pub-meta-icon">
                                            <?= (int)$c['support_count'] ?>
                                        </span>
                                    </div>
                                    <div class="pub-comment-text"><?= nl2br(htmlspecialchars($c['message'])) ?></div>

                                    <!-- REPLIES -->
                                    <?php if (!empty($c['replies'])) : ?>
                                        <div class="pub-reply-list">
                                            <?php foreach ($c['replies'] as $r) :
                                                $r_date     = date('M j, Y · g:i A', strtotime($r['created_at']));
                                                $r_initials = strtoupper(substr($r['author_name'], 0, 1));
                                            ?>
                                                <div class="pub-reply-item <?= !empty($r['is_mod_comment']) ? 'pub-reply-item--mod' : '' ?> <?= (!empty($r['removed_by_mod']) || !empty($r['removed_by_user'])) ? 'pub-reply-item--removed' : '' ?>">
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
                                                    <div class="pub-reply-avatar"><?= $r_initials ?></div>
                                                    <div class="pub-reply-body">
                                                        <div class="pub-comment-header">
                                                            <span class="pub-comment-author"><?= htmlspecialchars($r['author_name']) ?></span>
                                                            <?php if (!empty($r['is_mod_comment'])) : ?>
                                                                <span class="pub-mod-badge">SK Official</span>
                                                            <?php endif; ?>
                                                            <time class="pub-comment-date" datetime="<?= $r['created_at'] ?>"><?= $r_date ?></time>
                                                        </div>
                                                        <div class="pub-comment-text"><?= nl2br(htmlspecialchars($r['message'])) ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <!-- LOGIN CTA -->
                <div class="pub-comment-cta">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                    </svg>
                    <span>Want to join the conversation? <a href="../auth/login.php">Sign in</a> or <a href="../auth/register.php">create an account</a>.</span>
                </div>

            </section>

        </div>
    </main>

    <!--
        LIGHTBOX — uses the same IDs as the resident thread_view
        so the shared initCarousel() in community.js can call openLightbox().
    -->
    <div class="lightbox-overlay" id="lightbox-overlay" style="display:none;">
        <button class="lightbox-close" id="lightbox-close">&times;</button>
        <img class="lightbox-img" id="lightbox-img" src="" alt="Image preview">
    </div>

    <?php include __DIR__ . '/../../components/public/footer.php'; ?>

    <script src="../../scripts/public/community.js"></script>

</body>

</html>