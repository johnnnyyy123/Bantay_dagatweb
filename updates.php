
<?php
declare(strict_types=1);

// Load existing functions and database connection.
// Session initialization is handled by functions.php.
require_once __DIR__ . '/includes/functions.php';

// Get search and category filters.
$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));

// Available announcement categories.
$categories = [
    'Bantay Dagat Activities',
    'Coastal Clean-Up Drives',
    'Marine Conservation Programs',
    'Environmental Announcements',
    'Community Activities',
    'Coastal Warnings',
    'Emergency Advisories'
];

// Retrieve published announcements.
$sql = "SELECT announcement_id, title, category, description,
               featured_image, created_at
        FROM announcements
        WHERE publication_status = 'Published'";

$params = [];

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (in_array($category, $categories, true)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY created_at DESC";

$statement = db()->prepare($sql);
$statement->execute($params);
$items = $statement->fetchAll();

// Page information.
$pageTitle = 'Latest Updates';
$activePage = 'updates';

// Load the existing header.
require __DIR__ . '/includes/header.php';
?>

<section class="page-section">
    <div class="container">

        <div class="section-heading">
            <span class="section-eyebrow">BANTAY-DAGAT MARINDUQUE</span>
            <h1>Latest Updates</h1>
            <p>
                Stay informed about coastal activities, marine conservation
                programs, announcements, and important advisories.
            </p>
        </div>

        <!-- Search and Category Filter -->
        <form method="GET" action="updates.php" class="updates-filter">

            <div class="search-group">
                <input
                    type="search"
                    name="q"
                    placeholder="Search announcements..."
                    value="<?= e($search) ?>"
                >

                <button type="submit">
                    Search
                </button>
            </div>

            <div class="category-group">
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>

                    <?php foreach ($categories as $cat): ?>
                        <option
                            value="<?= e($cat) ?>"
                            <?= $category === $cat ? 'selected' : '' ?>
                        >
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </form>

        <!-- Announcements -->
        <div class="updates-grid">

            <?php if (!empty($items)): ?>

                <?php foreach ($items as $item): ?>

                    <article class="update-card">

                        <?php if (!empty($item['featured_image'])): ?>
                            <div class="update-image">
                                <img
                                    src="<?= e($item['featured_image']) ?>"
                                    alt="<?= e($item['title']) ?>"
                                    loading="lazy"
                                >
                            </div>
                        <?php endif; ?>

                        <div class="update-content">

                            <span class="update-category">
                                <?= e($item['category']) ?>
                            </span>

                            <h2>
                                <?= e($item['title']) ?>
                            </h2>

                            <p>
                                <?= nl2br(e($item['description'])) ?>
                            </p>

                            <div class="update-date">
                                <span>Published:</span>
                                <?= e(date(
                                    'F d, Y',
                                    strtotime((string) $item['created_at'])
                                )) ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-updates">
                    <h3>No announcements found.</h3>
                    <p>
                        There are currently no published announcements
                        matching your search.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
