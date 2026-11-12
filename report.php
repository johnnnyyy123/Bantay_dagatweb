<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Report a Coastal Issue';
$activePage = 'report';
$submissionReceived = false;
if (isset($_GET['submitted'], $_SESSION['submitted_report'])) {
    $submissionReceived = true;
    unset($_SESSION['submitted_report']);
}
if (!$submissionReceived && empty($_SESSION['report_submission_token'])) {
    $_SESSION['report_submission_token'] = bin2hex(random_bytes(32));
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow eyebrow-light">COMMUNITY COASTAL WATCH</span><h1>Report a coastal issue</h1><p>Your local knowledge helps Bantay Dagat understand what's happening along our shores. Please share what you saw and where.</p></div></section>
<section class="content-section"><div class="container">
<?php if ($submissionReceived): ?>
    <div class="surface-card text-center mx-auto" style="max-width:680px"><span class="stat-icon stat-icon-green mx-auto mb-3"><i class="fa-solid fa-check"></i></span><h2 class="h4 fw-bold">Your report has been successfully submitted.</h2><p class="text-secondary">An administrator will review your report before assigning a Reference ID.</p>
        <?php if (active_user_id() !== null): ?>
            <p class="text-secondary">The Reference ID and any follow-up message will appear in your private Inbox after the administrator sends them.</p><a class="btn btn-primary rounded-pill" href="user-inbox.php">Open my Inbox</a>
        <?php else: ?>
            <p class="text-secondary mb-0">You submitted this report as a guest. Your contact details were saved for administrator follow-up; no user account was created.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-4"><div class="col-lg-8"><form class="surface-card" action="actions/submit-report.php" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="submission_token" value="<?= e($_SESSION['report_submission_token']) ?>">
        <h2 class="h5 fw-bold mb-1">Incident details</h2><p class="help-text mb-4">Fields marked <span class="field-required">*</span> are required. Reporter contact details are not public.</p>
        <div class="row g-3">
            <div class="col-12"><label class="form-label">Report title <span class="field-required">*</span></label><input class="form-control" name="report_title" maxlength="200" required placeholder="e.g. Fishing net left near the reef"></div>
            <div class="col-md-6"><label class="form-label">Incident category <span class="field-required">*</span></label><select class="form-select" name="category" required><option value="">Select category</option><?php foreach (report_categories() as $category): ?><option><?= e($category) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Barangay <span class="field-required">*</span></label><input class="form-control" name="barangay" required maxlength="120" placeholder="Barangay name"></div>
            <div class="col-12"><label class="form-label">Specific location <span class="field-required">*</span></label><input class="form-control" name="location" required maxlength="255" placeholder="Beach, landmark, or nearby area"></div>
            <div class="col-12"><label class="form-label">Description <span class="field-required">*</span></label><textarea class="form-control" name="description" rows="5" maxlength="10000" required placeholder="Tell us what happened, when you noticed it, and any details that may help."></textarea></div>
            <div class="col-md-6"><label class="form-label">Date of incident <span class="field-required">*</span></label><input class="form-control" type="date" name="incident_date" max="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Time of incident <span class="field-required">*</span></label><input class="form-control" type="time" name="incident_time" required></div>
            <div class="col-12"><label class="form-label">Pin the location (optional)</label><div class="d-flex gap-2 mb-2"><button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="locate-me"><i class="fa-solid fa-location-crosshairs me-1"></i>Use my location</button><span class="help-text align-self-center">Or tap the map to place a pin.</span></div><div id="report-map" class="map-box" style="height:300px"></div></div>
            <div class="col-md-6"><label class="form-label">Latitude</label><input class="form-control" name="latitude" id="latitude" inputmode="decimal" placeholder="Select on map"></div>
            <div class="col-md-6"><label class="form-label">Longitude</label><input class="form-control" name="longitude" id="longitude" inputmode="decimal" placeholder="Select on map"></div>
            <div class="col-12"><label class="form-label">Photo evidence (optional)</label><input class="form-control" type="file" name="evidence_image" accept="image/jpeg,image/png,image/webp" data-image-preview="#evidence-preview"><div class="help-text mt-1">JPG, PNG, or WebP up to 5 MB. Do not put yourself in danger to take a photo.</div><img id="evidence-preview" class="evidence-preview" alt="Evidence preview"></div>
        </div>
        <hr class="my-4"><h2 class="h5 fw-bold mb-1">Your contact information</h2><p class="help-text mb-3">Used only if the response team needs to follow up with you.</p>
        <div class="row g-3"><div class="col-md-6"><label class="form-label">Your name <span class="field-required">*</span></label><input class="form-control" name="reporter_name" maxlength="150" required></div><div class="col-md-6"><label class="form-label">Contact number <span class="field-required">*</span></label><input class="form-control" name="contact_number" maxlength="30" required></div><div class="col-12"><label class="form-label">Email address <span class="field-required">*</span></label><input class="form-control" type="email" name="email" maxlength="190" required></div></div>
        <button class="btn btn-primary rounded-pill mt-4" type="submit">Submit report <i class="fa-solid fa-arrow-right ms-2"></i></button>
    </form></div><div class="col-lg-4"><aside class="surface-card mb-3"><h2 class="h6 fw-bold"><i class="fa-solid fa-shield-heart text-primary me-2"></i>Report responsibly</h2><p class="help-text mb-0">If there is immediate danger, contact local authorities directly. Do not approach unsafe situations. Public maps show only the reported category, area, date, and status—never your name or contact details.</p></aside><aside class="surface-card"><h2 class="h6 fw-bold">What happens next?</h2><ol class="small text-secondary ps-3 mb-0"><li class="mb-2">Bantay Dagat administrators review your report.</li><li class="mb-2">After review, the administrator assigns your Reference ID and sends it to your private Inbox.</li><li>Use the Reference ID to check status updates.</li></ol><a class="text-link d-inline-block mt-3" href="report-status.php">Track a report <i class="fa-solid fa-arrow-right"></i></a></aside></div></div>
<?php endif; ?>
</div></section>
<?php if (!$submissionReceived): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
document.addEventListener('DOMContentLoaded',()=>{const map=L.map('report-map').setView([13.45,121.95],9);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);let marker;function pin(lat,lng){if(marker)marker.setLatLng([lat,lng]);else marker=L.marker([lat,lng],{draggable:true}).addTo(map);document.getElementById('latitude').value=Number(lat).toFixed(7);document.getElementById('longitude').value=Number(lng).toFixed(7);marker.on('dragend',()=>{const p=marker.getLatLng();document.getElementById('latitude').value=p.lat.toFixed(7);document.getElementById('longitude').value=p.lng.toFixed(7)})}map.on('click',event=>pin(event.latlng.lat,event.latlng.lng));document.getElementById('locate-me').addEventListener('click',()=>{if(!navigator.geolocation){alert('Location services are unavailable in this browser.');return}navigator.geolocation.getCurrentPosition(pos=>{map.setView([pos.coords.latitude,pos.coords.longitude],14);pin(pos.coords.latitude,pos.coords.longitude)},()=>alert('Could not access your location. You can tap the map to set it instead.'))});setTimeout(()=>map.invalidateSize(),150)});
</script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
