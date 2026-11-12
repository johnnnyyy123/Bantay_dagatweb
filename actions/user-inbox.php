<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST requests only.');
}

require_user();
verify_csrf();

$action = (string) ($_POST['action'] ?? '');
$messageId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($action !== 'mark_read' || !$messageId || $messageId < 1) {
    http_response_code(400);
    exit('Invalid Inbox action.');
}

$userId = (int) current_user_id();
$statement = db()->prepare(
    'UPDATE user_inbox_messages
     SET read_at = COALESCE(read_at, NOW())
     WHERE inbox_message_id = ? AND recipient_user_id = ?'
);
$statement->execute([$messageId, $userId]);
if (!$statement->rowCount()) {
    $check = db()->prepare(
        'SELECT inbox_message_id FROM user_inbox_messages
         WHERE inbox_message_id = ? AND recipient_user_id = ?'
    );
    $check->execute([$messageId, $userId]);
    if (!$check->fetchColumn()) {
        http_response_code(404);
        exit('Inbox message not found.');
    }
}

flash('success', 'Message marked as read.');
redirect('../user-inbox-message.php?id=' . $messageId);
