<?php
require_once __DIR__ . '/includes/functions.php';
require_user();

$userId = (int) current_user_id();
$statement = db()->prepare(
    'SELECT inbox_message_id, report_id, reference_number, subject, message, read_at, created_at
     FROM user_inbox_messages
     WHERE recipient_user_id = ?
     ORDER BY created_at DESC, inbox_message_id DESC
     LIMIT 200'
);
$statement->execute([$userId]);
$messages = $statement->fetchAll();
$unreadCount = user_unread_message_count($userId);

$pageTitle = 'My Inbox';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">PRIVATE ACCOUNT MESSAGES</span>
        <h1>My Inbox</h1>
        <p>Read report updates and reference IDs sent to your account.</p>
    </div>
</section>
<section class="content-section">
    <div class="container">
        <div class="surface-card">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <h2 class="h5 fw-bold mb-0">Messages</h2>
                <span class="status-pill"><?= $unreadCount ?> unread</span>
            </div>
            <?php if ($messages): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($messages as $message): ?>
                        <?php $isUnread = $message['read_at'] === null; ?>
                        <article class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="status-pill <?= $isUnread ? 'status-under-review' : '' ?>"><?= $isUnread ? 'Unread' : 'Read' ?></span>
                                        <span class="help-text"><?= e(date('M j, Y g:i A', strtotime($message['created_at']))) ?></span>
                                    </div>
                                    <a class="fw-bold" href="user-inbox-message.php?id=<?= (int) $message['inbox_message_id'] ?>"><?= e($message['subject']) ?></a>
                                    <div class="small text-secondary mt-1">Report reference: <strong><?= e($message['reference_number']) ?></strong></div>
                                    <p class="small text-secondary mb-0 mt-2"><?= e(mb_strimwidth(strip_tags($message['message']), 0, 180, '...')) ?></p>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a class="btn btn-outline-primary btn-sm rounded-pill" href="user-inbox-message.php?id=<?= (int) $message['inbox_message_id'] ?>">Open</a>
                                    <?php if ($isUnread): ?>
                                        <form method="post" action="actions/user-inbox.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?= (int) $message['inbox_message_id'] ?>">
                                            <button class="btn btn-outline-secondary btn-sm rounded-pill" type="submit">Mark read</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-note">No messages have been sent to your Inbox yet.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
