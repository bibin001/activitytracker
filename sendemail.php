<?php
session_start();
?>
<style>
    .sidebar {
        height: 100vh;
        position: fixed;
        left: 0;
        top: 56px; /* Navbar height */
        width: 200px;
        background-color: #f8f9fa;
        padding-top: 20px;
        border-right: 1px solid #dee2e6;
    }
    .sidebar a {
        padding: 10px 15px;
        display: block;
        color: #343a40;
        text-decoration: none;
    }
    .sidebar a:hover {
        background-color: #e9ecef;
    }
    /* New CSS for table width */
    .table-container {
        max-width: 800px; /* Set your desired width */
        margin: 0 auto; /* Center the table */
    }
    .table {
        width: 100%; /* Ensure the table uses full width of the container */
    }
</style>
<?php
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not an admin
    exit();
}

// Database connection
include('db.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Faculty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 56px; /* Navbar height */
            width: 200px;
            background-color: #f8f9fa;
            padding-top: 20px;
            border-right: 1px solid #dee2e6;
        }
        .sidebar a {
            padding: 10px 15px;
            display: block;
            color: #343a40;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #e9ecef;
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
    <h5 class="text-center">Admin Menu</h5>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_admin.php">Add Admin</a>
    <a href="add_faculty.php">Add Faculty</a>
    <a href="add_hod.php">Add HoD</a>
    <a href="view_activities.php">View Activities</a>
</div>

<!-- Main Content -->
<!-- Main Content -->
       <!-- Main Content -->
       <?php if (isset($_SESSION['email_status'])): ?>
    <div class="alert alert-info mt-3">
        <?php 
        echo htmlspecialchars($_SESSION['email_status']); 
        unset($_SESSION['email_status']); // Clear the message after displaying it
        ?>
    </div>
    <?php endif; ?>
    
        <div class="container my-4" style="margin-left: 220px;"> <!-- Add margin to accommodate the sidebar -->
            <h2>Send Email to Faculties</h2>
            <p>Click the button below to send emails to faculties with pending approval status for their admission year matching that of students.</p>
        
            <form method="POST" action="send_email_to_pending.php">
                <button type="submit" class="btn btn-primary">Send Emails</button>
            </form>
        </div>
        
        <div class="container my-4" style="margin-left: 220px;"> <!-- Add margin to accommodate the sidebar -->
            <h2>Send Email to Faculties</h2>
            <p>Click the button below to send emails to faculties Who NOT Approved Student Activities.</p>
        
            <form method="POST" action="send_email_to_pending_activities.php">
                <button type="submit" class="btn btn-primary">Send Emails</button>
            </form>
        </div>



<!-- Bootstrap JS and dependencies -->


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmDelete(button) {
        const form = button.parentNode;
        const facultyName = form.previousElementSibling.previousElementSibling.innerText; // Get faculty name from the row
        if (confirm(`Are you sure you want to delete ${facultyName}?`)) {
            form.submit(); // Submit the form if confirmed
        }
    }
</script>
</body>
</html>
