<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_status']);
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
flash('success', 'You have signed out.');
redirect('index.php');
