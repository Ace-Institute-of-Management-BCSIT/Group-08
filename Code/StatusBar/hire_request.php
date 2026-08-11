<?php
/**
 * Creates hire requests sent from employers to workers.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Employer') {
    header('Location: ../dasboard/dashboard.php?hire=employer-required');
    exit();
}

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../JobsPage/jobs.php');
    exit();
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$workerId = (int) ($_POST['worker_id'] ?? 0);
$date = trim($_POST['requested_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? $_POST['requested_time'] ?? '');
$finishTime = trim($_POST['finish_time'] ?? '');
$offeredSalary = (float) ($_POST['offered_salary'] ?? 0);
$timeColumn = mysqli_query($conn, "SHOW COLUMNS FROM hire_requests LIKE 'requested_finish_time'");
$hireTimeColumnReady = (bool) ($timeColumn && mysqli_fetch_assoc($timeColumn));

if ($jobId <= 0 || $workerId <= 0 || !valid_date($date) || !valid_time($startTime) || !valid_time($finishTime) || $startTime === '' || $finishTime === '' || $finishTime <= $startTime || $offeredSalary < 0 || !$hireTimeColumnReady) {
    header('Location: ../JobsPage/jobs.php?hire=missing');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT j.title, j.salary FROM jobs j INNER JOIN worker_categories wc ON wc.category_id=j.category_id WHERE j.job_id=? AND wc.worker_id=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $jobId, $workerId);
mysqli_stmt_execute($stmt);
$job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$job) {
    header('Location: ../JobsPage/jobs.php?hire=job-not-found');
    exit();
}

$workerSalary = (float) $job['salary'];
$minimumOffer = max(0, $workerSalary - 20);
if ($offeredSalary < $minimumOffer) {
    $offeredSalary = $minimumOffer;
}

$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO hire_requests (job_id, employer_id, worker_id, requested_date, requested_time, requested_finish_time, worker_salary, offered_salary, status, employer_message)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$status = 'Pending';
$message = 'Employer requested this service booking.';
mysqli_stmt_bind_param($stmt, 'iiisssddss', $jobId, $user['user_id'], $workerId, $date, $startTime, $finishTime, $workerSalary, $offeredSalary, $status, $message);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

create_notification(
    $conn,
    $workerId,
    'New hire request',
    $user['full_name'] . ' wants to hire you for ' . $job['title'] . ' on ' . $date . ' from ' . $startTime . ' to ' . $finishTime . ' for Rs ' . number_format($offeredSalary, 0) . '.'
);

header('Location: ../dasboard/dashboard.php?hire=sent');
exit();
?>
