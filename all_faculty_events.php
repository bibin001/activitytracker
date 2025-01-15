<?php
session_start();
require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || ($_SESSION['usertype'] !== 'hod' && $_SESSION['usertype'] !== 'csadmin')) {
    header('Location: index.php'); // Redirect to login if not authorized
    exit();
}

$faculty_id=$_SESSION['id'];

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
            top: auto;
            width: 250px;
            background-color: #450A10FF;
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
        .logo {
            width: auto; /* Make the logo fill the width of the container */
            height: auto; /* Maintain aspect ratio */
            margin-bottom: auto; /* Space below the logo */
        }

       

    </style>

<!-- CSS Styles -->
<style>
    .sidebar {
        width: 250px; /* Adjust width as needed */
        background-color: #f8f9fa; /* Light background for sidebar */
        padding: 15px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1); /* Optional shadow for depth */
    }

    .sidebar h3 {
        background-color: #007bff; /* Change to desired color */
        color: white; /* Text color */
        padding: 10px; /* Space around text */
        border-radius: 5px; /* Optional: rounded corners */
        margin: 0 0 15px; /* Margin below header */
    }

    .sidebar ul {
        list-style: none; /* Remove default list style */
        padding: 0; /* Remove padding */
        margin: 0; /* Remove margin */
    }

    .sidebar li {
        margin-bottom: 10px; /* Space between list items */
        border-radius: 5px; /* Optional: rounded corners */
        background-color: #e9ecef; /* Change to desired color */
        transition: background-color 0.3s; /* Smooth color transition */
    }

    .sidebar li a {
        text-decoration: none; /* Remove underline from links */
        color: #333; /* Text color */
        display: block; /* Makes the entire list item clickable */
        padding: 10px; /* Space around link */
    }

    .sidebar li:hover {
        background-color: #007bff; /* Color on hover */
    }

    .sidebar li:hover a {
        color: #5942EEFF; /* Change text color on hover */
    }
</style>
<body>

<!-- Navbar -->
<?php require 'navbar.php'; ?>

<!-- Sidebar -->
<div class="sidebar">
<h3>Activity Tracker</h3>
    <ul>
        <li><a href="hod_dashboard.php" >Dashboard</a></li> <!-- Dashboard menu -->
        

</ul>
</div>

<!-- Main Content -->
<div class="content">
<?php 
     $sq = "SELECT u.name AS faculty_name, fe.event_type, fe.from_date, fe.to_date, fe.total_days, fe.proof 
     FROM faculty_events fe 
     JOIN users u ON fe.faculty_id = u.id 
     ORDER BY fe.faculty_id";

  
  $st = $conn->prepare($sq);
  
  $st->execute();
  $resu = $st->get_result();
    ?>
    <div id="allFacultyActivitySection" >

    <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" id="searchStudentName" class="form-control" placeholder="Search by Faculty Name">
                    </div>
                    
                    <div class="col-md-2">
                        <?php
                        // Define an array of event types
                        $eventTypes = [
                            "FDP", "STTP", "Workshop", "MOOC", "Resource Person", "Journal Paper Publication (UGC)", "Journal Paper Publication (SCOPUS)",
                            "Journal Paper Publication (SCIE)", "Journal Paper Publication (SCI)", "Conference Paper Publication (National)", "Conference Paper Publication (International)", "Book Chapter",
                            "Patent", "Projects"
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
                    <select id="searchDivision" class="form-select">
                        <option value="">Total Number of Days</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                        <option value="6">6</option><option value="7">7</option><option value="8">8</option>
                        <option value="9">9</option><option value="10">10</option><option value="11">11</option>
                        <option value="12">12</option><option value="13">13</option><option value="14">14</option>
                        <option value="15">15</option><option value="16">16</option><option value="17">17</option>
                        <option value="18">18</option><option value="19">19</option><option value="20">20</option>
                        <option value="21">21</option><option value="22">22</option><option value="23">23</option>
                        <option value="24">24</option><option value="25">25</option><option value="26">26</option>
                        <option value="27">27</option><option value="28">28</option><option value="29">29</option>
                        <option value="30">30</option>
                        <!-- Add more divisions as necessary -->
                    </select>
                </div>
                    <div class="col-md-6">
                    <button class="btn btn-success" id="exportToExcel">Export to Excel</button>
                    <button class="btn btn-danger" id="exportToPDF">Export to PDF</button>
                    </div>
                </div>

            <h2>Faculty Activities
            <button class="btn btn-primary" id="downloadAllProofs">Download All Proofs</button>
                    <button class="btn btn-primary" id="downloadFilteredProofs">Download Visible Events Proofs</button>
            
            </h2>
                
                
            <table id="activitiesTable" class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Sl. No</th>
                            <th>Name</th>
                            <th>Event Type</th>
                            <th>From Date</th>
                            <th>To Date</th>
                            <th>Total Days</th>
                            <th>Proof</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTableBody">
                        <?php
                        // Initialize a counter for serial number
                        $counter = 1;
                        while ($ro = $resu->fetch_assoc()) {
                            // Get the proof file path
                            $proofLink = $ro['proof'];
                            ?>
                            <tr data-student-name="<?php echo htmlspecialchars($ro['faculty_name']); ?>"
                                data-event-type="<?php echo htmlspecialchars($ro['event_type']); ?>"
                                data-from-date="<?php echo htmlspecialchars($ro['from_date']); ?>"
                                data-to-date="<?php echo htmlspecialchars($ro['to_date']); ?>"
                                data-total-days="<?php echo htmlspecialchars($ro['total_days']); ?>">
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($ro['faculty_name']); ?></td>
                                <td><?php echo htmlspecialchars($ro['event_type']); ?></td>
                                <td><?php echo htmlspecialchars($ro['from_date']); ?></td>
                                <td><?php echo htmlspecialchars($ro['to_date']); ?></td>
                                <td><?php echo htmlspecialchars($ro['total_days']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($proofLink); ?>" target="_blank">Download</a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php 
                $st->close();
               
                ?>

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




    <script>
// Function to filter the activities table
// Function to filter the activities table
function filterTable() {
    var studentName = document.getElementById('searchStudentName').value.toLowerCase();
    var eventType = document.getElementById('searchEventType').value;
    var fromDate = document.getElementById('searchFromDate').value;
    var toDate = document.getElementById('searchToDate').value;
    var academicYear = document.getElementById('searchAY').value;
    var totalDays = document.getElementById('searchDivision').value; // New filter

    // Automatically adjust fromDate and toDate based on selected academic year
    if (academicYear) {
        let startYear = parseInt(academicYear);
        let endYear = startYear + 1;
        fromDate = `${startYear}-07-01`; // From 1st July of the selected start year
        toDate = `${endYear}-06-30`; // To 30th June of the next year
    }

    const rows = document.querySelectorAll('#activitiesTableBody tr');

    rows.forEach(row => {
        const studentNameCell = row.getAttribute('data-student-name').toLowerCase();
        const eventTypeCell = row.getAttribute('data-event-type');
        const fromDateCell = row.getAttribute('data-from-date');
        const toDateCell = row.getAttribute('data-to-date');
        const totalDaysCell = row.getAttribute('data-total-days');

        const isStudentNameMatch = studentNameCell.includes(studentName);
        const isEventTypeMatch = eventType === '' || eventTypeCell === eventType;
        const isFromDateMatch = fromDate === '' || fromDateCell >= fromDate;
        const isToDateMatch = toDate === '' || toDateCell <= toDate;
        const isTotalDaysMatch = totalDays === '' || totalDaysCell === totalDays;

        if (isStudentNameMatch && isEventTypeMatch && isFromDateMatch && isToDateMatch && isTotalDaysMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Add event listeners to all filter inputs
document.getElementById('searchStudentName').addEventListener('input', filterTable);
document.getElementById('searchEventType').addEventListener('change', filterTable);
document.getElementById('searchFromDate').addEventListener('change', filterTable);
document.getElementById('searchToDate').addEventListener('change', filterTable);
document.getElementById('searchAY').addEventListener('change', filterTable);
document.getElementById('searchDivision').addEventListener('change', filterTable);
</script>


<!-- Bootstrap and jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!--PDF and EXCEL EXPORT -->
<!-- Include required JS libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>

<!--PDF and EXCEL EXPORT -->
<script>
// Export filtered table to Excel
document.getElementById('exportToExcel').addEventListener('click', function() {
    var table = document.getElementById('activitiesTable'); // Correct table ID
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, "faculty_activities.xlsx"); // File name adjusted
});

// Export filtered table to PDF
document.getElementById('exportToPDF').addEventListener('click', function() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF();

    var columns = [];
    var rows = [];

    // Get the header row content
    var headers = document.querySelectorAll('#activitiesTable thead th');
    headers.forEach(function(header) {
        columns.push(header.innerText);
    });

    // Get the visible rows from the table body
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

    // Use autoTable to export rows
    doc.autoTable({
        head: [columns],
        body: rows,
        startY: 10
    });

    doc.save("faculty_activities.pdf"); // File name adjusted
});

</script>






</body>
</html>
