<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$navActiveMap = [
    'announcement_view.php' => 'announcements.php',
    'public_thread_view.php' => 'community.php',
];
$activeTarget = $navActiveMap[$currentPage] ?? $currentPage;

function navLink(string $href, string $label, string $activeTarget): string
{
    $file   = basename($href);
    $active = ($file === $activeTarget) ? ' active' : '';
    return "<li><a href=\"{$href}\" class=\"nav-link{$active}\">{$label}</a></li>";
}
?>
<nav id="navbar">
    <div class="navbar-container">
        <a href="main.php" class="navbar-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
            <span>SKonnect</span>
        </a>

        <button class="navbar-toggle" id="navbarToggle" type="button" aria-label="Toggle navigation" aria-controls="navbarMenu" aria-expanded="false">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>

        <ul class="navbar-menu" id="navbarMenu">
            <?= navLink('main.php',          'Home',           $activeTarget) ?>
            <?= navLink('announcements.php', 'Announcements',  $activeTarget) ?>
            <?= navLink('services.php',      'Services',       $activeTarget) ?>
            <?= navLink('community.php',     'Community Feed', $activeTarget) ?>
            <?= navLink('../auth/login.php', '<i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Login', $activeTarget) ?>
        </ul>
    </div>
</nav>
<script src="../../scripts/public/navigation.js"></script>