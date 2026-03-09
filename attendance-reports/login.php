<?php
  include "config.php";
?>  
<!doctype html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="bootstrap.css" rel="stylesheet">
  <script src="jquerylibrary.js"></script>
</head>


<body class="bg-light">
  <!-- Card type form Login-->
  <div class="row justify-content-center mt-4">
    <div class="col-md-4">
      <div class="card shadow-lg">
        <div class="card-header bg-secondary text-white text-center">
          <h3>Login</h3>
        </div>
        <div class="card-body">

          <!-- Form Login -->
          <form id="loginForm" method="post">
            <div class="mb-3"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <p class="text-center mt-3"> Don’t have an account? <a href="signup.php">Sign Up</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- jquery code login -->
  <script>
    $(document).ready(function() {
      $("#loginForm").on("submit", function(e) {
        e.preventDefault();

        $.ajax({
          url: "login_check.php",
          type: "POST",
          data: $(this).serialize(),
          success: function(response) {
            response = response.trim();

            if (response === "invalid") {
              alert("Invalid Email or Password!");
            } else if (response === "admin") {
              alert("Welcome Admin ........!");
              window.location.href = "admin_dashboard.php";
            } else if (response === "user") {
              alert("Welcome User ........!");
              window.location.href ="employee_dashboard.php"; 
            } else {
              alert("Unexpected error: " + response);
            }
          },
          error: function() {
            alert("Request failed.");
          }
        });
      });
    });
  </script>

  <!-- boostrap min js file -->
  <script src="bootstrap.bundle.min.js"></script>
</body>

</html>