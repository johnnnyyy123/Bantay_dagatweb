<?php
require_once __DIR__ . '/includes/functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));

$categories = [
    'Bantay Dagat Activities',
    'Coastal Clean-Up Drives',
    'Marine Conservation Programs',
    'Environmental Announcements',
    'Community Activities',
    'Coastal Warnings',
    'Emergency Advisories'
];

$sql = "SELECT announcement_id, title, category, description,
        featured_image, created_at
        FROM announcements
        WHERE publication_status = 'Published'";

$params = [];

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (in_array($category, $categories, true)) {
    $sql .= ' AND category = ?';
    $params[] = $category;
}

$sql .= ' ORDER BY created_at DESC';

$statement = db()->prepare($sql);
$statement->execute($params);
$items = $statement->fetchAll();

$pageTitle = 'Latest Updates';
$activePage = 'updates';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">NEWS FROM OUR COAST</span>
        <h1>Latest updates</h1>
        <p>Announcements, community activities, and conservation programs from Bantay Dagat Marinduque.</p>
    </div>
</section>

<section class="content-section">
    <div class="container">

        <form class="surface-card mb-4" method="get">
            <div class="row g-3 align-items-end">

                <div class="col-md-6">
                    <label class="form-label">Search updates</label>
                    <input class="form-control"
                           name="q"
                           value="<?= e($search) ?>"
                           placeholder="Search title or description">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All categories</option>

                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"
                                <?= $category === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary rounded-pill">
                        Search
                    </button>
                </div>

            </div>
        </form>

        <?php if ($items): ?>

            <div class="row g-4">

                <?php foreach ($items as $index => $item): ?>

                    <div class="col-md-6 col-lg-4">

                        <article class="update-card">

                            <div class="update-image update-image-<?= $index % 3 + 1 ?>"
                                <?php if ($item['featured_image']): ?>
                                    style="background-image:linear-gradient(0deg,rgba(4,44,68,.15),rgba(4,44,68,.08)),url('<?= e($item['featured_image']) ?>')"
                                <?php endif; ?>>

                                <span><?= e($item['category']) ?></span>

                            </div>

                            <div class="update-body">

                                <small>
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    <?= e(date('M j, Y', strtotime($item['created_at']))) ?>
                                </small>

                                <h3><?= e($item['title']) ?></h3>

                                <p>
                                    <?= e(mb_strimwidth(strip_tags($item['description']), 0, 130, '…')) ?>
                                </p>

                                <a class="text-link"
                                   href="announcement-details.php?id=<?= (int) $item['announcement_id'] ?>">
                                    Read more
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>

                            </div>

                        </article>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-note">
                No published updates match your search yet.
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
