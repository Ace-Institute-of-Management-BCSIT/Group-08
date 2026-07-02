<?php
require_once __DIR__ . '/../includes/app.php';
$uploadStatus = $_GET['resume'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ghar Sathi About Us</title>

<link rel="stylesheet" href="aboutus.css">
<link rel="stylesheet" href="../Jobs page/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

</head>


<body>

<header class="site-header">
<?php render_navbar($conn, '..', 'about'); ?>
<button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks"><i class="fa-solid fa-bars"></i></button>
<section class="hero"><h1>About Us</h1></section>
</header>

<section class="about-section">


<div class="about-text">


<h2>

Ghar Sathi - Where Trust,
<br>
Care and Service
<br>
Come Together!

</h2>


<p>

Welcome to Ghar Sathi — your friendly partner for finding trusted household help without the stress.

</p>


<p>

We connect you with skilled workers for cleaning, plumbing, caretaking, electrical work and many other home services.

</p>


<p>

Our goal is to make your daily life easier by helping you quickly find reliable and verified helpers in one place.

</p>


</div>




<div class="worker-box">


<h2>
Become a Worker
</h2>


<p>

Join our platform and start your journey toward better opportunities. Create your account, upload your resume, explore available jobs and apply with ease.

</p>

<p>
Choose a job from the jobs page and use Apply Job to submit your resume for admin verification.
</p>

<p>
<a href="../Jobs page/jobs.php" style="display:inline-block;background:#28a745;color:#fff;padding:12px 18px;border-radius:6px;text-decoration:none;font-weight:600;">Find Jobs to Apply</a>
</p>


<div class="cards">


<a class="card" href="../Signup page/signup.html">

<h3>
Create Account
</h3>

<p>
Sign up quickly and build your professional profile.
</p>

</a>



<a class="card js-open-resume" href="upload_resume.php">

<h3>
Upload Resume
</h3>

<p>
Showcase your skills and experience to employers.
</p>

</a>



<a class="card" href="police_report.php">

<h3>
Upload Police Report
</h3>

<p>
Add your police clearance document for safer verification.
</p>

</a>



<a class="card" href="../Contact Us Page/contactus.php">

<h3>
Contact Us
</h3>

<p>
Reach out for service, worker, or platform support.
</p>

</a>


</div>


</div>


</section>

<div class="resume-modal <?php echo $uploadStatus ? 'is-open' : ''; ?>" id="resumeModal" aria-hidden="<?php echo $uploadStatus ? 'false' : 'true'; ?>">
<div class="resume-modal-panel" role="dialog" aria-modal="true" aria-labelledby="resumeTitle">
<button class="modal-close" type="button" aria-label="Close upload resume modal">&times;</button>
<h2 id="resumeTitle">Upload Resume</h2>
<?php if ($uploadStatus === 'success'): ?><p class="success">Resume uploaded successfully.</p><?php endif; ?>
<?php if ($uploadStatus === 'invalid'): ?><p class="error">Invalid file type.</p><?php endif; ?>
<form method="POST" action="upload_resume.php" enctype="multipart/form-data">
<input type="hidden" name="redirect" value="aboutus.php">
<label>Resume File
<input type="file" name="resume" accept=".pdf,.doc,.docx" required>
</label>
<button type="submit">Upload Resume</button>
</form>
</div>
</div>

<?php render_footer('..'); ?>

<script src="../Jobs page/jobs.js"></script>
<script src="aboutus.js"></script>


</body>

</html>
