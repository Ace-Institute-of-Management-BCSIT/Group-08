<?php
/**
 * Renders the dynamic Contact Us page with shared layout components.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
require_once __DIR__ . '/../includes/app.php';
// ===========================
// Page Rendering
// ===========================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ghar Sathi - Contact Us</title>
<link rel="icon" type="image/svg+xml" href="../images/logo-favicon.svg">
<link rel="stylesheet" href="contact.css">
<link rel="stylesheet" href="../JobsPage/jobs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>
<body>

<header class="site-header">
<?php render_navbar($conn, '..', 'contact'); ?>
<section class="hero"><h1>Contact Us</h1></section>
</header>

<main class="contact-section">
<div class="contact-left">
<h2>Got something to say? <br> We're all ears.</h2>
<p>Need assistance or have a question? Our team is here to provide quick support and the best solutions for you.</p>

<div class="info-grid">
<div class="info-item">
<div class="icon"><i class="fa-solid fa-phone"></i></div>
<h4>Call for inquiry</h4>
<p>+977 9762429433</p>
</div>

<div class="info-item">
<div class="icon"><i class="fa-solid fa-envelope"></i></div>
<h4>Send us email</h4>
<p>customercare.gharsathi@gmail.com</p>
</div>

<div class="info-item">
<div class="icon"><i class="fa-solid fa-clock"></i></div>
<h4>Opening hours</h4>
<p>Always open</p>
</div>

<div class="info-item">
<div class="icon"><i class="fa-solid fa-location-dot"></i></div>
<h4>Office</h4>
<p>Kathmandu-06, Nepal</p>
</div>
</div>
</div>

<div class="contact-right">
<h3>Contact Information</h3>
<form id="contactForm" action="contact.php" method="POST">
<div class="form-row">
<div class="form-group">
<label for="firstName">First Name</label>
<input type="text" id="firstName" name="firstName" placeholder="Your name" required>
</div>
<div class="form-group">
<label for="lastName">Last Name</label>
<input type="text" id="lastName" name="lastName" placeholder="Your last name" required>
</div>
</div>

<div class="form-group">
<label for="email">Email Address</label>
<input type="email" id="email" name="email" placeholder="Your E-mail address" required>
</div>

<div class="form-group">
<label for="subject">Subject</label>
<input type="text" id="subject" name="subject" placeholder="Subject" required>
</div>

<div class="form-group">
<label for="message">Message</label>
<textarea id="message" name="message" placeholder="Your message..." rows="5" required></textarea>
</div>

<button type="submit" class="send-btn">Send Message</button>
</form>
</div>
</main>

<?php render_footer('..', 'ContactUsPage/contactus.php'); ?>

<script src="../JobsPage/jobs.js"></script>
<script src="contact.js"></script>
</body>
</html>
