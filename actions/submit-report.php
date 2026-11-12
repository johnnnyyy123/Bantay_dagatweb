<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../report.php');
}
verify_csrf();
$submissionToken = (string) ($_POST['submission_token'] ?? '');
if ($submissionToken === '' || !hash_equals((string) ($_SESSION['report_submission_token'] ?? ''), $submissionToken)) {
    flash('error', 'This report form has already been submitted or expired. Please review the form and try again.');
    redirect('../report.php');
}

$title = trim((string) ($_POST['report_title'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$barangay = trim((string) ($_POST['barangay'] ?? ''));
$incidentDate = trim((string) ($_POST['incident_date'] ?? ''));
$incidentTime = trim((string) ($_POST['incident_time'] ?? ''));
$reporter = trim((string) ($_POST['reporter_name'] ?? ''));
$contact = trim((string) ($_POST['contact_number'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$latitude = trim((string) ($_POST['latitude'] ?? ''));
$longitude = trim((string) ($_POST['longitude'] ?? ''));

$errors = [];
if ($title === '' || mb_strlen($title) > 200) $errors[] = 'Enter a report title no longer than 200 characters.';
if (!in_array($category, report_categories(), true)) $errors[] = 'Choose a valid incident category.';
if ($description === '' || mb_strlen($description) > 10000) $errors[] = 'Describe the incident (up to 10,000 characters).';
if ($location === '' || mb_strlen($location) > 255 || $barangay === '' || mb_strlen($barangay) > 120) $errors[] = 'Enter the incident location and barangay.';
if ($reporter === '' || mb_strlen($reporter) > 150 || $contact === '' || mb_strlen($contact) > 30) $errors[] = 'Enter your name and contact number.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'Enter a valid email address.';
$dateParts = explode('-', $incidentDate);
if (count($dateParts) !== 3 || !checkdate((int) ($dateParts[1] ?? 0), (int) ($dateParts[2] ?? 0), (int) ($dateParts[0] ?? 0)) || $incidentDate > date('Y-m-d')) $errors[] = 'Choose a valid incident date that is not in the future.';
if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $incidentTime)) $errors[] = 'Choose a valid incident time.';
foreach ([$latitude, $longitude] as $coordinate) {
    if ($coordinate !== '' && !is_numeric($coordinate)) $errors[] = 'Map coordinates must be numeric.';
}
if (($latitude === '') !== ($longitude === '')) $errors[] = 'Provide both map coordinates or leave both blank.';
if ($latitude !== '' && (float) $latitude < -90 || (float) $latitude > 90) $errors[] = 'Latitude is outside the valid range.';
if ($longitude !== '' && (float) $longitude < -180 || (float) $longitude > 180) $errors[] = 'Longitude is outside the valid range.';

if ($errors) {
    flash('error', implode(' ', array_unique($errors)));
    redirect('../report.php');
}

$image = null;
try {
    $image = upload_image('evidence_image', 'reports');
    $pdo = db();
    $userId = active_user_id();
    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO reports (reference_number, user_id, reporter_name, email, contact_number, report_title, category, description, location, barangay, latitude, longitude, incident_date, incident_time, evidence_image) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([
        $userId,
        $reporter, $email, $contact, $title, $category, $description, $location, $barangay,
        $latitude === '' ? null : $latitude, $longitude === '' ? null : $longitude,
        $incidentDate, $incidentTime, $image,
    ]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO report_history (report_id, previous_status, new_status, remarks) VALUES (?, NULL, ?, ?)')->execute([$id, 'Pending', 'Report submitted and received.']);
    $pdo->commit();
    unset($_SESSION['report_submission_token']);
    $_SESSION['submitted_report'] = true;
    redirect('../report.php?submitted=1');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($image !== null) {
        $uploadedFile = dirname(__DIR__) . '/' . $image;
        if (is_file($uploadedFile)) unlink($uploadedFile);
    }
    error_log('Report submission failed: ' . $exception->getMessage());
    flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'We could not save your report just now. Please try again.');
    redirect('../report.php');
}
