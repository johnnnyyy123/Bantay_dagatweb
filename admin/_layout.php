<?php
require_once __DIR__ . '/../includes/functions.php';
function admin_header(string $title): void
{
    $current = basename($_SERVER['PHP_SELF']);
    $pendingInboxCount = (int) db()->query(
        "SELECT COUNT(*) FROM reports WHERE status = 'Pending' AND reference_sent_at IS NULL"
    )->fetchColumn();
    $items = [
        ['dashboard.php', 'gauge', 'Dashboard'],
        ['manage-reports.php', 'inbox', 'Report Inbox'],
        ['manage-map.php', 'map-location-dot', 'Map locations'],
        ['manage-announcements.php', 'bullhorn', 'Announcements'],
        ['manage-species.php', 'fish-fins', 'Marine species'],
        ['manage-users.php', 'users', 'Users'],
        ['manage-activities.php', 'leaf', 'Clean-up activities'],
        ['messages.php', 'envelope', 'Messages'],
        ['profile.php', 'user-gear', 'Admin profile'],
    ];
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($title) ?> | BDM Admin</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/admin.css"></head><body>
    <?php if ($notice = consume_flash()): ?><div class="container mt-3"><div class="alert alert-<?= e($notice['kind'] === 'error' ? 'danger' : 'success') ?> alert-dismissible fade show"><?= e($notice['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div></div><?php endif; ?>
    <div class="admin-shell"><aside class="admin-sidebar"><a class="brand" href="dashboard.php"><span class="brand-mark"><i class="fa-solid fa-water"></i></span><span>BANTAY-DAGAT<small>ADMINISTRATION</small></span></a>
    <?php foreach ($items as [$file, $icon, $label]): ?><a class="sidebar-link <?= $current === $file ? 'active' : '' ?>" href="<?= e($file) ?>"><i class="fa-solid fa-<?= e($icon) ?>"></i><?= e($label) ?><?php if ($file === 'manage-reports.php' && $pendingInboxCount > 0): ?><span class="badge rounded-pill text-bg-danger ms-auto"><?= $pendingInboxCount ?></span><?php endif; ?></a><?php endforeach; ?>
    <a class="sidebar-link mt-3" href="../index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i>View public site</a><form method="post" action="logout.php" class="sidebar-logout"><?= csrf_field() ?><button class="sidebar-link w-100 border-0 bg-transparent text-start"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form></aside><main class="admin-content"><div class="admin-top"><div><span class="eyebrow mb-1">BANTAY-DAGAT MARINDUQUE</span><h1><?= e($title) ?></h1></div><div class="small text-secondary"><i class="fa-regular fa-user me-1"></i><?= e($_SESSION['admin_name'] ?? 'Administrator') ?></div></div>
    <?php
}
function admin_footer(): void
{
    echo '</main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="../assets/js/main.js"></script></body></html>';
}
require_admin();
