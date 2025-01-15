<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Error messages
$file_size_error = isset($_GET['file_size_error']) && $_GET['file_size_error'] == 1;
$upload_error = isset($_GET['upload_error']) && $_GET['upload_error'] == 1;
$upload_success = isset($_GET['upload_success']) && $_GET['upload_success'] == 1;

// Database connection
include('db.php');

$student_id = $_SESSION['id'];

// Fetch activities
$sql = "SELECT * FROM events WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch student details
$sql1 = "SELECT * FROM users WHERE id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $student_id);
$stmt1->execute();
$student_data = $stmt1->get_result()->fetch_assoc();
$stmt1->close();

$approve = $student_data['approval'];
$regn = $student_data['ktu_register_no'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40 !important;
        }
        .navbar-brand, .nav-link, .dropdown-item {
            color: #2cf !important;
        }
        .nav-link:hover, .dropdown-item:hover {
            color: #adb5bd !important;
        }
        .container h1 {
            margin-top: 20px;
        }
        .modal-header {
            background-color: #007bff;
            color: #fff;
        }
        .btn-close {
            color: #fff;
        }
        .table thead th {
            background-color: #007bff;
            color: white;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .alert {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Student Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="change_password.php">Change Password</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Alerts -->
    <?php if ($file_size_error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error!</strong> Please upload a file smaller than 80KB.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($upload_error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error!</strong> There was an issue uploading your file. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($upload_success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Success!</strong> Your activity has been uploaded successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p class="text-muted">Register Number: <?php echo $regn; ?></p>

    <!-- Approval Section -->
    <?php if ($approve == 'approved'): ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addActivityModal">Add Activity</button>
    <?php elseif ($approve == 'pending'): ?>
        <button class="btn btn-warning mb-3" disabled>Approval from faculty advisor is pending</button>
    <?php endif; ?>

    <!-- Modal for Adding Activities -->
    <div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addActivityModalLabel">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="upload_activity.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" name="event_type" required>
                                <option disabled selected>Select Event Type</option>
                                <?php
                                $eventTypes = [
                                    "NCC", "NSS", "Sports", "Games", "Music", "MOOC", "Performing Arts",
                                    "Literary Arts", "Tech-fest", "Tech-quiz", "Professional Society Competitions",
                                    "Conference", "Seminar", "Exhibition", "Workshop", "STTP", "Paper Presentation",
                                    "Industrial Training", "Internship", "Patent Filed", "Prototype Developed",
                                    "Student Professional Societies", "Societal Innovations", "Special Initiatives"
                                ];
                                sort($eventTypes);
                                foreach ($eventTypes as $eventType):
                                ?>
                                    <option value="<?php echo htmlspecialchars($eventType); ?>"><?php echo htmlspecialchars($eventType); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">Upload PDF (max 80KB)</label>
                            <input type="file" class="form-control" name="pdf_file" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities Table -->
    <h2>Your Activities</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Event Type</th>
                <th>Event Level</th>
                <th>Category</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>PDF File</th>
                <th>Status</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($activities as $activity): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($activity['event_type']); ?></td>
                <td><?php echo htmlspecialchars($activity['event_level']); ?></td>
                <td><?php echo htmlspecialchars($activity['category'] ?: 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($activity['from_date']); ?></td>
                <td><?php echo htmlspecialchars($activity['to_date']); ?></td>
                <td><a href="<?php echo htmlspecialchars($activity['pdf_file']); ?>" target="_blank">View PDF</a></td>
                <td><?php echo htmlspecialchars($activity['stat']); ?></td>
                <td><?php echo htmlspecialchars($activity['points']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
