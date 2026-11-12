<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_admin()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare('SELECT admin_id, full_name, email, password FROM admins WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $admin = $statement->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_id'] = (int) $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        flash('success', 'Welcome back.');
        redirect('dashboard.php');
    }
    $error = 'Email or password is incorrect.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | Bantay-Dagat</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head><body><header class="site-header"><nav class="navbar container"><a class="brand" href="../index.php"><span class="brand-mark"><i class="fa-solid fa-water"></i></span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a><a class="text-link" href="../index.php">← Back to website</a></nav></header><main class="admin-login"><form method="post" class="surface-card admin-login-card"><span class="eyebrow">AUTHORIZED PERSONNEL</span><h1 class="h3 fw-bold">Administrator login</h1><p class="help-text mb-4">Sign in to manage community reports and updates.</p><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($notice = consume_flash()): ?><div class="alert alert-<?= e($notice['kind'] === 'error' ? 'danger' : 'success') ?>"><?= e($notice['message']) ?></div><?php endif; ?><?= csrf_field() ?><div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required autocomplete="username"></div><div class="mb-4"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required autocomplete="current-password"></div><button class="btn btn-primary rounded-pill w-100">Sign in securely</button><p class="help-text mt-3 mb-0">Initial local account is listed in the installation instructions.</p></form></main></body></html>
