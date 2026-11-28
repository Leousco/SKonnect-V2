<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireOfficerOrAbove();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | SK Answer Desk</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/answer_desk.css">
    <link rel="icon" type="image/svg+xml" href="../../../assets/img/favicon.svg">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>
    <main class="admin-content">
        <?php
        $pageTitle = 'SK Answer Desk';
        $pageBreadcrumb = [['Home', '#'], ['Community', null], ['SK Answer Desk', null]];
        $adminName = $_SESSION['user_name'] ?? 'Admin';
        $adminRole = 'System Admin';
        $notifCount = 0;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>
        <section class="answer-desk" id="answer-desk" data-endpoint="../../../backend/routes/admin_answer_desk.php">
            <header class="answer-desk-heading">
                <div>
                    <h1>SK Answer Desk</h1>
                    <p>Questions Kaya could not answer yet, with approved answers ready to teach it.</p>
                </div>
                <button class="answer-desk-primary" type="button" id="answer-desk-add-toggle">Add manual FAQ</button>
            </header>
            <form class="answer-desk-form answer-desk-manual-form" id="answer-desk-manual-form" hidden>
                <h2>Add a community answer</h2>
                <label>Question <input name="question" type="text" maxlength="300" required></label>
                <label>Answer <textarea name="answer" maxlength="1500" rows="4" required></textarea></label>
                <div class="answer-desk-form-row">
                    <label>Keywords or aliases <input name="keywords" type="text" maxlength="500"></label>
                    <label>Review by <input name="review_by" type="date"></label>
                </div>
                <div class="answer-desk-form-actions">
                    <button class="answer-desk-primary" type="submit">Save FAQ</button>
                    <button class="answer-desk-secondary" type="button" id="answer-desk-add-cancel">Cancel</button>
                </div>
            </form>
            <nav class="answer-desk-filters" aria-label="Filter questions">
                <button type="button" data-filter="pending" class="is-selected">Pending</button>
                <button type="button" data-filter="answered">Answered</button>
                <button type="button" data-filter="retracted">Retracted</button>
                <button type="button" data-filter="dismissed">Dismissed</button>
                <button type="button" data-filter="flagged">Flagged</button>
                <button type="button" data-filter="review">Needs review</button>
            </nav>
            <div class="answer-desk-list" id="answer-desk-list" aria-live="polite">
                <p class="answer-desk-message">Loading questions…</p>
            </div>
        </section>
    </main>
</div>
<script src="../../../scripts/management/answer_desk.js"></script>
</body>
</html>
