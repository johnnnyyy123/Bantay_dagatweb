<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../about.php');
verify_csrf();
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || $subject === '' || mb_strlen($subject) > 180 || $message === '' || mb_strlen($message) > 5000) {
    flash('error', 'Please complete all contact fields with a valid email address.');
    redirect('../about.php#contact');
}
$statement = db()->prepare('INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
$statement->execute([$name, $email, $subject, $message]);
flash('success', 'Thank you. Your message has been received.');
redirect('../about.php#contact');
