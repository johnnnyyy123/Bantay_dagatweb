<?php
require_once __DIR__ . '/includes/functions.php';

$activities = db()->query("
    SELECT activity_title, description, location,
           activity_date, activity_image
    FROM cleanup_activities
    WHERE status = 'Upcoming'
      AND activity_date >= NOW()
    ORDER BY activity_date
    LIMIT 6
")->fetchAll();

$pageTitle = 'Clean Our Seas';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">SMALL CHOICES. HEALTHIER SHORES.</span>
        <h1>Clean our seas</h1>
        <p>Practical ways to care for our coast and the marine life that depends on it.</p>
    </div>
</section>

<section class="content-section">
    <div class="container">

        <div class="row g-3">

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-recycle text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Dispose of waste properly</h2>
                    <p class="help-text mb-0">Sort and secure waste, avoid single-use plastics where practical, and never leave trash on beaches or waterways.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-broom text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Join coastal clean-ups</h2>
                    <p class="help-text mb-0">Take part in organized clean-ups, use appropriate safety gear, and follow local waste handling guidance.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-fish-fins text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Respect marine animals</h2>
                    <p class="help-text mb-0">Observe wildlife from a distance. Do not touch, feed, chase, or remove marine animals and corals.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-anchor text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Fish responsibly</h2>
                    <p class="help-text mb-0">Follow local rules and seasonal restrictions. Report suspected destructive or illegal fishing activity safely.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-leaf text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Protect coastal habitats</h2>
                    <p class="help-text mb-0">Avoid damaging mangroves, seagrass beds, reefs, and nesting areas. Leave natural habitats undisturbed.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="activity-card">
                    <i class="fa-solid fa-bullhorn text-primary fs-4"></i>
                    <h2 class="h6 fw-bold mt-3">Share awareness</h2>
                    <p class="help-text mb-0">Share accurate conservation information and encourage neighbors to care for local waterways.</p>
                </div>
            </div>

        </div>

        <div class="mt-5">
            <span class="eyebrow">GET INVOLVED</span>
            <h2 class="h3 fw-bold mb-3">Upcoming coastal activities</h2>

            <?php if ($activities): ?>

                <div class="row g-3">

                    <?php foreach ($activities as $activity): ?>

                        <div class="col-md-6 col-lg-4">
                            <article class="activity-card">

                                <?php if ($activity['activity_image']): ?>
                                    <img class="w-100 rounded-3 mb-3"
                                         style="height:150px;object-fit:cover"
                                         src="<?= e($activity['activity_image']) ?>"
                                         alt="">
                                <?php endif; ?>

                                <span class="status-pill">Upcoming activity</span>

                                <h3 class="h6 fw-bold mt-3">
                                    <?= e($activity['activity_title']) ?>
                                </h3>

                                <p class="help-text">
                                    <?= e(mb_strimwidth(strip_tags($activity['description']), 0, 140, '…')) ?>
                                </p>

                                <div class="small text-secondary">
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    <?= e(date('M j, Y g:i A', strtotime($activity['activity_date']))) ?>
                                    <br>

                                    <i class="fa-solid fa-location-dot me-1 mt-2"></i>
                                    <?= e($activity['location']) ?>
                                </div>

                            </article>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-note">
                    There are no upcoming activities published at the moment.
                    Please check back soon.
                </div>

            <?php endif; ?>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
