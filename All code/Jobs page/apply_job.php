<?php
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Worker') {
    header('Location: ../dasboard/dashboard.php?application=worker-required');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: jobs.php');
    exit();
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$coverLetter = trim($_POST['cover_letter'] ?? '');
$resumeText = trim($_POST['resume_text'] ?? '');
$resumeId = null;
$resumePath = null;
$policeReportPath = null;
$citizenshipPath = null;
$allowedDocs = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

if ($jobId <= 0 || $resumeText === '') {
    header('Location: jobs.php?application=missing');
    exit();
}

if (!empty($_FILES['resume']['name'])) {
    $file = $_FILES['resume'];
    if ($file['error'] === UPLOAD_ERR_OK && allowed_upload_extension($file, $allowedDocs)) {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $relativePath = save_uploaded_file($file, 'Jobs page/uploads/resumes', (int) $user['user_id'], $allowedDocs);
        if ($relativePath) {
            $resumePath = $relativePath;
            $stmt = mysqli_prepare($conn, 'INSERT INTO resume_uploads (user_id, file_name, file_path) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iss', $user['user_id'], $safeName, $relativePath);
            mysqli_stmt_execute($stmt);
            $resumeId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
    } else {
        header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=file');
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

if (!empty($_FILES['police_report']['name'])) {
    if (!allowed_upload_extension($_FILES['police_report'], $allowedDocs)) {
        header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=file');
        exit();
    }
    $policeReportPath = save_uploaded_file($_FILES['police_report'], 'Jobs page/uploads/police_reports', (int) $user['user_id'], $allowedDocs);
    if (!$policeReportPath) {
        header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=upload');
        exit();
    }
}

if (empty($_FILES['citizenship_card']['name'])) {
    header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=citizenship');
    exit();
}
if (!allowed_upload_extension($_FILES['citizenship_card'], $allowedDocs)) {
    header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=file');
    exit();
}
$citizenshipPath = save_uploaded_file($_FILES['citizenship_card'], 'Jobs page/uploads/citizenship', (int) $user['user_id'], $allowedDocs);
if (!$citizenshipPath) {
    header('Location: ../About Us Page/apply_resume.php?job_id=' . $jobId . '&error=upload');
    exit();
}

$safeCitizenshipName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['citizenship_card']['name']));
$stmt = mysqli_prepare($conn, 'INSERT INTO citizenship_uploads (user_id, file_name, file_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_name = VALUES(file_name), file_path = VALUES(file_path), upload_date = CURRENT_TIMESTAMP');
mysqli_stmt_bind_param($stmt, 'iss', $user['user_id'], $safeCitizenshipName, $citizenshipPath);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO applications (job_id, worker_id, resume_id, cover_letter, status, admin_status, resume_path, police_report_path, citizenship_card_path, upload_date)
     VALUES (?, ?, ?, ?, 'Applied', 'Pending', ?, ?, ?, NOW())"
);
mysqli_stmt_bind_param($stmt, 'iiissss', $jobId, $user['user_id'], $resumeId, $coverLetter, $resumePath, $policeReportPath, $citizenshipPath);
mysqli_stmt_execute($stmt);
$applicationId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO job_applications (job_id, worker_id, cover_letter, status, resume_text, resume_file, police_report_file, citizenship_file, admin_status)
     VALUES (?, ?, ?, 'Applied', ?, ?, ?, ?, 'Pending')"
);
mysqli_stmt_bind_param($stmt, 'iisssss', $jobId, $user['user_id'], $coverLetter, $resumeText, $resumePath, $policeReportPath, $citizenshipPath);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO application_documents (user_id, job_id, application_id, resume_path, police_report_path, citizenship_card_path)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE application_id = VALUES(application_id), resume_path = VALUES(resume_path), police_report_path = VALUES(police_report_path), citizenship_card_path = VALUES(citizenship_card_path), upload_date = CURRENT_TIMESTAMP'
);
mysqli_stmt_bind_param($stmt, 'iiisss', $user['user_id'], $jobId, $applicationId, $resumePath, $policeReportPath, $citizenshipPath);
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

header('Location: ../dasboard/dashboard.php?application=submitted');
exit();
?>
