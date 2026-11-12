<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST requests only.');
}
require_admin();
verify_csrf();

$action = (string) ($_POST['action'] ?? '');
$routes = [
    'update_report' => '../admin/manage-reports.php',
    'delete_report' => '../admin/manage-reports.php',
    'generate_report_reference' => '../admin/manage-reports.php',
    'send_report_reference' => '../admin/manage-reports.php',
    'update_location' => '../admin/manage-map.php',
    'save_announcement' => '../admin/manage-announcements.php',
    'delete_announcement' => '../admin/manage-announcements.php',
    'save_species' => '../admin/manage-species.php',
    'delete_species' => '../admin/manage-species.php',
    'save_activity' => '../admin/manage-activities.php',
    'delete_activity' => '../admin/manage-activities.php',
    'toggle_user' => '../admin/manage-users.php',
    'delete_user' => '../admin/manage-users.php',
    'delete_message' => '../admin/messages.php',
    'update_profile' => '../admin/profile.php',
];
$destination = $routes[$action] ?? '../admin/dashboard.php';

function posted_id(): int
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) throw new InvalidArgumentException('A valid record was not selected.');
    return $id;
}

function cleanup_upload(?string $relative, string $folder): void
{
    if (!$relative || !str_starts_with($relative, 'uploads/' . $folder . '/')) return;
    $base = realpath(dirname(__DIR__) . '/uploads/' . $folder);
    $file = realpath(dirname(__DIR__) . '/' . $relative);
    if ($base && $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) unlink($file);
}

function generate_report_reference(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $check = $pdo->prepare('SELECT report_id FROM reports WHERE reference_number = ? LIMIT 1');
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $suffix = '';
        for ($index = 0; $index < 6; $index++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $reference = 'BDM-' . date('Ymd') . '-' . $suffix;
        $check->execute([$reference]);
        if (!$check->fetchColumn()) {
            return $reference;
        }
    }

    throw new RuntimeException('A unique report reference could not be generated. Please try again.');
}

try {
    $pdo = db();
    switch ($action) {
        case 'update_report':
            $id = posted_id();
            $status = (string) ($_POST['status'] ?? '');
            $remarks = trim((string) ($_POST['remarks'] ?? ''));
            if (!in_array($status, report_statuses(), true) || mb_strlen($remarks) > 3000) throw new InvalidArgumentException('Choose a valid status and keep remarks under 3,000 characters.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status, admin_remarks FROM reports WHERE report_id=? FOR UPDATE');
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old) throw new InvalidArgumentException('Report not found.');
            if ($old['status'] !== $status || $old['admin_remarks'] !== $remarks) {
                $pdo->prepare('UPDATE reports SET status=?, admin_remarks=? WHERE report_id=?')->execute([$status, $remarks, $id]);
                $pdo->prepare('INSERT INTO report_history (report_id, admin_id, previous_status, new_status, remarks) VALUES (?, ?, ?, ?, ?)')->execute([$id, $_SESSION['admin_id'], $old['status'], $status, $remarks]);
                admin_log('report_update', "Updated report #{$id}: {$old['status']} to {$status}.");
            }
            $pdo->commit();
            flash('success', 'Report status and remarks were saved.');
            $destination .= '?id=' . $id;
            break;
        case 'generate_report_reference':
            $id = posted_id();
            $destination .= '?id=' . $id;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT reference_number, reference_sent_at FROM reports WHERE report_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $report = $stmt->fetch();
            if (!$report) throw new InvalidArgumentException('Report not found.');
            if ($report['reference_sent_at'] !== null) throw new InvalidArgumentException('This Reference ID has already been sent to the user.');
            if ($report['reference_number'] === null) {
                $reference = generate_report_reference($pdo);
                $pdo->prepare('UPDATE reports SET reference_number = ? WHERE report_id = ? AND reference_number IS NULL AND reference_sent_at IS NULL')
                    ->execute([$reference, $id]);
                admin_log('report_reference_generated', 'Generated a reference for report #' . $id . '.');
                flash('success', 'Reference ID generated. Review it and send it to the report owner when ready.');
            } else {
                flash('success', 'This report already has a Reference ID. You can send it to the report owner.');
            }
            $pdo->commit();
            break;
        case 'send_report_reference':
            $id = posted_id();
            $destination .= '?id=' . $id;
            $message = trim((string) ($_POST['message'] ?? ''));
            if ($message === '' || mb_strlen($message, 'UTF-8') > 5000) {
                throw new InvalidArgumentException('Enter a message of up to 5,000 characters before sending.');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'SELECT r.reference_number, r.reference_sent_at, r.user_id, r.report_title, u.account_status
                 FROM reports r
                 LEFT JOIN users u ON u.user_id = r.user_id
                 WHERE r.report_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id]);
            $report = $stmt->fetch();
            if (!$report) throw new InvalidArgumentException('Report not found.');
            if (!$report['user_id']) {
                throw new InvalidArgumentException('This guest report has no linked user Inbox. Contact the reporter using the details on the report.');
            }
            if ($report['account_status'] !== 'Active') {
                throw new InvalidArgumentException('The linked user account is not active and cannot receive an Inbox message.');
            }
            if ($report['reference_sent_at'] !== null) {
                throw new InvalidArgumentException('The Reference ID has already been sent. Previous messages are shown in this report.');
            }

            $reference = $report['reference_number'] ?: generate_report_reference($pdo);
            $subject = 'Report reference: ' . mb_substr((string) $report['report_title'], 0, 165, 'UTF-8');
            $pdo->prepare(
                'INSERT INTO user_inbox_messages
                    (recipient_user_id, report_id, reference_number, subject, message, admin_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                (int) $report['user_id'],
                $id,
                $reference,
                $subject,
                $message,
                (int) $_SESSION['admin_id'],
            ]);
            $update = $pdo->prepare(
                'UPDATE reports
                 SET reference_number = ?, reference_sent_at = NOW(), reference_sent_by = ?
                 WHERE report_id = ? AND reference_sent_at IS NULL'
            );
            $update->execute([$reference, (int) $_SESSION['admin_id'], $id]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('The report reference was already sent or could not be saved.');
            }
            admin_log('report_reference_sent', 'Sent Reference ID ' . $reference . ' for report #' . $id . '.');
            $pdo->commit();
            flash('success', 'Reference ID and message sent to the user Inbox.');
            break;
        case 'delete_report':
            $id = posted_id();
            $stmt = $pdo->prepare('SELECT evidence_image FROM reports WHERE report_id=?');
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM reports WHERE report_id=?');
            $stmt->execute([$id]);
            if (!$stmt->rowCount()) throw new InvalidArgumentException('Report not found.');
            cleanup_upload($image ?: null, 'reports');
            admin_log('report_delete', "Deleted report #{$id}.");
            flash('success', 'Report deleted.');
            break;
        case 'update_location':
            $id = posted_id();
            $latitude = trim((string) ($_POST['latitude'] ?? ''));
            $longitude = trim((string) ($_POST['longitude'] ?? ''));
            if ($latitude === '' || $longitude === '' || !is_numeric($latitude) || !is_numeric($longitude) || (float) $latitude < -90 || (float) $latitude > 90 || (float) $longitude < -180 || (float) $longitude > 180) {
                throw new InvalidArgumentException('Enter valid latitude and longitude coordinates.');
            }
            $stmt = $pdo->prepare('UPDATE reports SET latitude=?, longitude=? WHERE report_id=?');
            $stmt->execute([$latitude, $longitude, $id]);
            if (!$stmt->rowCount()) {
                $check = $pdo->prepare('SELECT report_id FROM reports WHERE report_id=?'); $check->execute([$id]);
                if (!$check->fetchColumn()) throw new InvalidArgumentException('Report not found.');
            }
            admin_log('location_update', "Updated coordinates for report #{$id}.");
            flash('success', 'Map coordinates saved.');
            break;
        case 'save_announcement':
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $publication = (string) ($_POST['publication_status'] ?? '');
            $validCategories = ['Bantay Dagat Activities','Coastal Clean-Up Drives','Marine Conservation Programs','Environmental Announcements','Community Activities','Coastal Warnings','Emergency Advisories'];
            if ($title === '' || mb_strlen($title) > 180 || !in_array($category, $validCategories, true) || $description === '' || !in_array($publication, ['Draft','Published','Unpublished'], true)) throw new InvalidArgumentException('Complete the announcement fields using a valid category and publication status.');
            $oldImage = null;
            if ($id) { $s = $pdo->prepare('SELECT featured_image FROM announcements WHERE announcement_id=?'); $s->execute([$id]); $oldImage = $s->fetchColumn(); if ($oldImage === false) throw new InvalidArgumentException('Announcement not found.'); }
            $image = upload_image('image', 'announcements') ?: ($oldImage ?: null);
            if ($id) {
                $pdo->prepare("UPDATE announcements SET title=?,category=?,description=?,featured_image=?,publication_status=?,published_at=IF(?='Published',COALESCE(published_at,NOW()),NULL) WHERE announcement_id=?")->execute([$title,$category,$description,$image,$publication,$publication,$id]);
                admin_log('announcement_update', "Updated announcement #{$id}.");
            } else {
                $pdo->prepare('INSERT INTO announcements (admin_id,title,category,description,featured_image,publication_status,published_at) VALUES (?,?,?,?,?,?,?)')->execute([$_SESSION['admin_id'],$title,$category,$description,$image,$publication,$publication === 'Published' ? date('Y-m-d H:i:s') : null]);
                admin_log('announcement_create', 'Created announcement: ' . $title);
            }
            if ($image && $oldImage && $image !== $oldImage) cleanup_upload($oldImage, 'announcements');
            flash('success', 'Announcement saved.');
            break;
        case 'delete_announcement':
            $id = posted_id(); $s = $pdo->prepare('SELECT featured_image FROM announcements WHERE announcement_id=?'); $s->execute([$id]); $image = $s->fetchColumn();
            $s = $pdo->prepare('DELETE FROM announcements WHERE announcement_id=?'); $s->execute([$id]); if (!$s->rowCount()) throw new InvalidArgumentException('Announcement not found.');
            cleanup_upload($image ?: null, 'announcements'); admin_log('announcement_delete', "Deleted announcement #{$id}."); flash('success', 'Announcement deleted.');
            break;
        case 'save_species':
            $id = (int) ($_POST['id'] ?? 0);
            $common = trim((string) ($_POST['common_name'] ?? '')); $scientific = trim((string) ($_POST['scientific_name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? '')); $habitat = trim((string) ($_POST['habitat'] ?? ''));
            $diet = trim((string) ($_POST['diet'] ?? '')); $occurrence = trim((string) ($_POST['occurrence_note'] ?? ''));
            $status = trim((string) ($_POST['conservation_status'] ?? ''));
            $facts = trim((string) ($_POST['interesting_facts'] ?? ''));
            $reference = trim((string) ($_POST['reference_url'] ?? ''));
            $imageSourceInput = trim((string) ($_POST['image_source_url'] ?? ''));
            if ($common === '' || $scientific === '' || $description === '' || mb_strlen($common) > 140 || mb_strlen($scientific) > 160 || mb_strlen($status) > 100 || !in_array($category, marine_species_categories(), true)) {
                throw new InvalidArgumentException('Enter valid common and scientific names, a category, and description; keep conservation status under 100 characters.');
            }
            $duplicate = $pdo->prepare('SELECT species_id FROM marine_species WHERE scientific_name=? AND species_id<>? LIMIT 1');
            $duplicate->execute([$scientific, $id]);
            if ($duplicate->fetchColumn()) throw new InvalidArgumentException('That scientific name is already in the species directory.');
            if ($occurrence === '' || $reference === '' || mb_strlen($occurrence) > 2000 || mb_strlen($reference) > 4000) {
                throw new InvalidArgumentException('Add an occurrence note and at least one reference; keep them under 2,000 and 4,000 characters respectively.');
            }
            $references = preg_split('/\R/', $reference) ?: [];
            foreach (array_filter(array_map('trim', $references)) as $referenceUrl) {
                $referenceParts = parse_url($referenceUrl);
                if (!filter_var($referenceUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($referenceParts['scheme'] ?? '')), ['http', 'https'], true)) {
                    throw new InvalidArgumentException('Each reference must be a valid HTTP or HTTPS URL on its own line.');
                }
            }
            $imageInput = trim((string) ($_POST['species_image'] ?? ''));
            if (mb_strlen($imageInput) > 255) throw new InvalidArgumentException('The image URL must be 255 characters or fewer.');
            if ($imageInput !== '') {
                $imageParts = parse_url($imageInput);
                if (!filter_var($imageInput, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($imageParts['scheme'] ?? '')), ['http', 'https'], true)) {
                    throw new InvalidArgumentException('Enter a valid HTTP or HTTPS image URL.');
                }
            }
            if (mb_strlen($imageSourceInput) > 1000) throw new InvalidArgumentException('The image source URL must be 1,000 characters or fewer.');
            if ($imageSourceInput !== '') {
                $sourceParts = parse_url($imageSourceInput);
                if (!filter_var($imageSourceInput, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($sourceParts['scheme'] ?? '')), ['http', 'https'], true)) {
                    throw new InvalidArgumentException('Enter a valid HTTP or HTTPS image source URL.');
                }
            }
            $oldImage = null;
            $oldImageSource = null;
            if ($id) {
                $s = $pdo->prepare('SELECT species_image, image_source_url FROM marine_species WHERE species_id=?');
                $s->execute([$id]);
                $old = $s->fetch();
                if (!$old) throw new InvalidArgumentException('Species record not found.');
                $oldImage = $old['species_image'];
                $oldImageSource = $old['image_source_url'];
            }
            $image = $imageInput !== '' ? $imageInput : ($oldImage ?: find_species_image_online($common, $scientific));
            $imageChanged = $imageInput !== '' && $imageInput !== $oldImage;
            $sourceChanged = $imageSourceInput !== '' && $imageSourceInput !== $oldImageSource;
            $imageSource = $sourceChanged
                ? $imageSourceInput
                : (!$imageChanged && $oldImageSource ? $oldImageSource : species_image_source_url($image));
            if ($id) {
                $pdo->prepare('UPDATE marine_species SET common_name=?,scientific_name=?,category=?,description=?,habitat=?,diet=?,occurrence_note=?,conservation_status=?,interesting_facts=?,species_image=?,image_source_url=?,reference_url=? WHERE species_id=?')
                    ->execute([$common,$scientific,$category,$description,$habitat,$diet,$occurrence,$status,$facts,$image,$imageSource,$reference,$id]);
                admin_log('species_update', "Updated species #{$id}.");
            } else {
                $pdo->prepare('INSERT INTO marine_species (common_name,scientific_name,category,description,habitat,diet,occurrence_note,conservation_status,interesting_facts,species_image,image_source_url,reference_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$common,$scientific,$category,$description,$habitat,$diet,$occurrence,$status,$facts,$image,$imageSource,$reference]);
                admin_log('species_create', 'Added species: ' . $common);
            }
            if ($image && $oldImage && $image !== $oldImage) cleanup_upload($oldImage, 'species');
            flash('success', $image
                ? 'Species details saved.'
                : 'Species details saved, but no matching online image was found. Add an image URL when one is available.');
            break;
        case 'delete_species':
            $id = posted_id(); $s = $pdo->prepare('SELECT species_image FROM marine_species WHERE species_id=?'); $s->execute([$id]); $image = $s->fetchColumn();
            $s = $pdo->prepare('DELETE FROM marine_species WHERE species_id=?'); $s->execute([$id]); if (!$s->rowCount()) throw new InvalidArgumentException('Species record not found.');
            cleanup_upload($image ?: null, 'species'); admin_log('species_delete', "Deleted species #{$id}."); flash('success', 'Species record deleted.');
            break;
        case 'save_activity':
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['activity_title'] ?? '')); $description = trim((string) ($_POST['description'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? '')); $date = trim((string) ($_POST['activity_date'] ?? ''));
            $status = (string) ($_POST['status'] ?? '');
            $parsedDate = DateTime::createFromFormat('Y-m-d\TH:i', $date);
            if ($title === '' || mb_strlen($title) > 180 || $description === '' || $location === '' || mb_strlen($location) > 180 || !$parsedDate || $parsedDate->format('Y-m-d\TH:i') !== $date || !in_array($status, ['Upcoming','Completed','Cancelled'], true)) throw new InvalidArgumentException('Complete all activity fields with a valid date and status.');
            $oldImage = null;
            if ($id) { $s = $pdo->prepare('SELECT activity_image FROM cleanup_activities WHERE activity_id=?'); $s->execute([$id]); $oldImage = $s->fetchColumn(); if ($oldImage === false) throw new InvalidArgumentException('Activity not found.'); }
            $image = upload_image('image', 'activities') ?: ($oldImage ?: null);
            if ($id) {
                $pdo->prepare('UPDATE cleanup_activities SET activity_title=?,description=?,location=?,activity_date=?,activity_image=?,status=? WHERE activity_id=?')->execute([$title,$description,$location,$parsedDate->format('Y-m-d H:i:s'),$image,$status,$id]);
                admin_log('activity_update', "Updated activity #{$id}.");
            } else {
                $pdo->prepare('INSERT INTO cleanup_activities (activity_title,description,location,activity_date,activity_image,status) VALUES (?,?,?,?,?,?)')->execute([$title,$description,$location,$parsedDate->format('Y-m-d H:i:s'),$image,$status]);
                admin_log('activity_create', 'Created activity: ' . $title);
            }
            if ($image && $oldImage && $image !== $oldImage) cleanup_upload($oldImage, 'activities');
            flash('success', 'Activity saved.');
            break;
        case 'delete_activity':
            $id = posted_id(); $s = $pdo->prepare('SELECT activity_image FROM cleanup_activities WHERE activity_id=?'); $s->execute([$id]); $image = $s->fetchColumn();
            $s = $pdo->prepare('DELETE FROM cleanup_activities WHERE activity_id=?'); $s->execute([$id]); if (!$s->rowCount()) throw new InvalidArgumentException('Activity not found.');
            cleanup_upload($image ?: null, 'activities'); admin_log('activity_delete', "Deleted activity #{$id}."); flash('success', 'Activity deleted.');
            break;
        case 'toggle_user':
            $id = posted_id(); $status = (string) ($_POST['status'] ?? '');
            if (!in_array($status, ['Active','Inactive'], true)) throw new InvalidArgumentException('Invalid user status.');
            $s = $pdo->prepare('UPDATE users SET account_status=? WHERE user_id=?'); $s->execute([$status,$id]);
            if (!$s->rowCount()) { $check=$pdo->prepare('SELECT user_id FROM users WHERE user_id=?');$check->execute([$id]);if(!$check->fetchColumn())throw new InvalidArgumentException('User not found.'); }
            admin_log('user_status', "Set user #{$id} to {$status}."); flash('success', 'User account status updated.');
            break;
        case 'delete_user':
            $id = posted_id(); $s = $pdo->prepare('DELETE FROM users WHERE user_id=?'); $s->execute([$id]); if (!$s->rowCount()) throw new InvalidArgumentException('User not found.');
            admin_log('user_delete', "Deleted user #{$id}."); flash('success', 'User account deleted.');
            break;
        case 'delete_message':
            $id = posted_id(); $s = $pdo->prepare('DELETE FROM messages WHERE message_id=?'); $s->execute([$id]); if (!$s->rowCount()) throw new InvalidArgumentException('Message not found.');
            admin_log('message_delete', "Deleted message #{$id}."); flash('success', 'Message deleted.');
            break;
        case 'update_profile':
            $name = trim((string) ($_POST['full_name'] ?? '')); $email = trim((string) ($_POST['email'] ?? ''));
            $current = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['new_password'] ?? '');
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid name and email address.');
            $s = $pdo->prepare('SELECT password,profile_image FROM admins WHERE admin_id=?'); $s->execute([$_SESSION['admin_id']]); $profile = $s->fetch();
            if (!$profile) throw new RuntimeException('Administrator account not found.');
            if ($new !== '' && (!password_verify($current, $profile['password']) || strlen($new) < 12)) throw new InvalidArgumentException('Enter your correct current password and choose a new password with at least 12 characters.');
            $image = upload_image('image', 'profiles') ?: ($profile['profile_image'] ?: null);
            if ($new === '') $pdo->prepare('UPDATE admins SET full_name=?,email=?,profile_image=? WHERE admin_id=?')->execute([$name,$email,$image,$_SESSION['admin_id']]);
            else $pdo->prepare('UPDATE admins SET full_name=?,email=?,profile_image=?,password=? WHERE admin_id=?')->execute([$name,$email,$image,password_hash($new, PASSWORD_DEFAULT),$_SESSION['admin_id']]);
            if ($image && $profile['profile_image'] && $image !== $profile['profile_image']) cleanup_upload($profile['profile_image'], 'profiles');
            $_SESSION['admin_name'] = $name; $_SESSION['admin_email'] = $email;
            admin_log('profile_update', 'Updated administrator profile.');
            flash('success', 'Your profile was updated.');
            break;
        default:
            throw new InvalidArgumentException('Unsupported administrative action.');
    }
} catch (InvalidArgumentException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Administrative action failed: ' . $exception->getMessage());
    flash('error', 'The requested change could not be completed. Please check the form and try again.');
}
redirect($destination);
