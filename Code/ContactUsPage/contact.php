<?php
/**
 * Processes contact form submissions and stores customer messages.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$messageText = '';
$messageClass = 'error';

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contactus.php');
    exit();
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$displayName = trim($firstName . ' ' . $lastName);

if ($firstName === '' || $email === '' || $subject === '' || $message === '') {
    $messageText = 'Please fill in all required fields.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $messageText = 'Please enter a valid email address.';
} else {
    $stmt = mysqli_prepare($conn, 'INSERT INTO contact_messages (name, first_name, email, subject, message) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssss', $displayName, $displayName, $email, $subject, $message);

    if (mysqli_stmt_execute($stmt)) {
        $messageText = 'Thank you, ' . $firstName . '! Your message has been sent successfully.';
        $messageClass = 'success';
    } else {
        $messageText = 'Could not send your message. Please try again.';
    }

    mysqli_stmt_close($stmt);
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
    <title>Contact Status</title>
    <link rel="icon" type="image/svg+xml" href="../images/logo-favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .message-box { background: white; max-width: 480px; width: 100%; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h2 { color: #132766; margin-bottom: 18px; }
        p { margin-bottom: 24px; font-size: 18px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        a { display: inline-block; background: #28a745; color: white; text-decoration: none; padding: 12px 22px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>Ghar Sathi</h2>
        <p class="<?php echo e($messageClass); ?>"><?php echo e($messageText); ?></p>
        <a href="contactus.php">Back to Contact</a>
    </div>
</body>
</html>
