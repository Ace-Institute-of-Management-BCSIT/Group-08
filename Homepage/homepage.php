<?php
require_once __DIR__ . '/../db_connect/db.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$stats = [
    'services' => 0,
    'families' => 0,
    'tasks' => 0,
];

$result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM jobs');
if ($result) {
    $stats['services'] = (int) mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role IN ('Employer','Worker')");
if ($result) {
    $stats['families'] = (int) mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM job_applications');
if ($result) {
    $stats['tasks'] = (int) mysqli_fetch_assoc($result)['total'];
}

$categories = [];
$categorySql = "SELECT categories.category_name, COUNT(jobs.job_id) AS total_jobs
                FROM categories
                LEFT JOIN jobs ON jobs.category_id = categories.category_id
                GROUP BY categories.category_id, categories.category_name
                ORDER BY categories.category_name";
$result = mysqli_query($conn, $categorySql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

$jobs = [];
$jobSql = "SELECT jobs.title, jobs.location, categories.category_name, users.full_name
           FROM jobs
           INNER JOIN categories ON categories.category_id = jobs.category_id
           INNER JOIN users ON users.user_id = jobs.employer_id
           ORDER BY jobs.job_id DESC";
$result = mysqli_query($conn, $jobSql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = [
            'title' => $row['title'],
            'company' => $row['full_name'],
            'location' => $row['location'],
            'category' => $row['category_name'],
        ];
    }
}

$categoryIcons = [
    'House Work' => 'fa-seedling',
    'Culinary Service' => 'fa-circle-check',
    'Culinary Aid' => 'fa-circle-check',
    'Personal' => 'fa-bag-shopping',
    'Education' => 'fa-graduation-cap',
    'Repair' => 'fa-screwdriver-wrench',
    'Pet Care' => 'fa-dog',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ghar Sathi</title>
<link rel="stylesheet" href="homepage.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script>
window.homepageJobs = <?php echo json_encode($jobs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="homepage.js" defer></script>
</head>
<body>
<header>
<nav class="navbar">
<div class="logo">
<div class="logo-icon"><img src="images/logo.png" alt="Ghar Sathi logo"></div>
<span>Ghar Sathi</span>
</div>
<ul class="nav-links">
<li><a href="homepage.php">Home</a></li>
<li><a href="../Jobs page/jobs.php">Jobs</a></li>
<li><a href="../About Us Page/aboutus.html">About Us</a></li>
<li><a href="../Contact Us Page/contactus.html">Contact Us</a></li>
</ul>
<div class="auth-buttons">
<a href="../Login Page/login.html" class="login-btn">Login</a>
<a href="../Signup page/signup.html" class="signup-btn">Sign Up</a>
</div>
</nav>
</header>

<section class="hero" id="hero">
<div class="overlay"></div>
<div class="hero-content">
<h1>Find Your Services Now!</h1>
<p>Connecting Talent with Opportunity: Your Gateway to Faster Services</p>

<form class="search-box" id="heroSearchForm">
<input type="text" placeholder="Job Title or Company" name="job" id="jobSearchInput">
<select name="location" id="locationSelect">
<option value="">Select Location</option>
<?php foreach (array_unique(array_filter(array_column($jobs, 'location'))) as $location): ?>
<option><?php echo e($location); ?></option>
<?php endforeach; ?>
</select>
<select name="category" id="categorySelect">
<option value="">Select Category</option>
<?php foreach ($categories as $category): ?>
<option><?php echo e($category['category_name']); ?></option>
<?php endforeach; ?>
</select>
<button type="submit" id="heroSearchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search Job</button>
</form>

<div class="search-results" id="searchResults" hidden>
<div class="search-results-header">
<h3 id="searchResultsTitle">Search Results</h3>
<button type="button" class="search-results-close" id="closeSearchResults" aria-label="Close results">&times;</button>
</div>
<ul class="search-results-list" id="searchResultsList"></ul>
</div>

<div class="hero-stats">
<div class="stat"><div class="stat-circle"><i class="fa-solid fa-briefcase"></i><h3><?php echo e($stats['services']); ?>+</h3></div><p>Services</p></div>
<div class="stat"><div class="stat-circle"><i class="fa-solid fa-users"></i><h3><?php echo e($stats['families']); ?>+</h3></div><p>Families</p></div>
<div class="stat"><div class="stat-circle"><i class="fa-solid fa-list-check"></i><h3><?php echo e($stats['tasks']); ?>+</h3></div><p>Tasks</p></div>
</div>
</div>
</section>

<section class="social-bar">
<a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
<a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
<a href="tel:+9779800000000" aria-label="Call us"><i class="fa-solid fa-phone"></i></a>
<a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
</section>

<section class="categories" id="categories">
<h2>Browse by Category</h2>
<p>Select your preferred category and explore!</p>
<div class="category-grid">
<?php foreach ($categories as $category): ?>
<?php $name = $category['category_name']; ?>
<div class="card" data-category="<?php echo e($name); ?>" tabindex="0" role="button">
<i class="fa-solid <?php echo e($categoryIcons[$name] ?? 'fa-briefcase'); ?>"></i>
<h3><?php echo e($name); ?></h3>
<span><?php echo e($category['total_jobs']); ?> Jobs</span>
</div>
<?php endforeach; ?>
</div>
</section>

<section class="service-section">
<div class="service-image"><img src="images/nurse.jpg" alt="Home care services"></div>
<div class="service-text">
<h2>Reliable Home Services, Right at Your Doorstep!</h2>
<p>Ghar Sathi brings trusted household services directly to your home. Our platform helps you choose the right service provider.</p>
<button type="button" class="search-job-btn">Search Job</button>
</div>
</section>

<section class="counter-section">
<div><h2><?php echo e($stats['families']); ?>+</h2><h4>Households Connected</h4><p>Helping families quickly find trusted professionals.</p></div>
<div><h2><?php echo e($stats['services']); ?>+</h2><h4>Active Services</h4><p>Growing network of household service opportunities.</p></div>
<div><h2><?php echo e($stats['tasks']); ?>+</h2><h4>Tasks Applied</h4><p>Providing reliable solutions.</p></div>
</section>

<section class="testimonial">
<h2>Feedbacks from Our Customers</h2>
<p>Hear from our happy customers and see how Ghar Sathi is making household services easier.</p>
<div class="testimonial-grid">
<div class="review">&#9733;&#9733;&#9733;&#9733;&#9733;<h3>Amazing Services</h3><p>Affordable services and good variety of options.</p><h4>Ram Shrestha</h4></div>
<div class="review">&#9733;&#9733;&#9733;&#9733;&#9733;<h3>Everything Simple</h3><p>The helper arrived on time and did the work perfectly.</p><h4>Sita Adhikari</h4></div>
<div class="review">&#9733;&#9733;&#9733;&#9733;&#9733;<h3>Awesome, Thank You!</h3><p>Booking process was smooth and great experience overall.</p><h4>Nirmala Pradhan</h4></div>
</div>
</section>

<footer>
<div class="footer-container">
<div><h3>Job</h3><p>Ghar Sathi connects skilled people with trusted opportunities.</p></div>
<div><h3>About Us</h3><ul><li>Our Team</li><li>For Service Providers</li><li>For Employers</li></ul></div>
<div><h3>Job Categories</h3><ul><?php foreach (array_slice($categories, 0, 5) as $category): ?><li><?php echo e($category['category_name']); ?></li><?php endforeach; ?></ul></div>
<div>
<h3>Be Up To Date!</h3>
<form action="../subscribe.php" method="POST">
<input type="email" name="email" placeholder="Email Address" required>
<input type="hidden" name="redirect" value="Homepage/homepage.php">
<button type="submit">Subscribe Now</button>
</form>
</div>
</div>
<div class="footer-bottom">
<div class="copyright">&copy; Copyright Ghar Sathi 2026.</div>
<div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms &amp; Conditions</a></div>
</div>
</footer>
</body>
</html>
