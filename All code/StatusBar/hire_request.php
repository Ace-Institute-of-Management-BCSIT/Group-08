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
$date = $_POST['requested_date'] ?? '';
$time = $_POST['requested_time'] ?? '';
$offeredSalary = (float) ($_POST['offered_salary'] ?? 0);

if ($jobId <= 0 || $workerId <= 0 || $date === '' || $time === '') {
    header('Location: ../JobsPage/jobs.php?hire=missing');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT title, salary FROM jobs WHERE job_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $jobId);
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
    'INSERT INTO hire_requests (job_id, employer_id, worker_id, requested_date, requested_time, worker_salary, offered_salary, status, employer_message)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$status = 'Pending';
$message = 'Employer requested this service booking.';
mysqli_stmt_bind_param($stmt, 'iiissddss', $jobId, $user['user_id'], $workerId, $date, $time, $workerSalary, $offeredSalary, $status, $message);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

create_notification(
    $conn,
    $workerId,
    'New hire request',
    $user['full_name'] . ' wants to hire you for ' . $job['title'] . ' on ' . $date . ' at ' . $time . ' for Rs ' . number_format($offeredSalary, 0) . '.'
);

header('Location: ../dasboard/dashboard.php?hire=sent');
exit();
?>
