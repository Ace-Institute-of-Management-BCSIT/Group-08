<?php
require_once __DIR__ . '/includes/app.php';

$user = require_user($conn);
$bookingId = (int) ($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: dashboard.php?booking=invalid');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT * FROM booking_requests WHERE booking_id = ? AND worker_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $bookingId, $user['user_id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: dashboard.php?booking=not-found');
    exit();
}

$status = 'Rejected';
$stmt = mysqli_prepare($conn, 'UPDATE booking_requests SET status = ? WHERE booking_id = ?');
mysqli_stmt_bind_param($stmt, 'si', $status, $bookingId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

create_notification($conn, (int) $booking['employer_id'], 'Booking rejected', 'Your service request has been rejected.');
record_employment_status($conn, (int) $booking['worker_id'], (int) $booking['employer_id'], (int) $booking['job_id'], 'Available Again');

header('Location: dashboard.php?booking=rejected');
exit();
?>
