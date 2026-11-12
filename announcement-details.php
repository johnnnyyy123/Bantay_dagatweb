<?php
require_once __DIR__ . '/includes/functions.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare("SELECT a.*, COALESCE(ad.full_name, 'Bantay Dagat') AS author FROM announcements a LEFT JOIN admins ad ON ad.admin_id = a.admin_id WHERE a.announcement_id = ? AND a.publication_status = 'Published'");
$statement->execute([$id ?: 0]);
$item = $statement->fetch();
$pageTitle = $item ? $item['title'] : 'Announcement not found';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow eyebrow-light">LATEST UPDATES</span><h1><?= e($item['title'] ?? 'Update not found') ?></h1><?php if ($item): ?><p><?= e($item['category']) ?> · <?= e(date('M j, Y', strtotime($item['created_at']))) ?> · <?= e($item['author']) ?></p><?php endif; ?></div></section>
<section class="content-section"><div class="container" style="max-width:850px"><?php if ($item): ?><article class="surface-card"><?php if ($item['featured_image']): ?><img src="<?= e($item['featured_image']) ?>" alt="" class="w-100 rounded-3 mb-4" style="max-height:430px;object-fit:cover"><?php endif; ?><div class="prose"><?= nl2br(e($item['description'])) ?></div><hr><a class="text-link" href="updates.php"><i class="fa-solid fa-arrow-left me-1"></i>Back to updates</a></article><?php else: ?><div class="empty-note">This announcement may have been unpublished or removed. <a class="text-link" href="updates.php">Browse current updates</a></div><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
