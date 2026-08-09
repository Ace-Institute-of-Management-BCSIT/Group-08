<?php
/**
 * Processes worker responses to hire requests.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';

$user = require_user($conn);
if ($user['role'] !== 'Worker') {
    header('Location: ../dasboard/dashboard.php?response=worker-required');
    exit();
}

// ===========================
// Request Handling
// ===========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dasboard/dashboard.php');
    exit();
}

$requestId = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$negotiatedSalary = (float) ($_POST['negotiated_salary'] ?? 0);
$workerMessage = trim($_POST['worker_message'] ?? '');

$allowed = ['Accepted', 'Declined', 'Negotiating'];
if ($requestId <= 0 || !in_array($action, $allowed, true)) {
    header('Location: ../dasboard/dashboard.php?response=invalid');
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT hire_requests.*, jobs.title, employers.full_name AS employer_name
     FROM hire_requests
     INNER JOIN jobs ON jobs.job_id = hire_requests.job_id
     INNER JOIN users employers ON employers.user_id = hire_requests.employer_id
     WHERE hire_requests.request_id = ? AND hire_requests.worker_id = ?
     LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'ii', $requestId, $user['user_id']);
mysqli_stmt_execute($stmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$request) {
    header('Location: ../dasboard/dashboard.php?response=not-found');
    exit();
}

$salary = (float) $request['offered_salary'];
if ($action === 'Negotiating' && $negotiatedSalary > 0) {
    $salary = $negotiatedSalary;
}

$stmt = mysqli_prepare($conn, 'UPDATE hire_requests SET status = ?, offered_salary = ?, worker_message = ? WHERE request_id = ?');
mysqli_stmt_bind_param($stmt, 'sdsi', $action, $salary, $workerMessage, $requestId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

create_notification(
    $conn,
    (int) $request['employer_id'],
    'Hire request ' . strtolower($action),
    $user['full_name'] . ' responded to your ' . $request['title'] . ' request. Current price: Rs ' . number_format($salary, 0) . '.'
);

header('Location: ../dasboard/dashboard.php?response=updated');
exit();
?>
