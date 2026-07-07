<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dasboard/dashboard.php');
    exit();
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$allowed = [
    'Employer Contacted',
    'Interview Scheduled',
    'Selected/Hired',
    'Currently Working',
    'Service Completed',
];

if ($bookingId <= 0 || !in_array($status, $allowed, true)) {
    header('Location: ../dasboard/dashboard.php?status=invalid');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM booking_requests WHERE booking_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $bookingId);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: ../dasboard/dashboard.php?status=missing');
    exit();
}

$canUpdate = $user['role'] === 'Admin'
    || ($user['role'] === 'Worker' && (int) $booking['worker_id'] === (int) $user['user_id']);

if (!$canUpdate) {
    header('Location: ../dasboard/dashboard.php?auth=denied');
    exit();
}

$startDate = in_array($status, ['Selected/Hired', 'Currently Working'], true) ? ($booking['booking_date'] ?: $booking['requested_date']) : null;
$completionDate = $status === 'Service Completed' ? date('Y-m-d') : null;

record_employment_status($conn, (int) $booking['worker_id'], (int) $booking['employer_id'], (int) $booking['job_id'], $status, $startDate, $completionDate);

if ($status === 'Service Completed') {
    $done = 'Completed';
    $stmt = mysqli_prepare($conn, 'UPDATE booking_requests SET status = ? WHERE booking_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $done, $bookingId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $available = 'Available';
    $stmt = mysqli_prepare($conn, 'UPDATE worker_profiles SET current_status = ? WHERE worker_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $available, $booking['worker_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$messages = [
    'Currently Working' => 'Your service has started.',
    'Service Completed' => 'Your service has been completed.',
];
if (isset($messages[$status])) {
    create_notification($conn, (int) $booking['employer_id'], 'Service update', $messages[$status]);
}

header('Location: ../dasboard/dashboard.php?status=updated');
exit();
?>
