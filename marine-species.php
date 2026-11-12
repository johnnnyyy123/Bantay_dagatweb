<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$pageSize = 12;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = '(common_name LIKE ? OR scientific_name LIKE ? OR category LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($category !== '') {
    $conditions[] = 'category = ?';
    $params[] = $category;
}
if ($status !== '') {
    $conditions[] = 'conservation_status = ?';
    $params[] = $status;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM marine_species' . $where);
$countStatement->execute($params);
$totalSpecies = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalSpecies / $pageSize));
$page = min(max(1, $requestedPage ?: 1), $totalPages);
$offset = ($page - 1) * $pageSize;

$statement = $pdo->prepare(
    'SELECT species_id, common_name, scientific_name, category, description,
            conservation_status, species_image, image_source_url, occurrence_note
     FROM marine_species' . $where . '
     ORDER BY common_name, scientific_name
     LIMIT ? OFFSET ?'
);
foreach ($params as $index => $value) {
    $statement->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$statement->bindValue(count($params) + 1, $pageSize, PDO::PARAM_INT);
$statement->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$statement->execute();
$species = $statement->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM marine_species WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$statuses = $pdo->query("SELECT DISTINCT conservation_status FROM marine_species WHERE conservation_status IS NOT NULL AND conservation_status <> '' ORDER BY conservation_status")->fetchAll(PDO::FETCH_COLUMN);

$paginationFilters = array_filter([
    'q' => $search,
    'category' => $category,
    'status' => $status,
], static fn ($value) => $value !== '');

$pageTitle = 'Marine Species';
$activePage = 'species';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">OUR ISLAND'S MARINE NEIGHBORS</span>
        <h1>Discover sea creatures</h1>
        <p>Explore marine life documented in Marinduque and nearby Philippine waters. Each entry notes where its occurrence is supported by available references.</p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <form class="surface-card mb-4" method="get" action="marine-species.php">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="species-search">Search species</label>
                    <input class="form-control" id="species-search" name="q" value="<?= e($search) ?>" placeholder="Common or scientific name">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="species-category">Category</label>
                    <select class="form-select" id="species-category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $option): ?>
                            <option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="species-status">Conservation status</label>
                    <select class="form-select" id="species-status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($statuses as $option): ?>
                            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary rounded-pill" aria-label="Apply filters"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </div>
            <?php if ($search !== '' || $category !== '' || $status !== ''): ?>
                <a class="small text-link d-inline-block mt-3" href="marine-species.php">Clear filters</a>
            <?php endif; ?>
        </form>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <p class="small text-secondary mb-0"><?= number_format($totalSpecies) ?> species found</p>
            <?php if ($totalPages > 1): ?><p class="small text-secondary mb-0">Page <?= $page ?> of <?= $totalPages ?></p><?php endif; ?>
        </div>

        <?php if ($species): ?>
            <div class="row g-4">
                <?php foreach ($species as $item): ?>
                    <?php $sourceUrl = $item['image_source_url'] ?: species_image_source_url($item['species_image']); ?>
                    <div class="col-sm-6 col-lg-4">
                        <article class="species-list-card">
                            <a class="species-list-link" href="species-details.php?id=<?= (int) $item['species_id'] ?>">
                                <div class="species-list-image">
                                    <img src="<?= e($item['species_image'] ?: 'assets/images/species-placeholder.svg') ?>" alt="<?= e($item['common_name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='assets/images/species-placeholder.svg';">
                                </div>
                                <div class="species-card-body">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span class="status-pill"><?= e($item['category'] ?: 'Marine species') ?></span>
                                        <span class="status-pill"><?= e($item['conservation_status'] ?: 'Not evaluated') ?></span>
                                    </div>
                                    <h2 class="h5 mt-3"><?= e($item['common_name']) ?></h2>
                                    <i><?= e($item['scientific_name']) ?></i>
                                    <p><?= e(mb_strimwidth(strip_tags($item['description']), 0, 120, '…')) ?></p>
                                    <?php if (!empty($item['occurrence_note'])): ?>
                                        <p class="species-occurrence"><?= e(mb_strimwidth(strip_tags($item['occurrence_note']), 0, 100, '…')) ?></p>
                                    <?php endif; ?>
                                    <span class="btn btn-outline-primary btn-sm rounded-pill">View details <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                </div>
                            </a>
                            <?php if ($sourceUrl): ?>
                                <div class="species-image-credit"><a href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer">Image source &amp; license</a></div>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-note">No species matched these filters.</div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="species-pagination mt-4" aria-label="Species pages">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <?php $query = http_build_query($paginationFilters + ['page' => $pageNumber]); ?>
                        <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                            <a class="page-link" href="marine-species.php?<?= e($query) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="privacy-callout mt-5">
            <b>Protect the habitat, protect the species.</b>
            Keep wildlife at a respectful distance, never remove animals or coral,
            and report injured or stranded marine animals through the incident form.
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
