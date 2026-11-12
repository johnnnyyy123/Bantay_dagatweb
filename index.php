<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
$stats = ['total' => 0, 'pending' => 0, 'resolved' => 0];
foreach ($pdo->query("SELECT COUNT(*) AS total, SUM(status = 'Pending') AS pending, SUM(status = 'Resolved') AS resolved FROM reports")->fetch() as $key => $value) {
    $stats[$key] = (int) $value;
}
$speciesPageSize = 6;
$speciesPageInput = filter_input(INPUT_GET, 'species_page', FILTER_VALIDATE_INT);
$speciesCount = (int) $pdo->query('SELECT COUNT(*) FROM marine_species')->fetchColumn();
$speciesPageCount = max(1, (int) ceil($speciesCount / $speciesPageSize));
$speciesPage = min(max(1, $speciesPageInput ?: 1), $speciesPageCount);
$speciesStatement = $pdo->prepare(
    'SELECT species_id AS id, common_name, scientific_name, category, description,
            species_image, image_source_url, conservation_status
     FROM marine_species
     ORDER BY is_featured DESC, created_at DESC, species_id DESC
     LIMIT ? OFFSET ?'
);
$speciesStatement->bindValue(1, $speciesPageSize, PDO::PARAM_INT);
$speciesStatement->bindValue(2, ($speciesPage - 1) * $speciesPageSize, PDO::PARAM_INT);
$speciesStatement->execute();
$species = $speciesStatement->fetchAll(PDO::FETCH_ASSOC);

$updates = $pdo->query("
    SELECT announcement_id AS id, title, category, description,
           featured_image, created_at
    FROM announcements
    WHERE publication_status = 'Published'
    ORDER BY created_at DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Protect Our Seas';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-art" aria-hidden="true"><div class="sun"></div><div class="hero-island"></div><div class="hero-wave wave-one"></div><div class="hero-wave wave-two"></div><span class="hero-fish fish-one">≈</span><span class="hero-fish fish-two">≈</span></div>
    <div class="container hero-content">
        <div class="hero-copy"><span class="eyebrow eyebrow-light"><i class="fa-solid fa-location-dot"></i> MARINDUQUE, PHILIPPINES</span><h1>Protect our seas,<br><em>preserve our future.</em></h1><p>Help protect Marinduque's coastal environment through community reporting, local action, and ocean awareness.</p><div class="hero-actions"><a class="btn btn-aqua" href="report.php">Report an issue <i class="fa-solid fa-arrow-right ms-2"></i></a><a class="btn btn-ghost-light" href="map.php"><i class="fa-regular fa-map me-2"></i>Explore the map</a></div><div class="hero-note"><span class="note-icon"><i class="fa-solid fa-heart"></i></span> Small actions create a lasting tide of change.</div></div>
        <div class="hero-badge"><span class="badge-icon"><i class="fa-solid fa-water"></i></span><strong>One island.<br>One shared sea.</strong><small>Let's keep it thriving.</small></div>
    </div>
</section>
<section class="stats-strip"><div class="container stats-inner">
    <div class="stats-intro"><span class="eyebrow">COMMUNITY IN ACTION</span><h2>Every report makes<br>a difference.</h2></div>
    <div class="stat-item"><span class="stat-icon"><i class="fa-solid fa-file-circle-check"></i></span><strong><?= number_format($stats['total']) ?></strong><span>Reports received</span></div>
    <div class="stat-item"><span class="stat-icon stat-icon-aqua"><i class="fa-solid fa-hourglass-half"></i></span><strong><?= number_format($stats['pending']) ?></strong><span>Awaiting review</span></div>
    <div class="stat-item"><span class="stat-icon stat-icon-green"><i class="fa-solid fa-circle-check"></i></span><strong><?= number_format($stats['resolved']) ?></strong><span>Issues resolved</span></div>
</div></section>
<section class="section-pad quick-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">HOW CAN WE HELP?</span><h2>Make a difference today</h2></div><p>Find the information and tools you need to help care for our coast.</p></div>
    <div class="quick-grid">
        <a class="quick-card" href="report-status.php"><span class="quick-icon"><i class="fa-solid fa-magnifying-glass-location"></i></span><span><b>Track a report</b><small>Check an incident's progress</small></span><i class="fa-solid fa-arrow-right quick-arrow"></i></a>
        <a class="quick-card" href="map.php"><span class="quick-icon aqua"><i class="fa-solid fa-map-location-dot"></i></span><span><b>Coastal map</b><small>Explore public incident locations</small></span><i class="fa-solid fa-arrow-right quick-arrow"></i></a>
        <a class="quick-card" href="marine-species.php"><span class="quick-icon green"><i class="fa-solid fa-fish-fins"></i></span><span><b>Sea creatures</b><small>Discover local marine life</small></span><i class="fa-solid fa-arrow-right quick-arrow"></i></a>
        <a class="quick-card" href="updates.php"><span class="quick-icon coral"><i class="fa-solid fa-bullhorn"></i></span><span><b>Latest updates</b><small>News and community activities</small></span><i class="fa-solid fa-arrow-right quick-arrow"></i></a>
    </div>
</div></section>
<section class="report-cta"><div class="container cta-inner"><div class="cta-icon"><i class="fa-solid fa-life-ring"></i></div><div><span class="eyebrow eyebrow-light">SEE SOMETHING? SAY SOMETHING.</span><h2>Our coast needs your eyes.</h2><p>Found pollution, illegal fishing, or another coastal issue? Your report helps our community respond.</p></div><a href="report.php" class="btn btn-aqua">Submit a report <i class="fa-solid fa-arrow-right ms-2"></i></a></div></section>
<section class="section-pad species-section" id="marine-species">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">LIFE BELOW THE SURFACE</span><h2>Meet our marine neighbors</h2></div>
            <a class="text-link" href="marine-species.php">Explore all species <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <?php if ($species): ?>
            <div class="row g-4">
                <?php foreach ($species as $i => $item): ?>
                    <?php $sourceUrl = $item['image_source_url'] ?: species_image_source_url($item['species_image']); ?>
                    <div class="col-md-4">
                        <article class="species-card">
                            <a class="species-art species-art-<?= $i + 1 ?>" href="species-details.php?id=<?= (int) $item['id'] ?>">
                                <img class="species-cover" src="<?= e($item['species_image'] ?: 'assets/images/species-placeholder.svg') ?>" alt="<?= e($item['common_name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='assets/images/species-placeholder.svg';">
                                <span class="species-status"><?= e($item['conservation_status'] ?: 'Not evaluated') ?></span>
                                <span class="species-watermark"><i class="fa-solid fa-fish-fins"></i></span>
                            </a>
                            <div class="species-card-body">
                                <span class="species-category"><?= e($item['category'] ?: 'Marine species') ?></span>
                                <h3><?= e($item['common_name']) ?></h3>
                                <i><?= e($item['scientific_name']) ?></i>
                                <p><?= e(mb_strimwidth(strip_tags($item['description']), 0, 115, '…')) ?></p>
                                <a class="text-link" href="species-details.php?id=<?= (int) $item['id'] ?>">View details <i class="fa-solid fa-arrow-right"></i></a>
                                <?php if ($sourceUrl): ?>
                                    <div class="species-image-credit mt-2"><a href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer">Image source &amp; license</a></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($speciesPageCount > 1): ?>
                <nav class="species-home-pagination mt-4" aria-label="Featured species pages">
                    <span>Showing <?= ($speciesPage - 1) * $speciesPageSize + 1 ?>–<?= min($speciesPage * $speciesPageSize, $speciesCount) ?> of <?= number_format($speciesCount) ?> species</span>
                    <div class="d-flex gap-2">
                        <?php if ($speciesPage > 1): ?><a class="btn btn-outline-primary btn-sm rounded-pill" href="index.php?species_page=<?= $speciesPage - 1 ?>#marine-species">Previous</a><?php endif; ?>
                        <?php if ($speciesPage < $speciesPageCount): ?><a class="btn btn-primary btn-sm rounded-pill" href="index.php?species_page=<?= $speciesPage + 1 ?>#marine-species">More species</a><?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-note">Marine species information will appear here when published by an administrator.</div>
        <?php endif; ?>
    </div>
</section>
<section class="section-pad updates-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">FROM OUR COMMUNITY</span><h2>Latest from the coast</h2></div><a class="text-link" href="updates.php">All updates <i class="fa-solid fa-arrow-right"></i></a></div>
    <?php if ($updates): ?><div class="row g-4"><?php foreach ($updates as $i => $item): ?><div class="col-md-4"><article class="update-card"><div class="update-image update-image-<?= $i + 1 ?>" <?php if ($item['featured_image']): ?>style="background-image:linear-gradient(0deg,rgba(4,44,68,.15),rgba(4,44,68,.08)),url('<?= e($item['featured_image']) ?>')"<?php endif; ?>><span><?= e($item['category']) ?></span></div><div class="update-body"><small><i class="fa-regular fa-calendar me-1"></i> <?= e(date('M j, Y', strtotime($item['created_at']))) ?></small><h3><?= e($item['title']) ?></h3><p><?= e(mb_strimwidth(strip_tags($item['description']), 0, 120, '…')) ?></p><a class="text-link" href="announcement-details.php?id=<?= (int) $item['id'] ?>">Read more <i class="fa-solid fa-arrow-right"></i></a></div></article></div><?php endforeach; ?></div><?php else: ?><div class="empty-note">Community announcements will appear here as they are published.</div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
