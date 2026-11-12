<?php
require_once __DIR__ . '/_layout.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$selected = null;
$history = [];
$sentMessages = [];
$linkedUser = null;
if ($id) {
    $statement = db()->prepare('SELECT reports.*, reports.report_id AS id FROM reports WHERE report_id = ?');
    $statement->execute([$id]);
    $selected = $statement->fetch();
    if ($selected) {
        if ($selected['user_id']) {
            $statement = db()->prepare('SELECT full_name, email, account_status FROM users WHERE user_id = ? LIMIT 1');
            $statement->execute([(int) $selected['user_id']]);
            $linkedUser = $statement->fetch() ?: null;
        }
        $statement = db()->prepare('SELECT h.*, a.full_name FROM report_history h LEFT JOIN admins a ON a.admin_id=h.admin_id WHERE h.report_id=? ORDER BY h.created_at DESC, h.history_id DESC');
        $statement->execute([$id]);
        $history = $statement->fetchAll();
        $statement = db()->prepare(
            'SELECT inbox_message_id, reference_number, subject, message, read_at, created_at
             FROM user_inbox_messages
             WHERE report_id = ?
             ORDER BY created_at DESC, inbox_message_id DESC'
        );
        $statement->execute([$id]);
        $sentMessages = $statement->fetchAll();
    }
}
$sql = 'SELECT report_id AS id, reference_number, reference_sent_at, user_id, reporter_name, category, location, barangay, created_at, status FROM reports WHERE 1=1';
$params = [];
if (in_array($statusFilter, report_statuses(), true)) { $sql .= ' AND status = ?'; $params[] = $statusFilter; }
if ($search !== '') { $sql .= ' AND (reference_number LIKE ? OR reporter_name LIKE ? OR report_title LIKE ? OR barangay LIKE ?)'; array_push($params, ...array_fill(0, 4, '%' . $search . '%')); }
$sql .= ' ORDER BY created_at DESC LIMIT 300';
$statement = db()->prepare($sql);
$statement->execute($params);
$reports = $statement->fetchAll();
admin_header('Report Inbox');
?>
<section class="surface-card mb-4"><form method="get" class="row g-2 align-items-end"><div class="col-md-7"><label class="form-label">Search reports</label><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Reference, reporter, title, or barangay"></div><div class="col-md-3"><label class="form-label">Status filter</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (report_statuses() as $status): ?><option <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div><div class="col-md-2 d-grid"><button class="btn btn-primary rounded-pill">Filter</button></div></form></section>
<?php if ($selected): ?><section class="surface-card mb-4"><div class="d-flex justify-content-between gap-2"><div><span class="eyebrow">PRIVATE REPORT DETAILS</span><h2 class="h5 fw-bold"><?= e($selected['reference_number'] ?: 'Reference not assigned') ?> · <?= e($selected['report_title']) ?></h2></div><a class="text-link" href="manage-reports.php">Close details</a></div><div class="row g-3 small mt-1"><div class="col-md-4"><b>Reporter:</b> <?= e($selected['reporter_name']) ?></div><div class="col-md-4"><b>Email:</b> <?= e($selected['email']) ?></div><div class="col-md-4"><b>Phone:</b> <?= e($selected['contact_number']) ?></div><div class="col-md-4"><b>Category:</b> <?= e($selected['category']) ?></div><div class="col-md-4"><b>Incident:</b> <?= e($selected['incident_date']) ?> <?= e(substr($selected['incident_time'],0,5)) ?></div><div class="col-md-4"><b>Location:</b> <?= e($selected['location']) ?>, <?= e($selected['barangay']) ?></div><div class="col-md-4"><b>Received:</b> <?= e(date('M j, Y g:i A', strtotime($selected['created_at']))) ?></div><div class="col-12"><b>Description:</b><br><?= nl2br(e($selected['description'])) ?></div><?php if ($selected['evidence_image']): ?><div class="col-12"><a class="btn btn-outline-primary btn-sm rounded-pill" target="_blank" rel="noopener" href="evidence.php?id=<?= (int) $selected['id'] ?>"><i class="fa-regular fa-image me-1"></i>View private evidence</a></div><?php endif; ?>
<div class="col-12"><form class="row g-2 align-items-end" action="../actions/admin.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update_report"><input type="hidden" name="id" value="<?= (int) $selected['id'] ?>"><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (report_statuses() as $status): ?><option <?= $selected['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div><div class="col-md-7"><label class="form-label">Administrator remarks</label><input class="form-control" name="remarks" value="<?= e($selected['admin_remarks']) ?>" maxlength="3000"></div><div class="col-md-2 d-grid"><button class="btn btn-primary rounded-pill">Save update</button></div></form></div></div>
<?php if ($history): ?><hr><h3 class="h6 fw-bold">Status history</h3><?php foreach ($history as $entry): ?><p class="small mb-2"><span class="status-pill <?= e(status_class($entry['new_status'])) ?>"><?= e($entry['new_status']) ?></span> <?= e($entry['remarks'] ?: '') ?> <span class="text-secondary"><?= e(date('M j, Y g:i A', strtotime($entry['created_at']))) ?></span></p><?php endforeach; ?><?php endif; ?></section>
<section class="surface-card mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <span class="eyebrow">REPORT FOLLOW-UP</span>
            <h2 class="h5 fw-bold">Reference ID and User Inbox</h2>
        </div>
        <?php if ($selected['reference_sent_at']): ?>
            <span class="status-pill status-resolved">Sent to Inbox <?= e(date('M j, Y g:i A', strtotime($selected['reference_sent_at']))) ?></span>
        <?php elseif ($selected['reference_number']): ?>
            <span class="status-pill status-pending">Not sent to Inbox</span>
        <?php else: ?>
            <span class="status-pill status-pending">Reference not generated</span>
        <?php endif; ?>
    </div>
    <?php if ($selected['reference_number']): ?>
        <p class="mb-2">Reference ID: <code class="fs-6"><?= e($selected['reference_number']) ?></code></p>
    <?php endif; ?>
    <?php if ($linkedUser): ?>
        <p class="small text-secondary">Inbox recipient: <?= e($linkedUser['full_name']) ?> (<?= e($linkedUser['email']) ?>, <?= e($linkedUser['account_status']) ?>)</p>
    <?php else: ?>
        <div class="alert alert-info py-2">Guest report: no user account was created, so the Reference ID cannot be delivered to a private Inbox. Use the reporter's contact details above for follow-up.</div>
    <?php endif; ?>
    <?php if (!$selected['reference_sent_at'] && !$selected['reference_number']): ?>
        <form method="post" action="../actions/admin.php" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generate_report_reference">
            <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
            <button class="btn btn-outline-primary rounded-pill" type="submit">Generate Reference ID</button>
        </form>
    <?php endif; ?>
    <?php if ($linkedUser && $linkedUser['account_status'] === 'Active' && $selected['reference_number'] && !$selected['reference_sent_at']): ?>
        <form method="post" action="../actions/admin.php" class="surface-card bg-light">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_report_reference">
            <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
            <label class="form-label" for="reference-message">Message to the report owner</label>
            <textarea class="form-control mb-3" id="reference-message" name="message" rows="4" maxlength="5000" required placeholder="Include any next steps or details for the reporter."></textarea>
            <button class="btn btn-primary rounded-pill" type="submit">Send Reference ID to Inbox</button>
        </form>
    <?php elseif ($linkedUser && $linkedUser['account_status'] !== 'Active' && !$selected['reference_sent_at']): ?>
        <p class="small text-warning-emphasis">This user's account is not active, so an Inbox message cannot be delivered.</p>
    <?php endif; ?>
    <?php if ($sentMessages): ?>
        <hr>
        <h3 class="h6 fw-bold">Messages previously sent for this report</h3>
        <?php foreach ($sentMessages as $sentMessage): ?>
            <article class="border rounded-3 p-3 mb-2">
                <div class="d-flex justify-content-between gap-2 flex-wrap">
                    <strong><?= e($sentMessage['subject']) ?></strong>
                    <span class="help-text"><?= e(date('M j, Y g:i A', strtotime($sentMessage['created_at']))) ?></span>
                </div>
                <p class="small my-2"><?= nl2br(e($sentMessage['message'])) ?></p>
                <span class="help-text">Reference: <?= e($sentMessage['reference_number']) ?> · <?= $sentMessage['read_at'] ? 'Read by user' : 'Unread' ?></span>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="surface-card"><h2 class="h6 fw-bold mb-3">Incident reports <span class="text-secondary fw-normal">(<?= count($reports) ?>)</span></h2><div class="admin-table-wrap"><table class="table table-hover align-middle"><thead><tr><th>Reference</th><th>Reporter</th><th>Category</th><th>Location</th><th>Date received</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($reports as $report): ?><tr><td><?= e($report['reference_number'] ?: 'Not assigned') ?><br><span class="help-text"><?= $report['reference_sent_at'] ? 'Sent to Inbox' : 'Not sent to Inbox' ?></span></td><td><?= e($report['reporter_name']) ?></td><td><?= e($report['category']) ?></td><td><?= e($report['location']) ?>, <?= e($report['barangay']) ?></td><td><?= e(date('M j, Y', strtotime($report['created_at']))) ?></td><td><span class="status-pill <?= e(status_class($report['status'])) ?>"><?= e($report['status']) ?></span></td><td class="text-nowrap"><a class="btn btn-outline-primary btn-sm" href="manage-reports.php?id=<?= (int) $report['id'] ?>">Review / update</a><form class="d-inline" method="post" action="../actions/admin.php"><?= csrf_field() ?><input type="hidden" name="action" value="delete_report"><input type="hidden" name="id" value="<?= (int) $report['id'] ?>"><button class="btn btn-outline-danger btn-sm" data-confirm="Permanently delete this report and its history?">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$reports): ?><tr><td colspan="7" class="text-secondary">No matching reports found.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_footer(); ?>
