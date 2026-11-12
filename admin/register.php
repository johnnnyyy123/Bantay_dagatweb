
<?php
session_start();

require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if ($full_name === '' || $email === '' ||
        $password === '' || $confirm_password === '') {
        $errors[] = "Please fill in all fields.";
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            // Check if email already exists
            $check = $pdo->prepare(
                "SELECT admin_id FROM admins WHERE email = ? LIMIT 1"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = "This email is already registered.";
            } else {

                // Securely hash password
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert new admin
                $insert = $pdo->prepare(
                    "INSERT INTO admins (full_name, email, password)
                     VALUES (?, ?, ?)"
                );

                $insert->execute([
                    $full_name,
                    $email,
                    $hashed_password
                ]);

                $success = "Admin account registered successfully!";
                
                // Clear form values
                $_POST = [];
            }

        } catch (PDOException $e) {
            error_log("Admin registration error: " . $e->getMessage());
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Registration | Bantay Dagat</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px 15px;
    background: linear-gradient(135deg, #063b5b, #087e9c, #54c5c2);
}

.register-container {
    width: 100%;
    max-width: 450px;
    background: white;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 10px 35px rgba(0,0,0,.2);
}

.logo {
    text-align: center;
    margin-bottom: 25px;
}

.logo h1 {
    color: #087e9c;
    font-size: 27px;
    margin-bottom: 6px;
}

.logo p {
    color: #777;
    font-size: 12px;
    letter-spacing: 1px;
}

h2 {
    text-align: center;
    color: #123c50;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #777;
    font-size: 13px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: bold;
    color: #294b5a;
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d4e0e5;
    border-radius: 7px;
    font-size: 14px;
    outline: none;
}

input:focus {
    border-color: #0789a5;
}

.password-field {
    position: relative;
}

.password-field input {
    padding-right: 60px;
}

.toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    color: #087e9c;
    cursor: pointer;
}

.register-btn {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 7px;
    background: #087e9c;
    color: white;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 8px;
}

.register-btn:hover {
    background: #05647d;
}

.message {
    padding: 12px;
    margin-bottom: 18px;
    border-radius: 7px;
    font-size: 13px;
}

.error {
    color: #a32121;
    background: #ffe6e6;
}

.success {
    color: #176b39;
    background: #e5f7eb;
}

.login-link {
    text-align: center;
    margin-top: 22px;
    font-size: 14px;
    color: #666;
}

.login-link a {
    color: #087e9c;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="register-container">

    <div class="logo">
        <h1>🌊 BANTAY DAGAT</h1>
        <p>MARINE CONSERVATION SYSTEM</p>
    </div>

    <h2>Create Admin Account</h2>
    <p class="subtitle">
        Register to manage the Bantay Dagat website.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="login.php">Go to Admin Login</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="form-group">
            <label>Full Name</label>
            <input
                type="text"
                name="full_name"
                placeholder="Enter your full name"
                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="password-field">
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Create a password"
                    minlength="8"
                    required>
                <button type="button" class="toggle"
                    onclick="togglePassword('password', this)">
                    Show
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="password-field">
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    placeholder="Confirm your password"
                    minlength="8"
                    required>
                <button type="button" class="toggle"
                    onclick="togglePassword('confirm_password', this)">
                    Show
                </button>
            </div>
        </div>

        <button type="submit" class="register-btn">
            Register Admin
        </button>

    </form>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login Here</a>
    </div>

</div>

<script>
function togglePassword(id, button) {
    const field = document.getElementById(id);

    if (field.type === "password") {
        field.type = "text";
        button.textContent = "Hide";
    } else {
        field.type = "password";
        button.textContent = "Show";
    }
}
</script>

</body>
</html>
