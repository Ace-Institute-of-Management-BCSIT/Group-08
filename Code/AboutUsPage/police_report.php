<?php
/**
 * Handles police report upload requests for worker verification.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Worker') {
    header('Location: ../dasboard/dashboard.php?verification=workers-only');
    exit();
}
$message = '';
$messageClass = 'error';
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['police_report']['name']) || !allowed_upload_extension($_FILES['police_report'], $allowed)) {
        $message = 'Please upload a PDF, JPG, JPEG, or PNG police report.';
    } else {
        $path = save_uploaded_file($_FILES['police_report'], 'AboutUsPage/uploads/police_reports', (int) $user['user_id'], $allowed);
        if ($path) {
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['police_report']['name']));
            $stmt = mysqli_prepare($conn, 'INSERT INTO police_report_uploads (user_id, file_name, file_path) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iss', $user['user_id'], $safeName, $path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $status = 'Pending';
            $stmt = mysqli_prepare($conn, 'INSERT INTO worker_verifications (worker_id, file_name, file_path, status) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'isss', $user['user_id'], $safeName, $path, $status);
            $saved = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($saved) {
                create_notification($conn, (int) $user['user_id'], 'Police report verification: Pending', 'Your police report has been submitted for verification. Approval may take 3-5 days.');
                $messageClass = 'success';
                $message = 'Police report uploaded and submitted for verification. Approval may take 3-5 days.';
            } else {
                $message = 'Your file was uploaded, but the verification request could not be created. Please try again.';
            }
        } else {
            $message = 'Could not save your file. Please try again.';
        }
    }
}

$reports = [];
$stmt = mysqli_prepare($conn, 'SELECT file_path, status, submitted_at FROM worker_verifications WHERE worker_id = ? ORDER BY verification_id DESC LIMIT 5');
mysqli_stmt_bind_param($stmt, 'i', $user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = $result ? mysqli_fetch_assoc($result) : null) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);
// ===========================
// Page Rendering
// ===========================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Police Report - Ghar Sathi</title>
<link rel="icon" type="image/svg+xml" href="../images/logo-favicon.svg">
<link rel="stylesheet" href="aboutus.css">
<link rel="stylesheet" href="../JobsPage/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>
body{background:#f7f8fa}.report-wrap{max-width:1080px;margin:38px auto;padding:0 20px;display:grid;grid-template-columns:1fr 1fr;gap:24px}.report-panel{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:24px;box-shadow:0 10px 24px rgba(16,35,74,.08)}.report-panel h1,.report-panel h2{color:#132766;margin-bottom:12px}.report-panel p,.report-panel li{color:#4b5563;line-height:1.7}.report-panel ol{padding-left:22px}.report-panel label{display:block;font-weight:700;color:#1f2933;margin:16px 0}.report-panel input{display:block;margin-top:8px}.report-panel button,.report-panel a.button{border:0;border-radius:6px;background:#28a745;color:#fff;padding:12px 18px;font-weight:700;text-decoration:none;display:inline-block}.status{display:inline-flex;border-radius:999px;background:#edf5f2;color:#255747;padding:3px 9px;font-size:12px;font-weight:700}.success{color:#28a745;font-weight:700}.error{color:#b42318;font-weight:700}.history{border-top:1px solid #edf1ef;padding-top:12px;margin-top:16px}@media(max-width:780px){.report-wrap{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="site-header">
<?php render_navbar($conn, '..', 'about'); ?>
</header>
<main class="report-wrap">
<section class="report-panel">
<h1>Upload Police Report</h1>
<p>Upload your police clearance report to strengthen your worker verification profile.</p>
<?php if ($message): ?><p class="<?php echo e($messageClass); ?>"><?php echo e($message); ?></p><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<label>Police report file
<input type="file" name="police_report" accept=".pdf,.jpg,.jpeg,.png" required>
</label>
<button type="submit">Upload Report</button>
</form>
<?php if ($reports): ?>
<div class="history">
<h2>Recent Uploads</h2>
<?php foreach ($reports as $report): ?>
<p><a href="../<?php echo e($report['file_path']); ?>">View report</a> <span class="status"><?php echo e($report['status']); ?></span> <span class="muted"><?php echo e($report['submitted_at']); ?></span></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
<section class="report-panel">
<h2>Police Clearance Process</h2>
<ol>
<li>Visit the official police clearance portal or nearby police office.</li>
<li>Fill in the required personal details exactly as shown on your citizenship card.</li>
<li>Submit the required identity documents and recent photo if requested.</li>
<li>Wait for verification and download or collect the issued clearance report.</li>
<li>Upload the final PDF or clear image copy here for Ghar Sathi review.</li>
</ol>
<p>Visit the official Police Clearance Portal: <a href="https://opcr.nepalpolice.gov.np" target="_blank" rel="noopener noreferrer">https://opcr.nepalpolice.gov.np</a></p>
<p><a class="button" href="aboutus.php">Back to About Us</a></p>
</section>
</main>
<?php render_footer('..'); ?>
<script src="../JobsPage/jobs.js"></script>
</body>
</html>
