<?php
require_once __DIR__ . '/../includes/app.php';

$fallbackJobs = [
    ['time' => '10 min ago', 'title' => 'House Cleaner', 'description' => 'Daily household task assistance', 'category' => 'House Work', 'type' => 'Full/Part Time', 'salary' => 'Rs 1000-Rs20,000', 'location' => 'Kathmandu', 'salary_max' => 20000, 'minutes' => 10],
    ['time' => '12 min ago', 'title' => 'Catering Service', 'description' => 'Professional cooking assistance', 'category' => 'Culinary Service', 'type' => 'Seasonal', 'salary' => 'Per plate- Rs 1250 (Negotiable)', 'location' => 'Bouddha-06, Kathmandu', 'salary_max' => 1250, 'minutes' => 12],
    ['time' => '15 min ago', 'title' => 'Self-Care', 'description' => 'Wellness and beauty support', 'category' => 'Personal', 'type' => 'Part time', 'salary' => 'Rs 5,000-Rs20,000', 'location' => 'Texas, USA', 'salary_max' => 20000, 'minutes' => 15],
    ['time' => '24 min ago', 'title' => 'Home Tuition', 'description' => 'Personalized learning at home', 'category' => 'Personal', 'type' => 'Part time', 'salary' => 'Rs 5000-Rs 10,000', 'location' => 'Kathmandu', 'salary_max' => 10000, 'minutes' => 24],
    ['time' => '26 min ago', 'title' => 'Tech Repair', 'description' => 'Fast solutions for devices', 'category' => 'Repair', 'type' => 'Freelance', 'salary' => 'Rs 500- Rs 15,000', 'location' => 'Kathmandu', 'salary_max' => 15000, 'minutes' => 26],
    ['time' => '30 min ago', 'title' => 'Pet Care', 'description' => 'Loving care for pets', 'category' => 'Pet Care', 'type' => 'Freelance', 'salary' => 'Rs 5000-Rs 30,000', 'location' => 'Kathmandu', 'salary_max' => 30000, 'minutes' => 30],
    ['time' => '35 min ago', 'title' => 'Electrician', 'description' => 'Safe wiring, lighting, and electrical repair support', 'category' => 'Repair', 'type' => 'Freelance', 'salary' => 'Rs 800-Rs 18,000', 'location' => 'Kathmandu', 'salary_max' => 18000, 'minutes' => 35],
    ['time' => '38 min ago', 'title' => 'Appliance Mechanic', 'description' => 'Home appliance diagnosis and maintenance service', 'category' => 'Repair', 'type' => 'Part time', 'salary' => 'Rs 1000-Rs 22,000', 'location' => 'Kathmandu', 'salary_max' => 22000, 'minutes' => 38],
    ['time' => '42 min ago', 'title' => 'Baker', 'description' => 'Fresh baked goods for homes and special orders', 'category' => 'Culinary Service', 'type' => 'Seasonal', 'salary' => 'Rs 1500-Rs 16,000', 'location' => 'Bouddha-06, Kathmandu', 'salary_max' => 16000, 'minutes' => 42],
    ['time' => '45 min ago', 'title' => 'Party Planner', 'description' => 'Event setup, decoration, and celebration planning', 'category' => 'Personal', 'type' => 'Fixed-Price', 'salary' => 'Rs 10,000-Rs 40,000', 'location' => 'Kathmandu', 'salary_max' => 40000, 'minutes' => 45],
];

$jobs = $fallbackJobs;
$result = mysqli_query(
    $conn,
    'SELECT jobs.job_id, jobs.category_id, jobs.title, jobs.description, jobs.job_type, jobs.salary, jobs.location,
            categories.category_name, users.full_name AS employer_name
     FROM jobs
     INNER JOIN categories ON categories.category_id = jobs.category_id
     INNER JOIN users ON users.user_id = jobs.employer_id
     ORDER BY jobs.job_id DESC'
);
if ($result && mysqli_num_rows($result) > 0) {
    $jobs = [];
    $minutes = 10;
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = [
            'job_id' => (int) $row['job_id'],
            'category_id' => (int) $row['category_id'],
            'time' => 'Recently',
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'category' => $row['category_name'] ?? '',
            'type' => $row['job_type'] ?? '',
            'salary' => 'Rs ' . number_format((float) ($row['salary'] ?? 0), 0),
            'location' => $row['location'] ?? '',
            'employer' => $row['employer_name'] ?? '',
            'salary_max' => (float) ($row['salary'] ?? 0),
            'minutes' => $minutes,
        ];
        $minutes += 2;
    }
}

$initialSearch = trim($_GET['search'] ?? '');
$initialLocation = trim($_GET['location'] ?? '');
$initialCategory = trim($_GET['category'] ?? '');
$locations = array_values(array_unique(array_filter(array_map(fn($job) => $job['location'] ?? '', $jobs))));
$categoriesForFilter = array_values(array_unique(array_filter(array_map(fn($job) => $job['category'] ?? '', $jobs))));

function max_salary_value($value) {
    preg_match_all('/\d[\d,]*/', (string) $value, $matches);
    $numbers = array_map(
        fn($number) => (int) str_replace(',', '', $number),
        $matches[0] ?? []
    );

    return $numbers ? max($numbers) : 40000;
}

function job_image($category) {
    return service_image($category);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ghar Sathi - Jobs</title>
<link rel="stylesheet" href="jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>
<body>

<header class="site-header">
<?php render_navbar($conn, '..', 'jobs'); ?>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
<i class="fa-solid fa-bars"></i>
</button>
<section class="hero"><h1>Jobs</h1></section>
</header>

<main class="jobs-section">
<aside class="filter-box" aria-label="Job filters">
<form id="jobFilterForm">
<div class="filter-group"><h3>Search by Job Title</h3><input id="jobSearch" type="search" placeholder="Job title or company" value="<?php echo e($initialSearch); ?>"></div>
<div class="filter-group">
<h3>Location</h3>
<select id="locationFilter">
<option value="">Choose Place</option>
<?php foreach ($locations as $location): ?>
<option <?php echo $initialLocation === $location ? 'selected' : ''; ?>><?php echo e($location); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="filter-group">
<h3>Category</h3>
<?php foreach ($categoriesForFilter as $categoryName): ?>
<label><input type="checkbox" name="category" value="<?php echo e($categoryName); ?>" <?php echo $initialCategory === $categoryName ? 'checked' : ''; ?>> <?php echo e($categoryName); ?></label>
<?php endforeach; ?>
<button class="show-btn" type="button">Show more</button>
</div>
<div class="filter-group">
<h3>Job Type</h3>
<label><input type="checkbox" name="type" value="Full/Part Time"> Full Time</label>
<label><input type="checkbox" name="type" value="Part time"> Part Time</label>
<label><input type="checkbox" name="type" value="Freelance"> Freelance</label>
<label><input type="checkbox" name="type" value="Seasonal"> Seasonal</label>
<label><input type="checkbox" name="type" value="Fixed-Price"> Fixed-Price</label>
</div>
<div class="filter-group">
<h3>Salary</h3>
<input id="salaryRange" type="range" min="0" max="40000" value="40000">
<p class="salary-text">Salary: Rs 0 - Rs <span id="salaryValue">40,000</span></p>
<button class="apply-btn" type="submit">Apply</button>
</div>
</form>
<div class="filter-group tags-group">
<h3>Tags</h3>
<div class="tags">
<button type="button" data-tag="Plumbing">Plumbing</button>
<button type="button" data-tag="Electrical Work">Electrical Work</button>
<button type="button" data-tag="Culinary Aid">Culinary Aid</button>
<button type="button" data-tag="Self Care">Self Care</button>
<button type="button" data-tag="Home Tuition">Home Tuition</button>
<button type="button" data-tag="House Work">House Work</button>
</div>
</div>
<div class="priority-panel" aria-label="Ghar Sathi promise"><p>Your<br>Satisfaction,<br>Our Priority.</p></div>
</aside>

<section class="job-container" aria-label="Available jobs">
<div class="job-top">
<p id="resultCount" data-total-results="<?php echo count($jobs); ?>">Showing 6-6 of <?php echo count($jobs); ?> results</p>
<button id="sortLatest" type="button">Sort by latest</button>
</div>

<div class="job-list" id="jobList">
<?php foreach ($jobs as $index => $job): ?>
<?php
$jobId = (int) ($job['job_id'] ?? 0);
$verifiedWorkers = fetch_workers_for_category($conn, (int) ($job['category_id'] ?? 0), 3);

?>
<article class="job-card" data-page="<?php echo $index < 6 ? '1' : '2'; ?>" data-title="<?php echo e($job['title']); ?>" data-category="<?php echo e($job['category']); ?>" data-type="<?php echo e($job['type']); ?>" data-location="<?php echo e($job['location']); ?>" data-salary="<?php echo e($job['salary_max']); ?>" data-minutes="<?php echo e($job['minutes']); ?>" <?php echo $index < 6 ? '' : 'hidden'; ?>>
<div class="job-card-head"><span class="category-badge"><?php echo e($job['category']); ?></span><span class="salary-badge"><?php echo e($job['salary']); ?></span></div>
<h2><?php echo e($job['title']); ?></h2>
<p><?php echo e($job['description']); ?></p>
<p class="job-employer">Employer: <?php echo e($job['employer'] ?? 'Ghar Sathi'); ?></p>
<div class="job-info">
<span><?php echo e($job['type']); ?></span>
<span><?php echo e($job['location']); ?></span>
<a href="../detail.php?id=<?php echo $jobId; ?>">details</a>
</div>
<?php if ($verifiedWorkers): ?>
<div class="worker-strip" aria-label="Available workers">
<?php foreach ($verifiedWorkers as $worker): ?>
<div class="mini-worker">
<div><strong><?php echo e($worker['full_name']); ?></strong><span><?php echo e($worker['experience_years']); ?> yrs | <?php echo e(number_format((float) $worker['avg_rating'], 1)); ?> ★</span></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="job-actions">
<a class="secondary-action" href="../About Us Page/apply_resume.php?job_id=<?php echo $jobId; ?>">Apply Job</a>
<?php if ($jobId > 0 && $verifiedWorkers): ?>
<form class="hire-form" action="../booking_request.php" method="POST">
<input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
<input type="hidden" name="category_id" value="<?php echo e($job['category_id'] ?? 0); ?>">
<label>Worker
<select name="worker_id" required>
<?php foreach ($verifiedWorkers as $worker): ?>
<option value="<?php echo e($worker['user_id']); ?>" data-salary="<?php echo e($job['salary_max']); ?>"><?php echo e($worker['full_name']); ?> - <?php echo e(number_format((float) $worker['avg_rating'], 1)); ?> ★</option>
<?php endforeach; ?>
</select>
</label>
<label>Date <input type="date" name="requested_date" required></label>
<label>Time <input type="time" name="requested_time" required></label>
<label>Offer salary <input type="number" name="offered_salary" min="0" step="1" value="<?php echo e(max(0, (float) $job['salary_max'] - 20)); ?>" required></label>
<label>Notes <input type="text" name="notes" placeholder="Service notes"></label>
<small>You can request around Rs 20 discount; the worker may accept, decline, or negotiate.</small>
<button type="submit">Hire Now</button>
</form>
<?php else: ?>
<p class="worker-note">No verified workers are available for this category yet.</p>
<?php endif; ?>
</div>
</article>
<?php endforeach; ?>
</div>

<nav class="pagination" aria-label="Job pages">
<a class="active" href="#" data-page-link="1">1</a>
<a href="#" data-page-link="2">2</a>
<a href="#" data-page-link="next">Next</a>
</nav>
</section>
</main>

<?php render_footer('..'); ?>

<script src="jobs.js"></script>
</body>
</html>
