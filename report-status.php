
<?php
require_once __DIR__ . '/includes/functions.php';

$reference = strtoupper(trim((string) ($_GET['reference'] ?? '')));
$report = null;
$history = [];

if ($reference !== '' && preg_match('/^BDM-(?:\d{4}-\d{4,}|\d{8}-[A-Z0-9]{6})$/', $reference)) {

    $query = db()->prepare(
        'SELECT reference_number, report_title, category, description,
         location, barangay, incident_date, status, admin_remarks,
         created_at, updated_at
         FROM reports
         WHERE reference_number = ?'
    );

    $query->execute([$reference]);
    $report = $query->fetch() ?: null;

    if ($report) {
        $query = db()->prepare(
            'SELECT previous_status, new_status, remarks, created_at
             FROM report_history
             WHERE report_id = (
                 SELECT report_id FROM reports
                 WHERE reference_number = ?
             )
             ORDER BY created_at ASC, history_id ASC'
        );

        $query->execute([$reference]);
        $history = $query->fetchAll();
    }
}

$pageTitle = 'Track a Report';
require __DIR__ . '/includes/header.php';

$steps = ($report['status'] ?? '') === 'Rejected'
    ? ['Pending', 'Under Review', 'Rejected']
    : ['Pending', 'Under Review', 'In Progress', 'Resolved'];

$recordedStatuses = array_column($history, 'new_status');
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">REPORT FOLLOW-UP</span>
        <h1>Track a report</h1>
        <p>Enter the reference number you received after submitting an incident report.</p>
    </div>
</section>

<section class="content-section">
    <div class="container" style="max-width:850px">

        <form class="surface-card mb-4" method="get">
            <label class="form-label" for="reference">
                Report reference number
            </label>

            <div class="d-flex gap-2 flex-wrap">
                <input
                    class="form-control flex-grow-1"
                    id="reference"
                    name="reference"
                    value="<?= e($reference) ?>"
                    placeholder="BDM-20261112-A8F42K"
                    required
                    style="min-width:220px"
                >

                <button class="btn btn-primary rounded-pill" type="submit">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>
                    Search report
                </button>
            </div>

            <div class="help-text mt-2">
                Reference IDs look like BDM-20261112-A8F42K. Older reports may use BDM-2026-0001.
            </div>
        </form>

        <?php if ($reference !== '' && !$report): ?>
            <div class="alert alert-info">
                We couldn't find a report with that reference number.
                Check it for typos and try again.
            </div>
        <?php endif; ?>

        <?php if ($report): ?>

            <article class="surface-card">

                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <span class="eyebrow">REPORT REFERENCE</span>

                        <h2 class="h4 fw-bold mb-1">
                            <?= e($report['reference_number']) ?>
                        </h2>

                        <p class="text-secondary small mb-0">
                            Submitted <?= e(date('M j, Y', strtotime($report['created_at']))) ?>
                        </p>
                    </div>

                    <span class="status-pill <?= e(status_class($report['status'])) ?>">
                        <?= e($report['status']) ?>
                    </span>
                </div>

                <div class="tracker">
                    <?php foreach ($steps as $i => $step):
                        $done = in_array($step, $recordedStatuses, true) && $step !== ($report['status'] ?? '');
                        $current = $step === $report['status'];
                    ?>
                        <div class="tracker-step <?= $done ? 'done' : '' ?> <?= $current ? 'current' : '' ?>">
                            <span>
                                <?= $done ? '<i class="fa-solid fa-check"></i>' : $i + 1 ?>
                            </span>
                            <?= e($step) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr>

                <div class="row g-3">

                    <div class="col-md-6">
                        <small class="help-text">Report title</small>
                        <div class="fw-semibold">
                            <?= e($report['report_title']) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="help-text">Category</small>
                        <div class="fw-semibold">
                            <?= e($report['category']) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="help-text">Location</small>
                        <div class="fw-semibold">
                            <?= e($report['location']) ?>,
                            <?= e($report['barangay']) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="help-text">Incident date</small>
                        <div class="fw-semibold">
                            <?= e(date('M j, Y', strtotime($report['incident_date']))) ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <small class="help-text">Description</small>
                        <p class="mb-0">
                            <?= nl2br(e($report['description'])) ?>
                        </p>
                    </div>

                    <?php if ($report['admin_remarks']): ?>
                        <div class="col-12">
                            <div class="privacy-callout">
                                <b>Latest update from Bantay Dagat</b>

                                <br>

                                <?= nl2br(e($report['admin_remarks'])) ?>

                                <small class="d-block mt-2">
                                    Updated
                                    <?= e(date('M j, Y g:i A', strtotime($report['updated_at']))) ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <?php if ($history): ?>
                    <hr>

                    <h3 class="h6 fw-bold">Update history</h3>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Update</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach (array_reverse($history) as $entry): ?>
                                    <tr>
                                        <td>
                                            <span class="status-pill <?= e(status_class($entry['new_status'])) ?>">
                                                <?= e($entry['new_status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($entry['remarks'] ?: 'Status updated.') ?>
                                        </td>

                                        <td>
                                            <?= e(date('M j, Y g:i A', strtotime($entry['created_at']))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </article>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
