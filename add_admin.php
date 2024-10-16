<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not an admin
    exit();
}

// Database connection
include('db.php');

$message = '';

// Check if the form is submitted to add a new admin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if it is for adding an admin
    if (isset($_POST['add_admin'])) {
        // Get form input values
        $name = $_POST['name'];
        $email = $_POST['email'];
        $usertype = 'admin'; // Default user type
        $password = $_POST['password'];

        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement
        $sql = "INSERT INTO users (name, email, usertype, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $usertype, $hashed_password);
        
        if ($stmt->execute()) {
            $message = "New admin added successfully!";
        } else {
            $message = "Error adding admin: " . $stmt->error;
        }

        $stmt->close();
    }

    // Check if it is for deleting an admin
    if (isset($_POST['delete_admin'])) {
        $admin_id = $_POST['admin_id'];

        // Prepare and execute the SQL statement to delete admin
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);

        if ($stmt->execute()) {
            $message = "Admin deleted successfully!";
        } else {
            $message = "Error deleting admin: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Fetch existing admin users
$sql = "SELECT * FROM users WHERE usertype = 'admin'";
$result = $conn->query($sql);
$admins = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
$result->close();

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin</title>
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
    <script>
        function confirmDelete(adminId) {
            const confirmAction = confirm("Are you sure you want to delete this admin?");
            if (confirmAction) {
                document.getElementById('deleteAdminForm' + adminId).submit();
            }
        }
    </script>
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
<div class="container mt-5" style="margin-left: 220px;"> <!-- Adjust left margin for sidebar -->
    <h1 class="text-center mb-4">Admin Users</h1>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addAdminModal">Add New Admin</button>

    <h2 class="text-center mb-4">Existing Admin Users</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?php echo htmlspecialchars($admin['id']); ?></td>
                <td><?php echo htmlspecialchars($admin['name']); ?></td>
                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                <td><?php echo htmlspecialchars($admin['usertype']); ?></td>
                <td>
                    <form id="deleteAdminForm<?php echo $admin['id']; ?>" method="POST" action="add_admin.php" style="display:inline;">
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                        <input type="hidden" name="delete_admin" value="1">
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $admin['id']; ?>)">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_admin.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
