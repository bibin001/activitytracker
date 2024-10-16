<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not an admin
    exit();
}

// Database connection
include('db.php');

// Fetch activities if needed, or any other admin-specific data here
$activities = []; // Example variable for activities, can be populated with actual data

// Close connection if opened
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        /* Custom styles for the sidebar */
        .sidebar {
            height: 100vh; /* Full-height sidebar */
            position: fixed; /* Fixed sidebar */
            left: 0; /* Align to the left */
            top: 56px; /* Align below navbar */
            width: 250px; /* Width of the sidebar */
            background-color: #f8f9fa; /* Light background */
            padding: 20px; /* Padding inside sidebar */
            border-right: 1px solid #dee2e6; /* Right border for visual separation */
        }
        .sidebar a {
            display: block; /* Make links block level */
            padding: 10px; /* Padding for links */
            margin: 5px 0; /* Margin between links */
            color: #333; /* Link color */
            text-decoration: none; /* Remove underline */
            border-radius: 4px; /* Rounded corners */
        }
        .sidebar a:hover {
            background-color: #e2e6ea; /* Hover effect */
        }
        .content {
            margin-left: 260px; /* Space for the sidebar */
            padding: 20px; /* Padding for content */
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="change_password.php">Change Password</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <h4>Admin Menu</h4>
    <a href="add_admin.php">Add Admin</a>
    <a href="add_faculty.php">Add Faculty</a>
    <a href="add_hod.php">Add HoD</a>
    <a href="view_activities.php">View Activities</a>
</div>

<!-- Main Content -->
<div class="content">
    <h1>Welcome, Admin!</h1>
    

    <!-- Content area for any additional information or functionality -->
    <!-- You can implement forms or tables here for adding admin, faculty, or displaying activities -->
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
