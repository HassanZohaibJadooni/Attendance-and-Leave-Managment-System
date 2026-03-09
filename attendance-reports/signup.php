<!doctype html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="bootstrap.css" rel="stylesheet">
    <script src="jquerylibrary.js"></script>
</head>

<body class="bg-light">
    <!-- Card type form -->
    <div class="row justify-content-center mt-2">
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-header bg-secondary text-white text-center">
                    <h3>Sign Up</h3>
                </div>
                <div class="card-body">
                    <!-- Sign up form -->
                    <form id="signupForm" method="post">
                        <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" name="username" required></div>
                        <div class="mb-3"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" required></div>
                        <div class="mb-3">
                            <label>Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="IT">IT</option>
                                <option value="HR">HR</option>
                                <option value="Finance">Finance</option>
                                <option value="Sales">Sales</option>
                                <option value="Marketing">Marketing</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                        <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="cpassword" required></div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                    <p class="text-center mt-3">Already have an account?<a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery code -->
    <script>
        $(document).ready(function() {
            $("#signupForm").on("submit", function(e) {
                e.preventDefault();

                $.ajax({
                    url: "signup_check.php",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        res = res.trim();
                        if (res === "exists") {
                            alert("Email already exists!");
                        } else if (res === "mismatch") {
                            alert("Passwords do not match!");
                        } else if (res === "short") {
                            alert("Password must be at least 8 characters");
                        } else if (res === "success") {
                            alert("Signup Successful! Please login now.");
                            window.location.href = "login.php";
                        } else {
                            alert("Error: " + res);
                        }
                    },
                    error: function() {
                        alert("Request failed.");
                    }
                });
            });
        });
    </script>

    <!-- Boostrap min .js file -->
    <script src="bootstrap.js"></script>

</body>

</html>
