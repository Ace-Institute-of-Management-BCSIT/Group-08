<?php

include "db.php";


$id=$_GET['id'] ?? 1;


$sql="SELECT * FROM jobs WHERE id='$id'";


$result=mysqli_query($conn,$sql);


$job=mysqli_fetch_assoc($result);


?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Job Details</title>

<link rel="stylesheet" href="details.css">

</head>


<body>


<section class="details-container">


<div class="job-details">


<span class="time">

<?php echo $job['date']; ?>

</span>


<h2>

<?php echo $job['title']; ?>

</h2>


<p>

<?php echo $job['description']; ?>

</p>



<div class="job-tags">

<span>
<?php echo $job['category']; ?>
</span>


<span>
<?php echo $job['type']; ?>
</span>


<span>
<?php echo $job['salary']; ?>
</span>


<span>
<?php echo $job['location']; ?>
</span>


</div>



<button class="apply-btn">

Apply Job

</button>



<h2>

Job Description

</h2>


<p class="description">

Find reliable household workers who provide professional cleaning and laundry services at your convenience. Our platform connects you with experienced helpers who can take care of your home cleaning, washing, ironing, and general household chores.

</p>


</div>


</section>


<script src="details.js"></script>


</body>

</html>