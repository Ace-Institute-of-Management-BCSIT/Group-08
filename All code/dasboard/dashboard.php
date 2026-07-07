<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
$role = $user['role'];
$userId = (int) $user['user_id'];

$statusSteps = [
    'Available',
    'Request Received',
    'Employer Contacted',
    'Interview Scheduled',
    'Selected/Hired',
    'Currently Working',
    'Service Completed',
    'Available Again',
];

function count_rows(mysqli $conn, string $sql): int {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

function fetch_all_rows($result): array {
    $rows = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $rows[] = $row;
    }
    return $rows;
}

function dashboard_document_href(?string $path): string {
    $path = trim((string) $path);
    if ($path === '') {
        return '#';
    }
    if (strpos($path, 'JobsPage/') === 0 || strpos($path, 'AboutUsPage/') === 0) {
        return '../' . $path;
    }
    if (strpos($path, 'uploads/') === 0) {
        return '../JobsPage/' . $path;
    }
    return $path;
}

$notifications = [];
$stmt = mysqli_prepare($conn, 'SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY notification_id DESC LIMIT 8');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$notifications = fetch_all_rows(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$bookingSql = "SELECT booking_requests.*, jobs.title, jobs.salary, employers.full_name AS employer_name,
        employers.email AS employer_email, employers.phone AS employer_phone,
        workers.full_name AS worker_name, workers.email AS worker_email, workers.phone AS worker_phone,
        categories.category_name
    FROM booking_requests
    INNER JOIN jobs ON jobs.job_id = booking_requests.job_id
    INNER JOIN users employers ON employers.user_id = booking_requests.employer_id
    INNER JOIN users workers ON workers.user_id = booking_requests.worker_id
    INNER JOIN categories ON categories.category_id = booking_requests.category_id";

if ($role === 'Worker') {
    $stmt = mysqli_prepare($conn, $bookingSql . ' WHERE booking_requests.worker_id = ? ORDER BY booking_requests.booking_id DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $bookings = fetch_all_rows(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} elseif ($role === 'Employer') {
    $stmt = mysqli_prepare($conn, $bookingSql . ' WHERE booking_requests.employer_id = ? ORDER BY booking_requests.booking_id DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $bookings = fetch_all_rows(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} else {
    $bookings = fetch_all_rows(mysqli_query($conn, $bookingSql . ' ORDER BY booking_requests.booking_id DESC LIMIT 80'));
}

$statusHistory = [];
$statusResult = mysqli_query($conn, "SELECT employment_status.*, workers.full_name AS worker_name, employers.full_name AS employer_name, jobs.title
    FROM employment_status
    INNER JOIN users workers ON workers.user_id = employment_status.worker_id
    LEFT JOIN users employers ON employers.user_id = employment_status.employer_id
    LEFT JOIN jobs ON jobs.job_id = employment_status.service_id
    ORDER BY employment_status.id DESC LIMIT 100");
while ($row = $statusResult ? mysqli_fetch_assoc($statusResult) : null) {
    $statusHistory[(int) $row['worker_id']][] = $row;
}

$applications = [];
if ($role === 'Admin') {
    $applications = fetch_all_rows(mysqli_query($conn, "SELECT job_applications.*, jobs.title, users.full_name, categories.category_name
        FROM job_applications
        INNER JOIN jobs ON jobs.job_id = job_applications.job_id
        INNER JOIN users ON users.user_id = job_applications.worker_id
        INNER JOIN categories ON categories.category_id = jobs.category_id
        ORDER BY job_applications.application_id DESC"));
} elseif ($role === 'Worker') {
    $stmt = mysqli_prepare($conn, "SELECT job_applications.*, jobs.title, users.full_name, categories.category_name
        FROM job_applications
        INNER JOIN jobs ON jobs.job_id = job_applications.job_id
        INNER JOIN users ON users.user_id = job_applications.worker_id
        INNER JOIN categories ON categories.category_id = jobs.category_id
        WHERE job_applications.worker_id = ?
        ORDER BY job_applications.application_id DESC");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $applications = fetch_all_rows(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$adminStats = [
    'Total Users' => count_rows($conn, "SELECT COUNT(*) total FROM users WHERE role = 'Employer'"),
    'Total Workers' => count_rows($conn, "SELECT COUNT(*) total FROM users WHERE role = 'Worker'"),
    'Active Jobs' => count_rows($conn, 'SELECT COUNT(*) total FROM jobs'),
    'Completed Services' => count_rows($conn, "SELECT COUNT(*) total FROM booking_requests WHERE status = 'Completed'"),
    'Revenue' => count_rows($conn, "SELECT COALESCE(SUM(salary), 0) total FROM jobs INNER JOIN booking_requests ON booking_requests.job_id = jobs.job_id WHERE booking_requests.status IN ('Accepted','Completed')"),
];

$allUsers = $role === 'Admin' ? fetch_all_rows(mysqli_query($conn, 'SELECT user_id, full_name, email, phone, role FROM users ORDER BY user_id DESC LIMIT 50')) : [];
$allJobs = $role === 'Admin' ? fetch_all_rows(mysqli_query($conn, "SELECT jobs.*, categories.category_name, users.full_name AS employer_name FROM jobs INNER JOIN categories ON categories.category_id = jobs.category_id INNER JOIN users ON users.user_id = jobs.employer_id ORDER BY jobs.job_id DESC LIMIT 50")) : [];
$reviews = $role === 'Admin' ? fetch_all_rows(mysqli_query($conn, 'SELECT * FROM reviews ORDER BY review_id DESC LIMIT 20')) : [];
$complaints = $role === 'Admin' ? fetch_all_rows(mysqli_query($conn, 'SELECT * FROM complaints ORDER BY complaint_id DESC LIMIT 20')) : [];

function current_status_for_booking(array $booking, array $history): string {
    $workerHistory = $history[(int) $booking['worker_id']] ?? [];
    foreach ($workerHistory as $event) {
        if ((int) ($event['service_id'] ?? 0) === (int) $booking['job_id'] && (int) ($event['employer_id'] ?? 0) === (int) $booking['employer_id']) {
            return $event['status'];
        }
    }
    if ($booking['status'] === 'Accepted') {
        return 'Selected/Hired';
    }
    if ($booking['status'] === 'Completed') {
        return 'Service Completed';
    }
    if ($booking['status'] === 'Rejected') {
        return 'Available Again';
    }
    return 'Request Received';
}

$reviewedBookingIds = [];
$pendingReview = null;
if ($role === 'Employer') {
    $stmt = mysqli_prepare($conn, 'SELECT booking_id FROM reviews WHERE employer_id = ? AND booking_id IS NOT NULL');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $reviewedBookingIds[(int) $row['booking_id']] = true;
    }
    mysqli_stmt_close($stmt);

    foreach ($bookings as $booking) {
        $bookingId = (int) $booking['booking_id'];
        if (!isset($reviewedBookingIds[$bookingId]) && current_status_for_booking($booking, $statusHistory) === 'Service Completed') {
            $pendingReview = $booking;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!$pendingReview): ?><meta http-equiv="refresh" content="30"><?php endif; ?>
<title><?php echo e($role); ?> Dashboard - Ghar Sathi</title>
<link rel="stylesheet" href="../JobsPage/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>
body{background:#f7f8fa}.dashboard{max-width:1200px;margin:0 auto;padding:30px 20px}.dash-top{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:24px}.dash-title{display:flex;align-items:center;gap:14px}.dash-avatar{width:58px;height:58px;border-radius:50%;object-fit:cover;border:3px solid #edf5f2}.dash-top h1{color:#132766}.dash-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px}.dash-card,.dash-section{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:20px;box-shadow:0 10px 24px rgba(16,35,74,.08)}.dash-card strong{display:block;font-size:28px;color:#28a745}.dash-section{margin-bottom:22px}.dash-section h2{color:#132766;margin-bottom:14px}.item{border-top:1px solid #edf1ef;padding:14px 0}.item:first-of-type{border-top:0}.status{display:inline-flex;border-radius:999px;background:#edf5f2;color:#255747;padding:5px 10px;font-size:12px;font-weight:700}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}.actions input,.actions textarea,.actions select{border:1px solid #dfe7e3;border-radius:6px;padding:9px}.actions button,.nav-link{border:0;border-radius:6px;background:#28a745;color:#fff;padding:10px 14px;font-weight:600;text-decoration:none;cursor:pointer}.actions button.danger{background:#b42318}.muted{color:#687383}.progress{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}.progress span{border:1px solid #dfe7e3;border-radius:999px;padding:7px 10px;color:#687383;background:#fff}.progress span.done{background:#28a745;color:#fff;border-color:#28a745}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #edf1ef;padding:10px;vertical-align:top}th{color:#132766}.review-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}.review-modal.is-open{display:flex}.review-dialog{width:100%;max-width:460px;background:#fff;border-radius:8px;padding:24px;box-shadow:0 24px 70px rgba(15,23,42,.25)}.review-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.review-head h2{color:#132766;margin:0 0 4px}.review-close{border:0;background:#edf1ef;color:#132766;border-radius:50%;width:34px;height:34px;font-size:22px;line-height:1;cursor:pointer}.star-rating{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px;margin:18px 0}.star-rating input{position:absolute;opacity:0}.star-rating label{font-size:34px;color:#cbd5e1;cursor:pointer}.star-rating input:checked ~ label,.star-rating label:hover,.star-rating label:hover ~ label{color:#f59e0b}.review-dialog textarea{width:100%;min-height:110px;border:1px solid #dfe7e3;border-radius:6px;padding:12px;font:inherit;resize:vertical}.review-dialog button[type=submit]{width:100%;margin-top:14px;border:0;border-radius:6px;background:#28a745;color:#fff;padding:12px 18px;font-weight:700;cursor:pointer}@media(max-width:900px){.dash-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.dash-grid{grid-template-columns:1fr}.dash-top{align-items:flex-start;flex-direction:column}.review-dialog{padding:20px}.star-rating label{font-size:30px}}
</style>
</head>
<body>
<header class="site-header">
<?php render_navbar($conn, '..', ''); ?>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks"><i class="fa-solid fa-bars"></i></button>
</header>
<main class="dashboard">
<div class="dash-top">
<div class="dash-title">
<?php if ($role === 'Worker'): ?><img class="dash-avatar" src="../images/profile.jpg" alt="<?php echo e($user['full_name']); ?>"><?php endif; ?>
<div>
<h1><?php echo e($role === 'Employer' ? 'Employer Dashboard' : ($role === 'Worker' ? $user['full_name'] : 'Admin Dashboard')); ?></h1>
<p class="muted"><?php echo e($user['full_name']); ?> | Live dashboard refreshes every 30 seconds.</p>
</div>
</div>
<a class="nav-link" href="../JobsPage/jobs.php">Browse Jobs</a>
</div>

<?php if ($role === 'Admin'): ?>
<section class="dash-grid">
<?php foreach ($adminStats as $label => $value): ?>
<div class="dash-card"><span><?php echo e($label); ?></span><strong><?php echo e($label === 'Revenue' ? 'Rs ' . number_format($value, 0) : $value); ?></strong></div>
<?php endforeach; ?>
</section>
<?php else: ?>
<section class="dash-grid">
<div class="dash-card"><span>Total Requests</span><strong><?php echo e(count($bookings)); ?></strong></div>
<div class="dash-card"><span>Pending</span><strong><?php echo e(count(array_filter($bookings, fn($b) => $b['status'] === 'Pending'))); ?></strong></div>
<div class="dash-card"><span>Accepted</span><strong><?php echo e(count(array_filter($bookings, fn($b) => $b['status'] === 'Accepted'))); ?></strong></div>
<div class="dash-card"><span>Completed</span><strong><?php echo e(count_rows($conn, "SELECT COUNT(*) total FROM booking_requests WHERE status = 'Completed' AND " . ($role === 'Worker' ? 'worker_id' : 'employer_id') . " = " . $userId)); ?></strong></div>
<div class="dash-card"><span>Notifications</span><strong><?php echo e(count($notifications)); ?></strong></div>
</section>
<?php endif; ?>

<section class="dash-section">
<h2>Notifications</h2>
<?php if (!$notifications): ?><p class="muted">No notifications yet.</p><?php endif; ?>
<?php foreach ($notifications as $note): ?>
<div class="item"><strong><?php echo e($note['title']); ?></strong><p><?php echo e($note['message']); ?></p><small><?php echo e($note['created_at']); ?></small></div>
<?php endforeach; ?>
</section>

<section class="dash-section">
<h2><?php echo e($role === 'Worker' ? 'Booking Requests' : ($role === 'Employer' ? 'My Hired Services' : 'All Bookings')); ?></h2>
<?php if (!$bookings): ?><p class="muted">No booking activity yet.</p><?php endif; ?>
<?php foreach ($bookings as $booking): ?>
<?php $currentStatus = current_status_for_booking($booking, $statusHistory); $currentIndex = array_search($currentStatus, $statusSteps, true); ?>
<div class="item">
<strong><?php echo e($booking['title']); ?></strong>
<p><?php echo e($booking['category_name']); ?> | Date: <?php echo e($booking['booking_date'] ?: $booking['requested_date']); ?> | <span class="status"><?php echo e($booking['status']); ?></span></p>
<?php $contactsVisible = in_array($booking['status'], ['Accepted', 'Completed'], true); ?>
<p>Employer: <?php echo e($booking['employer_name']); ?><?php echo $contactsVisible ? ' (' . e($booking['employer_phone'] ?: 'No phone') . ', ' . e($booking['employer_email'] ?: 'No email') . ')' : ''; ?> | Worker: <?php echo e($booking['worker_name']); ?><?php echo $contactsVisible ? ' (' . e($booking['worker_phone'] ?: 'No phone') . ', ' . e($booking['worker_email'] ?: 'No email') . ')' : ''; ?></p>
<?php if (!$contactsVisible): ?><p class="muted">Contact details appear after the worker accepts the hire request.</p><?php endif; ?>
<div class="progress">
<?php foreach ($statusSteps as $index => $step): ?>
<span class="<?php echo $currentIndex !== false && $index <= $currentIndex ? 'done' : ''; ?>"><?php echo e($step); ?></span>
<?php endforeach; ?>
</div>
<p class="muted">Notes: <?php echo e($booking['notes'] ?: 'No notes'); ?></p>
<?php if ($role === 'Worker' && $booking['status'] === 'Pending'): ?>
<form class="actions" method="POST">
<input type="hidden" name="booking_id" value="<?php echo e($booking['booking_id']); ?>">
<button formaction="../StatusBar/accept_booking.php" type="submit">Accept Request</button>
<button class="danger" formaction="../StatusBar/reject_booking.php" type="submit">Reject Request</button>
</form>
<?php endif; ?>
<?php if (($role === 'Worker' || $role === 'Admin') && $booking['status'] === 'Accepted'): ?>
<form class="actions" action="../StatusBar/update_status.php" method="POST">
<input type="hidden" name="booking_id" value="<?php echo e($booking['booking_id']); ?>">
<select name="status" required>
<option value="Employer Contacted">Employer Contacted</option>
<option value="Interview Scheduled">Interview Scheduled</option>
<option value="Currently Working">Start Service</option>
<option value="Service Completed">Mark Service Completed</option>
<option value="Available Again">Available Again</option>
</select>
<button type="submit">Update Status</button>
</form>
<?php endif; ?>
<?php if ($role === 'Employer' && ($currentStatus === 'Service Completed' || $booking['status'] === 'Completed')): ?>
<?php if (isset($reviewedBookingIds[(int) $booking['booking_id']])): ?>
<p class="status">Review submitted</p>
<?php else: ?>
<button class="nav-link js-open-review" type="button" data-review-booking="<?php echo e($booking['booking_id']); ?>" data-review-worker="<?php echo e($booking['worker_id']); ?>" data-review-worker-name="<?php echo e($booking['worker_name']); ?>" data-review-title="<?php echo e($booking['title']); ?>">Review Worker</button>
<?php endif; ?>
<?php endif; ?>
</div>
<?php endforeach; ?>
</section>

<?php if ($role === 'Employer'): ?>
<section class="dash-section">
<h2>Completed Worker Services</h2>
<?php $completedBookings = array_filter($bookings, fn($b) => $b['status'] === 'Completed' || current_status_for_booking($b, $statusHistory) === 'Service Completed'); ?>
<?php if (!$completedBookings): ?><p class="muted">No completed services yet.</p><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>Service</th><th>Completion Date</th><th>Employer</th><th>Status</th></tr></thead><tbody>
<?php foreach ($completedBookings as $booking): ?>
<?php $history = $statusHistory[(int) $booking['worker_id']] ?? []; $completedAt = $booking['updated_at']; foreach ($history as $event) { if ((int) ($event['service_id'] ?? 0) === (int) $booking['job_id'] && $event['status'] === 'Service Completed') { $completedAt = $event['completion_date'] ?: $event['updated_at']; break; } } ?>
<tr><td><?php echo e($booking['title']); ?></td><td><?php echo e($completedAt); ?></td><td><?php echo e($booking['employer_name']); ?></td><td><?php echo e($booking['status']); ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</section>
<?php endif; ?>

<?php if ($role !== 'Employer'): ?>
<section class="dash-section">
<h2><?php echo $role === 'Admin' ? 'Worker Verification' : 'My Resume Applications'; ?></h2>
<?php if (!$applications): ?><p class="muted">No resume applications yet.</p><?php endif; ?>
<?php foreach ($applications as $application): ?>
<div class="item">
<strong><?php echo e($application['title']); ?></strong>
<p><?php echo e($application['full_name']); ?> | <?php echo e($application['category_name']); ?> | <span class="status"><?php echo e($application['admin_status']); ?></span></p>
<p><?php echo e($application['resume_text']); ?></p>
<?php if (!empty($application['resume_file'])): ?><p><a href="<?php echo e(dashboard_document_href($application['resume_file'])); ?>">View Resume</a></p><?php endif; ?>
<?php if (!empty($application['police_report_file'])): ?><p><a href="<?php echo e(dashboard_document_href($application['police_report_file'])); ?>">View Police Report</a></p><?php endif; ?>
<?php if (!empty($application['citizenship_file'])): ?><p><a href="<?php echo e(dashboard_document_href($application['citizenship_file'])); ?>">View Citizenship Card</a></p><?php endif; ?>
<?php if ($role === 'Admin' && $application['admin_status'] === 'Pending'): ?>
<form class="actions" action="admin_application.php" method="POST">
<input type="hidden" name="application_id" value="<?php echo e($application['application_id']); ?>">
<button name="status" value="Verified" type="submit">Approve Worker</button>
<button class="danger" name="status" value="Declined" type="submit">Reject Worker</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($role === 'Admin'): ?>
<section class="dash-section">
<h2>Employment Status History</h2>
<div class="table-wrap"><table><thead><tr><th>Worker</th><th>Worker ID</th><th>Service</th><th>Current Employer</th><th>Hiring Date</th><th>Status</th><th>Updated</th></tr></thead><tbody>
<?php foreach ($statusHistory as $workerId => $events): ?>
<?php foreach ($events as $event): ?>
<tr><td><?php echo e($event['worker_name']); ?></td><td><?php echo e($event['worker_id']); ?></td><td><?php echo e($event['title'] ?: 'Service'); ?></td><td><?php echo e($event['employer_name'] ?: 'None'); ?></td><td><?php echo e($event['start_date'] ?: '-'); ?></td><td><?php echo e($event['status']); ?></td><td><?php echo e($event['updated_at']); ?></td></tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody></table></div>
</section>

<section class="dash-section">
<h2>Manage Users</h2>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th></tr></thead><tbody>
<?php foreach ($allUsers as $row): ?><tr><td><?php echo e($row['user_id']); ?></td><td><?php echo e($row['full_name']); ?></td><td><?php echo e($row['email']); ?></td><td><?php echo e($row['phone']); ?></td><td><?php echo e($row['role']); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>

<section class="dash-section">
<h2>Manage Jobs</h2>
<div class="table-wrap"><table><thead><tr><th>Job</th><th>Category</th><th>Employer</th><th>Salary</th><th>Location</th></tr></thead><tbody>
<?php foreach ($allJobs as $row): ?><tr><td><?php echo e($row['title']); ?></td><td><?php echo e($row['category_name']); ?></td><td><?php echo e($row['employer_name']); ?></td><td>Rs <?php echo e(number_format((float) $row['salary'], 0)); ?></td><td><?php echo e($row['location']); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>

<section class="dash-section">
<h2>Reviews and Complaints</h2>
<p class="muted">Reviews: <?php echo e(count($reviews)); ?> | Complaints: <?php echo e(count($complaints)); ?></p>
<?php foreach ($complaints as $complaint): ?><div class="item"><strong><?php echo e($complaint['subject']); ?></strong><p><?php echo e($complaint['message']); ?></p><span class="status"><?php echo e($complaint['status']); ?></span></div><?php endforeach; ?>
</section>
<?php endif; ?>
</main>
<?php if ($role === 'Employer'): ?>
<?php
$modalBookingId = (int) ($pendingReview['booking_id'] ?? 0);
$modalWorkerId = (int) ($pendingReview['worker_id'] ?? 0);
$modalWorkerName = $pendingReview['worker_name'] ?? 'Worker';
$modalServiceTitle = $pendingReview['title'] ?? 'Completed service';
?>
<div class="review-modal <?php echo $pendingReview ? 'is-open' : ''; ?>" id="reviewModal" aria-hidden="<?php echo $pendingReview ? 'false' : 'true'; ?>">
<div class="review-dialog" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
<div class="review-head">
<div>
<h2 id="reviewModalTitle">Rate <?php echo e($modalWorkerName); ?></h2>
<p class="muted" id="reviewModalService"><?php echo e($modalServiceTitle); ?></p>
</div>
<button class="review-close" type="button" data-review-close aria-label="Close review form">&times;</button>
</div>
<form id="reviewForm" action="../review/submit_review.php" method="POST">
<input type="hidden" name="booking_id" id="reviewBookingId" value="<?php echo e($modalBookingId); ?>">
<input type="hidden" name="worker_id" id="reviewWorkerId" value="<?php echo e($modalWorkerId); ?>">
<div class="star-rating" aria-label="Rating">
<input type="radio" id="review-star-5" name="rating" value="5" required><label for="review-star-5" title="5 stars">&#9733;</label>
<input type="radio" id="review-star-4" name="rating" value="4"><label for="review-star-4" title="4 stars">&#9733;</label>
<input type="radio" id="review-star-3" name="rating" value="3"><label for="review-star-3" title="3 stars">&#9733;</label>
<input type="radio" id="review-star-2" name="rating" value="2"><label for="review-star-2" title="2 stars">&#9733;</label>
<input type="radio" id="review-star-1" name="rating" value="1"><label for="review-star-1" title="1 star">&#9733;</label>
</div>
<textarea name="review_comment" id="reviewComment" placeholder="Share your experience" required></textarea>
<button type="submit">Submit Review</button>
</form>
</div>
</div>
<script>
const reviewModal = document.getElementById("reviewModal");
const reviewBookingId = document.getElementById("reviewBookingId");
const reviewWorkerId = document.getElementById("reviewWorkerId");
const reviewTitle = document.getElementById("reviewModalTitle");
const reviewService = document.getElementById("reviewModalService");
const reviewComment = document.getElementById("reviewComment");

function openReviewModal(button) {
    if (!reviewModal || !button) return;
    reviewBookingId.value = button.dataset.reviewBooking || "";
    reviewWorkerId.value = button.dataset.reviewWorker || "";
    reviewTitle.textContent = `Rate ${button.dataset.reviewWorkerName || "Worker"}`;
    reviewService.textContent = button.dataset.reviewTitle || "Completed service";
    reviewModal.classList.add("is-open");
    reviewModal.setAttribute("aria-hidden", "false");
    setTimeout(() => document.getElementById("review-star-5")?.focus(), 80);
}

document.querySelectorAll(".js-open-review").forEach((button) => {
    button.addEventListener("click", () => openReviewModal(button));
});

document.querySelectorAll("[data-review-close]").forEach((button) => {
    button.addEventListener("click", () => {
        reviewModal?.classList.remove("is-open");
        reviewModal?.setAttribute("aria-hidden", "true");
    });
});

if (reviewModal?.classList.contains("is-open")) {
    setTimeout(() => document.getElementById("review-star-5")?.focus(), 80);
}
</script>
<?php endif; ?>
<?php render_footer('..'); ?>
<script src="../JobsPage/jobs.js"></script>
</body>
</html>
