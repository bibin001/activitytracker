<?php
session_start();
require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || ($_SESSION['usertype'] !== 'hod'  && $_SESSION['usertype'] !== 'csadmin') ) {
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
        s.name, academic_year;
";

$result1 = $conn->query($sql1);

//Total points
$sql2 = "
    SELECT 
        s.name, 
        s.ktu_register_no,
        SUM(a.points) AS total_points,
        SUM(CASE WHEN a.category = 1 THEN a.points ELSE 0 END) AS category_1_points,
        SUM(CASE WHEN a.category = 2 THEN a.points ELSE 0 END) AS category_2_points,
        SUM(CASE WHEN a.category = 3 THEN a.points ELSE 0 END) AS category_3_points
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




// Fetch students who are pending approval (not approved)
$sql_pending = "
    SELECT u.id, u.name, u.email, u.admission_year, u.ktu_register_no, u.division, u.branch, u.approval 
    FROM users u 
    WHERE u.usertype = 'student' 
    AND u.branch = '$faculty_branch'
    AND u.approval != 'approved' order by u.ktu_register_no
";
$result_pending = $conn->query($sql_pending);

// Fetch students who are already approved
$sql_approved = "
    SELECT u.id, u.name, u.email, u.admission_year,u.ktu_register_no, u.division, u.branch, u.approval 
    FROM users u 
    WHERE u.usertype = 'student' 
    AND u.branch = '$faculty_branch'
    AND u.approval = 'approved'
    ORDER BY u.ktu_register_no
";
$result_approved = $conn->query($sql_approved);

// Fetch events for approved students
$sql_events = "
    SELECT e.id, e.event_type, e.from_date, e.to_date, e.event_level, e.category, e.pdf_file, e.remarks, e.stat, e.points,
           u.name AS student_name, u.ktu_register_no, u.admission_year
    FROM events e
    JOIN users u ON e.student_id = u.id
    WHERE u.approval = 'approved' 
    AND u.branch = '$faculty_branch' 
    AND u.usertype = 'student' order by 
    CASE 
        WHEN stat = 'Pending' THEN 1 
        ELSE 2 
    END, 
    u.ktu_register_no, 
    from_date
";
$result_events = $conn->query($sql_events);

//Total number of approved events
// Assuming you have already established a database connection in $conn

$sql_total_events = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
    ";
$result_total_events = $conn->query($sql_total_events);

if ($result_total_events) {
    $row4 = $result_total_events->fetch_assoc();
    $totalApprovedEvents = $row4['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    echo "Error: " . $conn->error;
}

//Total College level events
// Assuming you have already established a database connection in $conn

$sql_total_college = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
        AND events.event_level='Level I College Events'
    GROUP BY 
        users.branch, 
        users.division
    ";
$result_total_college = $conn->query($sql_total_college);

if ($result_total_college->num_rows > 0){
    $row5 = $result_total_college->fetch_assoc();
    $totalCollegeLevel = $row5['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    $totalCollegeLevel=0;
}

//Total Zonal level events
// Assuming you have already established a database connection in $conn

$sql_total_zonal = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
        AND events.event_level='Level II Zonal Events'
    GROUP BY 
        users.branch, 
        users.division
    ";
$result_total_zonal = $conn->query($sql_total_zonal);

if ($result_total_zonal->num_rows > 0){
    $row51 = $result_total_zonal->fetch_assoc();
    $totalZonalLevel = $row51['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    $totalZonalLevel=0;
}

//Total National level events
// Assuming you have already established a database connection in $conn

$sql_total_national = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
        AND events.event_level='Level IV National Events'
    GROUP BY 
        users.branch, 
        users.division
    ";
$result_total_national = $conn->query($sql_total_national);

if ($result_total_national->num_rows > 0){
    $row6 = $result_total_national->fetch_assoc();
    $totalNationalLevel = $row6['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    $totalNationalLevel=0;
}

//Total State/University level events
// Assuming you have already established a database connection in $conn

$sql_total_state = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
        AND events.event_level='Level III State/University Events'
    GROUP BY 
        users.branch, 
        users.division
    ";
$result_total_state = $conn->query($sql_total_state);

if ($result_total_state->num_rows > 0){
    $row60 = $result_total_state->fetch_assoc();
    $totalStateLevel = $row60['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    $totalStateLevel=0;
}


//Total International level events
// Assuming you have already established a database connection in $conn

$sql_total_inter = "
    SELECT 
        users.branch, 
        users.division, 
        COUNT(events.id) AS total_approved_events
    FROM 
        events
    JOIN 
        users ON events.student_id = users.id
    WHERE 
        events.stat = 'approved'
        AND users.branch = '$faculty_branch' 
        AND events.event_level='Level IV International Events'
    GROUP BY 
        users.branch, 
        users.division
    ";
$result_total_inter = $conn->query($sql_total_inter);

if ($result_total_inter->num_rows > 0){
    $row22 = $result_total_inter->fetch_assoc();
    $totalInternationalLevel = $row22['total_approved_events'];
    //echo "Total Approved Events: " . $totalApprovedEvents;
} else {
    $totalInternationalLevel=0;
}



// Check if there is an error query parameter in the URL upload faculty events
if (isset($_GET['error']) && $_GET['error'] === 'fileTooLarge') {
    echo '
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        The file is too large. Max file size is 300KB.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
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
        list-style: none; 
        padding: 0; 
        margin: 0; 
    }

    .sidebar li {
        margin-bottom: 10px; 
        border-radius: 5px; 
        background-color: #9F9999FF; 
        transition: background-color 0.3s; 
    }

    .sidebar li a {
        text-decoration: none; 
        color: #333; 
        display: block; 
        padding: 10px; 
    }
    /* Hover effect for <li> */
    .sidebar li a:hover {
        background-color: #007bff; /* Change background color on hover */
    }

    .sidebar li:hover {
        color: #BBEB4DFF; /* Change text color on hover */
    }
    
    

</style>
</head>
<body>
<!-- Navbar -->
<?php require 'navbar.php'; ?>
<!-- Navbar -->

<!-- Sidebar -->
<div class="sidebar">
    <h3>Activity Tracker</h3>
    
    <!-- Student Section -->
    <h4>Student Section </h4>
    <ul class="student-section">
        <li><a href="#" onclick="showSection('dashboardSection')">Dashboard</a></li> <!-- Dashboard menu --> 
        <li><a href="#" onclick="showSection('activitiesSection')">View Activities</a></li>
        <li><a href="#" onclick="showSection('summarySection')">Total Points Academic Year-wise</a></li>
        <li><a href="#" onclick="showSection('summaryTotalSection')">Summary of Total Points</a></li>
        <li><a href="#" onclick="showSection('studentListSection')">Manage Student</a></li>
    </ul>
    
   <!-- Faculty Section -->
     <h4>Faculty Section</h4>
    <ul class="faculty-section">
    <?php if($_SESSION['usertype'] == 'hod') { ?>
    <li><a href="#" onclick="showSection('facultyActivitiesSection')">View/Add your Activities</a></li>
    <?php } ?>
    <li><a href="all_faculty_events.php">All Faculty Activities</a></li>
</ul>

</div>



<!-- Main Content -->
<div class="content">



    <!-- Dashboard Section (shown on page load and when "Dashboard" is clicked) -->
     
    <div id="dashboardSection" class="section active-section">
   
                <?php $a=1; ?>
                <?php if ($result_pending->num_rows > 0): ?>
                    <h3>Pending Student Approvals</h3>

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
                                
                            </tr>
                        </thead>
                        <tbody>
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
                            
                        </tr>
                        <?php $a++; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                       <!-- <td colspan="8">No students pending approval.</td> -->
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="container">
            <div class="row">
              <div class="col-md-3"> <!-- First column for the first card -->

                    <div class="card text-white bg-danger mb-3" style="max-width: 14rem;min-width: 14rem;">
                        <div class="card-header text-center" style="color: #E9F547FF;"><h4>Total Activities</h4></div>
                        <div class="card-body text-center">
                            <h1 class="card-title"><?php echo $totalApprovedEvents;?></h1>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

                <div class="col-md-3"> <!-- Second column for the second card -->
                    <div class="card text-white mb-3" style="max-width: 14rem;min-width: 14rem;max-height: 8rem;min-height: 8rem; background-color: #221470FF;">
                        <div class="card-header text-center" style="color: #E5DE5AFF;"><h5>College Level</h5></div>
                        <div class="card-body text-center">
                            <h2 class="card-title"><?php echo $totalCollegeLevel;?></h2>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

                <div class="col-md-3"> <!-- Third column for the third card -->
                    <div class="card text-white bg-success mb-3" style="max-width: 14rem;min-width: 14rem;max-height: 8rem;min-height: 8rem; ">
                        <div class="card-header text-center" style="color: #EBE669FF;"><h5>Zonal Level</h5></div>
                        <div class="card-body text-center">
                            <h2 class="card-title"><?php echo $totalZonalLevel;?></h2>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

               

            </div>
        </div>

        <div class="container">
            <div class="row">


            <div class="col-md-3"> <!-- Fourth column for the fourth card -->
                    <div class="card text-white bg-dark mb-3" style="max-width: 14rem;min-width: 14rem;max-height: 8rem;min-height: 8rem; ">
                        <div class="card-header text-center" style="color: #E9F547FF;"><h6>State/University Level</h6></div>
                        <div class="card-body text-center">
                            <h2 class="card-title"><?php echo $totalStateLevel;?></h2>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

              <div class="col-md-3"> <!-- First column for the first card -->
              <div class="card text-white mb-3" style="max-width: 14rem;min-width: 14rem;max-height: 8rem;min-height: 8rem; background-color: #5E5510FF;">
                        <div class="card-header text-center" style="color: #DAE37FFF;"><h5>National Level</h5></div>
                        <div class="card-body text-center">
                            <h2 class="card-title"><?php echo $totalNationalLevel;?></h2>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

                <div class="col-md-3"> <!-- Second column for the second card -->
                    <div class="card text-white mb-3" style="max-width: 14rem;min-width: 1rem;max-height: 8rem;min-height: 8rem; background-color: #5E1033FF;">
                        <div class="card-header text-center" style="color: #C5C02DFF;"><h6>International Level</h6></div>
                        <div class="card-body text-center">
                            <h2 class="card-title"><?php echo $totalInternationalLevel;?></h2>
                            <!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
                        </div>
                    </div>
                </div>

                
               
            </div>
        </div>






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
                                <td>" . htmlspecialchars($row1['academic_year']) ."-".htmlspecialchars($row1['academic_year']+1). "</td>
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
                        <th>Category 1</th>
                        <th>Category 2</th>
                        <th>Category 3</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                                                                                    if($row2['category'] == 0){
                                                                                            $catg="NA";
                                                                                          }
                                                                                        if($row2['category'] != 0){
                                                                                            $catg=$row2['category'];
                                                                                        }
                            
                            echo "<tr>
                                <td>" . htmlspecialchars($row2['name']) . "</td>
                                <td>" . htmlspecialchars($row2['ktu_register_no']) . "</td>
                                <td>" . htmlspecialchars($row2['category_1_points']) . "</td>
                                <td>" . htmlspecialchars($row2['category_2_points']) . "</td>
                                <td>" . htmlspecialchars($row2['category_3_points']) . "</td>
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
                        <!-- Reset Password Modal -->
                    <div class="modal fade" id="resetPasswordModal<?= $row['id']; ?>" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password for <?php echo htmlspecialchars($row['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="reset_password.php">
                                        <input type="hidden" name="student_id" value="<?= $row['id']; ?>">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Reset Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <div class="col-md-2">
        <input type="text" id="searchStudentName" class="form-control" placeholder="Student Name">
    </div>
    <div class="col-md-2">
        <input type="text" id="searchKTU" class="form-control" placeholder="KTU Register No.">
    </div>
    <div class="col-md-2">
                            <?php
                        // Define an array of event types
                        $eventTypes = [
                            "NCC",
                            "NSS",
                            "Sports",
                            "Games",
                            "Music",
                            "MOOC",
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
        <select class="form-select" id="searchEventType" class="form-select">
             <option value="">Event Type</option>
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
                     <option value="2024">2024</option>
                    <!-- Add more years as necessary -->
                </select>
            </div>
    
     <div class="col-md-3">
        
        <select id="searchCategory" class="form-select">
            <option value="">Select Category</option>
            <option value="1">Category 1</option>
            <option value="2">Category 2</option>
            <option value="3">Category 3</option>
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



        <h2>View Activities
            <!--<button class="btn btn-primary" id="downloadAllProofs">Download All Proofs</button>-->
            <button class="btn btn-primary" id="downloadFilteredProofs">Download Visible Events Proofs</button>
            <button class="btn btn-info" id="activityPointsGuide" data-bs-toggle="modal" data-bs-target="#activityPointsModal">Activity Points Guide
        </h2>
            <!-- Modal for Activity Points Guide -->
            <div class="modal fade" id="activityPointsModal" tabindex="-1" aria-labelledby="activityPointsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="activityPointsModalLabel">Activity Points Guide</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="height: 75vh; overflow-y: scroll;">
                            <!-- Embed the PDF here -->
                            <embed src="images/AP.pdf" type="application/pdf" width="100%" height="100%" />
                        </div>
                    </div>
                </div>
            </div>

        <table id="activitiesTable" class="table table-responsive table-sm table-hover table-bordered">
            <thead>
                <tr class="table-info">
                    <th>#</th>
                    <th>Student Name</th>
                    <th>KTU Register No.</th>
                     <th>Admission Year</th>
                    <th>Event Type</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Event Level</th>
                    <th>Category</th>
                    <th>Proof</th>
                    <th>Points</th>
                    <th>Remarks</th>
                    
                </tr>
            </thead>
            <tbody  id="activitiesTableBody">
                <?php if ($result_events->num_rows > 0): ?>
                    <?php $a=1; ?>
                    <?php while ($row = $result_events->fetch_assoc()): ?>
                                    <tr data-student-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                        data-ktu-register-no="<?php echo htmlspecialchars($row['ktu_register_no']); ?>"
                                        data-admission-year="<?php echo htmlspecialchars($row['admission_year']); ?>"
                                        data-event-type="<?php echo htmlspecialchars($row['event_type']); ?>"
                                        data-from-date="<?php echo htmlspecialchars($row['from_date']); ?>"
                                        data-to-date="<?php echo htmlspecialchars($row['to_date']); ?>"
                                        data-event-level="<?php echo htmlspecialchars($row['event_level']); ?>"
                                        data-category="<?php echo htmlspecialchars($row['category']); ?>"
                                        >
                                        <td><?php echo $a; ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ktu_register_no']); ?></td>
                                      <td><?php echo htmlspecialchars($row['admission_year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['to_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_level']); ?></td>
                                        <?php if($row['category'] == 0){
                                                $catgy="NA";
                                              }
                                            if($row['category'] != 0){
                                                $catgy=$row['category'];
                                            }
                                            ?>
                                        <td><?php echo $catgy; ?></td>
                                        
                                        <!-- PDF Thumbnail (if available) -->
                                        <td><a href="<?php echo $row['pdf_file']; ?>" target="_blank">View</a></td>

                                        <td><?php echo htmlspecialchars($row['points']); ?></td>
                                         <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                                        
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


    <!-- Faculty Activity Section -->
    <?php  
    $sqlw = "SELECT event_type, from_date, to_date, total_days, proof FROM faculty_events WHERE faculty_id = ?";
    $stmt = $conn->prepare($sqlw);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $resultss = $stmt->get_result();
    ?>

    <div id="facultyActivitiesSection" class="section">
        <h2>Faculty Activities</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyActivityModal">Add Activity</button>
        <!-- Table to display faculty activities -->
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Sl. No</th>
                    <th>Event Type</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Total Days</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Initialize a counter for serial number
                $counter = 1;
                while ($rowd = $resultss->fetch_assoc()) {
                    // Get the proof file path
                    $proofLink = $rowd['proof'];
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($rowd['event_type']); ?></td>
                        <td><?php echo htmlspecialchars($rowd['from_date']); ?></td>
                        <td><?php echo htmlspecialchars($rowd['to_date']); ?></td>
                        <td><?php echo htmlspecialchars($rowd['total_days']); ?></td>
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
        $stmt->close();
        $conn->close();
        ?>
    </div>




</div>






<!--Faculty Activity Entry Modal -->
<!-- Modal -->
<div class="modal fade" id="addFacultyActivityModal" tabindex="-1" aria-labelledby="facultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <form id="activityForm" method="POST" action="process_faculty_events.php?action=approves" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="facultyModalLabel">Add Faculty Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="faculty_modal_event_id" value="">

                    <!-- Type of Event Dropdown -->
                    <div class="form-group">
                        <label for="eventType">Type of Event</label>
                        <select class="form-control" name="eventType" id="eventType" required>
                            <option value="" disabled selected>Select Event Type</option>
                            <option value="FDP">FDP</option>
                            <option value="STTP">STTP</option>
                            <option value="Workshop">Workshop</option>
                            <option value="MOOC">MOOC</option>
                            <option value="Resource Person">Resource Person</option>
                            <option value="Journal Paper Publication (UGC)">Journal Paper Publication (UGC)</option>
                            <option value="Journal Paper Publication (SCOPUS)">Journal Paper Publication (SCOPUS)</option>
                            <option value="Journal Paper Publication (SCIE)">Journal Paper Publication (SCIE)</option>
                            <option value="Journal Paper Publication (SCI)">Journal Paper Publication (SCI)</option>
                            <option value="Conference Paper Publication (National)">Conference Paper Publication (National)</option>
                            <option value="Conference Paper Publication (International)">Conference Paper Publication (International)</option>
                            <option value="Book Chapter">Book Chapter</option>
                            <option value="Patent">Patent</option>
                            <option value="Projects">Projects</option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="form-group">
                        <label for="fromDate">From Date</label>
                        <input type="date" class="form-control" name="fromDate" id="fromDate" >
                    </div>

                    <!-- To Date -->
                    <div class="form-group">
                        <label for="toDate">To Date</label>
                        <input type="date" class="form-control" name="toDate" id="toDate" >
                    </div>

                    <!-- Total Number of Days -->
                    <div class="form-group">
                        <label for="totalDays">Total Number of Days</label>
                        <input type="number" class="form-control" name="totalDays" id="totalDays" min="1" >
                    </div>

                    <!-- Proof Upload -->
                    <div class="form-group">
                        <label for="proof">Proof (PDF only, max 300KB)</label>
                        <input type="file" class="form-control" name="proof" id="proof" accept="application/pdf" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>




<!-- END of Faculty Activity Entry Modal-->




<!-- Points Modal -->
<div class="modal fade" id="pointsModal" tabindex="-1" aria-labelledby="pointsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="pointsForm" method="POST" action="process_events.php?action=approval">
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
                                    <option value="MOOC">MOOC</option>
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
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select id="category" name="category" class="form-select">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                     <option value="0">Select this if NA</option>
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
    var category = document.getElementById('searchCategory').value;
    var admissionYear = document.getElementById('searchAdmissionYear').value;

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
        var categoryValue = row.getAttribute('data-category');
        var admissionYearValue = row.getAttribute('data-admission-year');

        var showRow = true;

        // Filter based on search criteria
        if (studentName && !studentNameValue.includes(studentName)) showRow = false;
        if (ktuRegisterNo && !ktuRegisterNoValue.includes(ktuRegisterNo)) showRow = false;
        if (eventType && eventType !== eventTypeValue) showRow = false;
        if (fromDate && fromDate > fromDateValue) showRow = false; // Check if event's start date is before the filter fromDate
        if (toDate && toDate < toDateValue) showRow = false; // Check if event's end date is after the filter toDate
        if (eventLevel && eventLevel !== eventLevelValue) showRow = false;
        if (category && category !== categoryValue) showRow = false;
        if (admissionYear && admissionYear !== admissionYearValue) showRow = false;

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
document.getElementById('searchCategory').addEventListener('change', filterTable);
document.getElementById('searchAdmissionYear').addEventListener('change', filterTable);



</script>

<script>
    // Open Edit Modal and populate data
    function openEditModal(eventId, eventType, fromDate, toDate, eventLevel, category) {
    document.getElementById('edit_event_id').value = eventId; // Check this ID
    document.getElementById('from_date').value = fromDate; // Check this ID
    document.getElementById('to_date').value = toDate; // Check this ID

    // Set the selected values for event type and event level
    const eventTypeSelect = document.getElementById('event_type'); // Check this ID
    const eventLevelSelect = document.getElementById('event_level'); // Check this ID
    
    document.getElementById('category').value = category;

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
<!-- Bootstrap Bundle with Popper.js -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
// Export filtered table to Excel
document.getElementById('exportToExcel').addEventListener('click', function() {
    var table = document.getElementById('activitiesTable');
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, "activities.xlsx");
});

//Summary of points 
//document.getElementById('exportToExcelsummary').addEventListener('click', function() {
  //  var table = document.getElementById('summaryTable');
    //var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    //XLSX.writeFile(wb, "summaryactivities.xlsx");
//});

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

    //Function to show the activities section based on URL parameters
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        
        if (section === 'facultyActivitiesSection') {
            showSection('facultyActivitiesSection'); // Call your existing function to show the activities section
        }
        if (section === 'activities') {
            showSection('activitiesSection'); // Call your existing function to show the activities section
        }

        if (section === 'deletion') {
            showSection('activitiesSection'); // Call your existing function to show the activities section
        }
        if (section === 'edition') {
            showSection('activitiesSection'); // Call your existing function to show the activities section
        }

    }



</script>




<script>
document.getElementById('downloadAllProofs').addEventListener('click', function() {
    window.location.href = 'download_all_proofs.php';
});
</script>


<script>
document.getElementById('downloadFilteredProofs').addEventListener('click', function() {
    let visibleRows = document.querySelectorAll('#activitiesTableBody tr:not([style*="display: none"])'); // Only visible rows
    let filesToDownload = [];

    visibleRows.forEach(function(row) {
        let fileLink = row.querySelector('td a').getAttribute('href'); // Get the file link from the 'Proof' column
        let slNo = row.querySelector('td:nth-child(1)').innerText; // Serial number
        let ktuRegisterNo = row.querySelector('td:nth-child(3)').innerText; // KTU Register No.
        let eventType = row.querySelector('td:nth-child(4)').innerText; // Event Type
        let fromDate = row.querySelector('td:nth-child(5)').innerText; // From Date

        if (fileLink) {
            filesToDownload.push({
                file: fileLink,
                name: `${slNo}_${ktuRegisterNo}_${eventType}_${fromDate}.pdf`
            });
        }
    });

    if (filesToDownload.length > 0) {
        // Send the files info to the server via POST (using fetch API)
        fetch('download_filtered_proofs.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({files: filesToDownload})
        })
        .then(response => response.blob()) // Get the ZIP file as a blob
        .then(blob => {
            // Download the ZIP file
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'filtered_proofs.zip'; // Name of the ZIP file
            link.click();
        })
        .catch(error => {
            console.error('Error downloading files:', error);
        });
    } else {
        alert('No proofs available to download.');
    }
});
</script>

<script>
  // Function to show the notification
  function showNotification() {
    const notification = document.getElementById('importantNotification');
    notification.style.display = 'block';
  }

  // Function to close the notification
  function closeNotification() {
    const notification = document.getElementById('importantNotification');
    notification.style.display = 'none';
  }

  // Show the notification after 2 seconds
  window.onload = function() {
    setTimeout(showNotification, 1000);
  };
</script>


</body>
</html>
