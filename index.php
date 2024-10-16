<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Tracker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 20px;
        }
        .btn-login {
            background-color: #007bff;
            color: white;
            border-radius: 20px;
        }
        .btn-login:hover {
            background-color: #0056b3;
        }
         /* Adjust logo to fill the container */
         .logo {
            width: 100%; /* Make the logo fill the width of the container */
            height: auto; /* Maintain aspect ratio */
            margin-bottom: 20px; /* Space below the logo */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo -->
        <img src="images/logo1.png" alt="Logo" class="logo"> <!-- Add the logo image here -->

        <h3 class="text-center mb-4">Activity Tracker</h3>
        
        <!-- Form -->
        <form id="loginForm" action="authenticate.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="invalid-feedback">Password must be at least 6 characters long.</div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Login</button>
            
            <!-- Display error message if login fails -->
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger mt-3">Invalid email or password.</div>';
            }
            ?>
        </form>

        <!-- Register Link -->
        <div class="text-center mt-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">New user? Register here</a>
        </div>

        <p class="text-muted text-center" >
            &copy; <span id="currentYear"></span> Department of Computer Science and Engineering, TIST. All rights reserved.
        </p>
    </div>
    <!-- Forgot Password Link -->
    <!-- <div class="text-center mt-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
        </div>
    </div> -->

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm" action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                          <!--  <div class="invalid-feedback">Email must be a valid @tistcochin.edu.in address.</div> -->
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" required>
                            <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                        </div>
                        <!-- <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" name="semester" aria-label="Default select example" required>
                                <option value="">Open this select menu</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                                <option value="S4">S4</option>
                                <option value="S5">S5</option>
                                <option value="S6">S6</option>
                                <option value="S7">S7</option>
                                <option value="S8">S8</option>
                            </select>
                        </div> -->
                        <div class="mb-3">
                            <label for="branch" class="form-label">Branch</label>
                            <select class="form-select" name="branch" id="branch" aria-label="Default select example" required>
                                <option value="">Open this select menu</option>
                                <option value="CSE">CSE</option>
                                <!-- <option value="CE">CE</option>
                                <option value="S3">S3</option>
                                <option value="S4">S4</option>
                                <option value="S5">S5</option>
                                <option value="S6">S6</option>
                                <option value="S7">S7</option>
                                <option value="S8">S8</option> -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="division" class="form-label">Division</label>
                            <select class="form-select" name="division" aria-label="Default select example" required>
                                <option value="">Open this select menu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                               
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admission_year" class="form-label">Admission Year</label>
                            <select class="form-select" name="admission_year" id="admission_year" aria-label="Default select example" required>
                                <option value="">Open this select menu</option>
                                <option value="2021">2020</option>
                                <option value="2022">2022</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                
                                
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ktu_register_no" class="form-label">KTU Register Number</label>
                            <input type="text" class="form-control" id="ktu_register_no" name="ktu_register_no" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="forgotPasswordForm" action="send_reset_link.php" method="POST">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Registered Email</label>
                            <input type="email" class="form-control" id="forgotEmail" name="forgotEmail" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                    <!-- Success message -->
                    <div id="resetMessage" class="mt-3" style="display:none;">
                        <div class="alert alert-success">Password reset link sent to your email.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <!-- JavaScript for Form Validation -->
    <script>
        document.getElementById('registerForm').addEventListener('submit', function (event) {
            var isValid = true;

            // Validate email domain (@tistcochin.edu.in)
            // var emailField = document.getElementById('registerEmail');
            // var emailPattern = /^[a-zA-Z0-9._%+-]+@tistcochin\.edu\.in$/;
            // if (!emailPattern.test(emailField.value)) {
            //     emailField.classList.add('is-invalid');
            //     isValid = false;
            // } else {
            //     emailField.classList.remove('is-invalid');
            // }

            // Validate password (at least 6 characters)
            var passwordField = document.getElementById('registerPassword');
            if (passwordField.value.length < 6) {
                passwordField.classList.add('is-invalid');
                isValid = false;
            } else {
                passwordField.classList.remove('is-invalid');
            }

            // Prevent form submission if validation fails
            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
<?php //include('footer.php'); ?>
</body>
</html>
