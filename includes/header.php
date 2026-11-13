<?php
source code
error_reporting(0);
ini_set('display_errors',0);

require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Bantay-Dagat Marinduque';
$activePage = $activePage ?? '';
$navUserId = active_user_id();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#064a78">
    <title><?= e($pageTitle) ?> | Bantay-Dagat Marinduque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/species.css">
</head>
<body>
<header class="site-header">
    <nav class="navbar navbar-expand-lg container">
        <a class="navbar-brand brand" href="index.php"><span class="brand-mark"><i class="fa-solid fa-water"></i></span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav ms-auto align-items-lg-center">
                <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">Home</a>
                <a class="nav-link <?= $activePage === 'report' ? 'active' : '' ?>" href="report.php">Report an Issue</a>
                <a class="nav-link <?= $activePage === 'map' ? 'active' : '' ?>" href="map.php">Explore Map</a>
                <a class="nav-link <?= $activePage === 'species' ? 'active' : '' ?>" href="marine-species.php">Sea Creatures</a>
                <a class="nav-link <?= $activePage === 'updates' ? 'active' : '' ?>" href="updates.php">Latest Updates</a>
                <a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="about.php">About</a>
                <?php if ($navUserId !== null): ?>
                    <span class="nav-link"><?= e(current_user_name()) ?></span>
                    <a class="nav-link" href="user-inbox.php">Inbox <span class="badge rounded-pill text-bg-primary"><?= user_unread_message_count($navUserId) ?></span></a>
                    <a class="nav-link" href="profile.php">My Account</a>
                    <form method="post" action="logout.php" class="m-0">
                        <?= csrf_field() ?>
                        <button class="nav-link btn btn-link border-0" type="submit">Logout</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                <?php endif; ?>
                <a class="btn btn-primary rounded-pill nav-cta" href="report.php">Report an Issue <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </nav>
</header>
<?php if ($notice = consume_flash()): ?>
    <div class="container flash-wrap"><div class="alert alert-<?= e($notice['kind'] === 'error' ? 'danger' : 'success') ?> alert-dismissible fade show" role="alert"><?= e($notice['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>
<?php endif; ?>
<main>
