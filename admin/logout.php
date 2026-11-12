<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(is_admin() ? 'dashboard.php' : 'login.php');
}
require_admin();
verify_csrf();
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
flash('success', 'Administrator signed out.');
redirect('login.php');
