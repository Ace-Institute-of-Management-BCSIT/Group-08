<?php
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
$dbFile = __DIR__ . '/db.php';

if (file_exists($dbFile)) {
    include $dbFile;

    if (isset($conn) && $conn instanceof mysqli) {
        $result = mysqli_query($conn, 'SELECT * FROM jobs');
        if ($result && mysqli_num_rows($result) > 0) {
            $jobs = [];
            $minutes = 10;
            while ($row = mysqli_fetch_assoc($result)) {
                $jobs[] = [
                    'time' => $row['date'] ?? 'Recently',
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'category' => $row['category'] ?? '',
                    'type' => $row['type'] ?? '',
                    'salary' => $row['salary'] ?? '',
                    'location' => $row['location'] ?? '',
                    'salary_max' => max_salary_value($row['salary'] ?? '40000'),
                    'minutes' => $minutes,
                ];
                $minutes += 2;
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function max_salary_value($value) {
    preg_match_all('/\d[\d,]*/', (string) $value, $matches);
    $numbers = array_map(
        fn($number) => (int) str_replace(',', '', $number),
        $matches[0] ?? []
    );

    return $numbers ? max($numbers) : 40000;
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
<nav class="navbar">
<a class="logo" href="../Homepage/homepage.html" aria-label="Ghar Sathi home">
<img src="../logo.png" alt="Ghar Sathi logo">
<span>Ghar Sathi</span>
</a>
<ul class="nav-links" id="navLinks">
<li><a href="../Homepage/homepage.html">Home</a></li>
<li><a class="active" href="jobs.php">Jobs</a></li>
<li><a href="../About Us Page/aboutus.html">About Us</a></li>
<li><a href="../Contact Us Page/contactus.html">Contact Us</a></li>
</ul>
<div class="nav-buttons">
<a class="login-btn" href="../Login Page/login.html">Login</a>
<a class="signup-btn" href="../Signup page/signup.html">Sign up</a>
</div>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
<i class="fa-solid fa-bars"></i>
</button>
</nav>
<section class="hero"><h1>Jobs</h1></section>
</header>

<main class="jobs-section">
<aside class="filter-box" aria-label="Job filters">
<form id="jobFilterForm">
<div class="filter-group"><h3>Search by Job Title</h3><input id="jobSearch" type="search" placeholder="Job title or company"></div>
<div class="filter-group">
<h3>Location</h3>
<select id="locationFilter">
<option value="">Choose Place</option>
<option>Kathmandu</option>
<option>Texas, USA</option>
<option>Bouddha-06, Kathmandu</option>
</select>
</div>
<div class="filter-group">
<h3>Category</h3>
<label><input type="checkbox" name="category" value="House Work"> House Work</label>
<label><input type="checkbox" name="category" value="Repair"> House Repairs</label>
<label><input type="checkbox" name="category" value="Personal"> Self Care</label>
<label><input type="checkbox" name="category" value="Education"> Home Tuition</label>
<label><input type="checkbox" name="category" value="Culinary Service"> Culinary Aid</label>
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
<h3>Experience Level</h3>
<label><input type="checkbox"> No-experience</label>
<label><input type="checkbox"> Fresher</label>
<label><input type="checkbox"> Intermediate</label>
<label><input type="checkbox"> Expert</label>
</div>
<div class="filter-group">
<h3>Date Posted</h3>
<label><input type="checkbox"> All</label>
<label><input type="checkbox"> Last Hour</label>
<label><input type="checkbox"> Last 24 Hours</label>
<label><input type="checkbox"> Last 7 Days</label>
<label><input type="checkbox"> Last 30 Days</label>
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
<button type="button" data-tag="Repair">Repair</button>
<button type="button" data-tag="Personal">Personal</button>
<button type="button" data-tag="Culinary Service">Cullinary Service</button>
<button type="button" data-tag="Self Care">Self Care</button>
<button type="button" data-tag="Education">Education</button>
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
<article class="job-card" data-page="<?php echo $index < 6 ? '1' : '2'; ?>" data-title="<?php echo e($job['title']); ?>" data-category="<?php echo e($job['category']); ?>" data-type="<?php echo e($job['type']); ?>" data-location="<?php echo e($job['location']); ?>" data-salary="<?php echo e($job['salary_max']); ?>" data-minutes="<?php echo e($job['minutes']); ?>" <?php echo $index < 6 ? '' : 'hidden'; ?>>
<h2><?php echo e($job['title']); ?></h2>
<p><?php echo e($job['description']); ?></p>
<div class="job-info">
<span><?php echo e($job['category']); ?></span>
<span><?php echo e($job['type']); ?></span>
<span><?php echo e($job['salary']); ?></span>
<span><?php echo e($job['location']); ?></span>
<a href="../Details Page/details.html">details</a>
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

<footer>
<div class="footer-container">
<div class="footer-column">
<h3><i class="fa-solid fa-briefcase"></i> Job</h3>
<p>Ghar Sathi connects skilled people with trusted opportunities, making everyday services easier, faster, and more reliable for every home.</p>
</div>
<div class="footer-column"><h3>About Us</h3><ul><li>Our Team</li><li>For Service Providers</li><li>For Employers</li></ul></div>
<div class="footer-column"><h3>Job Categories</h3><ul><li>House Work</li><li>Culinary Aid</li><li>Home Tuition</li><li>Pet Care</li><li>Self Care</li></ul></div>
<div class="footer-column">
<h3>Be Up to Date!</h3>
<p>Stay updated with trusted home services and latest job opportunities from Ghar Sathi.</p>
<form class="subscribe-form"><input type="email" placeholder="Email Address" aria-label="Email Address"><button type="submit">Subscribe now</button></form>
</div>
</div>
<div class="footer-bottom">
<p>&copy; Copyright Ghar Sathi 2026.</p>
<div><a href="#">Privacy Policy</a><a href="#">Terms &amp; Conditions</a></div>
</div>
</footer>

<script src="jobs.js"></script>
</body>
</html>
