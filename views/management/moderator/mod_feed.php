<?php
// views/management/moderator/mod_feed.php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/models/ThreadModel.php';

$db          = new Database();
$conn        = $db->getConnection();
$threadModel = new ThreadModel($conn);
$threads     = $threadModel->getModFeedThreads();

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
    <title>SKonnect | Mod Feed</title>
    <link rel="stylesheet" href="../../../styles/management/mod_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_feed.css">
</head>

<body>

    <div class="mod-layout">

        <?php include __DIR__ . '/../../../components/management/moderator/mod_sidebar.php'; ?>

        <main class="mod-content">

            <?php
            $pageTitle      = 'Community Feed';
            $pageBreadcrumb = [['Home', '#'], ['Moderation', null], ['Community Feed', null]];
            $modName        = $_SESSION['user_name'] ?? 'Moderator';
            $modRole        = 'Moderator';
            $notifCount     = 0;
            include __DIR__ . '/../../../components/management/moderator/mod_topbar.php';
            ?>

            <!-- CONTROLS -->
            <section class="mod-feed-controls">
                <div class="mod-feed-controls-left">
                    <div class="mod-search-wrap">
                        <svg class="mod-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" id="mod-feed-search" placeholder="Search threads…" class="mod-search-input">
                    </div>
                </div>
                <div class="mod-feed-controls-right">
                    <select id="mod-feed-category" class="mod-feed-select">
                        <option value="all">All Categories</option>
                        <option value="inquiry">Inquiry</option>
                        <option value="complaint">Complaint</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="event_question">Event</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="mod-feed-status" class="mod-feed-select">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="responded">Responded</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <select id="mod-feed-visibility" class="mod-feed-select">
                        <option value="all">All Visibility</option>
                        <option value="visible">Visible</option>
                        <option value="hidden">Hidden</option>
                    </select>
                    <select id="mod-feed-sort" class="mod-feed-select">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="comments">Most Comments</option>
                        <option value="flagged">Flagged First</option>
                        <option value="pinned">Pinned First</option>
                    </select>
                </div>
            </section>

            <!-- FEED PANEL -->
            <section class="mod-feed-section">
                <div class="panel-header">
                    <h2 class="section-label">Community Threads</h2>
                    <span class="mod-feed-count" id="mod-feed-count">
                        Showing <?= count($threads) ?> thread<?= count($threads) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="mod-feed-grid" id="mod-feed-grid">

                    <?php if (empty($threads)) : ?>
                        <div class="mod-no-results" style="display:flex;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            <p>No threads found. Check back later.</p>
                        </div>
                    <?php else : ?>

                        <?php foreach ($threads as $t) :
                            $cat_key         = $t['category'];
                            $cat_label       = $cat_labels[$cat_key] ?? 'Other';
                            $initials        = strtoupper(substr($t['author_name'], 0, 2));
                            $date_fmt        = date('M j, Y', strtotime($t['created_at']));
                            $is_removed      = (bool)$t['is_removed'];
                            $is_removed_by_user = !empty($t['removed_by_user']);
                            $is_flagged      = (bool)$t['is_flagged'];
                            $is_pinned       = (bool)$t['is_pinned'];
                        ?>
                            <article class="mod-feed-card <?= $is_removed ? 'mod-feed-card--removed' : '' ?> <?= $is_flagged ? 'mod-feed-card--flagged' : '' ?> <?= $is_pinned ? 'mod-feed-card--pinned' : '' ?>" data-id="<?= (int)$t['id'] ?>" data-category="<?= htmlspecialchars($cat_key) ?>" data-status="<?= htmlspecialchars($t['status']) ?>" data-date="<?= $t['created_at'] ?>" data-comments="<?= (int)$t['comment_count'] ?>" data-removed="<?= $is_removed ? '1' : '0' ?>" data-flagged="<?= $is_flagged ? '1' : '0' ?>" data-pinned="<?= $is_pinned ? '1' : '0' ?>">

                                <div class="mod-feed-card-body">

                                    <!-- BADGES -->
                                    <div class="mod-feed-badges">
                                        <span class="mod-cat-badge category-<?= $cat_key ?>"><?= $cat_label ?></span>
                                        <span class="mod-status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                        <?php if ($is_pinned) : ?>
                                            <span class="mod-pin-indicator">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11">
                                                    <path d="M15.75 1.5a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM12.75 7.5a.75.75 0 0 0-1.5 0v5.69l-2.22-2.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V7.5Z" />
                                                </svg>
                                                Pinned
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_flagged) : ?>
                                            <span class="mod-flag-indicator">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11">
                                                    <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                                                </svg>
                                                Flagged
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_removed) : ?>
                                            <?php if ($is_removed_by_user) : ?>
                                                <span class="mod-remove-indicator mod-remove-indicator--user">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                    Deleted by User
                                                </span>
                                            <?php else : ?>
                                                <span class="mod-remove-indicator">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                    </svg>
                                                    Hidden
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- TITLE & EXCERPT -->
                                    <h3 class="mod-feed-title"><?= htmlspecialchars($t['subject']) ?></h3>
                                    <p class="mod-feed-excerpt"><?= htmlspecialchars(mb_substr($t['message'], 0, 140)) ?><?= mb_strlen($t['message']) > 140 ? '…' : '' ?></p>

                                    <!-- META -->
                                    <div class="mod-feed-meta">
                                        <span class="mod-feed-author">
                                            <span class="mod-feed-avatar"><?= $initials ?></span>
                                            <?= htmlspecialchars($t['author_name']) ?>
                                        </span>
                                        <time datetime="<?= $t['created_at'] ?>"><?= $date_fmt ?></time>
                                        <span class="mod-feed-comments">💬 <?= (int)$t['comment_count'] ?></span>
                                    </div>

                                </div><!-- /.mod-feed-card-body -->

                                <!-- CARD FOOTER -->
                                <div class="mod-feed-card-footer">

                                    <!-- STATUS TOGGLER -->
                                    <div class="mod-status-toggler" data-thread-id="<?= (int)$t['id'] ?>">
                                        <button class="mod-status-opt <?= $t['status'] === 'pending'   ? 'active' : '' ?>" data-status="pending" title="Set Pending">Pending</button>
                                        <button class="mod-status-opt <?= $t['status'] === 'responded' ? 'active' : '' ?>" data-status="responded" title="Set Responded">Responded</button>
                                        <button class="mod-status-opt <?= $t['status'] === 'resolved'  ? 'active' : '' ?>" data-status="resolved" title="Set Resolved">Resolved</button>
                                    </div>

                                    <!-- MOD ACTIONS -->
                                    <div class="mod-thread-actions">
                                        <!-- VIEW (slide-in) -->
                                        <button class="mod-action-btn mod-action-view" data-thread-id="<?= (int)$t['id'] ?>" title="View Thread">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            View
                                        </button>

                                        <!-- PIN / UNPIN -->
                                        <button class="mod-action-btn mod-action-pin <?= $is_pinned ? 'mod-action-pin--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_pinned ? 'Unpin Thread' : 'Pin Thread' ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            <?= $is_pinned ? 'Unpin' : 'Pin' ?>
                                        </button>

                                        <!-- FLAG / UNFLAG -->
                                        <button class="mod-action-btn mod-action-flag <?= $is_flagged ? 'mod-action-flag--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_flagged ? 'Unflag Thread' : 'Flag for Review' ?>">
                                            <?php if ($is_flagged) : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                                                    <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                                                </svg>
                                                Unflag
                                            <?php else : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                                                </svg>
                                                Flag
                                            <?php endif; ?>
                                        </button>

                                        <!-- REMOVE / RESTORE -->
                                        <button class="mod-action-btn mod-action-remove <?= $is_removed ? 'mod-action-remove--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_removed ? 'Restore Thread' : 'Hide Thread' ?>">
                                            <?php if ($is_removed) : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Restore
                                            <?php else : ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                                Hide
                                            <?php endif; ?>
                                        </button>
                                    </div>

                                </div><!-- /.mod-feed-card-footer -->

                            </article>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div><!-- /#mod-feed-grid -->

                <!-- NO RESULTS -->
                <div class="mod-no-results" id="mod-no-results" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p>No threads found matching your filters.</p>
                </div>

            </section>

            <!-- PAGINATION -->
            <section class="mod-pagination">
                <button class="mod-page-btn" id="mod-prev-btn" disabled>&#8249; Previous</button>
                <div class="mod-page-numbers" id="mod-page-numbers"></div>
                <button class="mod-page-btn" id="mod-next-btn">Next &#8250;</button>
            </section>

        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════
     THREAD SLIDE-IN PANEL
══════════════════════════════════════════════════════════ -->
    <div class="mod-panel-backdrop" id="mod-panel-backdrop"></div>

    <aside class="mod-thread-panel" id="mod-thread-panel" aria-label="Thread detail panel">

        <!-- PANEL HEADER -->
        <div class="mod-panel-header">
            <div class="mod-panel-header-left">
                <div class="mod-panel-badges" id="panel-badges"></div>
            </div>
            <button class="mod-panel-close" id="mod-panel-close" aria-label="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- PANEL BODY (scrollable) -->
        <div class="mod-panel-body" id="mod-panel-body">

            <!-- Loading state -->
            <div class="mod-panel-loading" id="mod-panel-loading">
                <div class="mod-panel-spinner"></div>
                <span>Loading thread…</span>
            </div>

            <!-- Thread content (injected by JS) -->
            <div id="mod-panel-content" style="display:none;">

                <h2 class="mod-panel-title" id="panel-title"></h2>

                <div class="mod-panel-meta" id="panel-meta"></div>

                <div class="mod-panel-divider"></div>

                <div class="mod-panel-body-text" id="panel-body-text"></div>

                <!-- Attached images -->
                <div class="mod-panel-images" id="panel-images"></div>

                <div class="mod-panel-divider"></div>

                <!-- Moderator action strip (inside panel) -->
                <div class="mod-panel-actions" id="panel-actions">
                    <div class="mod-panel-status-wrap">
                        <span class="mod-panel-actions-label">Status</span>
                        <div class="mod-status-toggler" id="panel-status-toggler" data-thread-id="">
                            <button class="mod-status-opt" data-status="pending">Pending</button>
                            <button class="mod-status-opt" data-status="responded">Responded</button>
                            <button class="mod-status-opt" data-status="resolved">Resolved</button>
                        </div>
                    </div>
                    <div class="mod-panel-btn-row">
                        <button class="mod-action-btn mod-action-pin" id="panel-pin-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span id="panel-pin-label">Pin</span>
                        </button>
                        <button class="mod-action-btn mod-action-flag" id="panel-flag-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                            <span id="panel-flag-label">Flag</span>
                        </button>
                        <button class="mod-action-btn mod-action-remove" id="panel-remove-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <span id="panel-remove-label">Hide</span>
                        </button>
                    </div>
                </div>

                <div class="mod-panel-divider"></div>

                <!-- Comments -->
                <div class="mod-panel-comments-section">

                    <!-- MOD REPLY BOX -->
                    <div class="mod-panel-reply-box" id="mod-panel-reply-box">
                        <div class="mod-panel-reply-avatar" id="mod-panel-reply-avatar">M</div>
                        <div class="mod-panel-reply-wrap">
                            <textarea id="mod-panel-reply-textarea" class="mod-panel-reply-textarea" rows="3" placeholder="Write a comment as moderator…"></textarea>
                            <div class="mod-panel-reply-footer">
                                <span class="reply-hint">Provide an official update or resolution for this thread.</span>
                                <button class="mod-panel-reply-submit" id="mod-panel-reply-submit" type="button">
                                    <span id="mod-panel-reply-label">Post Comment</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <h3 class="mod-panel-comments-heading">
                        Comments
                        <span class="mod-panel-comments-count" id="panel-comments-count">0</span>
                    </h3>
                    <div class="mod-panel-comment-list" id="panel-comment-list"></div>
                    
                </div>

            </div><!-- /#mod-panel-content -->

        </div><!-- /.mod-panel-body -->

    </aside>

    <!-- CONFIRM MODAL -->
    <div class="mod-confirm-overlay" id="mod-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
        <div class="mod-confirm-box">
            <div class="mod-confirm-icon" id="mod-confirm-icon">⚠️</div>
            <h3 class="mod-confirm-title" id="mod-confirm-title">Confirm Action</h3>
            <p class="mod-confirm-body" id="mod-confirm-body">Are you sure you want to perform this action?</p>
            <div class="mod-confirm-footer">
                <button class="btn-mod-sm" id="mod-confirm-cancel">Cancel</button>
                <button class="mod-confirm-ok" id="mod-confirm-ok">Confirm</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div class="mod-toast" id="mod-toast" aria-live="polite"></div>

    <!-- LIGHTBOX -->
    <div class="mod-lightbox-overlay" id="mod-lightbox" style="display:none;">
        <button class="mod-lightbox-close" id="mod-lightbox-close">&times;</button>
        <img class="mod-lightbox-img" id="mod-lightbox-img" src="" alt="Image preview">
    </div>

    <!-- FLAG CATEGORY MODAL -->
    <div class="mod-flag-modal-overlay" id="mod-flag-modal-overlay" style="display:none;" aria-modal="true" role="dialog">
        <div class="mod-flag-modal-box">
            <div class="mod-flag-modal-header">
                <div class="mod-flag-modal-title-row">
                    <div class="mod-flag-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="mod-flag-modal-title">Flag Thread for Review</h3>
                        <p class="mod-flag-modal-subtitle">Select a reason to add this thread to the moderation queue.</p>
                    </div>
                </div>
                <button class="mod-flag-modal-close" id="mod-flag-modal-close">&times;</button>
            </div>
            <div class="mod-flag-modal-body">
                <div class="mod-flag-categories" id="mod-flag-categories">
                    <label class="mod-flag-cat-option">
                        <input type="radio" name="mod-flag-category" value="inappropriate">
                        <span class="mod-flag-cat-label">
                            <span class="mod-flag-cat-name">Inappropriate</span>
                            <span class="mod-flag-cat-desc">Offensive, explicit, or violates community standards</span>
                        </span>
                    </label>
                    <label class="mod-flag-cat-option">
                        <input type="radio" name="mod-flag-category" value="spam">
                        <span class="mod-flag-cat-label">
                            <span class="mod-flag-cat-name">Spam</span>
                            <span class="mod-flag-cat-desc">Repetitive, promotional, or irrelevant content</span>
                        </span>
                    </label>
                    <label class="mod-flag-cat-option">
                        <input type="radio" name="mod-flag-category" value="misinformation">
                        <span class="mod-flag-cat-label">
                            <span class="mod-flag-cat-name">Misinformation</span>
                            <span class="mod-flag-cat-desc">False or misleading information</span>
                        </span>
                    </label>
                    <label class="mod-flag-cat-option">
                        <input type="radio" name="mod-flag-category" value="harassment">
                        <span class="mod-flag-cat-label">
                            <span class="mod-flag-cat-name">Harassment</span>
                            <span class="mod-flag-cat-desc">Bullying, threats, or targeted attacks</span>
                        </span>
                    </label>
                </div>
                <p class="mod-flag-cat-error" id="mod-flag-cat-error"></p>
            </div>
            <div class="mod-flag-modal-footer">
                <button class="btn-mod-sm" id="mod-flag-modal-cancel">Cancel</button>
                <button class="mod-flag-modal-submit" id="mod-flag-modal-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                    </svg>
                    Flag Thread
                </button>
            </div>
        </div>
    </div>

    <script>
        const MOD_NAME = <?= json_encode($_SESSION['user_name'] ?? 'Moderator') ?>;
    </script>
    <script src="../../../scripts/management/moderator/mod_feed.js"></script>

</body>

</html>