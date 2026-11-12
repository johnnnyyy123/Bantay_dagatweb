<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$table = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
if (!$table) {
    throw new RuntimeException('The users table does not exist. Import database\\database.sql first.');
}

$lock = $pdo->query("SELECT GET_LOCK('bantay_dagat_user_auth_migration', 10)")->fetchColumn();
if ((int) $lock !== 1) {
    throw new RuntimeException('Could not acquire the user-authentication migration lock.');
}

try {
    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
    $hasUsername = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'username') {
            $hasUsername = true;
            break;
        }
    }

    $addedUsernameColumn = false;
    if (!$hasUsername) {
        $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER email');
        $addedUsernameColumn = true;
    }

    $users = $pdo->query('SELECT user_id, email, username FROM users ORDER BY user_id')->fetchAll(PDO::FETCH_ASSOC);
    $knownNames = [];
    foreach ($users as $user) {
        $value = trim((string) ($user['username'] ?? ''));
        if ($value === '') {
            continue;
        }
        $normalized = strtolower($value);
        if (isset($knownNames[$normalized])) {
            throw new RuntimeException('Duplicate usernames exist; resolve them before running this migration.');
        }
        $knownNames[$normalized] = true;
    }

    $update = $pdo->prepare("UPDATE users SET username = ? WHERE user_id = ? AND (username IS NULL OR TRIM(username) = '')");
    foreach ($users as $user) {
        if (trim((string) ($user['username'] ?? '')) !== '') {
            continue;
        }

        $emailParts = explode('@', (string) $user['email'], 2);
        $base = strtolower((string) ($emailParts[0] ?? ''));
        $base = preg_replace('/[^a-z0-9._-]/', '', $base);
        $base = trim((string) $base, '._-');
        if (strlen($base) < 3) {
            $base = 'user';
        }
        $base = substr($base, 0, 60);
        $candidate = $base;
        $suffixNumber = 1;
        while (isset($knownNames[strtolower($candidate)])) {
            $suffixNumber++;
            $suffix = '_' . $suffixNumber;
            $candidate = substr($base, 0, 60 - strlen($suffix)) . $suffix;
        }

        $update->execute([$candidate, (int) $user['user_id']]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Could not assign a username to user ID ' . (int) $user['user_id'] . '.');
        }
        $knownNames[strtolower($candidate)] = true;
    }

    $duplicates = $pdo->query(
        "SELECT username FROM users WHERE username IS NOT NULL AND username <> ''
         GROUP BY username HAVING COUNT(*) > 1 LIMIT 1"
    )->fetchColumn();
    if ($duplicates !== false) {
        throw new RuntimeException('Duplicate usernames exist under the database collation; resolve them before continuing.');
    }

    $indexes = [];
    foreach ($pdo->query('SHOW INDEX FROM users')->fetchAll(PDO::FETCH_ASSOC) as $index) {
        $indexes[$index['Key_name']][] = $index;
    }
    $hasUniqueUsernameIndex = false;
    foreach ($indexes as $indexName => $indexColumns) {
        if (
            count($indexColumns) === 1
            && $indexColumns[0]['Column_name'] === 'username'
            && (int) $indexColumns[0]['Non_unique'] === 0
        ) {
            $hasUniqueUsernameIndex = true;
        }
    }

    if ($addedUsernameColumn) {
        $pdo->exec('ALTER TABLE users MODIFY username VARCHAR(60) NOT NULL');
    }
    if (!$hasUniqueUsernameIndex) {
        if (isset($indexes['uq_users_username'])) {
            throw new RuntimeException('Index name uq_users_username is already in use by another index.');
        }
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)');
    }

    echo "User-authentication schema is ready. Existing user records were preserved.\n";
} finally {
    $pdo->query("SELECT RELEASE_LOCK('bantay_dagat_user_auth_migration')");
}
