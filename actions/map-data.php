<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$category = trim((string) ($_GET['category'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = ['latitude IS NOT NULL', 'longitude IS NOT NULL', "status <> 'Rejected'"];
$params = [];
if ($category !== '' && in_array($category, report_categories(), true)) {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($status !== '' && in_array($status, report_statuses(), true) && $status !== 'Rejected') {
    $where[] = 'status = ?';
    $params[] = $status;
}
$statement = db()->prepare('SELECT reference_number, category, location, barangay, incident_date, status, latitude, longitude FROM reports WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 500');
$statement->execute($params);
echo json_encode($statement->fetchAll(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
