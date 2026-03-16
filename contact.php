



<?php
require_once 'includes/header.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Contact Us - Local Freelancing Platform</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>



<div class="container" style="margin-top:40px; max-width:600px;">
<h1>Contact Us</h1>
<p>If you have any questions or need support, feel free to message us.</p>


<form class="form-container" style="margin:0;">
<div class="form-group">
<label>Your Name</label>
<input type="text" required />
</div>


<div class="form-group">
<label>Email</label>
<input type="email" required />
</div>


<div class="form-group">
<label>Message</label>
<textarea rows="5" required></textarea>
</div>


<button class="btn" type="submit">Send Message</button>
</form>
</div>


<?php
require_once 'includes/footer.php';
?>
