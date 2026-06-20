<?php
require_once __DIR__ . '/includes/app.php';

$user = require_user($conn, 'Employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$workerId = (int) ($_POST['worker_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['review_comment'] ?? '');

if ($bookingId <= 0 || $workerId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    header('Location: dashboard.php?review=invalid');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT booking_id FROM booking_requests WHERE booking_id = ? AND employer_id = ? AND worker_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'iii', $bookingId, $user['user_id'], $workerId);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: dashboard.php?review=denied');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT review_id FROM reviews WHERE booking_id = ? AND employer_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $bookingId, $user['user_id']);
mysqli_stmt_execute($stmt);
$exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$exists) {
    $stmt = mysqli_prepare($conn, 'INSERT INTO reviews (reviewer_id, reviewee_id, worker_id, employer_id, booking_id, rating, comment, review_comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiiiiiss', $user['user_id'], $workerId, $workerId, $user['user_id'], $bookingId, $rating, $comment, $comment);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    create_notification($conn, $workerId, 'New review received', $user['full_name'] . ' left you a ' . $rating . '-star review.');
}

header('Location: dashboard.php?review=submitted');
exit();
?>
