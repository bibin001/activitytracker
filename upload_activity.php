<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
include('db.php');

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['id']; // Get the logged-in student ID
    $event_type = $_POST['event_type']; // Get event type from form
    $from_date = $_POST['from_date']; // Get from date from form
    $to_date = $_POST['to_date']; // Get to date from form
    $event_level = $_POST['event_level']; // Get event level from form
    $status = "Pending"; // Set status to pending

    // File upload handling
    $uploaded_file = $_FILES['pdf_file'];
    $file_name = $uploaded_file['name'];
    $file_tmp = $uploaded_file['tmp_name'];
    $file_size = $uploaded_file['size'];
    $file_error = $uploaded_file['error'];
    
    // Allowed file extensions
    $allowed_extensions = ['pdf', 'jpg', 'jpeg'];

    // Get the file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Check if the file extension is valid and file size is below 80KB
    if (in_array($file_ext, $allowed_extensions) && $file_size <= 81920) { // 81920 bytes = 80KB
        if ($file_error === UPLOAD_ERR_OK) {
            // Move the uploaded file to a designated folder
            $target_directory = "uploads/";
            if (!is_dir($target_directory)) {
                mkdir($target_directory); // Create uploads directory if it doesn't exist
            }
            $target_file = $target_directory . basename($file_name);

            // Move the uploaded file
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Prepare SQL statement to insert activity details into database
                $sql = "INSERT INTO events (student_id, event_type, event_level, from_date, to_date, pdf_file, stat) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssss", $student_id, $event_type, $event_level, $from_date, $to_date, $target_file, $status);

                // Execute the prepared statement
                if ($stmt->execute()) {
                    // Redirect back to the dashboard with success message
                    header("Location: studentdashboard.php?upload_success=1");
                } else {
                    // Handle database insertion error
                    header("Location: studentdashboard.php?upload_error=1");
                }

                $stmt->close(); // Close the prepared statement
            } else {
                // Handle file move error
                header("Location: studentdashboard.php?upload_error=1");
            }
        } else {
            // Handle upload error
            header("Location: studentdashboard.php?upload_error=1");
        }
    } else {
        // Handle invalid file type or size exceeding 80KB
        if ($file_size > 81920) {
            header("Location: studentdashboard.php?file_size_error=1");
        } else {
            header("Location: studentdashboard.php?file_error=1");
        }
    }
} else {
    // Redirect if the request method is not POST
    header("Location: studentdashboard.php");
}

$conn->close(); // Close the database connection
?>
