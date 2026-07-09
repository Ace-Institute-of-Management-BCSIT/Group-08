<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/app.php';

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$otp   = trim($_POST['otp'] ?? '');

if (!$email || $otp === '') {
    echo json_encode(['success' => false, 'error' => 'Missing email or OTP.']);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM email_otps WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'ss', $email, $otp);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);

    $del = mysqli_prepare($conn, 'DELETE FROM email_otps WHERE email = ?');
    mysqli_stmt_bind_param($del, 's', $email);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    if (!isset($_SESSION['email_verified'])) {
        $_SESSION['email_verified'] = [];
    }
    $_SESSION['email_verified'][$email] = true;

    echo json_encode(['success' => true, 'message' => 'Email verified successfully.']);
} else {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP.']);
}
