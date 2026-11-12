<?php
require_once __DIR__ . '/includes/functions.php';
require_user();

$messageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = (int) current_user_id();
if (!$messageId || $messageId < 1) {
    flash('error', 'That Inbox message could not be found.');
    redirect('user-inbox.php');
}

$statement = db()->prepare(
    'SELECT inbox_message_id, report_id, reference_number, subject, message, read_at, created_at
     FROM user_inbox_messages
     WHERE inbox_message_id = ? AND recipient_user_id = ?
     LIMIT 1'
);
$statement->execute([$messageId, $userId]);
$inboxMessage = $statement->fetch();
if (!$inboxMessage) {
    flash('error', 'That Inbox message could not be found.');
    redirect('user-inbox.php');
}

$pageTitle = 'Inbox Message';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">PRIVATE ACCOUNT MESSAGE</span>
        <h1><?= e($inboxMessage['subject']) ?></h1>
        <p>Received <?= e(date('M j, Y g:i A', strtotime($inboxMessage['created_at']))) ?></p>
    </div>
</section>
<section class="content-section">
    <div class="container" style="max-width: 850px">
        <article class="surface-card">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <span class="eyebrow">REPORT REFERENCE</span>
                    <div class="input-group mb-3" style="max-width: 420px">
                        <input class="form-control fw-bold" id="report-reference" value="<?= e($inboxMessage['reference_number']) ?>" readonly aria-label="Report Reference ID">
                        <button class="btn btn-outline-primary" type="button" id="copy-reference">Copy</button>
                    </div>
                    <div class="help-text" id="copy-status" aria-live="polite">Use this reference on the Report Status page.</div>
                </div>
                <span class="status-pill <?= $inboxMessage['read_at'] === null ? 'status-under-review' : '' ?>"><?= $inboxMessage['read_at'] === null ? 'Unread' : 'Read' ?></span>
            </div>
            <hr>
            <div class="prose"><p><?= nl2br(e($inboxMessage['message'])) ?></p></div>
            <div class="d-flex gap-2 flex-wrap mt-4">
                <a class="btn btn-primary rounded-pill" href="report-status.php?reference=<?= urlencode($inboxMessage['reference_number']) ?>">Track this report</a>
                <a class="btn btn-outline-secondary rounded-pill" href="user-inbox.php">Back to Inbox</a>
                <?php if ($inboxMessage['read_at'] === null): ?>
                    <form method="post" action="actions/user-inbox.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="id" value="<?= (int) $inboxMessage['inbox_message_id'] ?>">
                        <button class="btn btn-outline-primary rounded-pill" type="submit">Mark as read</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
<script>
document.getElementById('copy-reference').addEventListener('click', async () => {
    const reference = document.getElementById('report-reference').value;
    const status = document.getElementById('copy-status');
    try {
        await navigator.clipboard.writeText(reference);
        status.textContent = 'Reference ID copied.';
    } catch (error) {
        status.textContent = 'Copy was unavailable. Select the Reference ID and copy it manually.';
    }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
