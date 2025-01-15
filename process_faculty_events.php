<?php
session_start();
require 'db.php'; // Contains database connection

// Check if the user is logged in and has the 'faculty' role
//if (isset($_SESSION['id']) || $_SESSION['usertype'] == 'faculty') {
    //echo $_SESSION['usertype'];
    //echo $_SESSION['id'];
    //header('Location: index.php');
    //exit();
//}

// Check if the request is a POST and the action is approve
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'approves') {

    // Retrieve the faculty ID from the session
    $faculty_id = $_SESSION['id'];

    // Get the form input values and validate them
    $event_type = isset($_POST['eventType']) ? trim($_POST['eventType']) : '';
    $from_date = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    $to_date = isset($_POST['toDate']) ? $_POST['toDate'] : '';
    $total_days = isset($_POST['totalDays']) ? (int)$_POST['totalDays'] : 0;

    // Validate input data
    if (empty($event_type)) {
        die('Please select the event type');
    }
      // Handle the file upload (proof PDF)
      $target_dir = "faculty_uploads/"; // Directory where files will be saved
      $fileType = strtolower(pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION));
  
      // Check if file is a PDF
      if ($fileType != "pdf") {
          die("Only PDF files are allowed.");
      }
  



    //$target_file = $target_dir . basename($_FILES["proof"]["name"]);
    // Generate a random number between 1000 and 9999
    $randomNumber = rand(100, 999999999);

    // Construct the target file name
    $target_file = $target_dir . $faculty_id . '_' . $event_type . '_' . $randomNumber . '.'. $fileType;
    
    // If file passes validation, move it to the server
    if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
        // File successfully uploaded
    } else {
        die("Sorry, there was an error uploading your file.");
    }

    // Check if file is a PDF
    if ($fileType != "pdf") {
        $uploadOk = 0;
        die("Only PDF files are allowed.");
    }

    // Check file size (limit to 300KB)
    if ($_FILES["proof"]["size"] > 300000) {
        header('Location: hod_dashboard.php?section=facultyActivitiesSection');
        exit();
    }


    // Store the data in the database
    $stmt = $conn->prepare("INSERT INTO faculty_events (faculty_id, event_type, from_date, to_date, total_days, proof) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $faculty_id, $event_type, $from_date, $to_date, $total_days, $target_file);

    if ($stmt->execute()) {
        // Redirect or provide success feedback
        header('Location: faculty_dashboard.php?section=facultyActivitiesSection');
        exit();
    } else {
        die("Error inserting data: " . $conn->error);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Redirect if the request is invalid
    header('Location: faculty_dashboard.php');
    exit();
}
?>
