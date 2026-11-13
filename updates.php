
<?php
/**
 * Bantay-Dagat Marinduque
 * Latest Updates Page
 */

// Load functions and initialize session first.
require_once __DIR__ . '/includes/functions.php';

// Get search filters.
$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));

// Available categories.
$categories = [
    'Bantay Dagat Activities',
    'Coastal Clean-Up Drives',
    'Marine Conservation Programs',
    'Environmental Announcements',
    'Community Activities',
    'Coastal Warnings',
    'Emergency Advisories'
];

// Initialize results.
$items = [];
$errorMessage = null;

try {
    // Base SQL query.
    $sql = "
        SELECT
            announcement_id,
            title,
            category,
            description,
            featured_image,
            created_at
        FROM announcements
        WHERE publication_status = 'Published'
    ";

    $params = [];

    // Search filter.
    if ($search !== '') {
        $sql .= "
            AND (
                title LIKE ?
                OR description LIKE ?
            )
        ";

        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    // Category filter.
    if (in_array($category, $categories, true)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    // Latest announcements first.
    $sql .= " ORDER BY created_at DESC";

    // Execute query.
    $statement = db()->prepare($sql);
    $statement->execute($params);

    $items = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    // Record error in Railway logs.
    error_log(
        'BANTAY-DAGAT UPDATES ERROR: '
        . $exception->getMessage()
    );

    $errorMessage = 'Unable to load updates right now. Please try again later.';
}

// Page settings.
$pageTitle = 'Latest Updates';
$activePage = 'updates';

// Load header after PHP processing.
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">

        <span class="eyebrow eyebrow-light">
            NEWS FROM OUR COAST
        </span>

        <h1>Latest Updates</h1>

        <p>
            Announcements, community activities, and conservation
            programs from Bantay Dagat Marinduque.
        </p>

    </div>
</section>

<section class="content-section">

    <div class="container">

        <!-- SEARCH AND CATEGORY FILTER -->
        <form class="surface-card mb-4" method="get">

            <div class="row g-3 align-items-end">

                <div class="col-md-6">

                    <label class="form-label">
                        Search Updates
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="q"
                        value="<?= e($search) ?>"
                        placeholder="Search title or description"
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Category
                    </label>

                    <select
                        class="form-select"
                        name="category"
                    >

                        <option value="">
                            All Categories
                        </option>

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

                <div class="col-md-2 d-grid">

                    <button
                        type="submit"
                        class="btn btn-primary rounded-pill"
                    >
                        Search
                    </button>

                </div>

            </div>

        </form>

        <!-- ERROR MESSAGE -->
        <?php if ($errorMessage !== null): ?>

            <div class="alert alert-danger" role="alert">
                <?= e($errorMessage) ?>
            </div>

        <!-- ANNOUNCEMENTS -->
        <?php elseif (!empty($items)): ?>

            <div class="row g-4">

                <?php foreach ($items as $index => $item): ?>

                    <?php
                    $image = trim((string) ($item['featured_image'] ?? ''));

                    $date = '';

                    if (!empty($item['created_at'])) {
                        $timestamp = strtotime($item['created_at']);

                        if ($timestamp !== false) {
                            $date = date('M j, Y', $timestamp);
                        }
                    }

                    $description = trim(
                        strip_tags((string) ($item['description'] ?? ''))
                    );

                    if (function_exists('mb_strimwidth')) {
                        $description = mb_strimwidth(
                            $description,
                            0,
                            130,
                            '…',
                            'UTF-8'
                        );
                    } else {
                        $description = substr($description, 0, 130);
                    }

                    $imageStyle = '';

                    if ($image !== '') {
                        $imageStyle = "background-image: linear-gradient(0deg, rgba(4,44,68,.15), rgba(4,44,68,.08)), url('" .
                            e($image) .
                            "');";
                    }
                    ?>

                    <div class="col-md-6 col-lg-4">

                        <article class="update-card">

                            <div
                                class="update-image update-image-<?= ($index % 3) + 1 ?>"
                                <?php if ($imageStyle !== ''): ?>
                                    style="<?= $imageStyle ?>"
                                <?php endif; ?>
                            >

                                <span>
                                    <?= e($item['category'] ?? '') ?>
                                </span>

                            </div>

                            <div class="update-body">

                                <small>

                                    <i class="fa-regular fa-calendar me-1"></i>

                                    <?= e($date) ?>

                                </small>

                                <h3>
                                    <?= e($item['title'] ?? '') ?>
                                </h3>

                                <p>
                                    <?= e($description) ?>
                                </p>

                                <a
                                    class="text-link"
                                    href="announcement-details.php?id=<?= (int) $item['announcement_id'] ?>"
                                >
                                    Read More

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            </div>

                        </article>

                    </div>

                <?php endforeach; ?>

            </div>

        <!-- NO RESULTS -->
        <?php else: ?>

            <div class="empty-note">

                <h4>No Updates Found</h4>

                <p>
                    No published updates match your search yet.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php
// Load footer.
require __DIR__ . '/includes/footer.php';
?>
