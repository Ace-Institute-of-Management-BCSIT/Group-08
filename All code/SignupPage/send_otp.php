<?php
/**
 * Generates and emails signup OTP codes for account verification.
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/app.php';
    require_once __DIR__ . '/../includes/mail.php';

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }

    $check = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($check, 's', $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        mysqli_stmt_close($check);
        echo json_encode(['success' => false, 'error' => 'This email is already registered! Please login.']);
        exit;
    }
    mysqli_stmt_close($check);

    $otp = sprintf('%06d', random_int(0, 999999));

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO email_otps (email, otp, expires_at) VALUES (?, ?, NOW() + INTERVAL 5 MINUTE)
         ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = NOW() + INTERVAL 5 MINUTE'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $email, $otp);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $subject = 'Ghar Sathi - Your Verification OTP';
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; border: 1px solid #e3e1da; border-radius: 20px; overflow: hidden;'>
        <div style='background: #132766; padding: 20px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Ghar Sathi</h1>
        </div>
        <div style='padding: 30px 20px; background: #ffffff;'>
            <p style='color: #5e5e72; font-size: 15px;'>Use the following OTP to verify your email address. It expires in 5 minutes.</p>
            <div style='background: #eef5f2; padding: 20px; text-align: center; border-radius: 16px; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #132766; margin: 25px 0;'>
                {$otp}
            </div>
            <p style='color: #5e5e72; font-size: 13px; text-align: center;'>If you did not request this, please ignore this email.</p>
        </div>
    </div>
    ";

    if (sendMail($email, $subject, $body)) {
        if (!isset($_SESSION['email_verified'])) {
            $_SESSION['email_verified'] = [];
        }
        unset($_SESSION['email_verified'][$email]);

        echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send OTP. Check your Gmail App Password in includes/mail.php.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
