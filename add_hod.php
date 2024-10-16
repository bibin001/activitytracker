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

$message = '';

// Check if the form is submitted to add a new faculty
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new faculty
    if (isset($_POST['add_hod'])) {
        // Get form input values
        $name = $_POST['name'];
        $email = $_POST['email'];
        $usertype = 'hod'; // Default user type
        $password = $_POST['password'];
        
        $branch = $_POST['branch'];
        

        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement
        $sql = "INSERT INTO users (name, email, usertype, password,  branch) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $usertype, $hashed_password, $branch);
        
        if ($stmt->execute()) {
            $message = "New HoD added successfully!";
        } else {
            $message = "Error adding HoD: " . $stmt->error;
        }

        $stmt->close();
    }

    // Update Faculty
    if (isset($_POST['update_hod'])) {
        $id = $_POST['id'];
        $name = $_POST['edit_name'];
        $email = $_POST['edit_email'];
        
        $branch = $_POST['edit_branch'];
       

        $sql = "UPDATE users SET name=?, email=?,  branch=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $branch, $id);
        
        if ($stmt->execute()) {
            $message = "HoD updated successfully!";
        } else {
            $message = "Error updating HoD: " . $stmt->error;
        }

        $stmt->close();
    }

    // Delete Faculty
    if (isset($_POST['delete_fhod'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM users WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "HoD deleted successfully!";
        } else {
            $message = "Error deleting HoD: " . $stmt->error;
        }
    
        $stmt->close();
    }
    
}

// Fetch existing faculty users
$sql = "SELECT * FROM users WHERE usertype = 'hod'";
$result = $conn->query($sql);
$faculties = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculties[] = $row;
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
<div class="container mt-5" style="margin-left: 220px;"> <!-- Adjust left margin for sidebar -->
    <h1 class="text-center mb-4">Admin Users</h1>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addFacultyModal">Add New HoD</button>

    <h2 class="text-center mb-4">Existing HoDs</h2>
    <div class="table-container">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                
                <th>Branch</th>
                
                <th>Actions</th> <!-- Added Actions Column -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($faculties as $faculty): ?>
            <tr>
                <td><?php echo htmlspecialchars($faculty['id']); ?></td>
                <td><?php echo htmlspecialchars($faculty['name']); ?></td>
                <td><?php echo htmlspecialchars($faculty['email']); ?></td>
                
                <td><?php echo htmlspecialchars($faculty['branch']); ?></td>
                
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editFacultyModal<?php echo $faculty['id']; ?>">Edit</button>
                    <!-- Delete Button -->
                    <form action="add_hod.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $faculty['id']; ?>">
                        <button type="submit" name="delete_hod" class="btn btn-danger btn-sm">Delete</button>
                    </form>

                </td>
            </tr>

            <!-- Edit Faculty Modal -->
            <div class="modal fade" id="editFacultyModal<?php echo $faculty['id']; ?>" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editFacultyModalLabel">Edit HoD Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="add_hod.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $faculty['id']; ?>">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="edit_name" value="<?php echo htmlspecialchars($faculty['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="edit_email" value="<?php echo htmlspecialchars($faculty['email']); ?>" required>
                                </div>
                               
                                <div class="mb-3">
                                    <label for="edit_branch" class="form-label">Branch</label>
                                    <select class="form-select" name="edit_branch" required>
                                        <option value="<?php echo htmlspecialchars($faculty['branch']); ?>" selected><?php echo htmlspecialchars($faculty['branch']); ?></option>
                                        <option value="CSE">CSE</option>
                                    </select>
                                </div>
                                
                                
                                <button type="submit" name="update_hod" class="btn btn-primary">Update Faculty</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFacultyModalLabel">Add New HoD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_hod.php" method="POST">
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
                    
                    <div class="mb-3">
                        <label for="branch" class="form-label">Branch</label>
                        <select class="form-select" name="branch" required>
                            <option value="">Select Branch</option>
                            <option value="CSE">CSE</option>
                        </select>
                    </div>
                    
                    </div>
                    
                    <button type="submit" name="add_hod" class="btn btn-primary">Add Faculty</button>
                </form>
            </div>
        </div>
    </div>
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
