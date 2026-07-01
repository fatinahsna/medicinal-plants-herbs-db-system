<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>PlantMedX - Plant Medicine Exchange</title>
	<link rel="stylesheet" type="text/css" href="assets/css/style.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<meta charset="UTF-8">
</head>
<body>
	<header class="header">
		<div class="header-top">
			<div class="logo-section">
				<img src="assets/images/logo.png" alt="PlantMedX Logo" class="logo">
				<div class="title">
					<h1>PlantMedX</h1>
					<p>Nature's Remedy, At Your Fingertips</p>
				</div>
			</div>
		<div class="header-right">
			<button class="theme" id="themeToggle">
				<i class="bi bi-moon-fill" id="icon"></i>
			</button>
			
<?php if (isset($_SESSION['admin'])) { ?>
		<a href="logout.php" class="login-button">Logout</a>
<?php } else { ?>
		<a href="login.php" class="login-button">Admin Login</a>
<?php } ?>
		</div>
	</div>
</header>