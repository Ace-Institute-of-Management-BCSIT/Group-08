<?php

include "db.php";


$sql="SELECT * FROM jobs";

$result=mysqli_query($conn,$sql);


?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ghar Sathi Jobs</title>

<link rel="stylesheet" href="jobs.css">

</head>


<body>


<header>

<nav class="navbar">


<div class="logo">

<img src="logo.png">

<span>
Ghar Sathi
</span>

</div>



<ul class="nav-links">

<li>
<a href="#">
Home
</a>
</li>


<li>
<a href="#">
Jobs
</a>
</li>


<li>
<a href="#">
About Us
</a>
</li>


<li>
<a href="#">
Contact Us
</a>
</li>


</ul>


<button class="signup-btn">

Sign Up

</button>


</nav>


<div class="hero">

<h1>
Jobs
</h1>

</div>


</header>



<section class="jobs-section">


<div class="job-container">


<div class="job-top">

<p>
Latest Jobs
</p>

</div>



<?php


if(mysqli_num_rows($result)>0){


while($row=mysqli_fetch_assoc($result)){


?>


<div class="job-card">


<small>

<?php echo $row['date']; ?>

</small>


<h2>

<?php echo $row['title']; ?>

</h2>


<p>

<?php echo $row['description']; ?>

</p>



<div class="job-info">


<span>

<?php echo $row['category']; ?>

</span>


<span>

<?php echo $row['type']; ?>

</span>


<span>

<?php echo $row['salary']; ?>

</span>


<span>

<?php echo $row['location']; ?>

</span>



<button>

Details

</button>


</div>


</div>



<?php


}


}

else{


echo "<h3>No Jobs Found</h3>";


}


?>


</div>


</section>




<footer>


<div class="footer-container">


<div class="footer-column">


<h3>
Job
</h3>


<p>

Ghar Sathi connects skilled people with trusted opportunities, making everyday services easier, faster and more reliable for every home.

</p>


</div>




<div class="footer-column">


<h3>
About Us
</h3>


<ul>

<li>
Our Team
</li>

<li>
For Service Providers
</li>

<li>
For Employers
</li>

</ul>


</div>




<div class="footer-column">


<h3>
Job Categories
</h3>


<ul>

<li>
House Work
</li>

<li>
Culinary Aid
</li>

<li>
Home Tuition
</li>

<li>
Pet Care
</li>

<li>
Self Care
</li>

</ul>


</div>




<div class="footer-column">


<h3>
Be Up to Date!
</h3>


<p>

Stay updated with trusted home services and latest job opportunities from Ghar Sathi.

</p>


<input type="email" placeholder="Email Address">


<button>

Subscribe now

</button>


</div>


</div>



<div class="footer-bottom">


<p>
© Copyright Ghar Sathi 2026.
</p>


<div>

<a href="#">
Privacy Policy
</a>


<a href="#">
Terms & Conditions
</a>


</div>


</div>


</footer>



<script src="jobs.js"></script>


</body>

</html>