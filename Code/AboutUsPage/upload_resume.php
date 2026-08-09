<?php
/**
 * Processes standalone resume uploads for authenticated users.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Worker') {
    header('Location: ../dasboard/dashboard.php?resume=worker-required');
    exit();
}

$message = '';
$messageClass = 'error';
$redirect = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '');
if (!in_array($redirect, ['', 'aboutus.php'], true)) {
    $redirect = '';
}

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['resume']['name'])) {
        $message = 'Please choose a resume file.';
    } else {
        $file = $_FILES['resume'];
        $allowed = ['pdf', 'doc', 'docx'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Could not upload the resume. Please try again.';
        } elseif (!allowed_upload_extension($file, $allowed)) {
            $message = 'Invalid file type.';
        } else {
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
            $relativePath = save_uploaded_file($file, 'AboutUsPage/uploads/resumes', (int) $user['user_id'], $allowed);

            if ($relativePath) {
                $stmt = mysqli_prepare($conn, 'INSERT INTO resume_uploads (user_id, file_name, file_path) VALUES (?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'iss', $user['user_id'], $safeName, $relativePath);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $message = 'Resume uploaded successfully.';
                $messageClass = 'success';
            } else {
                $message = 'Could not save the resume file.';
            }
        }
    }

    if ($redirect !== '') {
        $status = $messageClass === 'success' ? 'success' : ($message === 'Invalid file type.' ? 'invalid' : 'error');
        header('Location: ' . str_replace(' ', '%20', $redirect) . '?resume=' . $status);
        exit();
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
<title>Upload Resume - Ghar Sathi</title>
<link rel="icon" type="image/svg+xml" href="../images/logo-favicon.svg">
<link rel="stylesheet" href="../JobsPage/jobs.css">
<style>
.upload-page{max-width:640px;margin:40px auto;padding:0 20px}.upload-box{background:#fff;border:1px solid #dfe7e3;border-radius:8px;padding:28px;box-shadow:0 10px 24px rgba(16,35,74,.08)}.upload-box h1{color:#132766;margin-bottom:14px}.upload-box input{width:100%;border:1px solid #dfe7e3;border-radius:6px;padding:12px;margin:14px 0}.upload-box button,.upload-box a{display:inline-flex;border:0;border-radius:6px;background:#28a745;color:#fff;padding:12px 18px;text-decoration:none;font-weight:600}.success{color:#28a745}.error{color:#b42318}
</style>
</head>
<body>
<main class="upload-page">
<div class="upload-box">
<h1>Upload Resume</h1>
<?php if ($message): ?><p class="<?php echo e($messageClass); ?>"><?php echo e($message); ?></p><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">
<input type="file" name="resume" accept=".pdf,.doc,.docx" required>
<button type="submit">Upload Resume</button>
<a href="../dasboard/dashboard.php">Dashboard</a>
</form>
</div>
</main>
</body>
</html>
