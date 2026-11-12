<?php
require_once __DIR__ . '/includes/functions.php';
require_user();

$userId = (int) $_SESSION['user_id'];
$errors = [];
$fullName = '';
$email = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));

    if ($fullName === '' || mb_strlen($fullName, 'UTF-8') > 150) {
        $errors[] = 'Enter your full name (up to 150 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/\A[a-z0-9][a-z0-9._-]{2,59}\z/', $username)) {
        $errors[] = 'Username must be 3-60 characters and use letters, numbers, dots, underscores, or hyphens.';
    }

    if (!$errors) {
        $statement = db()->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        $statement->execute([$email, $userId]);
        if ($statement->fetch()) {
            $errors[] = 'That email address is already in use.';
        }

        $statement = db()->prepare('SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1');
        $statement->execute([$username, $userId]);
        if ($statement->fetch()) {
            $errors[] = 'That username is already in use.';
        }
    }

    if (!$errors) {
        try {
            $statement = db()->prepare(
                'UPDATE users SET full_name = ?, email = ?, username = ? WHERE user_id = ?'
            );
            $statement->execute([$fullName, $email, $username, $userId]);
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            flash('success', 'Your profile has been updated.');
            redirect('profile.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors[] = 'That email address or username is already in use.';
            } else {
                error_log('User profile update failed: ' . $exception->getMessage());
                $errors[] = 'Your profile could not be updated. Please try again.';
            }
        }
    }
}

$statement = db()->prepare('SELECT full_name, email, username, account_status FROM users WHERE user_id = ? LIMIT 1');
$statement->execute([$userId]);
$profile = $statement->fetch();
if (!$profile) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_status']);
    flash('error', 'Your account could not be found. Please sign in again.');
    redirect('login.php');
}
$accountStatus = (string) $profile['account_status'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fullName = (string) $profile['full_name'];
    $email = (string) $profile['email'];
    $username = (string) $profile['username'];
}

$pageTitle = 'My Account';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow-light">COMMUNITY COASTAL WATCH</span>
        <h1>My Account</h1>
        <p>Review and update your account information.</p>
    </div>
</section>
<section class="content-section">
    <div class="container">
        <div class="surface-card mx-auto" style="max-width: 720px">
            <h2 class="h4 fw-bold mb-4">Profile Information</h2>
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <form method="post" action="profile.php">
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
                <div class="mb-4">
                    <label class="form-label" for="account_status">Account Status</label>
                    <input class="form-control" id="account_status" value="<?= e($accountStatus) ?>" readonly>
                    <div class="help-text mt-1">Only an administrator can change your account status.</div>
                </div>
                <button class="btn btn-primary rounded-pill" type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
