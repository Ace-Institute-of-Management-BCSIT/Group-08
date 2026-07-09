<?php
/**
 * Processes newsletter subscription requests and redirects users back to the site.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$email = trim($_POST['email'] ?? '');
$redirect = trim($_POST['redirect'] ?? '../Homepage/homepage.php');
if ($redirect === '' || preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $redirect)) {
    $redirect = '../Homepage/homepage.php';
}
$status = 'Please enter a valid email address.';

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO subscribers (email) VALUES (?)');
    mysqli_stmt_bind_param($stmt, 's', $email);

    if (mysqli_stmt_execute($stmt)) {
        $status = mysqli_stmt_affected_rows($stmt) > 0
            ? 'Thank you for subscribing to Ghar Sathi updates!'
            : 'You are already subscribed to Ghar Sathi updates.';
    } else {
        $status = 'Could not subscribe right now. Please try again.';
    }

    mysqli_stmt_close($stmt);

    $legacy = mysqli_prepare($conn, 'INSERT IGNORE INTO newsletter_subscriptions (email) VALUES (?)');
    if ($legacy) {
        mysqli_stmt_bind_param($legacy, 's', $email);
        mysqli_stmt_execute($legacy);
        mysqli_stmt_close($legacy);
    }
}
// ===========================
// Page Rendering
// ===========================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Status</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .message-box { background: white; max-width: 460px; width: 100%; padding: 36px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h2 { color: #132766; }
        a { display: inline-block; margin-top: 16px; background: #28a745; color: white; text-decoration: none; padding: 12px 22px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>Ghar Sathi</h2>
        <p><?php echo e($status); ?></p>
        <a href="<?php echo e($redirect); ?>">Back</a>
    </div>
</body>
</html>
