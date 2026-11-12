<?php
require_once __DIR__ . '/includes/functions.php';

if (is_user_logged_in()) {
    redirect('profile.php');
}

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = strtolower(trim((string) ($_POST['identifier'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = 'Enter your email or username and password.';
    } else {
        $statement = db()->prepare(
            'SELECT user_id, full_name, email, username, password, account_status
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1'
        );
        $statement->execute([$identifier, $identifier]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'The email/username or password is incorrect.';
        } elseif ($user['account_status'] !== 'Active') {
            $error = 'Your account is ' . strtolower((string) $user['account_status']) . '. Please contact an administrator.';
        } else {
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = db()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                $update->execute([$hash, (int) $user['user_id']]);
            }

            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_status'] = $user['account_status'];
            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect('index.php');
        }
    }
}

$pageTitle = 'User Login';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-login">
    <div class="surface-card admin-login-card">
        <span class="eyebrow">COMMUNITY COASTAL WATCH</span>
        <h1 class="h3 fw-bold mb-2">Sign in</h1>
        <p class="text-secondary small mb-4">Access your Bantay-Dagat account.</p>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="identifier">Email or Username</label>
                <input class="form-control" id="identifier" name="identifier" value="<?= e($identifier) ?>" required autocomplete="username" autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary rounded-pill w-100" type="submit">Login</button>
        </form>
        <p class="small text-center mt-4 mb-0">Don't have an account? <a class="text-link" href="register.php">Register</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
