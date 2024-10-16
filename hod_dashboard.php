<?php
session_start();
require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'hod') {
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
//$faculty_division = $_SESSION['division'];
//$faculty_admission_year = $_SESSION['admission_year'];

// Fetch students who are pending approval (not approved)
$sql_pending = "
    SELECT e.id, e.event_type, e.from_date, e.to_date, e.event_level, e.pdf_file, e.remarks, e.stat, e.points,
           u.name AS student_name, u.ktu_register_no, u.admission_year, u.division
    FROM events e
    JOIN users u ON e.student_id = u.id
    WHERE u.approval != 'approved' 
    AND u.branch = '$faculty_branch' 
    
    AND u.usertype = 'student' order by u.ktu_register_no
";
$result_pending = $conn->query($sql_pending);

// Fetch students who are already approved
$sql_approved = "
    SELECT u.id, u.name, u.email, u.admission_year,u.ktu_register_no,  u.division, u.branch, u.approval 
    FROM users u 
    WHERE u.usertype = 'student' 
    AND u.branch = '$faculty_branch' 
     
    AND u.approval = 'approved'
    ORDER BY u.ktu_register_no
";
$result_approved = $conn->query($sql_approved);

// Fetch events for approved students
$sql_events = "
    SELECT e.id, e.event_type, e.from_date, e.to_date, e.event_level, e.pdf_file, e.remarks, e.stat, e.points,
           u.name AS student_name, u.ktu_register_no, u.admission_year, u.division
    FROM events e
    JOIN users u ON e.student_id = u.id
    WHERE u.approval = 'approved' 
    AND u.branch = '$faculty_branch' 
    
    AND u.usertype = 'student' order by u.ktu_register_no
";
$result_events = $conn->query($sql_events);

// SQL query to get total points for each student by academic year
$sql1 = "
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
    WHERE s.approval = 'approved' 
    AND s.branch = '$faculty_branch' 
    AND s.usertype = 'student'
    GROUP BY 
        s.name, s.ktu_register_no, academic_year
    ORDER BY 
        s.ktu_register_no, academic_year;
";

$result1 = $conn->query($sql1);


//Total points
$sql2 = "
    SELECT 
        s.name, 
        s.ktu_register_no,
        SUM(a.points) AS total_points
    FROM 
        events a
    JOIN 
        users s ON a.student_id = s.id
    WHERE s.approval = 'approved' 
    AND s.branch = '$faculty_branch' 
    AND s.usertype = 'student'
    GROUP BY 
        s.name, s.ktu_register_no
    ORDER BY 
        s.ktu_register_no;
";

$result2 = $conn->query($sql2);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoD Dashboard</title>
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
        <a class="navbar-brand" href="#">HoD Dashboard</a>
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
        <li><a href="#" onclick="showSection('summarySection')">Summary of Points</a></li>
        <li><a href="#" onclick="showSection('summaryTotalSection')">Summary of Total Points</a></li>
        <li><a href="#" onclick="showSection('studentListSection')">Student List</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="content">

    <!-- Dashboard Section (shown on page load and when "Dashboard" is clicked) -->
    <div id="dashboardSection" class="section active-section">
        <h2>Pending Activities Approvals</h2>

        <table class="table table-responsive table-sm table-hover">
            <thead>
                <tr class="bg-warning">
                    <th>Sl.no</th>
                    <th>KTU Reg. No</th>
                    <th>Name</th>
                    <th>Division</th>
                    <th>Admission Year</th>
                    <th>Approval Status</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php $a=1; ?>
                <?php if ($result_pending->num_rows > 0): ?>
                    <?php while ($row = $result_pending->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $a; ?></td>
                            <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            
                            
                            <td><?php echo htmlspecialchars($row['division']); ?></td>
                            <td><?php echo htmlspecialchars($row['admission_year']); ?></td>
                            <td><?php echo htmlspecialchars($row['stat']); ?></td>
                            
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


<!-- summarySection -->
<div id="summarySection" class="section">
            <h2>Summary of Points</h2>
            <div class="col-md-6">
                 <button class="btn btn-success" id="exportToExcelsummary">Export to Excel</button>
                 <button class="btn btn-danger" id="exportToPDFsummary">Export to PDF</button>
             </div>

            <table id="summaryTable" class="table table-responsive table-sm table-hover table-bordered">
                <thead>
                    <tr  class="table-info">
                        <th>Student Name</th>
                        <th>KTU Register No</th>
                        <th>Academic Year</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row1['name']) . "</td>
                                <td>" . htmlspecialchars($row1['ktu_register_no']) . "</td>
                                <td>" . htmlspecialchars($row1['academic_year']) . "</td>
                                <td>" . htmlspecialchars($row1['total_points']) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>


        <!--summaryTotal-->
         <!-- summaryTotalSection -->
     <div id="summaryTotalSection" class="section">
            <h2>Summary of Points</h2>

            <div class="col-md-6">
                 <button class="btn btn-success" id="exportToExceltotalsummary">Export to Excel</button>
                 <button class="btn btn-danger" id="exportToPDFtotalsummary">Export to PDF</button>
             </div>

            <table id="summarytotalTable" class="table table-responsive table-sm table-hover table-bordered">
                <thead>
                    <tr  class="table-info">
                        <th>Student Name</th>
                        <th>KTU Register No</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row2['name']) . "</td>
                                <td>" . htmlspecialchars($row2['ktu_register_no']) . "</td>
                                
                                <td>" . htmlspecialchars($row2['total_points']) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No records found</td></tr>";
                    }
                    ?>
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
                    <th>KTU Reg. no.</th>
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
                            <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
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
            <?php
            // Define an array of event types
            $eventTypes = [
                "NCC", "NSS", "Sports", "Games", "Music", "Performing Arts", "Literary Arts",
                "Tech-fest", "Tech-quiz", "Professional Society Competitions", "Conference", "Seminar",
                "Exhibition", "Workshop", "STTP", "Paper Presentation at IIT or NIT", "Industrial Training",
                "Internship", "Industrial Visit", "Foreign Language Skill", "Start Up Company", "Patent Filed",
                "Patent Published", "Patent Approved", "Patent Licensed", "Prototype Developed",
                "Awards for Product Developed", "Innovative Tech Developed Used by Users",
                "Funding for Innovative Products", "Startup Employment", "Societal Innovations",
                "Student Professional Societies", "College Association Chapters", "Festival and Technical Events",
                "Hobby Clubs", "Special Initiatives"
            ];

            // Sort the array alphabetically
            sort($eventTypes);
            ?>
            <select class="form-select" id="searchEventType">
                <option value="">Select Event Type</option>
                <?php foreach ($eventTypes as $eventType): ?>
                    <option value="<?php echo htmlspecialchars($eventType); ?>"><?php echo htmlspecialchars($eventType); ?></option>
                <?php endforeach; ?>
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
        <!-- New Filters -->
        <div class="col-md-3">
            <select id="searchAdmissionYear" class="form-select">
                <option value="">Select Admission Year</option>
                <option value="2020">2020</option>
                <option value="2021">2021</option>
                <option value="2022">2022</option>
                <option value="2023">2023</option>
                <!-- Add more years as necessary -->
            </select>
        </div>
        <div class="col-md-3">
            <select id="searchDivision" class="form-select">
                <option value="">Select Division</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <!-- Add more divisions as necessary -->
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
        <tbody id="activitiesTableBody">
            <?php if ($result_events->num_rows > 0): ?>
                <?php $a=1; ?>
                <?php while ($row = $result_events->fetch_assoc()): ?>
                    <tr data-student-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                        data-ktu-register-no="<?php echo htmlspecialchars($row['ktu_register_no']); ?>"
                        data-event-type="<?php echo htmlspecialchars($row['event_type']); ?>"
                        data-from-date="<?php echo htmlspecialchars($row['from_date']); ?>"
                        data-to-date="<?php echo htmlspecialchars($row['to_date']); ?>"
                        data-event-level="<?php echo htmlspecialchars($row['event_level']); ?>"
                        data-admission-year="<?php echo htmlspecialchars($row['admission_year']); ?>"  
                        data-division="<?php echo htmlspecialchars($row['division']); ?>"> <!-- New attribute -->
                        <td><?php echo $a; ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['event_level']); ?></td>
                        <td><a href="<?php echo $row['pdf_file']; ?>" target="_blank">View</a></td>
                        <td><?php echo htmlspecialchars($row['points']); ?></td>
                        <td>
                            <?php
                            if ($row['stat'] === 'approved'):
                                echo $row['remarks'];
                            endif
                            ?>
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
    var admissionYear = document.getElementById('searchAdmissionYear').value; // New filter
    var division = document.getElementById('searchDivision').value; // New filter

    // Automatically adjust fromDate and toDate based on selected academic year
    if (academicYear) {
        let startYear = parseInt(academicYear);
        let endYear = startYear + 1;

        // Convert the start and end dates to the format YYYY-MM-DD for comparison
        fromDate = `${startYear}-07-01`; // From 1st July of the selected start year
        toDate = `${endYear}-06-30`; // To 30th June of the next year
    }

    const rows = document.querySelectorAll('#activitiesTableBody tr');

    rows.forEach(row => {
        const studentNameCell = row.getAttribute('data-student-name').toLowerCase();
        const ktuRegisterNoCell = row.getAttribute('data-ktu-register-no').toLowerCase();
        const eventTypeCell = row.getAttribute('data-event-type');
        const fromDateCell = row.getAttribute('data-from-date');
        const toDateCell = row.getAttribute('data-to-date');
        const eventLevelCell = row.getAttribute('data-event-level');
        const admissionYearCell = row.getAttribute('data-admission-year'); // New attribute
        const divisionCell = row.getAttribute('data-division'); // New attribute

        const isStudentNameMatch = studentNameCell.includes(studentName);
        const isKtuRegisterNoMatch = ktuRegisterNoCell.includes(ktuRegisterNo);
        const isEventTypeMatch = eventType === '' || eventTypeCell === eventType;
        const isFromDateMatch = fromDate === '' || fromDateCell >= fromDate;
        const isToDateMatch = toDate === '' || toDateCell <= toDate;
        const isEventLevelMatch = eventLevel === '' || eventLevelCell === eventLevel;
        const isAdmissionYearMatch = admissionYear === '' || admissionYearCell === admissionYear; // New filter match
        const isDivisionMatch = division === '' || divisionCell === division; // New filter match

        // Show row if all filter criteria match
        if (isStudentNameMatch && isKtuRegisterNoMatch && isEventTypeMatch &&
            isFromDateMatch && isToDateMatch && isEventLevelMatch &&
            isAdmissionYearMatch && isDivisionMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Add event listeners to all filter inputs
document.getElementById('searchStudentName').addEventListener('input', filterTable);
document.getElementById('searchKTU').addEventListener('input', filterTable);
document.getElementById('searchEventType').addEventListener('change', filterTable);
document.getElementById('searchFromDate').addEventListener('change', filterTable);
document.getElementById('searchToDate').addEventListener('change', filterTable);
document.getElementById('searchEventLevel').addEventListener('change', filterTable);
document.getElementById('searchAY').addEventListener('change', filterTable);
document.getElementById('searchAdmissionYear').addEventListener('change', filterTable); // New filter event
document.getElementById('searchDivision').addEventListener('change', filterTable); // New filter event




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

//Summary of points
document.getElementById('exportToExcelsummary').addEventListener('click', function() {
    var table = document.getElementById('summaryTable');
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, "summaryactivities.xlsx");
});

//Total Summary of points
document.getElementById('exportToExceltotalsummary').addEventListener('click', function() {
    var table = document.getElementById('summarytotalTable');
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, "totalpoints.xlsx");
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

//SUmmary to PDF
// Export summary to PDF
document.getElementById('exportToPDFsummary').addEventListener('click', function() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF();

    var columns = [];
    var rows = [];

    var headers = document.querySelectorAll('#summaryTable thead th');
    headers.forEach(function(header) {
        columns.push(header.innerText);
    });

    var tableRows = document.querySelectorAll('#summaryTable tbody tr');
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

    doc.save("summaryofpoints.pdf");
});


// Export Totalpoints to PDF
document.getElementById('exportToPDFtotalsummary').addEventListener('click', function() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF();

    var columns = [];
    var rows = [];

    var headers = document.querySelectorAll('#summarytotalTable thead th');
    headers.forEach(function(header) {
        columns.push(header.innerText);
    });

    var tableRows = document.querySelectorAll('#summarytotalTable tbody tr');
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

    doc.save("totalpoints.pdf");
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
