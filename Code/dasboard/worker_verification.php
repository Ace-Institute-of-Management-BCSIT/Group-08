<?php
/** Processes an admin decision for a submitted worker police report. */

require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?verification=denied');
    exit();
}

$verificationId = (int) ($_POST['verification_id'] ?? 0);
$status = $_POST['status'] ?? '';
if ($verificationId <= 0 || !in_array($status, ['Accepted', 'Rejected'], true)) {
    header('Location: dashboard.php?verification=invalid');
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT worker_id, status FROM worker_verifications WHERE verification_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $verificationId);
mysqli_stmt_execute($stmt);
$verification = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$verification || $verification['status'] !== 'Pending') {
    header('Location: dashboard.php?verification=reviewed');
    exit();
}

$stmt = mysqli_prepare($conn, 'UPDATE worker_verifications SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE verification_id = ? AND status = \'Pending\'');
mysqli_stmt_bind_param($stmt, 'sii', $status, $user['user_id'], $verificationId);
mysqli_stmt_execute($stmt);
$updated = mysqli_stmt_affected_rows($stmt) === 1;
mysqli_stmt_close($stmt);

if ($updated) {
    $message = $status === 'Accepted'
        ? 'Your police report verification request has been accepted.'
        : 'Your police report verification request has been rejected. Please upload a clear and valid report to submit a new request.';
    create_notification($conn, (int) $verification['worker_id'], 'Police report verification: ' . $status, $message);
}

header('Location: dashboard.php?verification=' . ($updated ? 'updated' : 'reviewed'));
exit();
