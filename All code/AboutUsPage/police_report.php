<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
$message = '';
$messageClass = 'error';
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];

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
            $messageClass = 'success';
            $message = 'Police report uploaded successfully.';
        } else {
            $message = 'Could not save your file. Please try again.';
        }
    }
}

$reports = [];
$stmt = mysqli_prepare($conn, 'SELECT file_path, upload_date FROM police_report_uploads WHERE user_id = ? ORDER BY report_id DESC LIMIT 5');
mysqli_stmt_bind_param($stmt, 'i', $user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = $result ? mysqli_fetch_assoc($result) : null) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Police Report - Ghar Sathi</title>
<link rel="stylesheet" href="aboutus.css">
<link rel="stylesheet" href="../JobsPage/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>
body{background:#f7f8fa}.report-wrap{max-width:1080px;margin:38px auto;padding:0 20px;display:grid;grid-template-columns:1fr 1fr;gap:24px}.report-panel{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:24px;box-shadow:0 10px 24px rgba(16,35,74,.08)}.report-panel h1,.report-panel h2{color:#132766;margin-bottom:12px}.report-panel p,.report-panel li{color:#4b5563;line-height:1.7}.report-panel ol{padding-left:22px}.report-panel label{display:block;font-weight:700;color:#1f2933;margin:16px 0}.report-panel input{display:block;margin-top:8px}.report-panel button,.report-panel a.button{border:0;border-radius:6px;background:#28a745;color:#fff;padding:12px 18px;font-weight:700;text-decoration:none;display:inline-block}.success{color:#28a745;font-weight:700}.error{color:#b42318;font-weight:700}.history{border-top:1px solid #edf1ef;padding-top:12px;margin-top:16px}@media(max-width:780px){.report-wrap{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="site-header">
<?php render_navbar($conn, '..', 'about'); ?>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks"><i class="fa-solid fa-bars"></i></button>
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
<p><a href="../<?php echo e($report['file_path']); ?>">View report</a> <span class="muted"><?php echo e($report['upload_date']); ?></span></p>
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
<p><a class="button" href="aboutus.php">Back to About Us</a></p>
</section>
</main>
<?php render_footer('..'); ?>
<script src="../JobsPage/jobs.js"></script>
</body>
</html>
