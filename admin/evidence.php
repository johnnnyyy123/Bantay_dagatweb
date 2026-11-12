<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare('SELECT evidence_image FROM reports WHERE report_id = ?');
$statement->execute([$id ?: 0]);
$path = $statement->fetchColumn();
if (!$path) { http_response_code(404); exit('Evidence not found.'); }
$fullPath = dirname(__DIR__) . '/' . ltrim((string) $path, '/\\');
$realUpload = realpath(dirname(__DIR__) . '/uploads/reports');
$realFile = realpath($fullPath);
if (!$realUpload || !$realFile || !str_starts_with($realFile, $realUpload . DIRECTORY_SEPARATOR) || !is_file($realFile)) { http_response_code(404); exit('Evidence not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($realFile);
if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) { http_response_code(404); exit('Evidence not found.'); }
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($realFile);
exit;
