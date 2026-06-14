<?php
require_once __DIR__ . '/../db_connect/db.php';

$messageText = '';
$messageClass = 'error';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contactus.html');
    exit();
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$displayName = trim($firstName . ' ' . $lastName);

if ($firstName === '' || $email === '' || $message === '') {
    $messageText = 'Please fill in all required fields.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $messageText = 'Please enter a valid email address.';
} else {
    $stmt = mysqli_prepare($conn, 'INSERT INTO contact_messages (first_name, email, message) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sss', $displayName, $email, $message);

    if (mysqli_stmt_execute($stmt)) {
        $messageText = 'Thank you, ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . '! Your message has been sent successfully.';
        $messageClass = 'success';
    } else {
        $messageText = 'Could not send your message. Please try again.';
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Status</title>
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
        <p class="<?php echo $messageClass; ?>"><?php echo $messageText; ?></p>
        <a href="contactus.html">Back to Contact</a>
    </div>
</body>
</html>
