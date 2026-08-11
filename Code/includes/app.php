<?php
/** Shared application bootstrap and helpers. Database schema is imported separately. */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gharsathi_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Shows only the first and final character of a reviewer's name. */
function mask_reviewer_name(?string $name): string {
    $characters = preg_split('//u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $count = count($characters);
    if ($count === 0) return 'Anonymous';
    if ($count === 1) return $characters[0];
    return $characters[0] . str_repeat('*', $count - 2) . $characters[$count - 1];
}

function project_path(string $base, string $path): string {
    if (preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/|#)/i', $path) || str_starts_with($path, '../') || str_starts_with($path, './')) return $path;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function valid_date(string $value): bool { $date = DateTime::createFromFormat('Y-m-d', $value); return $date && $date->format('Y-m-d') === $value && $date >= new DateTime('today'); }
function valid_time(string $value): bool { return $value === '' || (bool) preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value); }

function allowed_upload_extension(array $file, array $allowed): bool {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['name']) || empty($file['tmp_name'])) return false;
    if (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) return false;
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) return false;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $validMimes = ['pdf'=>['application/pdf'], 'jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'], 'doc'=>['application/msword','application/CDFV2'], 'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip']];
    return in_array($mime, $validMimes[$extension] ?? [], true);
}

function save_uploaded_file(array $file, string $relativeDir, int $userId, array $allowed): ?string {
    if (!allowed_upload_extension($file, $allowed)) return null;
    $uploadDir = dirname(__DIR__) . '/' . trim(str_replace('\\', '/', $relativeDir), '/');
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) return null;
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $file['name']));
    $storedName = $userId . '_' . bin2hex(random_bytes(8)) . '_' . $safeName;
    return move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $storedName)
        ? trim($relativeDir, '/') . '/' . $storedName : null;
}

function profile_image_url(?string $filename, string $base = '..', ?string $fallback = null): string {
    $filename = trim((string) $filename);
    if ($filename === '' || strtolower(str_replace('\\', '/', $filename)) === 'images/profile.jpg') return $fallback ?: $base . '/images/clean.jpg';
    return str_contains($filename, '/') || str_contains($filename, '\\') ? $base . '/' . ltrim(str_replace('\\', '/', $filename), '/') : $base . '/images/' . $filename;
}

function service_image(string $category): string {
    $images = ['House Work'=>'../images/housework.jpg','Culinary Aid'=>'../images/culinary.jpg','Culinary Service'=>'../images/culinary.jpg','Home Tuition'=>'../images/tutor.jpg','Education'=>'../images/tutor.jpg','Pet Care'=>'../images/petgroom.jpeg','Self Care'=>'../images/selfcare.jpg','Elderly Care'=>'../images/elderlycare.jpg','Babysitting'=>'../images/childcare.jpg','Gardening'=>'../images/gardening.jpg','Plumbing'=>'../images/plumbing.jpg','Electrical Work'=>'../images/electrician.jpg','Repair'=>'../images/techRepair.jpg'];
    return $images[$category] ?? '../images/clean.jpg';
}

function worker_hourly_rate_available(mysqli $conn): bool {
    static $available = null;
    if ($available !== null) return $available;
    $result = mysqli_query($conn, "SHOW COLUMNS FROM worker_profiles LIKE 'hourly_rate'");
    return $available = (bool) ($result && mysqli_fetch_assoc($result));
}

function fetch_workers_for_category(mysqli $conn, int $categoryId, int $limit = 6): array {
    $hasHourlyRate = worker_hourly_rate_available($conn);
    $hourlyRateSelect = $hasHourlyRate ? 'COALESCE(wp.hourly_rate, 2000) AS hourly_rate,' : '2000 AS hourly_rate,';
    $hourlyRateGroup = $hasHourlyRate ? ',wp.hourly_rate' : '';
    $sql = "SELECT u.user_id,u.full_name,u.email,u.phone,wp.skills,wp.experience_years,$hourlyRateSelect wp.profile_image,wp.current_status,COALESCE(AVG(r.rating),0) AS avg_rating,COUNT(r.review_id) AS total_reviews FROM users u INNER JOIN (SELECT worker_id, MAX(profile_id) AS profile_id FROM worker_profiles GROUP BY worker_id) latest_profile ON latest_profile.worker_id=u.user_id INNER JOIN worker_profiles wp ON wp.profile_id=latest_profile.profile_id INNER JOIN worker_categories wc ON wc.worker_id=u.user_id LEFT JOIN reviews r ON r.worker_id=u.user_id WHERE u.role='Worker' AND wp.verification_status='Approved' AND wc.category_id=? GROUP BY u.user_id,u.full_name,u.email,u.phone,wp.skills,wp.experience_years,wp.profile_image,wp.current_status$hourlyRateGroup ORDER BY avg_rating DESC,wp.experience_years DESC,u.full_name LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, 'ii', $categoryId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

function booked_dates_by_worker(mysqli $conn, array $workerIds): array {
    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$workerIds) return [];
    $ids = implode(',', $workerIds);
    $result = mysqli_query($conn, "SELECT worker_id, booking_date FROM booked_dates WHERE worker_id IN ($ids) AND booking_date >= CURDATE()");
    $dates = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) $dates[(int) $row['worker_id']][] = $row['booking_date'];
    return $dates;
}

function fetch_latest_reviews(mysqli $conn, int $workerId, int $limit = 3): array {
    $stmt = mysqli_prepare($conn, 'SELECT r.rating,COALESCE(r.review_comment,r.comment) AS review_comment,COALESCE(r.review_date,r.created_at) AS review_date,u.full_name AS employer_name FROM reviews r LEFT JOIN users u ON u.user_id=COALESCE(r.employer_id,r.reviewer_id) WHERE r.worker_id=? OR r.reviewee_id=? ORDER BY r.review_id DESC LIMIT ?');
    mysqli_stmt_bind_param($stmt, 'iii', $workerId, $workerId, $limit); mysqli_stmt_execute($stmt); $result = mysqli_stmt_get_result($stmt); $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : []; mysqli_stmt_close($stmt); return $rows;
}

function fetch_worker_completed_history(mysqli $conn, int $workerId, int $limit = 5): array {
    $stmt = mysqli_prepare($conn, 'SELECT b.booking_id,j.title,u.full_name AS employer_name,es.completion_date,es.updated_at,r.rating,COALESCE(r.review_comment,r.comment) AS review_summary FROM employment_status es INNER JOIN jobs j ON j.job_id=es.service_id LEFT JOIN booking_requests b ON b.job_id=es.service_id AND b.worker_id=es.worker_id AND b.employer_id=es.employer_id LEFT JOIN users u ON u.user_id=es.employer_id LEFT JOIN reviews r ON r.booking_id=b.booking_id WHERE es.worker_id=? AND es.status=\'Service Completed\' ORDER BY es.id DESC LIMIT ?');
    mysqli_stmt_bind_param($stmt, 'ii', $workerId, $limit); mysqli_stmt_execute($stmt); $result = mysqli_stmt_get_result($stmt); $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : []; mysqli_stmt_close($stmt); return $rows;
}

function current_user(mysqli $conn): ?array {
    $id = (int) ($_SESSION['user_id'] ?? 0); if ($id <= 0) return null;
    $stmt = mysqli_prepare($conn, 'SELECT user_id,full_name,username,email,phone,role FROM users WHERE user_id=? LIMIT 1'); mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt); $result = mysqli_stmt_get_result($stmt); $user = $result ? mysqli_fetch_assoc($result) : null; mysqli_stmt_close($stmt); return $user ?: null;
}

function require_user(mysqli $conn, ?string $role = null): array {
    $user = current_user($conn); if (!$user) { header('Location: ../LoginPage/login.html'); exit; }
    if ($role && $user['role'] !== $role && $user['role'] !== 'Admin') { header('Location: ../dasboard/dashboard.php?auth=denied'); exit; }
    return $user;
}

function create_notification(mysqli $conn, int $userId, string $title, string $message): void { $stmt = mysqli_prepare($conn, 'INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)'); mysqli_stmt_bind_param($stmt, 'iss', $userId, $title, $message); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
function record_employment_status(mysqli $conn, int $workerId, ?int $employerId, ?int $serviceId, string $status, ?string $startDate = null, ?string $completionDate = null): void { $stmt = mysqli_prepare($conn, 'INSERT INTO employment_status (worker_id,employer_id,service_id,status,start_date,completion_date) VALUES (?,?,?,?,?,?)'); mysqli_stmt_bind_param($stmt, 'iiisss', $workerId, $employerId, $serviceId, $status, $startDate, $completionDate); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); $stmt = mysqli_prepare($conn, 'UPDATE worker_profiles SET current_status=? WHERE worker_id=?'); mysqli_stmt_bind_param($stmt, 'si', $status, $workerId); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
function latest_status_for_worker(mysqli $conn, int $workerId): string { $stmt = mysqli_prepare($conn, 'SELECT status FROM employment_status WHERE worker_id=? ORDER BY id DESC LIMIT 1'); mysqli_stmt_bind_param($stmt, 'i', $workerId); mysqli_stmt_execute($stmt); $result=mysqli_stmt_get_result($stmt); $row=$result?mysqli_fetch_assoc($result):null; mysqli_stmt_close($stmt); return $row['status'] ?? 'Available'; }

function render_navbar(mysqli $conn, string $base = '..', string $active = ''): void { $user=current_user($conn); $home=project_path($base,'Homepage/homepage.php'); $jobs=project_path($base,'JobsPage/jobs.php'); $about=project_path($base,'AboutUsPage/aboutus.php'); $contact=project_path($base,'ContactUsPage/contactus.php'); $dashboard=project_path($base,'dasboard/dashboard.php'); $login=project_path($base,'LoginPage/login.html'); $signup=project_path($base,'SignupPage/signup.html'); $logout=project_path($base,'LoginPage/logout.php'); $logo=project_path($base,'images/logo.png'); ?>
<nav class="navbar"><a class="logo" href="<?= e($home) ?>"><img src="<?= e($logo) ?>" alt="Ghar Sathi logo"><span>Ghar Sathi</span></a><ul class="nav-links" id="navLinks"><li><a class="<?= $active==='home'?'active':'' ?>" href="<?= e($home) ?>">Home</a></li><li><a class="<?= $active==='jobs'?'active':'' ?>" href="<?= e($jobs) ?>">Jobs</a></li><li><a class="<?= $active==='about'?'active':'' ?>" href="<?= e($about) ?>">About Us</a></li><li><a class="<?= $active==='contact'?'active':'' ?>" href="<?= e($contact) ?>">Contact Us</a></li><li class="mobile-auth"><?php if($user): ?><a href="<?= e($dashboard) ?>">Dashboard</a><a href="<?= e($logout) ?>">Logout</a><?php else: ?><a href="<?= e($login) ?>">Login</a><a href="<?= e($signup) ?>">Sign Up</a><?php endif; ?></li></ul><div class="auth-buttons nav-buttons"><?php if($user): ?><a class="signup-btn" href="<?= e($dashboard) ?>">Dashboard</a><a class="login-btn" href="<?= e($logout) ?>">Logout</a><?php else: ?><a class="login-btn" href="<?= e($login) ?>">Login</a><a class="signup-btn" href="<?= e($signup) ?>">Sign Up</a><?php endif; ?></div><button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks"><i class="fa-solid fa-bars"></i></button></nav>
<?php }
function render_footer(string $base = '..', string $redirect = 'Homepage/homepage.php'): void {
    $subscribe = project_path($base, 'ContactUsPage/subscribe.php');
    $redirectPath = project_path($base, $redirect);
    $about = project_path($base, 'AboutUsPage/aboutus.php');
    $jobs = project_path($base, 'JobsPage/jobs.php');
?>
<footer>
    <div class="footer-container">
        <div class="footer-column">
            <h3>Job</h3>
            <p>Ghar Sathi connects skilled people with trusted opportunities, making everyday services easier, faster, and more reliable for every home.</p>
        </div>
        <div class="footer-column">
            <h3>Company</h3>
            <p><a href="<?= e($about) ?>">About Us</a><br>Our Team<br>For Service Providers<br>For Employers</p>
        </div>
        <div class="footer-column">
            <h3>Job Categories</h3>
            <p><a href="<?= e($jobs) ?>?category=House%20Work">House Work</a><br><a href="<?= e($jobs) ?>?category=Culinary%20Aid">Culinary Aid</a><br><a href="<?= e($jobs) ?>?category=Home%20Tuition">Home Tuition</a><br><a href="<?= e($jobs) ?>?category=Pet%20Care">Pet Care</a><br><a href="<?= e($jobs) ?>?category=Self%20Care">Self Care</a></p>
        </div>
        <div class="footer-column">
            <h3>Be Up to Date!</h3>
            <p>Stay updated with trusted home services and latest job opportunities from Ghar Sathi.</p>
            <form action="<?= e($subscribe) ?>" method="POST">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="hidden" name="redirect" value="<?= e($redirectPath) ?>">
                <button type="submit">Subscribe now</button>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; Copyright Ghar Sathi 2026.</p>
        <div><a href="#">Privacy Policy</a><a href="#">Terms &amp; Conditions</a></div>
    </div>
</footer>
<?php }
