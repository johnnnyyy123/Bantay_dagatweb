<?php
require_once __DIR__ . '/_layout.php';
$items = db()->query('SELECT message_id AS id, messages.* FROM messages ORDER BY created_at DESC LIMIT 300')->fetchAll();
admin_header('Messages and inquiries');
?>
<section class="surface-card"><h2 class="h6 fw-bold mb-3">Community messages</h2><div class="admin-table-wrap"><table class="table table-hover align-middle"><thead><tr><th>Received</th><th>From</th><th>Subject</th><th>Message</th><th>Action</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e(date('M j, Y', strtotime($item['created_at']))) ?></td><td><?= e($item['name']) ?><br><a href="mailto:<?= e($item['email']) ?>"><?= e($item['email']) ?></a></td><td><?= e($item['subject']) ?></td><td style="min-width:220px"><?= nl2br(e($item['message'])) ?></td><td><form method="post" action="../actions/admin.php"><?= csrf_field() ?><input type="hidden" name="action" value="delete_message"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-outline-danger btn-sm" data-confirm="Delete this message?">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$items): ?><tr><td colspan="5" class="text-secondary">No messages have been received.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_footer(); ?>
