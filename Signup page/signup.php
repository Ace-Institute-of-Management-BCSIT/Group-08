<?php
require_once __DIR__ . '/../includes/app.php';

$message = '';
$messageClass = 'error';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.html');
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = $_POST['role'] ?? 'Worker';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
    $message = 'Please fill in all required fields.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Please enter a valid email address.';
} elseif (!in_array($role, ['Employer', 'Worker'], true)) {
    $message = 'Please choose a valid role.';
} elseif (strlen($password) < 6) {
    $message = 'Password must be at least 6 characters long.';
} elseif ($password !== $confirmPassword) {
    $message = 'Passwords do not match.';
} else {
    $check = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
    if (!$check) {
        $message = 'Database error: please import database.sql and make sure the users table exists.';
    } else {
    mysqli_stmt_bind_param($check, 'ss', $username, $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $message = 'An account with this username or email already exists.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO users (full_name, username, email, phone, password, role)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'ssssss', $fullName, $username, $email, $phone, $passwordHash, $role);

        if (mysqli_stmt_execute($stmt)) {
            $newUserId = mysqli_insert_id($conn);
            if ($role === 'Worker') {
                $profile = mysqli_prepare($conn, "INSERT INTO worker_profiles (worker_id, skills, experience_years, verification_status, current_status) VALUES (?, '', 0, 'Pending', 'Available')");
                mysqli_stmt_bind_param($profile, 'i', $newUserId);
                mysqli_stmt_execute($profile);
                mysqli_stmt_close($profile);
                record_employment_status($conn, $newUserId, null, null, 'Available');
            }
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = $role;
            header('Location: ../dasboard/dashboard.php');
            exit();
        } else {
            $message = 'Could not create account. Please try again.';
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f4f6f9; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .message-box { background: white; width: 100%; max-width: 500px; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .message-box h2 { color: #132766; margin-bottom: 20px; }
        .success { color: #28a745; font-size: 18px; margin-bottom: 25px; }
        .error { color: #dc3545; font-size: 18px; margin-bottom: 25px; }
        .btn { display: inline-block; background-color: #28a745; color: white; text-decoration: none; padding: 12px 25px; border-radius: 5px; transition: 0.3s; }
        .btn:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>Ghar Sathi</h2>
        <p class="<?php echo $messageClass; ?>"><?php echo $message; ?></p>
        <a href="../Login Page/login.html" class="btn">Go to Login</a>
    </div>
</body>
</html>
