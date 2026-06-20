<?php
require_once __DIR__ . '/includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Admin') {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$applicationId = (int) ($_POST['application_id'] ?? 0);
$status = $_POST['status'] ?? '';
if ($applicationId <= 0 || !in_array($status, ['Verified', 'Declined'], true)) {
    header('Location: dashboard.php?admin=invalid');
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT job_applications.worker_id, job_applications.job_id, jobs.title, jobs.category_id
     FROM job_applications
     INNER JOIN jobs ON jobs.job_id = job_applications.job_id
     WHERE application_id = ? LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'i', $applicationId);
mysqli_stmt_execute($stmt);
$application = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'UPDATE job_applications SET admin_status = ?, status = ? WHERE application_id = ?');
mysqli_stmt_bind_param($stmt, 'ssi', $status, $status, $applicationId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($application) {
    $stmt = mysqli_prepare($conn, 'UPDATE applications SET admin_status = ?, status = ? WHERE job_id = ? AND worker_id = ?');
    mysqli_stmt_bind_param($stmt, 'ssii', $status, $status, $application['job_id'], $application['worker_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($status === 'Verified') {
        $profile = mysqli_prepare($conn, "UPDATE worker_profiles SET verification_status = 'Approved' WHERE worker_id = ?");
        mysqli_stmt_bind_param($profile, 'i', $application['worker_id']);
        mysqli_stmt_execute($profile);
        $changed = mysqli_stmt_affected_rows($profile);
        mysqli_stmt_close($profile);
        if ($changed === 0) {
            $profile = mysqli_prepare($conn, "INSERT INTO worker_profiles (worker_id, skills, experience_years, verification_status, current_status) VALUES (?, '', 0, 'Approved', 'Available')");
            mysqli_stmt_bind_param($profile, 'i', $application['worker_id']);
            mysqli_stmt_execute($profile);
            mysqli_stmt_close($profile);
        }

        $category = mysqli_prepare($conn, 'INSERT IGNORE INTO worker_categories (worker_id, category_id) VALUES (?, ?)');
        mysqli_stmt_bind_param($category, 'ii', $application['worker_id'], $application['category_id']);
        mysqli_stmt_execute($category);
        mysqli_stmt_close($category);

        record_employment_status($conn, (int) $application['worker_id'], null, (int) $application['job_id'], 'Available');
    }

    create_notification($conn, (int) $application['worker_id'], 'Resume ' . strtolower($status), 'Your application for ' . $application['title'] . ' was ' . strtolower($status) . ' by admin.');
}

header('Location: dashboard.php?admin=updated');
exit();
?>
