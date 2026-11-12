<?php
require_once __DIR__ . '/includes/functions.php';

if (is_user_logged_in()) {
    redirect('profile.php');
}

$errors = [];
$fullName = '';
$email = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || mb_strlen($fullName, 'UTF-8') > 150) {
        $errors[] = 'Enter your full name (up to 150 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/\A[a-z0-9][a-z0-9._-]{2,59}\z/', $username)) {
        $errors[] = 'Username must be 3-60 characters and use letters, numbers, dots, underscores, or hyphens.';
    }
    if (strlen($password) < 12 || strlen($password) > 72) {
        $errors[] = 'Password must be between 12 and 72 bytes.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        $statement = db()->prepare('SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $statement->execute([$email, $username]);
        if ($statement->fetch()) {
            $errors[] = 'That email address or username is already registered.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $statement = db()->prepare(
                    'INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)'
                );
                $statement->execute([$fullName, $email, $username, $hash]);
                flash('success', 'Your account has been created. You can now sign in.');
                redirect('login.php');
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    $errors[] = 'That email address or username is already registered.';
                } else {
                    error_log('User registration failed: ' . $exception->getMessage());
                    $errors[] = 'Your account could not be created. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Create an Account';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-login">
    <div class="surface-card admin-login-card">
        <span class="eyebrow">COMMUNITY COASTAL WATCH</span>
        <h1 class="h3 fw-bold mb-2">Create your account</h1>
        <p class="text-secondary small mb-4">Register to manage your Bantay-Dagat account.</p>
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="full_name">Full Name</label>
                <input class="form-control" id="full_name" name="full_name" maxlength="150" value="<?= e($fullName) ?>" required autocomplete="name">
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" id="username" name="username" minlength="3" maxlength="60" pattern="[A-Za-z0-9][A-Za-z0-9._\-]{2,59}" value="<?= e($username) ?>" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
                <div class="help-text mt-1">Use at least 12 characters.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary rounded-pill w-100" type="submit">Create Account</button>
        </form>
        <p class="small text-center mt-4 mb-0">Already registered? <a class="text-link" href="login.php">Sign in</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
