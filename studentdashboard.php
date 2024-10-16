<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'student') {
    header("Location: login.php"); // Redirect to login if not a student
    exit();
}


// Check if the file_size_error flag is set in the URL parameters
if (isset($_GET['file_size_error']) && $_GET['file_size_error'] == 1): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> Please upload a file smaller than 80KB.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Check if other error flags are set (optional, if needed)
if (isset($_GET['upload_error']) && $_GET['upload_error'] == 1): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> There was an issue uploading your file. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Optionally handle success messages as well
if (isset($_GET['upload_success']) && $_GET['upload_success'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Your activity has been uploaded successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; 


// Database connection
include('db.php');

// Fetch previously uploaded activities
$student_id = $_SESSION['id'];
$sql = "SELECT * FROM events WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$stmt->close();



$stud_id = $_SESSION['id'];
$sql1 = "SELECT * FROM users WHERE id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $stud_id);
$stmt1->execute();
$result1 = $stmt1->get_result();


while ($row = $result1->fetch_assoc()) {
    $approve = $row['approval'];
}

$stmt1->close();
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
        /* Custom styles for the table */
        .activities-table {
            max-width: 800px; /* Set a maximum width */
            margin: auto; /* Center the table */
        }
        .activities-table th, .activities-table td {
            border: 1px solid #dee2e6; /* Add borders */
            padding: 10px; /* Add padding */
            text-align: center; /* Center the text */
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="#">Student Dashboard</a>
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

<div class="container mt-5">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <?php
        if($approve=='approved') {
    ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">Add Activity</button>
    <?php
        }
    ?>
    <?php
        if($approve=='pending') {
    ?>
        <button class="btn btn-warning">Approval from faculty advisor is pending</button>
    <?php
        }
    ?>
    <!-- Modal -->
    <div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addActivityModalLabel">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="upload_activity.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                        <?php
                        // Define an array of event types
                            $eventTypes = [
                            "NCC",
                            "NSS",
                            "Sports",
                            "Games",
                            "Music",
                            "Performing Arts",
                            "Literary Arts",
                            "Tech-fest",
                            "Tech-quiz",
                            "Professional Society Competitions",
                            "Conference",
                            "Seminar",
                            "Exhibition",
                            "Workshop",
                            "STTP",
                            "Paper Presentation at IIT or NIT",
                            "Industrial Training",
                            "Internship",
                            "Industrial Visit",
                            "Foreign Language Skill",
                            "Start Up Company",
                            "Patent Filed",
                            "Patent Published",
                            "Patent Approved",
                            "Patent Licensed",
                            "Prototype Developed",
                            "Awards for Product Developed",
                            "Innovative Tech Developed Used by Users",
                            "Funding for Innovative Products",
                            "Startup Employment",
                            "Societal Innovations",
                            "Student Professional Societies",
                            "College Association Chapters",
                            "Festival and Technical Events",
                            "Hobby Clubs",
                            "Special Initiatives"
                        ];

                        // Sort the array alphabetically
                        sort($eventTypes);
                        ?>
                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" name="event_type" aria-label="Default select example" required>
                                 <option selected disabled>Open this select menu</option>
                                 <?php foreach ($eventTypes as $eventType): ?>
                                 <option value="<?php echo htmlspecialchars($eventType); ?>"><?php echo htmlspecialchars($eventType); ?></option>
                                 <?php endforeach; ?>
                            </select>
                        </div>
                        <label for="event_level" class="form-label">Event Level</label>
                        <select class="form-select" name="event_level" aria-label="Default select example" required>
                            <option selected>Open this select menu</option>
                            <option value="Level I College Events">Level I College Events</option>
                            <option value="Level II Zonal Events">Level II Zonal Events</option>
                            <option value="Level III State or University Events">Level III State/University Events</option>
                            <option value="Level IV National Events">Level IV National Events</option>
                            <option value="Level V International Events">Level V International Events</option>
                        </select>
                        <div class="mb-3">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="to_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" required>
                        </div>
                        <!-- <div class="mb-3">
                            <label for="semester" class="form-label">Semester in which you participated for the event </label>
                            <select class="form-select" name="semester" aria-label="Default select example" required>
                                <option selected>Open this select menu</option>
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
                            <label for="pdf_file" class="form-label">Upload PDF,jpg or jpeg (max 80kb)</label>
                            <input type="file" class="form-control" name="pdf_file" accept=".pdf, .jpg, .jpeg" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mt-5">Your Activities</h2>
    <div class="activities-table">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event Type</th>
                    <th>Event Level</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <!-- <th>Semester</th> -->
                    <th>PDF File</th>
                    <th>Status</th>
                    <th>Activity Points</th>
                </tr>
            </thead>
            <tbody>
                <?php  $a=1;?>
                <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo $a; ?></td>
                    <td><?php echo htmlspecialchars($activity['event_type']); ?></td>
                    <td><?php echo htmlspecialchars($activity['event_level']); ?></td>
                    <td><?php echo htmlspecialchars($activity['from_date']); ?></td>
                    <td><?php echo htmlspecialchars($activity['to_date']); ?></td>
                    <!-- <td><?php //echo htmlspecialchars($activity['semester']); ?></td> -->
                    <td><a href="<?php echo htmlspecialchars($activity['pdf_file']); ?>" target="_blank">View PDF</a></td>
                    <td><?php echo htmlspecialchars($activity['stat']); ?></td>
                    <td><?php echo htmlspecialchars($activity['points']); ?></td>
                </tr>
                <?php $a=$a+1; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php
                    $yearly_points = []; // To store points for each academic year
                    $total_points = 0;   // To store total points across all activities

                    foreach ($activities as $activity) {
                    // Extract the from_date
                    $from_date = $activity['from_date'];
                    $year = date('Y', strtotime($from_date));   // Get the year from the from_date
                    $month = date('m', strtotime($from_date));  // Get the month from the from_date

                    // Determine the academic year
                    if ($month >= 7) {
                        // If the month is July (07) or later, academic year is current year to next year
                        $academic_year = $year . '-' . ($year + 1);
                    } else {
                        // If the month is before July, academic year is previous year to current year
                        $academic_year = ($year - 1) . '-' . $year;
                    }

                    // Add the points for this activity to the corresponding academic year
                    $points = (int)$activity['points'];
                    if (!isset($yearly_points[$academic_year])) {
                        $yearly_points[$academic_year] = 0; // Initialize if year doesn't exist yet
                    }
                    $yearly_points[$academic_year] += $points;

                    // Add to the total points
                    $total_points += $points;
                }
                ?>

                <!-- Display Total Points for Each Academic Year -->
                <h3>Total Points per Academic Year</h3>
                <ul>
                    <?php foreach ($yearly_points as $academic_year => $points): ?>
                    <li><strong><?php echo $academic_year; ?>:</strong> <?php echo $points; ?> points</li>
                    <?php endforeach; ?>
                </ul>

                <!-- Display Overall Total Points -->
                <h3>Total Points for All Activities: <?php echo $total_points; ?> points</h3>


</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
