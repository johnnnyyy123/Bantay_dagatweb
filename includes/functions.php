<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $provided = (string) ($_POST['csrf_token'] ?? '');
    if ($provided === '' || !hash_equals(csrf_token(), $provided)) {
        http_response_code(403);
        exit('The form expired. Return to the page and try again.');
    }
}

function flash(string $kind, string $message): void
{
    $_SESSION['flash'] = ['kind' => $kind, 'message' => $message];
}

function consume_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function is_user_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_name(): string
{
    return (string) ($_SESSION['user_name'] ?? '');
}

function current_user_id(): ?int
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    return $userId > 0 ? $userId : null;
}

function active_user_id(): ?int
{
    $userId = current_user_id();
    if ($userId === null) {
        return null;
    }

    $statement = db()->prepare("SELECT user_id FROM users WHERE user_id = ? AND account_status = 'Active' LIMIT 1");
    $statement->execute([$userId]);
    if (!$statement->fetchColumn()) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_status']);
        return null;
    }

    return $userId;
}

function user_unread_message_count(int $userId): int
{
    $statement = db()->prepare(
        'SELECT COUNT(*) FROM user_inbox_messages WHERE recipient_user_id = ? AND read_at IS NULL'
    );
    $statement->execute([$userId]);
    return (int) $statement->fetchColumn();
}

function require_user(): void
{
    $userId = active_user_id();
    if ($userId === null) {
        flash('error', 'Please sign in to access your account.');
        redirect('login.php');
    }

    $statement = db()->prepare('SELECT full_name, email, account_status FROM users WHERE user_id = ? LIMIT 1');
    $statement->execute([$userId]);
    $user = $statement->fetch();
    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_status']);
        flash('error', 'Your account is not active. Please contact an administrator.');
        redirect('login.php');
    }

    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['account_status'];
}

function app_root_url(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $root = str_replace('\\', '/', dirname(dirname($script)));
    return $root === '/' || $root === '.' ? '' : rtrim($root, '/');
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('error', 'Please sign in to access the admin dashboard.');
        redirect(app_root_url() . '/admin/login.php');
    }
}

function upload_image(string $field, string $folder): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeds the 5 MB limit.');
    }
    if (!is_uploaded_file($file['tmp_name']) || filesize($file['tmp_name']) > 5 * 1024 * 1024) {
        throw new RuntimeException('The uploaded image is invalid or exceeds the 5 MB limit.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG, or WebP image.');
    }
    $relative = 'uploads/' . $folder . '/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = dirname(__DIR__) . '/' . $relative;
    if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true) && !is_dir(dirname($destination))) {
        throw new RuntimeException('The upload folder could not be created.');
    }
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The uploaded image could not be saved.');
    }
    return $relative;
}

function find_species_image_online(string $commonName, string $scientificName): ?string
{
    if (!empty($GLOBALS['commons_image_lookup_rate_limited'])) {
        return null;
    }

    if (!function_exists('curl_init')) {
        error_log('Marine species image lookup requires the PHP cURL extension.');
        return null;
    }

    $searchTerms = array_unique(array_filter([
        trim($commonName . ' ' . $scientificName),
        trim($commonName),
        trim($scientificName),
    ]));

    foreach ($searchTerms as $searchTerm) {
        $apiUrl = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
            'action' => 'query',
            'generator' => 'search',
            'gsrsearch' => $searchTerm,
            'gsrnamespace' => 6,
            'gsrlimit' => 10,
            'prop' => 'imageinfo',
            'iiprop' => 'url|mime|extmetadata',
            'iiurlwidth' => 960,
            'format' => 'json',
        ], '', '&', PHP_QUERY_RFC3986);

        $lastRequest = (float) ($GLOBALS['commons_image_lookup_last_request'] ?? 0);
        $waitMicroseconds = 5000000 - (int) ((microtime(true) - $lastRequest) * 1000000);
        if ($waitMicroseconds > 0) {
            usleep($waitMicroseconds);
        }
        $GLOBALS['commons_image_lookup_last_request'] = microtime(true);

        $curl = curl_init($apiUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_USERAGENT => 'BantayDagatMarinduque/1.0 (marine species image lookup)',
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($response === false) {
            error_log('Wikimedia Commons image lookup failed: ' . curl_error($curl));
            curl_close($curl);
            continue;
        }
        curl_close($curl);

        if ($status === 429) {
            $GLOBALS['commons_image_lookup_rate_limited'] = true;
            error_log('Wikimedia Commons image lookup was rate-limited.');
            return null;
        }
        if ($status !== 200) {
            error_log('Wikimedia Commons image lookup returned HTTP ' . $status . '.');
            continue;
        }

        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            error_log('Wikimedia Commons returned invalid JSON: ' . $exception->getMessage());
            continue;
        }
        if (!is_array($data)) {
            error_log('Wikimedia Commons returned an unexpected response.');
            continue;
        }

        $common = mb_strtolower($commonName, 'UTF-8');
        $scientific = mb_strtolower($scientificName, 'UTF-8');
        $bestUrl = null;
        $bestScore = 0;

        foreach (($data['query']['pages'] ?? []) as $page) {
            $title = (string) ($page['title'] ?? '');
            if (!preg_match('/\.(jpe?g|png|webp)$/i', $title)) {
                continue;
            }

            $imageInfo = $page['imageinfo'][0] ?? [];
            $metadata = $imageInfo['extmetadata'] ?? [];
            $license = trim(strip_tags((string) ($metadata['LicenseShortName']['value'] ?? '')));
            $imageUrl = (string) preg_replace('/\?.*$/', '', (string) ($imageInfo['thumburl'] ?? ''));
            if (strlen($imageUrl) > 255) {
                $filename = preg_replace('/^File:/', '', $title);
                $imageUrl = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode((string) $filename) . '?width=960';
            }
            $urlParts = parse_url($imageUrl);
            if (
                $license === ''
                || !str_starts_with(strtolower((string) ($imageInfo['mime'] ?? '')), 'image/')
                || ($urlParts['scheme'] ?? '') !== 'https'
                || !in_array(strtolower((string) ($urlParts['host'] ?? '')), ['thumb.wikimedia.org', 'upload.wikimedia.org', 'commons.wikimedia.org'], true)
                || (strtolower((string) ($urlParts['host'] ?? '')) === 'commons.wikimedia.org' && !str_starts_with((string) ($urlParts['path'] ?? ''), '/wiki/Special:FilePath/'))
                || strlen($imageUrl) > 255
            ) {
                continue;
            }

            $description = html_entity_decode(
                strip_tags((string) ($metadata['ImageDescription']['value'] ?? '')),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $title = mb_strtolower($title, 'UTF-8');
            $description = mb_strtolower($description, 'UTF-8');
            $commonInTitle = $common !== '' && str_contains($title, $common);
            $scientificInTitle = $scientific !== '' && str_contains($title, $scientific);
            $commonInDescription = $common !== '' && str_contains($description, $common);
            $scientificInDescription = $scientific !== '' && str_contains($description, $scientific);
            if (!$commonInTitle && !$scientificInTitle && !$commonInDescription && !$scientificInDescription) {
                continue;
            }

            $score = ($scientificInTitle ? 10 : 0)
                + ($commonInTitle ? 8 : 0)
                + ($scientificInDescription ? 5 : 0)
                + ($commonInDescription ? 3 : 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestUrl = $imageUrl;
            }
        }

        if ($bestUrl !== null) {
            return $bestUrl;
        }
    }

    return null;
}

function species_image_source_url(?string $imageUrl): ?string
{
    if (!$imageUrl) {
        return null;
    }

    $parts = parse_url($imageUrl);
    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host === 'commons.wikimedia.org' && str_starts_with((string) ($parts['path'] ?? ''), '/wiki/Special:FilePath/')) {
        $filename = rawurldecode(substr((string) $parts['path'], strlen('/wiki/Special:FilePath/')));
        if (preg_match('/\.(jpe?g|png|webp)$/i', $filename)) {
            return 'https://commons.wikimedia.org/wiki/File:' . rawurlencode($filename);
        }
        return null;
    }
    if (!in_array($host, ['thumb.wikimedia.org', 'upload.wikimedia.org'], true)) {
        return filter_var($imageUrl, FILTER_VALIDATE_URL) ? $imageUrl : null;
    }

    $segments = explode('/', trim(rawurldecode((string) ($parts['path'] ?? '')), '/'));
    $thumbIndex = array_search('thumb', $segments, true);
    $filenameIndex = $thumbIndex === false ? count($segments) - 1 : $thumbIndex + 3;
    $filename = $segments[$filenameIndex] ?? '';
    if (!preg_match('/\.(jpe?g|png|webp)$/i', $filename)) {
        return null;
    }

    return 'https://commons.wikimedia.org/wiki/File:' . rawurlencode($filename);
}

function admin_log(string $action, string $description): void
{
    $statement = db()->prepare('INSERT INTO admin_activity_logs (admin_id, action, description) VALUES (?, ?, ?)');
    $statement->execute([$_SESSION['admin_id'] ?? null, $action, $description]);
}

function status_class(string $status): string
{
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}

function report_categories(): array
{
    return ['Illegal Fishing', 'Marine Pollution', 'Improper Waste Disposal', 'Coastal Damage', 'Destroyed Coral Reefs', 'Stranded Marine Animals', 'Injured Marine Animals', 'Oil Spills', 'Other Coastal Issues'];
}

function report_statuses(): array
{
    return ['Pending', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
}

function marine_species_categories(): array
{
    return ['Fish', 'Marine Mammal', 'Marine Reptile', 'Crustacean', 'Mollusk', 'Echinoderm', 'Coral', 'Jellyfish', 'Cnidarian', 'Sponge', 'Marine Plant', 'Other'];
}
