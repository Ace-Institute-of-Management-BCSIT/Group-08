<?php
/**
 * Renders worker details, booking controls, reviews, and related service history.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$id = (int) ($_GET['id'] ?? 0);
$job = null;
$workers = [];

// ===========================
// Shared Helper Functions
// ===========================
function detail_job_image($category, $title = '') {
    // Some existing Home Electrician records were previously saved under Repair.
    // Prefer the electrician asset based on the job title in that case.
    if (stripos((string) $title, 'electrician') !== false) {
        return '../images/electric.jpg';
    }
    if (stripos((string) $title, 'tech repair') !== false) {
        return '../images/techRepair.jpg';
    }
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

    if ($job) {
        $workers = fetch_workers_for_category($conn, (int) $job['category_id'], 8);
    }
}

$user = current_user($conn);
$userRole = $user['role'] ?? '';
$contactExchanges = [];
$bookingStatusByWorker = [];

if ($job && $user) {
    $stmt = mysqli_prepare($conn, "SELECT worker_id FROM contact_exchanges WHERE job_id = ? AND (employer_id = ? OR worker_id = ?)
        UNION
        SELECT worker_id FROM booking_requests WHERE job_id = ? AND status IN ('Accepted','Completed') AND (employer_id = ? OR worker_id = ?)");
    mysqli_stmt_bind_param($stmt, 'iiiiii', $id, $user['user_id'], $user['user_id'], $id, $user['user_id'], $user['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $contactExchanges[(int) $row['worker_id']] = true;
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT worker_id, status FROM booking_requests WHERE job_id = ? AND (employer_id = ? OR worker_id = ?) ORDER BY booking_id DESC');
    mysqli_stmt_bind_param($stmt, 'iii', $id, $user['user_id'], $user['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $workerId = (int) $row['worker_id'];
        if (!isset($bookingStatusByWorker[$workerId])) {
            $bookingStatusByWorker[$workerId] = $row['status'];
        }
    }
    mysqli_stmt_close($stmt);
}
// ===========================
// Page Rendering
// ===========================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Details</title>
<link rel="icon" type="image/svg+xml" href="../images/logo-favicon.svg">
<link rel="stylesheet" href="details.css">
<link rel="stylesheet" href="../JobsPage/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>
body{background:#f7f8fa}.details-container{max-width:1180px;margin:0 auto;padding:42px 20px}.job-details{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:28px;box-shadow:0 12px 28px rgba(16,35,74,.1)}.detail-image{width:100%;max-height:340px;object-fit:cover;border-radius:8px;margin-bottom:20px}.applied-workers{margin-top:28px}.worker-grid{display:grid;grid-template-columns:1fr;gap:18px}.worker-card{border:1px solid #dfe7e3;border-radius:8px;padding:18px;background:#fff;box-shadow:0 8px 20px rgba(16,35,74,.06);display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:20px;align-items:start}.worker-main{min-width:0}.worker-history-panel{border-left:1px solid #edf1ef;padding-left:20px;min-width:0}.worker-history-panel h3{color:#132766;font-size:18px;margin:0 0 10px}.worker-history-panel h3+div{margin-bottom:16px}.worker-head{display:flex;gap:14px;align-items:center;margin-bottom:12px}.worker-head img{width:64px;height:64px;border-radius:50%;object-fit:cover}.worker-card h3{color:#132766;margin-bottom:2px}.rating-line{color:#7a4f00;font-weight:700}.detail-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}.detail-actions a,.booking-form button{background:#28a745;color:#fff;border:0;border-radius:6px;padding:12px 18px;text-decoration:none;font-weight:600;cursor:pointer}.booking-form{border:1px solid #dfe7e3;border-radius:8px;padding:18px;background:#fbfdfc;margin-top:18px}.booking-form label{display:block;margin:12px 0;color:#132766;font-weight:600}.booking-form input,.booking-form select,.booking-form textarea{width:100%;border:1px solid #dfe7e3;border-radius:6px;padding:10px;margin-top:6px}.muted{color:#687383}.worker-meta{color:#687383;font-size:14px}.status-pill{display:inline-flex;border-radius:999px;background:#edf5f2;color:#255747;padding:5px 10px;font-size:12px;font-weight:700;margin:8px 0}.history-list p,.review-list p{font-size:13px;margin:8px 0;color:#4b5563;line-height:1.5}@media(max-width:860px){.worker-card{grid-template-columns:1fr}.worker-history-panel{border-left:0;border-top:1px solid #edf1ef;padding-left:0;padding-top:16px}.job-details{padding:20px}}
</style>
</head>
<body>
<header class="site-header">
<?php render_navbar($conn, '..', 'jobs'); ?>
<section class="hero"><h1>Job Details</h1></section>
</header>
<section class="details-container">
<div class="job-details">
<?php if (!$job): ?>
<h2>Job not found</h2>
<p class="description">Please return to the jobs page and select a valid job.</p>
<div class="detail-actions"><a href="../JobsPage/jobs.php">Back to Jobs</a></div>
<?php else: ?>
<span class="time">Recently</span>
<img class="detail-image" src="<?php echo e(detail_job_image($job['category_name'], $job['title'])); ?>" alt="<?php echo e($job['category_name']); ?> service">
<h2><?php echo e($job['title']); ?></h2>
<p><?php echo e($job['description']); ?></p>
<div class="job-tags">
<span><?php echo e($job['category_name']); ?></span>
<span><?php echo e($job['job_type']); ?></span>
<span>Rs <?php echo e(number_format((float) $job['salary'], 0)); ?></span>
<span><?php echo e($job['location']); ?></span>
</div>
<div class="detail-actions">
<?php if (!$user): ?>
<a href="../LoginPage/login.html">Login to Apply or Hire</a>
<?php elseif ($userRole === 'Worker'): ?>
<a href="../AboutUsPage/apply_resume.php?job_id=<?php echo e($id); ?>">Apply Job</a>
<?php elseif ($userRole === 'Employer'): ?>
<span class="muted">Choose a worker below and send a hire request.</span>
<?php else: ?>
<a href="../dasboard/dashboard.php">Admin Dashboard</a>
<?php endif; ?>
<a href="../JobsPage/jobs.php?category=<?php echo urlencode($job['category_name']); ?>">More <?php echo e($job['category_name']); ?></a>
</div>
<h2>Job Description</h2>
<p class="description"><?php echo e($job['description']); ?></p>
<section class="applied-workers">
<h2>Available Workers</h2>
<?php if (!$workers): ?>
<p class="muted">No verified workers are available for this category yet.</p>
<?php endif; ?>
<div class="worker-grid">
<?php foreach ($workers as $worker): ?>
<?php
$workerId = (int) $worker['user_id'];
$canViewContact = isset($contactExchanges[$workerId]);
$requestStatus = $bookingStatusByWorker[$workerId] ?? 'No request sent';
$workHistory = fetch_worker_completed_history($conn, $workerId, 3);
$latestReviews = fetch_latest_reviews($conn, $workerId, 3);
$busyDatesByWorker = booked_dates_by_worker($conn, [$workerId]);
?>
<article class="worker-card">
<div class="worker-main">
<div class="worker-head">
<img src="../images/profile.jpg" alt="<?php echo e($worker['full_name']); ?>">
<div>
<h3><?php echo e($worker['full_name']); ?></h3>
<p class="rating-line"><?php echo e(number_format((float) $worker['avg_rating'], 1)); ?> star | <?php echo e((int) $worker['total_reviews']); ?> reviews</p>
</div>
</div>
<p><?php echo e($worker['skills']); ?></p>
<p class="worker-meta"><?php echo e($worker['experience_years']); ?> years experience | <?php echo e($worker['current_status']); ?></p>
<p><span class="status-pill">Request Status: <?php echo e($requestStatus); ?></span></p>
<?php if ($canViewContact): ?>
<p class="worker-meta">Employer: <?php echo e($job['employer_name']); ?> | <?php echo e($job['employer_phone'] ?: 'Phone not provided'); ?> | <?php echo e($job['employer_email']); ?></p>
<p class="worker-meta">Worker: <?php echo e($worker['phone'] ?: 'Phone not provided'); ?> | <?php echo e($worker['email'] ?: 'Email not provided'); ?></p>
<?php else: ?>
<p class="worker-meta">Contact details will appear here after the worker accepts the hire request.</p>
<?php endif; ?>
<?php if ($userRole === 'Employer'): ?>
<form class="booking-form" action="../StatusBar/booking_request.php" method="POST">
<input type="hidden" name="job_id" value="<?php echo e($id); ?>">
<input type="hidden" name="category_id" value="<?php echo e($job['category_id']); ?>">
<input type="hidden" name="worker_id" value="<?php echo e($workerId); ?>">
<label>Service Date
<input type="date" name="booking_date" class="availability-date-input" data-busy-dates="<?php echo e(json_encode($busyDatesByWorker)); ?>" required>
</label>
<label>Time <input type="time" name="requested_time" required></label>
<label>Offer salary <input type="number" name="offered_salary" min="0" step="1" value="<?php echo e(max(0, (float) $job['salary'] - 20)); ?>" required></label>
<label>Service Category <input type="text" name="service_category" value="<?php echo e($job['category_name']); ?>" readonly></label>
<label>Notes <textarea name="notes" rows="3" placeholder="Add timing, address, or service notes"></textarea></label>
<button type="submit">Hire Now</button>
</form>
<?php elseif (!$user): ?>
<p><a href="../LoginPage/login.html">Login as employer to hire this worker</a></p>
<?php elseif ($userRole === 'Worker'): ?>
<p class="muted">Workers can apply for this job, but only employers can hire workers.</p>
<?php else: ?>
<p class="muted">Admin accounts can review this worker from the dashboard.</p>
<?php endif; ?>
</div>
<aside class="worker-history-panel">
<h3>Completed Work History</h3>
<div class="history-list">
<?php if (!$workHistory): ?><p>No completed services yet.</p><?php endif; ?>
<?php foreach ($workHistory as $history): ?>
<p><strong><?php echo e($history['title']); ?></strong><br>Employer: <?php echo e(mask_reviewer_name($history['employer_name'])); ?><br>Completion: <?php echo e($history['completion_date'] ?: $history['updated_at']); ?><?php if ($history['rating']): ?><br>Rating: <?php echo e((int) $history['rating']); ?> star<?php endif; ?><?php if ($history['review_summary']): ?><br><?php echo e($history['review_summary']); ?><?php endif; ?></p>
<?php endforeach; ?>
</div>
<h3>Latest Reviews</h3>
<div class="review-list">
<?php if (!$latestReviews): ?><p>No reviews yet.</p><?php endif; ?>
<?php foreach ($latestReviews as $review): ?>
<p><strong>Reviewer: <?php echo e(mask_reviewer_name($review['employer_name'])); ?></strong><br><?php echo e((int) $review['rating']); ?> star <?php echo e($review['review_comment']); ?></p>
<?php endforeach; ?>
</div>
</aside>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</div>
</section>
<?php render_footer('..'); ?>
<script src="../JobsPage/jobs.js"></script>
</body>
</html>
