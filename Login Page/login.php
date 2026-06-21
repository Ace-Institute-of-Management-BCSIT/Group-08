<?php
require_once __DIR__ . '/../includes/app.php';

$message = '';
$messageClass = 'error';
$backLink = 'login.html';
$backText = 'Back to Login';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

$login = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    $message = 'Please fill in all fields.';
} else {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT user_id, full_name, username, email, password, role
         FROM users
         WHERE username = ? OR email = ? OR phone = ?
         LIMIT 1'
    );

    if (!$stmt) {
        $message = 'Database error: please import database.sql and make sure the users table exists.';
    } else {
    mysqli_stmt_bind_param($stmt, 'sss', $login, $login, $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $passwordMatches = $user && (password_verify($password, $user['password']) || $password === $user['password']);
    if ($passwordMatches) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        header('Location: ../dasboard/dashboard.php');
        exit();
    } else {
        $message = 'Invalid username, email, phone, or password.';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; height: 100vh; display: flex; justify-content: center; align-items: center; }
        .message-box { background: white; padding: 40px; border-radius: 15px; text-align: center; width: 90%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .message-box h2 { margin-bottom: 20px; color: #132766; }
        .success { color: #28a745; font-size: 18px; margin-bottom: 25px; }
        .error { color: #dc3545; font-size: 18px; margin-bottom: 25px; }
        .btn { display: inline-block; text-decoration: none; background-color: #28a745; color: white; padding: 12px 25px; border-radius: 6px; transition: 0.3s; }
        .btn:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>Ghar Sathi</h2>
        <p class="<?php echo e($messageClass); ?>"><?php echo e($message); ?></p>
        <a href="<?php echo e($backLink); ?>" class="btn"><?php echo e($backText); ?></a>
    </div>
</body>
</html>
