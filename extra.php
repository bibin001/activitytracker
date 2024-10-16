<?php
session_start();
require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}


// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $email = $_SESSION['email']; // Faculty email
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Verify the old password
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify the current password
        if (password_verify($old_password, $row['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                session_destroy(); // Destroy the session
                echo "<script>alert('Password changed successfully!'); window.location.href = 'index.php';</script>";
            } else {
                echo "<script>alert('Failed to change password.');</script>";
            }
            $update_stmt->close();
        } else {
            echo "<script>alert('Old password is incorrect!');</script>";
        }
    } else {
        echo "<script>alert('User not found!');</script>";
    }
    $stmt->close();
}


// Fetch faculty's branch, division, and admission year
$faculty_branch = $_SESSION['branch'];
$faculty_division = $_SESSION['division'];
$faculty_admission_year = $_SESSION['admission_year'];

// Fetch students who are pending approval (not approved)
$sql_pending = "
    SELECT u.id, u.name, u.email, u.admission_year, u.ktu_register_no, u.division, u.branch, u.approval 
    FROM users u 
    WHERE u.usertype = 'student' 
    AND u.branch = '$faculty_branch' 
    AND u.division = '$faculty_division' 
    AND u.admission_year = '$faculty_admission_year' 
    AND u.approval != 'approved'
";
$result_pending = $conn->query($sql_pending);

// Fetch students who are already approved
$sql_approved = "
    SELECT u.id, u.name, u.email, u.admission_year, u.division, u.branch, u.approval 
    FROM users u 
    WHERE u.usertype = 'student' 
    AND u.branch = '$faculty_branch' 
    AND u.division = '$faculty_division' 
    AND u.admission_year = '$faculty_admission_year' 
    AND u.approval = 'approved'
";
$result_approved = $conn->query($sql_approved);

// Fetch events for approved students
$sql_events = "
    SELECT e.id, e.event_type, e.from_date, e.to_date, e.event_level, e.pdf_file, e.remarks, e.stat, e.points,
           u.name AS student_name, u.ktu_register_no, u.admission_year
    FROM events e
    JOIN users u ON e.student_id = u.id
    WHERE u.approval = 'approved' 
    AND u.branch = '$faculty_branch' 
    AND u.division = '$faculty_division'
    AND u.usertype = 'student'
";
$result_events = $conn->query($sql_events);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
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
        <li><a href="#" onclick="showSection('studentListSection')">Student List</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="content">

    <!-- Dashboard Section (shown on page load and when "Dashboard" is clicked) -->
    <div id="dashboardSection" class="section active-section">
        <h2>Pending Student Approvals</h2>

        <table class="table table-responsive table-sm table-hover">
            <thead>
                <tr class="bg-warning">
                    <th>Sl.no</th>
                    <th>KTU Reg. No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Branch</th>
                    <th>Division</th>
                    <th>Admission Year</th>
                    <th>Approval Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $a=1; ?>
                <?php if ($result_pending->num_rows > 0): ?>
                    <?php while ($row = $result_pending->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $a; ?></td>
                            <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo htmlspecialchars($row['division']); ?></td>
                            <td><?php echo htmlspecialchars($row['admission_year']); ?></td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                                <a href="process_students.php?action=approve&student_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Approve</a>
                                <a href="process_students.php?action=delete&student_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                        <?php $a++; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No students pending approval.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Approved Student List Section -->
    <div id="studentListSection" class="section">
        <h2>Approved Student List</h2>

        <table class="table table-responsive table-sm table-hover table-bordered">
            <thead>
                <tr>
                    <th>Sl.no</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Branch</th>
                    <th>Division</th>
                    <th>Admission Year</th>
                    <th>Approval Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $a=1; ?>
                <?php if ($result_approved->num_rows > 0): ?>
                    <?php while ($row = $result_approved->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $a; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo htmlspecialchars($row['division']); ?></td>
                            <td><?php echo htmlspecialchars($row['admission_year']); ?></td>
                            <td><span class="badge bg-success">Approved</span></td>
                        </tr>
                        <?php  $a++;?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No approved students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>






    <!-- Activities Section -->
<div id="activitiesSection" class="section">

                    <!-- Filters for the activities -->
    <div class="row mb-3">
    <div class="col-md-3">
        <input type="text" id="searchStudentName" class="form-control" placeholder="Search by Student Name">
    </div>
    <div class="col-md-3">
        <input type="text" id="searchKTU" class="form-control" placeholder="Search by KTU Register No.">
    </div>
    <div class="col-md-2">
        <select id="searchEventType" class="form-select">
            <option value="">Select Event Type</option>
            <option value="NCC">NCC</option>
                                <option value="NSS">NSS</option>
                                <option value="Sports">Sports</option>
                                <option value="Games">Games</option>
                                <option value="Music">Music</option>
                                <option value="Performing Arts">Performing_Arts</option>
                                <option value="Literary Arts">Literary_Arts</option>
                                <option value="Tech-fest">Techfest</option>
                                <option value="Tech-quiz">Techquiz</option>
                                <option value="Professional Society Competitions">Professional_Society_Competitions</option>
                                <option value="Conference">Conference</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Exhibition">Exhibition</option>
                                <option value="Workshop">Workshop</option>
                                <option value="STTP">STTP</option>
                                <option value="Paper Presentation at IIT or NIT">Paper_Presentation_at_IIT_NIT</option>
                                <option value="Industrial Training">Industrial_Training</option>
                                <option value="Internship">Internship</option>
                                <option value="Industrial Visit">Industrial_Visit</option>
                                <option value="Foreign Language Skill">Foreign_Language_Skill</option>
                                <option value="Start Up Company">Start_Up_Company</option>
                                <option value="Patent Filed">Patent_Filed</option>
                                <option value="Patent Published">Patent_Published</option>
                                <option value="Patent Approved">Patent_Approved</option>
                                <option value="Patent Licensed">Patent_Licensed</option>
                                <option value="Prototype Developed">Prototype_Developed</option>
                                <option value="Awards for Product Developed">Awards_for_Product_Developed</option>
                                <option value="Innovative Tech Developed Used by Users">Innovative_Tech_Developed_Used_by_Users</option>
                                <option value="Funding for Innovative Products">Funding_for_Innovative_Products</option>
                                <option value="Startup Employment">Startup_Employment</option>
                                <option value="Societal Innovations">Societal_Innovations</option>
                                <option value="Student Professional Societies">Student_Professional_Societies</option>
                                <option value="College Association Chapters">College_Association_Chapters</option>
                                <option value="Festival and Technical_Events">Festival_and_Technical_Events</option>
                                <option value="Hobby Clubs">Hobby_Clubs</option>
                                <option value="Special Initiatives">Special_Initiatives</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" id="searchFromDate" class="form-control" placeholder="From Date">
    </div>
    <div class="col-md-2">
        <input type="date" id="searchToDate" class="form-control" placeholder="To Date">
    </div>
    <div class="col-md-3">
        <select id="searchEventLevel" class="form-select">
            <option value="">Select Event Level</option>
            <option value="Level I College Events">Level I College Events</option>
            <option value="Level II Zonal Events">Level II Zonal Events</option>
            <option value="Level III State or University Events">Level III State/University Events</option>
            <option value="Level IV National Events">Level IV National Events</option>
            <option value="Level V International Events">Level V International Events</option>
        </select>
    </div>
    <div class="col-md-3">
        <select id="searchAY" class="form-select">
            <option value="">Select Academic Year</option>
            <option value="2020">2020-21</option>
            <option value="2021">2021-22</option>
            <option value="2022">2022-23</option>
            <option value="2023">2023-24</option>
            <option value="2024">2024-25</option>
            <option value="2025">2025-26</option>
            <option value="2026">2026-27</option>
            <option value="2027">2027-28</option>
            <!-- Continue for all semesters -->
        </select>
    </div>

    <div class="col-md-6">
        <button class="btn btn-success" id="exportToExcel">Export to Excel</button>
        <button class="btn btn-danger" id="exportToPDF">Export to PDF</button>
    </div>
    </div>



        <h2>View Activities</h2>

        <table id="activitiesTable" class="table table-responsive table-sm table-hover table-bordered">
            <thead>
                <tr class="table-info">
                    <th>#</th>
                    <th>Student Name</th>
                    <th>KTU Register No.</th>
                    <th>Event Type</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Event Level</th>
                    <th>Proof</th>
                    <th>Points</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody  id="activitiesTableBody">
                <?php if ($result_events->num_rows > 0): ?>
                    <?php $a=1; ?>
                    <?php while ($row = $result_events->fetch_assoc()): ?>
                                    <tr data-student-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                        data-ktu-register-no="<?php echo htmlspecialchars($row['ktu_register_no']); ?>"
                                        data-event-type="<?php echo htmlspecialchars($row['event_type']); ?>"
                                        data-from-date="<?php echo htmlspecialchars($row['from_date']); ?>"
                                        data-to-date="<?php echo htmlspecialchars($row['to_date']); ?>"
                                        data-event-level="<?php echo htmlspecialchars($row['event_level']); ?>"
                                        >
                                        <td><?php echo $a; ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_level']); ?></td>
                                        <!-- PDF Thumbnail (if available) -->
                                        <td><a href="<?php echo $row['pdf_file']; ?>" target="_blank">View</a></td>

                                        <td><?php echo htmlspecialchars($row['points']); ?></td>
                                        <td>
                                            <?php if ($row['stat'] === 'Pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="openPointsModal(<?php echo $row['id']; ?>)">Approve</button>
                                                <a href="process_events.php?action=delete&event_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                                                <button class="btn btn-sm btn-warning" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['event_type']); ?>', '<?php echo htmlspecialchars($row['from_date']); ?>', '<?php echo htmlspecialchars($row['to_date']); ?>', '<?php echo htmlspecialchars($row['event_level']); ?>')">Edit</button>


                                                <?php else: ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($row['stat']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $a++; ?>
                             <?php endwhile; ?>
                         <?php else: ?>
                        <tr>
                        <td colspan="10">No events found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Points Modal -->
<div class="modal fade" id="pointsModal" tabindex="-1" aria-labelledby="pointsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="pointsForm" method="POST" action="process_events.php?action=approve">
                <div class="modal-header">
                    <h5 class="modal-title" id="pointsModalLabel">Approve Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="modal_event_id" value="">
                    <div class="form-group">
                        <label for="points">Points</label>
                        <input type="number" class="form-control" name="points" id="points" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" name="remarks" id="remarks" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<!-- Edit Event Modal -->
<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editEventForm" method="POST" action="process_events.php?action=edit">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- <input type="hidden" name="event_id" id="modal_event_id" value=""> -->
                    <input type="hidden" name="event_id" value="<?php echo $row['id'];?>" id="edit_event_id">
                    <div class="form-group">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" name="event_type" id="event_type" required>
                            <option selected disabled>Open this select menu</option>
                            <option value="NCC">NCC</option>
                            <option value="NSS">NSS</option>
                            <option value="Sports">Sports</option>
                            <option value="Games">Games</option>
                            <option value="Music">Music</option>
                            <option value="Performing Arts">Performing Arts</option>
                            <option value="Literary Arts">Literary Arts</option>
                            <option value="Tech-fest">Tech-fest</option>
                            <option value="Tech-quiz">Tech-quiz</option>
                            <option value="Professional Society Competitions">Professional Society Competitions</option>
                            <option value="Conference">Conference</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Exhibition">Exhibition</option>
                            <option value="Workshop">Workshop</option>
                            <option value="STTP">STTP</option>
                            <option value="Paper Presentation at IIT or NIT">Paper Presentation at IIT or NIT</option>
                            <option value="Industrial Training">Industrial Training</option>
                            <option value="Internship">Internship</option>
                            <option value="Industrial Visit">Industrial Visit</option>
                            <option value="Foreign Language Skill">Foreign Language Skill</option>
                            <option value="Start Up Company">Start Up Company</option>
                            <option value="Patent Filed">Patent Filed</option>
                            <option value="Patent Published">Patent Published</option>
                            <option value="Patent Approved">Patent Approved</option>
                            <option value="Patent Licensed">Patent Licensed</option>
                            <option value="Prototype Developed">Prototype Developed</option>
                            <option value="Awards for Product Developed">Awards for Product Developed</option>
                            <option value="Innovative Tech Developed Used by Users">Innovative Tech Developed Used by Users</option>
                            <option value="Funding for Innovative Products">Funding for Innovative Products</option>
                            <option value="Startup Employment">Startup Employment</option>
                            <option value="Societal Innovations">Societal Innovations</option>
                            <option value="Student Professional Societies">Student Professional Societies</option>
                            <option value="College Association Chapters">College Association Chapters</option>
                            <option value="Festival and Technical Events">Festival and Technical Events</option>
                            <option value="Hobby Clubs">Hobby Clubs</option>
                            <option value="Special Initiatives">Special Initiatives</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" id="from_date" required>
                    </div>
                    <div class="form-group">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" id="to_date" required>
                    </div>
                    <div class="form-group">
                        <label for="event_level" class="form-label">Event Level</label>
                        <select class="form-select" name="event_level" id="event_level" required>
                            <option selected disabled>Open this select menu</option>
                            <option value="Level I College Events">Level I College Events</option>
                            <option value="Level II Zonal Events">Level II Zonal Events</option>
                            <option value="Level III State or University Events">Level III State or University Events</option>
                            <option value="Level IV National Events">Level IV National Events</option>
                            <option value="Level V International Events">Level V International Events</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>




<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="old_password" class="form-label">Old Password</label>
                            <input type="password" class="form-control" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Bootstrap and jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Function to filter the activities table
// Function to filter the activities table
function filterTable() {
    var studentName = document.getElementById('searchStudentName').value.toLowerCase();
    var ktuRegisterNo = document.getElementById('searchKTU').value.toLowerCase();
    var eventType = document.getElementById('searchEventType').value;
    var fromDate = document.getElementById('searchFromDate').value;
    var toDate = document.getElementById('searchToDate').value;
    var eventLevel = document.getElementById('searchEventLevel').value;
    var academicYear = document.getElementById('searchAY').value;

    // Automatically adjust fromDate and toDate based on selected academic year
    if (academicYear) {
        let startYear = parseInt(academicYear);
        let endYear = startYear + 1;

        // Convert the start and end dates to the format YYYY-MM-DD for comparison
        fromDate = `${startYear}-07-01`; // From 1st July of the selected start year
        toDate = `${endYear}-06-30`; // To 30th June of the next year
    }

    var rows = document.querySelectorAll('#activitiesTableBody tr');

    rows.forEach(function(row) {
        var studentNameValue = row.getAttribute('data-student-name').toLowerCase();
        var ktuRegisterNoValue = row.getAttribute('data-ktu-register-no').toLowerCase();
        var eventTypeValue = row.getAttribute('data-event-type');
        var fromDateValue = row.getAttribute('data-from-date');
        var toDateValue = row.getAttribute('data-to-date');
        var eventLevelValue = row.getAttribute('data-event-level');

        var showRow = true;

        // Filter based on search criteria
        if (studentName && !studentNameValue.includes(studentName)) showRow = false;
        if (ktuRegisterNo && !ktuRegisterNoValue.includes(ktuRegisterNo)) showRow = false;
        if (eventType && eventType !== eventTypeValue) showRow = false;
        if (fromDate && fromDate > fromDateValue) showRow = false; // Check if event's start date is before the filter fromDate
        if (toDate && toDate < toDateValue) showRow = false; // Check if event's end date is after the filter toDate
        if (eventLevel && eventLevel !== eventLevelValue) showRow = false;

        // Show or hide the row
        row.style.display = showRow ? '' : 'none';
    });
}

// Add event listeners to search inputs
document.getElementById('searchStudentName').addEventListener('input', filterTable);
document.getElementById('searchKTU').addEventListener('input', filterTable);
document.getElementById('searchEventType').addEventListener('change', filterTable);
document.getElementById('searchFromDate').addEventListener('change', filterTable);
document.getElementById('searchToDate').addEventListener('change', filterTable);
document.getElementById('searchEventLevel').addEventListener('change', filterTable);
document.getElementById('searchAY').addEventListener('change', filterTable);



</script>

<script>
    // Open Edit Modal and populate data
    function openEditModal(eventId, eventType, fromDate, toDate, eventLevel) {
    document.getElementById('edit_event_id').value = eventId; // Check this ID
    document.getElementById('from_date').value = fromDate; // Check this ID
    document.getElementById('to_date').value = toDate; // Check this ID

    // Set the selected values for event type and event level
    const eventTypeSelect = document.getElementById('event_type'); // Check this ID
    const eventLevelSelect = document.getElementById('event_level'); // Check this ID

    // Set the selected option for event type
    eventTypeSelect.value = eventType;

    // Set the selected option for event level
    eventLevelSelect.value = eventLevel;

    var editModal = new bootstrap.Modal(document.getElementById('editEventModal')); // Check this ID
    editModal.show();


}

</script>


<!--PDF and EXCEL EXPORT -->
<!-- Include required JS libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>

<script>
// Export filtered table to Excel
document.getElementById('exportToExcel').addEventListener('click', function() {
    var table = document.getElementById('activitiesTable');
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, "activities.xlsx");
});

// Export filtered table to PDF
document.getElementById('exportToPDF').addEventListener('click', function() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF();

    var columns = [];
    var rows = [];

    var headers = document.querySelectorAll('#activitiesTable thead th');
    headers.forEach(function(header) {
        columns.push(header.innerText);
    });

    var tableRows = document.querySelectorAll('#activitiesTable tbody tr');
    tableRows.forEach(function(row) {
        if (row.style.display !== 'none') { // Only export visible (filtered) rows
            var rowData = [];
            var cells = row.querySelectorAll('td');
            cells.forEach(function(cell) {
                rowData.push(cell.innerText);
            });
            rows.push(rowData);
        }
    });

    doc.autoTable({
        head: [columns],
        body: rows,
        startY: 10
    });

    doc.save("activities.pdf");
});
</script>





<script>
    function showSection(sectionId) {
        // Hide all sections
        var sections = document.querySelectorAll('.section');
        sections.forEach(function (section) {
            section.classList.remove('active-section');
        });
        
        // Show the selected section
        document.getElementById(sectionId).classList.add('active-section');
    }

    function openPointsModal(eventId) {
        document.getElementById('modal_event_id').value = eventId;
        var pointsModal = new bootstrap.Modal(document.getElementById('pointsModal'));
        pointsModal.show();
    }
</script>
</body>
</html>
