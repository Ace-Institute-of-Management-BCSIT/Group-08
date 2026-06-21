<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Worker') {
    header('Location: ../dasboard/dashboard.php?application=worker-required');
    exit();
}

$jobId = (int) ($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$message = ($_GET['error'] ?? '') === 'file' ? 'Invalid file type.' : '';

$job = null;
if ($jobId > 0) {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT jobs.*, categories.category_name
         FROM jobs
         INNER JOIN categories ON categories.category_id = jobs.category_id
         WHERE jobs.job_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $jobId);
    mysqli_stmt_execute($stmt);
    $job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply Resume - Ghar Sathi</title>
<link rel="stylesheet" href="aboutus.css">
<style>
.apply-wrap{max-width:980px;margin:40px auto;padding:0 20px;display:grid;grid-template-columns:1fr 1fr;gap:28px}
.apply-panel{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:24px;box-shadow:0 10px 24px rgba(16,35,74,.08)}
.apply-panel h1,.apply-panel h2{color:#132766;margin-bottom:12px}
.apply-panel p{line-height:1.7;color:#4b5563;margin-bottom:14px}
.resume-form label{display:block;margin-bottom:14px;color:#1f2933;font-weight:600}
.resume-form textarea{width:100%;min-height:130px;border:1px solid #dfe7e3;border-radius:6px;padding:12px;font:inherit}
.resume-form button,.back-link{display:inline-flex;border:0;border-radius:6px;background:#28a745;color:#fff;padding:12px 18px;font-weight:600;text-decoration:none}
.error{color:#b42318;font-weight:600}
@media(max-width:780px){.apply-wrap{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="apply-wrap">
<section class="apply-panel">
<h1>About Us</h1>
<p>Ghar Sathi connects families with verified household service workers for cleaning, repairs, tuition, care, cooking, and daily home support.</p>
<h2>Become a Worker</h2>
<p>Submit your resume once, apply to jobs that match your skills, and wait for admin verification. Verified applications appear on the job detail page and become available for employers to hire.</p>
<a class="back-link" href="../Jobs page/jobs.php">Back to Jobs</a>
</section>
<section class="apply-panel">
<?php if (!$job): ?>
<h2>Job not found</h2>
<p>Please return to the jobs page and choose a valid job.</p>
<?php else: ?>
<h2>Apply for <?php echo e($job['title']); ?></h2>
<p><?php echo e($job['category_name']); ?> · <?php echo e($job['location']); ?> · Rs <?php echo e(number_format((float) $job['salary'], 0)); ?></p>
<?php if ($message): ?><p class="error"><?php echo e($message); ?></p><?php endif; ?>
<form class="resume-form" action="../Jobs page/apply_job.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="job_id" value="<?php echo e($jobId); ?>">
<label>Resume file
<input type="file" name="resume" accept=".pdf,.doc,.docx">
</label>
<label>Resume details
<textarea name="resume_text" placeholder="Skills, experience, availability, and contact details" required></textarea>
</label>
<label>Cover letter
<textarea name="cover_letter" placeholder="Tell the employer why you are suitable"></textarea>
</label>
<button type="submit">Submit Resume for Admin Verification</button>
</form>
<?php endif; ?>
</section>
</main>
</body>
</html>
