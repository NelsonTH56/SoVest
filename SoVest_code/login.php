<?php
/**
 * SoVest - Login Page
 * 
 * Displays the login form and verifies database connectivity.
 * Uses DatabaseService for database operations.
 */

// Include database configuration
require_once 'includes/db_config.php';

// Import DatabaseService
use Services\DatabaseService;

// Check database connectivity using DatabaseService
try {
    $dbService = DatabaseService::getInstance();
    // Just getting the connection validates it's working
    $dbService->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
	<head>
    	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
    	<title>SoVest</title>
		<link href="css/bootstrap.min.css" rel="stylesheet">  
		
		<link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="images/favicon-16x16.png">
		<link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
		<link rel="manifest" href="images/site.webmanifest">	
			
  	</head>
  	<body>

		<div class="container py-3">
  			<header>
   				<div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
      				<a href="index.php" class="d-flex align-items-center link-body-emphasis text-decoration-none">
       					<span class="fs-4">SoVest</span>
					</a>

      				<nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
					  <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="home.php">Home</a>
	  					<a class="me-3 py-2 link-body-emphasis text-decoration-none" href="about.php">About SoVest</a>
	  					<a class="me-3 py-2 link-body-emphasis text-decoration-none" href="login.php">Log In</a>
      				</nav>
    			</div>

    			<div class="pricing-header p-3 pb-md-4 mx-auto text-center">
      				<p class="fs-5 text-body-secondary"></p>
    			</div>
 		 	</header>

			<main>

				<div class="row row-cols-1 row-cols-md-1 mb-1 text-center">

					<div class="col">
        				<div class="card mb-4 rounded-3 shadow-sm">
          					<div class="card-header py-3">
            					<h4 class="my-0 fw-normal">Log In</h4>
          					</div>
          					<div class="card-body">

								<!-- CUSTOMIZE THIS SECTION WITH FORM INFO -->

								<form action="loginCheck.php" method="post">


								<div class="form-floating">
       								<input type="email" class="form-control" id="tryEmail" name="tryEmail" required>
        							<label for="tryEmail">Email</label>
  									</div>
   										<br>

    <!-- SAMPLE PASSWORD FORM (WITH REQUIRED) -->
   									<div class="form-floating">
    								    <input type="password" class="form-control" id="tryPass" name="tryPass" required>
    								    <label for="tryPass">Password</label>
    									</div>
   									<br>

									<button class="btn btn-success w-100 py-2" type="submit">Submit</button>
								</form>

								<!-- END FORM INFO -->

							</div>
        				</div>
      				</div>
				</div>


			</main>


			<footer class="pt-4 my-md-5 pt-md-5 border-top">
    			<div class="row">
      				<div class="col-12 col-md">
        				<small class="d-block mb-3 text-body-secondary">Created by Nate Pedigo, Nelson Hayslett</small>
      				</div>
				</div>
			</footer>
 
		</div>
		<script src="js/bootstrap.bundle.min.js"></script>
	</body>
</html>