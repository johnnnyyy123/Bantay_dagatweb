
<?php
require_once __DIR__ . '/includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$statement = db()->prepare(
    'SELECT * FROM marine_species WHERE species_id = ?'
);
$statement->execute([$id ?: 0]);
$item = $statement->fetch();

$related = [];
$referenceUrls = [];
$imageSourceUrl = null;

if ($item) {
    $referenceUrls = array_values(array_filter(
        array_map('trim', preg_split('/\R/', (string) ($item['reference_url'] ?? '')) ?: []),
        static fn (string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false
    ));
    $imageSourceUrl = $item['image_source_url'] ?: species_image_source_url($item['species_image']);
    $statement = db()->prepare(
        'SELECT species_id, common_name
         FROM marine_species
         WHERE species_id <> ?
         ORDER BY RAND()
         LIMIT 3'
    );

    $statement->execute([$item['species_id']]);
    $related = $statement->fetchAll();
}

$pageTitle = $item ? $item['common_name'] : 'Species not found';
$activePage = 'species';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">MARINE SPECIES GUIDE</span>

        <h1><?= e($item['common_name'] ?? 'Species not found') ?></h1>

        <?php if ($item): ?>
            <p><i><?= e($item['scientific_name']) ?></i></p>
        <?php endif; ?>
    </div>
</section>

<section class="content-section">
    <div class="container" style="max-width:900px">

        <?php if ($item): ?>

            <article class="surface-card">

                <div class="detail-image mb-4">
                    <img class="species-detail-photo" src="<?= e($item['species_image'] ?: 'assets/images/species-placeholder.svg') ?>" alt="<?= e($item['common_name']) ?>" onerror="this.onerror=null;this.src='assets/images/species-placeholder.svg';">
                </div>
                <?php if ($imageSourceUrl): ?>
                    <p class="species-image-credit mb-4"><a href="<?= e($imageSourceUrl) ?>" target="_blank" rel="noopener noreferrer">Image source &amp; license</a></p>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2">
                    <span class="status-pill"><?= e($item['category'] ?: 'Marine species') ?></span>
                    <span class="status-pill"><?= e($item['conservation_status'] ?: 'Not evaluated') ?></span>
                </div>

                <div class="prose">

                    <p class="mt-3">
                        <?= nl2br(e($item['description'])) ?>
                    </p>

                    <h2>Habitat</h2>
                    <p>
                        <?= nl2br(e($item['habitat'] ?: 'Habitat information is being updated.')) ?>
                    </p>

                    <h2>Occurrence</h2>
                    <p><?= nl2br(e($item['occurrence_note'] ?: 'Local occurrence has not been verified.')) ?></p>

                    <h2>Diet</h2>
                    <p>
                        <?= nl2br(e($item['diet'] ?: 'Diet information is being updated.')) ?>
                    </p>

                    <h2>Interesting facts</h2>
                    <p>
                        <?= nl2br(e($item['interesting_facts'] ?: 'More facts will be added soon.')) ?>
                    </p>

                    <?php if ($referenceUrls): ?>
                        <h2>References</h2>
                        <ul>
                            <?php foreach ($referenceUrls as $index => $referenceUrl): ?>
                                <?php $referenceHost = parse_url($referenceUrl, PHP_URL_HOST) ?: 'Source'; ?>
                                <li><a href="<?= e($referenceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($referenceHost) ?> (reference <?= $index + 1 ?>)</a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>
            </article>

            <?php if ($related): ?>

                <div class="mt-4">
                    <h2 class="h5 fw-bold mb-3">Related species</h2>

                    <?php foreach ($related as $relatedItem): ?>

                        <a href="species-details.php?id=<?= (int) $relatedItem['species_id'] ?>"
                           class="btn btn-outline-primary btn-sm rounded-pill me-2 mb-2">

                            <?= e($relatedItem['common_name']) ?>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="empty-note">
                This species is unavailable.
                <a class="text-link" href="marine-species.php">
                    View all species
                </a>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
