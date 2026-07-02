<?php
require_once __DIR__ . '/../includes/app.php';

$token = trim($_GET['token'] ?? '');
$messageClass = 'error';
$message = 'Invalid or expired verification link.';

if ($token !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE verification_token = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($user) {
        $stmt = mysqli_prepare($conn, 'UPDATE users SET email_verified = 1, verification_token = NULL WHERE user_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $user['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $messageClass = 'success';
        $message = 'Your email has been verified. You can now log in to Ghar Sathi.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verification - Ghar Sathi</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif}body{background:#f4f6f9;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}.message-box{background:#fff;width:100%;max-width:500px;padding:40px;border-radius:15px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,.1)}h2{color:#132766;margin-bottom:20px}.success{color:#28a745;font-size:18px;margin-bottom:25px}.error{color:#dc3545;font-size:18px;margin-bottom:25px}.btn{display:inline-block;background:#28a745;color:#fff;text-decoration:none;padding:12px 25px;border-radius:5px}
</style>
</head>
<body>
<div class="message-box">
<h2>Ghar Sathi</h2>
<p class="<?php echo e($messageClass); ?>"><?php echo e($message); ?></p>
<a class="btn" href="../Login Page/login.html">Go to Login</a>
</div>
</body>
</html>
