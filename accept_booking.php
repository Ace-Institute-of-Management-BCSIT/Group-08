<?php
require_once __DIR__ . '/includes/app.php';

$user = require_user($conn);
$bookingId = (int) ($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: dashboard.php?booking=invalid');
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT booking_requests.*, categories.category_name
     FROM booking_requests
     INNER JOIN categories ON categories.category_id = booking_requests.category_id
     WHERE booking_id = ? AND worker_id = ?
     LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'ii', $bookingId, $user['user_id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: dashboard.php?booking=not-found');
    exit();
}

$bookingDate = $booking['booking_date'] ?: $booking['requested_date'];
$stmt = mysqli_prepare($conn, 'SELECT booking_id FROM booked_dates WHERE worker_id = ? AND booking_date = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'is', $booking['worker_id'], $bookingDate);
mysqli_stmt_execute($stmt);
$alreadyBooked = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($alreadyBooked) {
    header('Location: dashboard.php?booking=already-booked');
    exit();
}

$status = 'Accepted';
$stmt = mysqli_prepare($conn, 'UPDATE booking_requests SET status = ? WHERE booking_id = ?');
mysqli_stmt_bind_param($stmt, 'si', $status, $bookingId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$reserved = 'Reserved';
$stmt = mysqli_prepare($conn, 'INSERT INTO booked_dates (booking_id, worker_id, booking_date, request_id, status) VALUES (?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iisis', $bookingId, $booking['worker_id'], $bookingDate, $bookingId, $reserved);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

create_notification($conn, (int) $booking['employer_id'], 'Booking accepted', 'Your service request has been accepted.');
record_employment_status($conn, (int) $booking['worker_id'], (int) $booking['employer_id'], (int) $booking['job_id'], 'Selected/Hired', $bookingDate);

header('Location: dashboard.php?booking=accepted');
exit();
?>
