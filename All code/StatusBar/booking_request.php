<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Employer') {
    header('Location: ../dasboard/dashboard.php?booking=employer-required');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../JobsPage/jobs.php');
    exit();
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$workerId = (int) ($_POST['worker_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$requestedDate = trim($_POST['booking_date'] ?? $_POST['requested_date'] ?? '');
$requestedTime = trim($_POST['requested_time'] ?? '');
$offeredSalary = (float) ($_POST['offered_salary'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$serviceCategory = trim($_POST['service_category'] ?? '');

if ($jobId <= 0 || $workerId <= 0 || $categoryId <= 0 || $requestedDate === '') {
    $status = 'Please choose a worker, date, and service category.';
} else {
    $stmt = mysqli_prepare($conn, 'SELECT booking_id FROM booked_dates WHERE worker_id = ? AND booking_date = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'is', $workerId, $requestedDate);
    mysqli_stmt_execute($stmt);
    $alreadyBooked = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($alreadyBooked) {
        $status = 'This service provider is already booked for the selected date.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO booking_requests (job_id, employer_id, worker_id, service_id, category_id, booking_date, requested_date, service_category, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );
        mysqli_stmt_bind_param($stmt, 'iiiiissss', $jobId, $user['user_id'], $workerId, $jobId, $categoryId, $requestedDate, $requestedDate, $serviceCategory, $notes);
        $saved = mysqli_stmt_execute($stmt);
        $bookingId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        if ($bookingId > 0) {
            $sync = mysqli_prepare($conn, 'UPDATE booking_requests SET request_id = ? WHERE booking_id = ?');
            mysqli_stmt_bind_param($sync, 'ii', $bookingId, $bookingId);
            mysqli_stmt_execute($sync);
            mysqli_stmt_close($sync);
        }

        $jobStmt = mysqli_prepare($conn, 'SELECT title, salary FROM jobs WHERE job_id = ? LIMIT 1');
        mysqli_stmt_bind_param($jobStmt, 'i', $jobId);
        mysqli_stmt_execute($jobStmt);
        $job = mysqli_fetch_assoc(mysqli_stmt_get_result($jobStmt));
        mysqli_stmt_close($jobStmt);

        if ($saved) {
            $workerSalary = (float) ($job['salary'] ?? 0);
            $minimumOffer = max(0, $workerSalary - 20);
            if ($offeredSalary < $minimumOffer) {
                $offeredSalary = $minimumOffer;
            }

            $hireStatus = 'Pending';
            $hireMessage = $notes ?: 'Employer requested this service booking.';
            $hireStmt = mysqli_prepare(
                $conn,
                'INSERT INTO hire_requests (job_id, employer_id, worker_id, requested_date, requested_time, worker_salary, offered_salary, status, employer_message)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($hireStmt, 'iiissddss', $jobId, $user['user_id'], $workerId, $requestedDate, $requestedTime, $workerSalary, $offeredSalary, $hireStatus, $hireMessage);
            mysqli_stmt_execute($hireStmt);
            mysqli_stmt_close($hireStmt);

            create_notification($conn, $workerId, 'New booking request', $user['full_name'] . ' requested ' . ($job['title'] ?? 'a service') . ' for ' . $requestedDate . '.');
            record_employment_status($conn, $workerId, (int) $user['user_id'], $jobId, 'Request Received', $requestedDate);
            $status = 'Your request has been sent to the service provider.';
        } else {
            $status = 'Could not send booking request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Request - Ghar Sathi</title>
<style>
*{box-sizing:border-box;font-family:Arial,sans-serif}body{margin:0;background:#f4f6f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.box{background:#fff;max-width:480px;width:100%;padding:36px;border-radius:12px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,.1)}h2{color:#132766}a{display:inline-block;margin-top:18px;background:#28a745;color:#fff;text-decoration:none;padding:12px 22px;border-radius:5px}
</style>
</head>
<body>
<div class="box">
<h2>Ghar Sathi</h2>
<p><?php echo e($status); ?></p>
<a href="../JobsPage/jobs.php">Back to Jobs</a>
</div>
</body>
</html>
