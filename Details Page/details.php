<?php
require_once __DIR__ . '/../includes/app.php';

$id = (int) ($_GET['id'] ?? 0);
$job = null;
$workers = [];

function detail_job_image($category) {
    return service_image($category);
}

if ($id > 0) {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT jobs.*, categories.category_name, users.full_name AS employer_name, users.email AS employer_email, users.phone AS employer_phone
         FROM jobs
         INNER JOIN categories ON categories.category_id = jobs.category_id
         INNER JOIN users ON users.user_id = jobs.employer_id
         WHERE jobs.job_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $workers = fetch_workers_for_category($conn, (int) $job['category_id'], 8);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Details</title>
<link rel="stylesheet" href="details.css">
<link rel="stylesheet" href="../Jobs page/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>
body{background:#f7f8fa}.details-container{max-width:1180px;margin:0 auto;padding:42px 20px}.job-details{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:28px;box-shadow:0 12px 28px rgba(16,35,74,.1)}.detail-image{width:100%;max-height:340px;object-fit:cover;border-radius:8px;margin-bottom:20px}.applied-workers{margin-top:28px}.worker-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.worker-card{border:1px solid #dfe7e3;border-radius:8px;padding:18px;background:#fff;box-shadow:0 8px 20px rgba(16,35,74,.06)}.worker-head{display:flex;gap:14px;align-items:center;margin-bottom:12px}.worker-head img{width:64px;height:64px;border-radius:50%;object-fit:cover}.worker-card h3{color:#132766;margin-bottom:2px}.rating-line{color:#7a4f00;font-weight:700}.detail-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}.detail-actions a,.booking-form button{background:#28a745;color:#fff;border:0;border-radius:6px;padding:12px 18px;text-decoration:none;font-weight:600;cursor:pointer}.booking-form{border:1px solid #dfe7e3;border-radius:8px;padding:18px;background:#fbfdfc;margin-top:18px}.booking-form label{display:block;margin:12px 0;color:#132766;font-weight:600}.booking-form input,.booking-form select,.booking-form textarea{width:100%;border:1px solid #dfe7e3;border-radius:6px;padding:10px;margin-top:6px}.muted{color:#687383}.worker-meta{color:#687383;font-size:14px}.latest-reviews{margin-top:12px;border-top:1px solid #edf1ef;padding-top:12px}.latest-reviews p{font-size:13px;margin:6px 0;color:#4b5563}@media(max-width:780px){.worker-grid{grid-template-columns:1fr}.job-details{padding:20px}}
</style>
</head>
<body>
<header class="site-header">
<?php render_navbar($conn, '..', 'jobs'); ?>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks"><i class="fa-solid fa-bars"></i></button>
<section class="hero"><h1>Job Details</h1></section>
</header>
<section class="details-container">
<div class="job-details">
<?php if (!$job): ?>
<h2>Job not found</h2>
<p class="description">Please return to the jobs page and select a valid job.</p>
<div class="detail-actions"><a href="../Jobs page/jobs.php">Back to Jobs</a></div>
<?php else: ?>
<span class="time">Recently</span>
<img class="detail-image" src="<?php echo e(detail_job_image($job['category_name'])); ?>" alt="<?php echo e($job['category_name']); ?> service">
<h2><?php echo e($job['title']); ?></h2>
<p><?php echo e($job['description']); ?></p>
<div class="job-tags">
<span><?php echo e($job['category_name']); ?></span>
<span><?php echo e($job['job_type']); ?></span>
<span>Rs <?php echo e(number_format((float) $job['salary'], 0)); ?></span>
<span><?php echo e($job['location']); ?></span>
</div>
<div class="detail-actions">
<a href="../About Us Page/apply_resume.php?job_id=<?php echo e($id); ?>">Apply Job</a>
<a href="../Jobs page/jobs.php?category=<?php echo urlencode($job['category_name']); ?>">More <?php echo e($job['category_name']); ?></a>
</div>
<h2>Job Description</h2>
<p class="description"><?php echo e($job['description']); ?></p>
<h2>Employer Info</h2>
<p class="description">
<?php echo e($job['employer_name']); ?> · <?php echo e($job['employer_email']); ?> · <?php echo e($job['employer_phone'] ?: 'Phone not provided'); ?>
</p>
<section class="applied-workers">
<h2>Available Workers</h2>
<?php if (!$workers): ?>
<p class="muted">No verified workers are available for this category yet.</p>
<?php endif; ?>
<div class="worker-grid">
<?php foreach ($workers as $worker): ?>
<article class="worker-card">
<div class="worker-head">
<img src="../<?php echo e($worker['profile_image'] ?: 'profile.jpg'); ?>" alt="<?php echo e($worker['full_name']); ?>">
<div>
<h3><?php echo e($worker['full_name']); ?></h3>
<p class="rating-line"><?php echo e(number_format((float) $worker['avg_rating'], 1)); ?> ★ | <?php echo e((int) $worker['total_reviews']); ?> reviews</p>
</div>
</div>
<p><?php echo e($worker['skills']); ?></p>
<p class="worker-meta"><?php echo e($worker['experience_years']); ?> years experience | <?php echo e($worker['current_status']); ?></p>
<p class="worker-meta">Phone: <?php echo e($worker['phone'] ?: 'Not provided'); ?> | Email: <?php echo e($worker['email'] ?: 'Not provided'); ?></p>
<?php $latestReviews = fetch_latest_reviews($conn, (int) $worker['user_id'], 2); ?>
<?php if ($latestReviews): ?>
<div class="latest-reviews">
<strong>Latest Reviews</strong>
<?php foreach ($latestReviews as $review): ?>
<p><?php echo e((int) $review['rating']); ?> ★ <?php echo e($review['review_comment']); ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if (current_user($conn) && current_user($conn)['role'] === 'Employer'): ?>
<form class="booking-form" action="../booking_request.php" method="POST">
<input type="hidden" name="job_id" value="<?php echo e($id); ?>">
<input type="hidden" name="category_id" value="<?php echo e($job['category_id']); ?>">
<input type="hidden" name="worker_id" value="<?php echo e($worker['user_id']); ?>">
<input type="hidden" name="offered_salary" value="<?php echo e($job['salary']); ?>">
<label>Service Date <input type="date" name="booking_date" required></label>
<label>Service Category <input type="text" name="service_category" value="<?php echo e($job['category_name']); ?>" readonly></label>
<label>Notes <textarea name="notes" rows="3" placeholder="Add timing, address, or service notes"></textarea></label>
<button type="submit">Hire Now</button>
</form>
<?php elseif (!current_user($conn)): ?>
<p><a href="../Login Page/login.html">Login as employer to hire this worker</a></p>
<?php endif; ?>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</div>
</section>
<?php render_footer('..'); ?>
<script src="../Jobs page/jobs.js"></script>
</body>
</html>
