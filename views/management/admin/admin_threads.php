<?php

require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();

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
    <title>Admin | Threads</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_threads.css">
</head>

<body>

    <div class="admin-layout">

        <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

        <main class="admin-content">

            <?php
            $pageTitle      = 'Threads';
            $pageBreadcrumb = [['Home', '#'], ['Community', '#'], ['Threads', null]];
            $adminName      = $_SESSION['user_name'] ?? 'Admin';
            $adminRole      = 'System Admin';
            $notifCount     = 0;
            include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
            ?>

            
            <section class="adm-feed-controls">
                <div class="adm-feed-controls-left">
                    <div class="adm-search-wrap">
                        <svg class="adm-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" id="adm-feed-search" placeholder="Search threads…" class="adm-search-input">
                    </div>
                </div>
                <div class="adm-feed-controls-right">
                    <select id="adm-feed-category" class="adm-feed-select">
                        <option value="all">All Categories</option>
                        <option value="inquiry">Inquiry</option>
                        <option value="complaint">Complaint</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="event_question">Event</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="adm-feed-status" class="adm-feed-select">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="responded">Responded</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <select id="adm-feed-visibility" class="adm-feed-select">
                        <option value="all">All Visibility</option>
                        <option value="visible">Visible</option>
                        <option value="hidden">Hidden</option>
                    </select>
                    <select id="adm-feed-sort" class="adm-feed-select">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="comments">Most Comments</option>
                        <option value="flagged">Flagged First</option>
                        <option value="pinned">Pinned First</option>
                    </select>
                </div>
            </section>

            
            <section class="adm-feed-section">
                <div class="panel-header">
                    <h2 class="section-label">Community Threads</h2>
                    <span class="adm-feed-count" id="adm-feed-count">
                        Showing <?= count($threads) ?> thread<?= count($threads) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="adm-feed-grid" id="adm-feed-grid">

                    <?php if (empty($threads)) : ?>
                        <div class="adm-no-results" style="display:flex;">
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
                            <article class="adm-feed-card <?= $is_removed ? 'adm-feed-card--removed' : '' ?> <?= $is_flagged ? 'adm-feed-card--flagged' : '' ?> <?= $is_pinned ? 'adm-feed-card--pinned' : '' ?>" data-id="<?= (int)$t['id'] ?>" data-category="<?= htmlspecialchars($cat_key) ?>" data-status="<?= htmlspecialchars($t['status']) ?>" data-date="<?= $t['created_at'] ?>" data-comments="<?= (int)$t['comment_count'] ?>" data-removed="<?= $is_removed ? '1' : '0' ?>" data-flagged="<?= $is_flagged ? '1' : '0' ?>" data-pinned="<?= $is_pinned ? '1' : '0' ?>">

                                <div class="adm-feed-card-body">

                                    
                                    <div class="adm-feed-badges">
                                        <span class="adm-cat-badge category-<?= $cat_key ?>"><?= $cat_label ?></span>
                                        <span class="adm-status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                        <?php if ($is_pinned) : ?>
                                            <span class="adm-pin-indicator">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11">
                                                    <path d="M15.75 1.5a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM12.75 7.5a.75.75 0 0 0-1.5 0v5.69l-2.22-2.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V7.5Z" />
                                                </svg>
                                                Pinned
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_flagged) : ?>
                                            <span class="adm-flag-indicator">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11" height="11">
                                                    <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                                                </svg>
                                                Flagged
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($is_removed) : ?>
                                            <?php if ($is_removed_by_user) : ?>
                                                <span class="adm-remove-indicator adm-remove-indicator--user">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                    Deleted by User
                                                </span>
                                            <?php else : ?>
                                                <span class="adm-remove-indicator">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                    </svg>
                                                    Hidden
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    
                                    <h3 class="adm-feed-title"><?= htmlspecialchars($t['subject']) ?></h3>
                                    <p class="adm-feed-excerpt"><?= htmlspecialchars(mb_substr($t['message'], 0, 140)) ?><?= mb_strlen($t['message']) > 140 ? '…' : '' ?></p>

                                    
                                    <div class="adm-feed-meta">
                                        <span class="adm-feed-author">
                                            <span class="adm-feed-avatar"><?= $initials ?></span>
                                            <?= htmlspecialchars($t['author_name']) ?>
                                        </span>
                                        <time datetime="<?= $t['created_at'] ?>"><?= $date_fmt ?></time>
                                        <span class="adm-feed-comments">💬 <?= (int)$t['comment_count'] ?></span>
                                    </div>

                                </div>

                                
                                <div class="adm-feed-card-footer">

                                    
                                    <div class="adm-status-toggler" data-thread-id="<?= (int)$t['id'] ?>">
                                        <button class="adm-status-opt <?= $t['status'] === 'pending'   ? 'active' : '' ?>" data-status="pending" title="Set Pending">Pending</button>
                                        <button class="adm-status-opt <?= $t['status'] === 'responded' ? 'active' : '' ?>" data-status="responded" title="Set Responded">Responded</button>
                                        <button class="adm-status-opt <?= $t['status'] === 'resolved'  ? 'active' : '' ?>" data-status="resolved" title="Set Resolved">Resolved</button>
                                    </div>

                                    
                                    <div class="adm-thread-actions">
                                        
                                        <button class="adm-action-btn adm-action-view" data-thread-id="<?= (int)$t['id'] ?>" title="View Thread">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            View
                                        </button>

                                        
                                        <button class="adm-action-btn adm-action-pin <?= $is_pinned ? 'adm-action-pin--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_pinned ? 'Unpin Thread' : 'Pin Thread' ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            <?= $is_pinned ? 'Unpin' : 'Pin' ?>
                                        </button>

                                        
                                        <button class="adm-action-btn adm-action-flag <?= $is_flagged ? 'adm-action-flag--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_flagged ? 'Unflag Thread' : 'Flag for Review' ?>">
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

                                        
                                        <button class="adm-action-btn adm-action-remove <?= $is_removed ? 'adm-action-remove--active' : '' ?>" data-thread-id="<?= (int)$t['id'] ?>" title="<?= $is_removed ? 'Restore Thread' : 'Hide Thread' ?>">
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

                                </div>

                            </article>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                
                <div class="adm-no-results" id="adm-no-results" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p>No threads found matching your filters.</p>
                </div>

            </section>

            
            <section class="adm-pagination">
                <button class="adm-page-btn" id="adm-prev-btn" disabled>&#8249; Previous</button>
                <div class="adm-page-numbers" id="adm-page-numbers"></div>
                <button class="adm-page-btn" id="adm-next-btn">Next &#8250;</button>
            </section>

        </main>
    </div>

    


    <div class="adm-panel-backdrop" id="adm-panel-backdrop"></div>

    <aside class="adm-thread-panel" id="adm-thread-panel" aria-label="Thread detail panel">

        
        <div class="adm-panel-header">
            <div class="adm-panel-header-left">
                <div class="adm-panel-badges" id="panel-badges"></div>
            </div>
            <button class="adm-panel-close" id="adm-panel-close" aria-label="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        
        <div class="adm-panel-body" id="adm-panel-body">

            
            <div class="adm-panel-loading" id="adm-panel-loading">
                <div class="adm-panel-spinner"></div>
                <span>Loading thread…</span>
            </div>

            
            <div id="adm-panel-content" style="display:none;">

                <h2 class="adm-panel-title" id="panel-title"></h2>

                <div class="adm-panel-meta" id="panel-meta"></div>

                <div class="adm-panel-divider"></div>

                <div class="adm-panel-body-text" id="panel-body-text"></div>

                
                <div class="adm-panel-images" id="panel-images"></div>

                <div class="adm-panel-divider"></div>

                
                <div class="adm-panel-actions" id="panel-actions">
                    <div class="adm-panel-status-wrap">
                        <span class="adm-panel-actions-label">Status</span>
                        <div class="adm-status-toggler" id="panel-status-toggler" data-thread-id="">
                            <button class="adm-status-opt" data-status="pending">Pending</button>
                            <button class="adm-status-opt" data-status="responded">Responded</button>
                            <button class="adm-status-opt" data-status="resolved">Resolved</button>
                        </div>
                    </div>
                    <div class="adm-panel-btn-row">
                        <button class="adm-action-btn adm-action-pin" id="panel-pin-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span id="panel-pin-label">Pin</span>
                        </button>
                        <button class="adm-action-btn adm-action-flag" id="panel-flag-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                            <span id="panel-flag-label">Flag</span>
                        </button>
                        <button class="adm-action-btn adm-action-remove" id="panel-remove-btn" data-thread-id="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <span id="panel-remove-label">Hide</span>
                        </button>
                    </div>
                </div>

                <div class="adm-panel-divider"></div>

                
                <div class="adm-panel-comments-section">

                    
                    <div class="adm-panel-reply-box" id="adm-panel-reply-box">
                        <div class="adm-panel-reply-avatar" id="adm-panel-reply-avatar">A</div>
                        <div class="adm-panel-reply-wrap">
                            <textarea id="adm-panel-reply-textarea" class="adm-panel-reply-textarea" rows="3" placeholder="Write a comment as admin…"></textarea>
                            <div class="adm-panel-reply-footer">
                                <span class="reply-hint">Provide an official update or resolution for this thread.</span>
                                <button class="adm-panel-reply-submit" id="adm-panel-reply-submit" type="button">
                                    <span id="adm-panel-reply-label">Post Comment</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <h3 class="adm-panel-comments-heading">
                        Comments
                        <span class="adm-panel-comments-count" id="panel-comments-count">0</span>
                    </h3>
                    <div class="adm-panel-comment-list" id="panel-comment-list"></div>
                    
                </div>

            </div>

        </div>

    </aside>

    
    <div class="adm-confirm-overlay" id="adm-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
        <div class="adm-confirm-box">
            <div class="adm-confirm-icon" id="adm-confirm-icon">⚠️</div>
            <h3 class="adm-confirm-title" id="adm-confirm-title">Confirm Action</h3>
            <p class="adm-confirm-body" id="adm-confirm-body">Are you sure you want to perform this action?</p>
            <div class="adm-confirm-footer">
                <button class="btn-adm-sm" id="adm-confirm-cancel">Cancel</button>
                <button class="adm-confirm-ok" id="adm-confirm-ok">Confirm</button>
            </div>
        </div>
    </div>

    
    <div class="adm-toast" id="adm-toast" aria-live="polite"></div>

    
    <div class="adm-lightbox-overlay" id="adm-lightbox" style="display:none;">
        <button class="adm-lightbox-close" id="adm-lightbox-close">&times;</button>
        <img class="adm-lightbox-img" id="adm-lightbox-img" src="" alt="Image preview">
    </div>

    
    <div class="adm-flag-modal-overlay" id="adm-flag-modal-overlay" style="display:none;" aria-modal="true" role="dialog">
        <div class="adm-flag-modal-box">
            <div class="adm-flag-modal-header">
                <div class="adm-flag-modal-title-row">
                    <div class="adm-flag-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="adm-flag-modal-title">Flag Thread for Review</h3>
                        <p class="adm-flag-modal-subtitle">Select a reason to add this thread to the moderation queue.</p>
                    </div>
                </div>
                <button class="adm-flag-modal-close" id="adm-flag-modal-close">&times;</button>
            </div>
            <div class="adm-flag-modal-body">
                <div class="adm-flag-categories" id="adm-flag-categories">
                    <label class="adm-flag-cat-option">
                        <input type="radio" name="adm-flag-category" value="inappropriate">
                        <span class="adm-flag-cat-label">
                            <span class="adm-flag-cat-name">Inappropriate</span>
                            <span class="adm-flag-cat-desc">Offensive, explicit, or violates community standards</span>
                        </span>
                    </label>
                    <label class="adm-flag-cat-option">
                        <input type="radio" name="adm-flag-category" value="spam">
                        <span class="adm-flag-cat-label">
                            <span class="adm-flag-cat-name">Spam</span>
                            <span class="adm-flag-cat-desc">Repetitive, promotional, or irrelevant content</span>
                        </span>
                    </label>
                    <label class="adm-flag-cat-option">
                        <input type="radio" name="adm-flag-category" value="misinformation">
                        <span class="adm-flag-cat-label">
                            <span class="adm-flag-cat-name">Misinformation</span>
                            <span class="adm-flag-cat-desc">False or misleading information</span>
                        </span>
                    </label>
                    <label class="adm-flag-cat-option">
                        <input type="radio" name="adm-flag-category" value="harassment">
                        <span class="adm-flag-cat-label">
                            <span class="adm-flag-cat-name">Harassment</span>
                            <span class="adm-flag-cat-desc">Bullying, threats, or targeted attacks</span>
                        </span>
                    </label>
                </div>
                <p class="adm-flag-cat-error" id="adm-flag-cat-error"></p>
            </div>
            <div class="adm-flag-modal-footer">
                <button class="btn-adm-sm" id="adm-flag-modal-cancel">Cancel</button>
                <button class="adm-flag-modal-submit" id="adm-flag-modal-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 6.725.738l.108.054A8.25 8.25 0 0 0 18 4.524l3.11-.732a.75.75 0 0 1 .917.81 47.784 47.784 0 0 0 .005 10.337.75.75 0 0 1-.574.812l-3.114.733a9.75 9.75 0 0 1-6.594-.77l-.108-.054a8.25 8.25 0 0 0-5.69-.625l-2.202.55V21a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd" />
                    </svg>
                    Flag Thread
                </button>
            </div>
        </div>
    </div>

    <script>
        const ADMIN_NAME = <?= json_encode($_SESSION['user_name'] ?? 'System Admin') ?>;
    </script>
    <script src="../../../scripts/management/admin/admin_threads.js"></script>

</body>

</html>