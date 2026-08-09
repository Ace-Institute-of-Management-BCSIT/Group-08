<?php
/**
 * Configures the shared PHPMailer instance used for outbound email.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===========================
// Shared Helper Functions
// ===========================
function sendMail(string $toEmail, string $subject, string $body): bool {
    // config.php is loaded by the application bootstrap. Its settings are global,
    // so they must be imported into this function scope explicitly.
    global $smtp_host, $smtp_port, $smtp_encryption, $smtp_username, $smtp_password, $smtp_from_email, $smtp_from_name;

    $requiredSettings = [$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_from_email];
    foreach ($requiredSettings as $setting) {
        if ($setting === null || trim((string) $setting) === '' || str_starts_with((string) $setting, '<REPLACE')) {
            error_log('SMTP is not configured. Update config.php with external SMTP credentials.');
            return false;
        }
    }

    $encryption = strtolower(trim((string) ($smtp_encryption ?? 'tls')));
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        error_log('SMTP encryption must be set to tls or ssl in config.php.');
        return false;
    }

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('Email was not sent because the recipient address is invalid.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $smtp_port;
        $mail->Timeout    = 20;
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;

        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('SMTP send failed: ' . $mail->ErrorInfo);
        return false;
    }
}
