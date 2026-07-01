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
			
			<a href="login.php" class="login-button">Admin Login</a>
		</div>
	</div>
		<div class="header-intro">
			<h2>Welcome to PlantMedX!</h2>
			<p>Your trusted guide to medicinal plants and natural remedies.Browse our database to 
				explore herbs, their benefits, and their traditional uses. </p>
		</div>
		<div class="search-wrapper">
			<div class="search-container">
				<input type="text" class="search-bar" placeholder="Search Plants...">
				<a href="view_details.php" class="search-button">
					<i class="bi bi-search"></i>
				</a>
				<button class="filter-button" id="filterBtn">
					<i class="bi bi-filter"></i>
				</button>
			</div>
	</div>
</header>