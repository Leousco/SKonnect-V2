<?php  
    require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
    RoleMiddleware::requireRole('sk_officer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect Officer | Announcements</title>
    <link rel="stylesheet" href="../../../styles/management/officer/officer_announcements.css">
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <main class="off-content">

    <?php
        $pageTitle      = 'Announcements';
        $pageBreadcrumb = [['Home', '#'], ['Announcements', null]];
        $officerName    = $_SESSION['user_name'] ?? 'Officer';
        $officerRole    = $_SESSION['user_role'] ?? 'SK Officer';
        $notifCount     = 3;
        include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- PAGE TABS -->
        <div class="ann-page-tabs">
            <button class="ann-tab active" data-tab="list">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                Published
            </button>
            <button class="ann-tab" data-tab="drafts">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                Drafts
            </button>
            <button class="ann-tab" data-tab="archive">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                Archived
            </button>
            <button class="ann-tab ann-tab--create" data-tab="create">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Create Announcement
            </button>
        </div>

        <!-- TAB: PUBLISHED -->
        <div class="ann-panel" id="panel-list">

            <!-- Controls -->
            <div class="ann-controls">
                <div class="ann-search-wrap">
                    <svg class="ann-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" class="ann-search-input" id="list-search-input" placeholder="Search published announcements…">
                </div>
                <div class="ann-filters">
                    <select class="ann-select" id="list-filter-cat">
                        <option value="">All Categories</option>
                        <option value="event">Event</option>
                        <option value="program">Program</option>
                        <option value="meeting">Meeting</option>
                        <option value="notice">Notice</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <select class="ann-select" id="list-filter-sort">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>

            <!-- Stats strip -->
            <div class="ann-stats-strip">
                <div class="ann-stat-pill stat-published">
                    <span class="stat-num" id="stat-published">0</span>
                    <span class="stat-lbl">Published</span>
                </div>
                <div class="ann-stat-pill stat-featured">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.45 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd"/></svg>
                    <span class="stat-num" id="stat-featured">0</span>
                    <span class="stat-lbl">Featured</span>
                </div>
                <div class="ann-stat-pill stat-urgent">
                    <span class="stat-num" id="stat-urgent">0</span>
                    <span class="stat-lbl">Urgent</span>
                </div>
            </div>

            <!-- Table -->
            <div class="ann-table-wrap">
                <table class="ann-table">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Featured</th>
                            <th>Published</th>
                            <th>Preview</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="list-tbody"></tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="ann-pagination" id="list-pagination"></div>

        </div>

        <!-- TAB: DRAFTS -->
        <div class="ann-panel ann-panel--hidden" id="panel-drafts">

            <!-- Controls -->
            <div class="ann-controls">
                <div class="ann-search-wrap">
                    <svg class="ann-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" class="ann-search-input" id="drafts-search-input" placeholder="Search drafts…">
                </div>
                <div class="ann-filters">
                    <select class="ann-select" id="drafts-filter-cat">
                        <option value="">All Categories</option>
                        <option value="event">Event</option>
                        <option value="program">Program</option>
                        <option value="meeting">Meeting</option>
                        <option value="notice">Notice</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <select class="ann-select" id="drafts-filter-sort">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>

            <!-- Info banner -->
            <div class="ann-archive-info-banner ann-drafts-info-banner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <p>Drafts are not visible to the public. Edit them and publish when ready.</p>
            </div>

            <!-- Stats strip -->
            <div class="ann-stats-strip">
                <div class="ann-stat-pill stat-draft">
                    <span class="stat-num" id="stat-drafts">0</span>
                    <span class="stat-lbl">Drafts</span>
                </div>
            </div>

            <!-- Table -->
            <div class="ann-table-wrap">
                <table class="ann-table">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Last Saved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="drafts-tbody"></tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="ann-pagination" id="drafts-pagination"></div>

        </div>

        <!-- TAB: CREATE ANNOUNCEMENT -->
        <div class="ann-panel ann-panel--hidden" id="panel-create">

            <div class="ann-editor-layout">

                <!-- LEFT FORM -->
                <div class="ann-form-col">

                    <div class="ann-form-card">

                        <!-- SECTION: Banner Image -->
                        <div class="ann-form-section">
                            <div class="ann-section-header">
                                <span class="ann-section-num">01</span>
                                <div>
                                    <h3 class="ann-section-title">Banner Image</h3>
                                    <p class="ann-section-sub">This will appear as the announcement cover photo on cards and detail pages.</p>
                                </div>
                            </div>

                            <div class="ann-banner-drop" id="banner-drop-zone">
                                <input type="file" id="banner-file" accept="image/*" class="ann-file-input">
                                <div class="ann-drop-inner" id="banner-drop-inner">
                                    <div class="ann-drop-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </div>
                                    <p class="ann-drop-label">Drag &amp; drop an image here</p>
                                    <p class="ann-drop-sub">or <label for="banner-file" class="ann-browse-link">browse to upload</label></p>
                                    <p class="ann-drop-hint">PNG, JPG, WEBP · Recommended 1200 × 400px · Max 5 MB</p>
                                </div>
                                <div class="ann-banner-preview" id="banner-preview" style="display:none;">
                                    <img id="banner-preview-img" src="" alt="Banner preview">
                                    <button type="button" class="ann-banner-remove" id="banner-remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ann-form-divider"></div>

                        <!-- SECTION: Title & Details -->
                        <div class="ann-form-section">
                            <div class="ann-section-header">
                                <span class="ann-section-num">02</span>
                                <div>
                                    <h3 class="ann-section-title">Title &amp; Details</h3>
                                    <p class="ann-section-sub">Write a clear, concise title and the full announcement body.</p>
                                </div>
                            </div>

                            <div class="ann-field-group">
                                <label class="ann-label" for="ann-title">Announcement Title <span class="ann-required">*</span></label>
                                <input type="text" id="ann-title" class="ann-input" placeholder="e.g. Livelihood Training Program – April Batch" maxlength="120">
                                <span class="ann-char-count" id="title-char">0 / 120</span>
                            </div>

                            <div class="ann-field-group">
                                <label class="ann-label" for="ann-body">Announcement Details <span class="ann-required">*</span></label>
                                <!-- Text Toolbar -->
                                <div class="ann-toolbar" id="ann-toolbar">
                                    <button type="button" class="toolbar-btn" data-cmd="bold" title="Bold"><strong>B</strong></button>
                                    <button type="button" class="toolbar-btn" data-cmd="italic" title="Italic"><em>I</em></button>
                                    <button type="button" class="toolbar-btn" data-cmd="underline" title="Underline"><u>U</u></button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" class="toolbar-btn" data-cmd="insertUnorderedList" title="Bullet list">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" data-cmd="insertOrderedList" title="Numbered list">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" class="toolbar-btn" data-cmd="justifyLeft" title="Align left">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5"/></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" data-cmd="justifyCenter" title="Align center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 12h10.5M3.75 17.25h16.5"/></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" data-cmd="justifyRight" title="Align right">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M9.75 12h10.5M3.75 17.25h16.5"/></svg>
                                    </button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" class="toolbar-btn" data-cmd="createLink" title="Insert link">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" data-cmd="removeFormat" title="Clear formatting">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 17.94 6M17.94 18 6 6"/></svg>
                                    </button>
                                </div>
                                <div id="ann-body"
                                     class="ann-richtext"
                                     contenteditable="true"
                                     data-placeholder="Write the full details of the announcement here…"
                                     spellcheck="true"></div>
                                <textarea id="ann-body-hidden" name="content" style="display:none;"></textarea>
                            </div>
                        </div>

                        <div class="ann-form-divider"></div>

                        <!-- SECTION: Category & Settings -->
                        <div class="ann-form-section">
                            <div class="ann-section-header">
                                <span class="ann-section-num">03</span>
                                <div>
                                    <h3 class="ann-section-title">Category &amp; Settings</h3>
                                    <p class="ann-section-sub">Classify the announcement and configure its visibility options.</p>
                                </div>
                            </div>

                            <div class="ann-two-col">
                                <div class="ann-field-group">
                                    <label class="ann-label">Category <span class="ann-required">*</span></label>
                                    <div class="ann-category-grid">
                                        <label class="ann-cat-option">
                                            <input type="radio" name="category" value="event">
                                            <span class="ann-cat-card cat-event-card">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                                Event
                                            </span>
                                        </label>
                                        <label class="ann-cat-option">
                                            <input type="radio" name="category" value="program">
                                            <span class="ann-cat-card cat-program-card">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                                                Program
                                            </span>
                                        </label>
                                        <label class="ann-cat-option">
                                            <input type="radio" name="category" value="meeting">
                                            <span class="ann-cat-card cat-meeting-card">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                                                Meeting
                                            </span>
                                        </label>
                                        <label class="ann-cat-option">
                                            <input type="radio" name="category" value="notice">
                                            <span class="ann-cat-card cat-notice-card">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/></svg>
                                                Notice
                                            </span>
                                        </label>
                                        <label class="ann-cat-option">
                                            <input type="radio" name="category" value="urgent">
                                            <span class="ann-cat-card cat-urgent-card">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                                Urgent
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Settings column -->
                                <div class="ann-settings-col">

                                    <!-- Featured toggle -->
                                    <div class="ann-field-group">
                                        <label class="ann-label">Featured Announcement</label>
                                        <div class="ann-toggle-card" id="featured-toggle-card">
                                            <div class="ann-toggle-info">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="ann-toggle-icon"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.45 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd"/></svg>
                                                <div>
                                                    <strong>Mark as Featured</strong>
                                                    <p>Displays prominently on the member portal dashboard.</p>
                                                </div>
                                            </div>
                                            <label class="ann-switch">
                                                <input type="checkbox" id="featured-checkbox">
                                                <span class="ann-switch-track"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Publish date -->
                                    <div class="ann-field-group">
                                        <label class="ann-label" for="ann-publish-date">Publish Date</label>
                                        <input type="date" id="ann-publish-date" class="ann-input">
                                        <span class="ann-field-hint">Leave blank to publish immediately.</span>
                                    </div>

                                    <!-- Expiry date -->
                                    <div class="ann-field-group">
                                        <label class="ann-label" for="ann-expiry-date">Expiry Date</label>
                                        <input type="date" id="ann-expiry-date" class="ann-input">
                                        <span class="ann-field-hint">Announcement will be archived after this date.</span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="ann-form-divider"></div>

                        <!-- SECTION: Attachments -->
                        <div class="ann-form-section">
                            <div class="ann-section-header">
                                <span class="ann-section-num">04</span>
                                <div>
                                    <h3 class="ann-section-title">Attachments</h3>
                                    <p class="ann-section-sub">Attach supporting documents or images members can download.</p>
                                </div>
                            </div>

                            <div class="ann-attach-drop" id="attach-drop-zone">
                                <input type="file" id="attach-files" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp" class="ann-file-input">
                                <div class="ann-attach-inner">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                                    <p>Drag &amp; drop files here or <label for="attach-files" class="ann-browse-link">browse</label></p>
                                    <p class="ann-drop-hint">PDF, DOC, XLS, PNG, JPG · Max 10 MB each</p>
                                </div>
                            </div>

                            <ul class="ann-attach-list" id="attach-list"></ul>
                        </div>

                        <!-- FORM ACTIONS -->
                        <div class="ann-form-actions">
                            <button type="button" class="btn-ann-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                Save as Draft
                            </button>
                            <div class="form-actions-right">
                                <button type="button" class="btn-ann-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>
                                    Publish Now
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- RIGHT FORM: LIVE PREVIEW -->
                <aside class="ann-preview-col">
                    <div class="ann-preview-sticky">

                        <div class="ann-preview-header">
                            <span class="ann-preview-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Live Preview
                            </span>
                            <span class="ann-preview-hint">Updates as you type</span>
                        </div>

                        <div class="ann-preview-card" id="preview-card">
                            <div class="preview-banner" id="preview-banner">
                                <div class="preview-banner-placeholder" id="preview-banner-placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    <span>Banner image will appear here</span>
                                </div>
                                <img id="preview-banner-img" src="" alt="" style="display:none; width:100%; height:100%; object-fit:cover;">
                            </div>

                            <div class="preview-body">
                                <div class="preview-badges">
                                    <span class="ann-badge preview-img-badge" id="preview-cat-pill">Category</span>
                                </div>
                                <h4 class="preview-title" id="preview-title">Your announcement title will appear here…</h4>
                                <p class="preview-excerpt" id="preview-excerpt">The announcement body text will be summarised here for the card view.</p>
                                <div class="preview-meta-row" id="preview-meta-row">
                                    <span class="preview-posted-by">By: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Officer') ?></strong></span>
                                    <span class="preview-date" id="preview-date"><?= date('M j, Y') ?></span>
                                </div>
                                <div class="preview-footer">
                                    <a href="#" class="preview-read-more">Read More</a>
                                    <button class="preview-bookmark-btn" title="Bookmark" tabindex="-1">🔖</button>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="ann-checklist">
                            <p class="ann-checklist-label">Completion</p>
                            <div class="ann-checklist-item" id="check-banner">
                                <svg class="check-icon check-empty" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/></svg>
                                <svg class="check-icon check-done" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-6m-9.75 5.25A9 9 0 1 1 21 12a9 9 0 0 1-18 0Z"/></svg>
                                <span>Banner image</span>
                            </div>
                            <div class="ann-checklist-item" id="check-title">
                                <svg class="check-icon check-empty" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/></svg>
                                <svg class="check-icon check-done" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-6m-9.75 5.25A9 9 0 1 1 21 12a9 9 0 0 1-18 0Z"/></svg>
                                <span>Title entered</span>
                            </div>
                            <div class="ann-checklist-item" id="check-body">
                                <svg class="check-icon check-empty" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/></svg>
                                <svg class="check-icon check-done" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-6m-9.75 5.25A9 9 0 1 1 21 12a9 9 0 0 1-18 0Z"/></svg>
                                <span>Details written</span>
                            </div>
                            <div class="ann-checklist-item" id="check-category">
                                <svg class="check-icon check-empty" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/></svg>
                                <svg class="check-icon check-done" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3 6-6m-9.75 5.25A9 9 0 1 1 21 12a9 9 0 0 1-18 0Z"/></svg>
                                <span>Category selected</span>
                            </div>
                        </div>

                    </div>
                </aside>

            </div>
        </div>

        <!-- TAB: ARCHIVE -->
        <div class="ann-panel ann-panel--hidden" id="panel-archive">

            <!-- Controls -->
            <div class="ann-controls">
                <div class="ann-search-wrap">
                    <svg class="ann-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" class="ann-search-input" id="archive-search-input" placeholder="Search archived announcements…">
                </div>
                <div class="ann-filters">
                    <select class="ann-select" id="archive-filter-cat">
                        <option value="">All Categories</option>
                        <option value="event">Event</option>
                        <option value="program">Program</option>
                        <option value="meeting">Meeting</option>
                        <option value="notice">Notice</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <select class="ann-select" id="archive-filter-sort">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>

            <!-- Archive info banner -->
            <div class="ann-archive-info-banner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <p>Archived announcements are hidden from the public portal. You can restore them to <strong>Draft</strong> or permanently delete them.</p>
            </div>

            <!-- Stats strip -->
            <div class="ann-stats-strip">
                <div class="ann-stat-pill stat-archived-total">
                    <span class="stat-num" id="stat-archived">0</span>
                    <span class="stat-lbl">Archived</span>
                </div>
            </div>

            <!-- Archive Table -->
            <div class="ann-table-wrap ann-table-wrap--archived">
                <table class="ann-table">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Archived Reason</th>
                            <th>Originally Published</th>
                            <th>Archived On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="archive-tbody"></tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="ann-pagination" id="archive-pagination"></div>

        </div>

    </main>
</div>

<script src="../../../scripts/management/officer/officer_announcements.js"></script>

</body>
</html>