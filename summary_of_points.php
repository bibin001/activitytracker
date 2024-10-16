<?php
session_start();
include 'db.php'; // Include your database connection file

// Check if user is logged in
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}

// SQL query to get total points for each student by academic year
$sql = "
    SELECT 
        s.name, 
        s.ktu_register_no,
        CASE 
            WHEN MONTH(a.from_date) >= 7 THEN YEAR(a.from_date) 
            ELSE YEAR(a.from_date) - 1 
        END AS academic_year, 
        SUM(a.points) AS total_points
    FROM 
        events a
    JOIN 
        users s ON a.student_id = s.id
    GROUP BY 
        s.name, s.ktu_register_no, academic_year
    ORDER BY 
        s.name, academic_year;
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .section { display: none; }
        .active-section { display: block; }
    </style>
    <style>
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 56px;
            width: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            margin: 5px 0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .sidebar a:hover {
            background-color: #e2e6ea;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="#">Faculty Dashboard</a>
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
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</a>
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
    <h3>Menu</h3>
    <ul>
        <li><a href="#" onclick="showSection('dashboardSection')">Dashboard</a></li> <!-- Dashboard menu -->
        <li><a href="#" onclick="showSection('activitiesSection')">View Activities</a></li>
        <li><a onclick="showSection('summarySection')" href="summary_of_points.php">Summary of Points</a></li>
        <li><a href="#" onclick="showSection('studentListSection')">Student List</a></li>
    </ul>
    
</div>


<!-- Main Content -->
<div class="main-content">
<div id="summarySection" class="section active-section">
<div class="mb-3">
            <button class="btn btn-success" onclick="exportToExcel()">Export to Excel</button>
            <button class="btn btn-danger" onclick="exportToPDF()">Export to PDF</button>
        </div>

    <h1>Summary of Points</h1>

   

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>KTU Register No</th>
                <th>Academic Year</th>
                <th>Total Points</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['ktu_register_no']) . "</td>
                        <td>" . htmlspecialchars($row['academic_year']) . "</td>
                        <td>" . htmlspecialchars($row['total_points']) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</div>


<script>
    function exportToExcel() {
        window.location.href = 'export_to_excel.php';
    }

    function exportToPDF() {
        window.location.href = 'export_to_pdf.php';
    }
</script>
</body>
</html>
