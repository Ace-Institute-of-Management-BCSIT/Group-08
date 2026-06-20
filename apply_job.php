<?php
require_once __DIR__ . '/includes/app.php';

$user = require_user($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Jobs page/jobs.php');
    exit();
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$coverLetter = trim($_POST['cover_letter'] ?? '');
$resumeText = trim($_POST['resume_text'] ?? '');
$resumeId = null;

if ($jobId <= 0 || $resumeText === '') {
    header('Location: Jobs page/jobs.php?application=missing');
    exit();
}

if (!empty($_FILES['resume']['name'])) {
    $file = $_FILES['resume'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file['error'] === UPLOAD_ERR_OK && in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        $uploadDir = __DIR__ . '/uploads/resumes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $storedName = $user['user_id'] . '_' . time() . '_' . $safeName;
        $relativePath = 'uploads/resumes/' . $storedName;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $storedName)) {
            $stmt = mysqli_prepare($conn, 'INSERT INTO resume_uploads (user_id, file_name, file_path) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iss', $user['user_id'], $safeName, $relativePath);
            mysqli_stmt_execute($stmt);
            $resumeId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
    } else {
        header('Location: About Us Page/apply_resume.php?job_id=' . $jobId . '&error=file');
        exit();
    }
} else {
    $stmt = mysqli_prepare($conn, 'SELECT resume_id FROM resume_uploads WHERE user_id = ? ORDER BY resume_id DESC LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $user['user_id']);
    mysqli_stmt_execute($stmt);
    $latest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($latest) {
        $resumeId = (int) $latest['resume_id'];
    }
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO applications (job_id, worker_id, resume_id, cover_letter, status, admin_status)
     VALUES (?, ?, ?, ?, 'Applied', 'Pending')"
);
mysqli_stmt_bind_param($stmt, 'iiis', $jobId, $user['user_id'], $resumeId, $coverLetter);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO job_applications (job_id, worker_id, cover_letter, status, resume_text, admin_status)
     VALUES (?, ?, ?, 'Applied', ?, 'Pending')"
);
mysqli_stmt_bind_param($stmt, 'iiss', $jobId, $user['user_id'], $coverLetter, $resumeText);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT category_id, title FROM jobs WHERE job_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $jobId);
mysqli_stmt_execute($stmt);
$job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($job) {
    $categoryId = (int) $job['category_id'];
    $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO worker_categories (worker_id, category_id) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ii', $user['user_id'], $categoryId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $admins = mysqli_query($conn, "SELECT user_id FROM users WHERE role = 'Admin'");
    while ($admin = $admins ? mysqli_fetch_assoc($admins) : null) {
        create_notification($conn, (int) $admin['user_id'], 'Application needs verification', $user['full_name'] . ' applied for ' . $job['title'] . '.');
    }
}

header('Location: dashboard.php?application=submitted');
exit();
?>
