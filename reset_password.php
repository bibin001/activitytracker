<?php
session_start();
include('db.php'); // Include your database connection file

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the student ID and new password from the POST request
    $student_id = intval($_POST['student_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the new password and confirmation
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please fill in both password fields.";
        header("Location: faculty_dashboard.php"); // Redirect back to the faculty dashboard
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: faculty_dashboard.php"); // Redirect back to the faculty dashboard
        exit();
    }

    // Password validation: at least 6 characters, can be customized
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
        header("Location: faculty_dashboard.php"); // Redirect back to the faculty dashboard
        exit();
    }

    // Hash the new password for security
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Prepare an SQL statement to update the user's password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $student_id);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        $_SESSION['success'] = "Password has been successfully reset.";
    } else {
        $_SESSION['error'] = "Error resetting password. Please try again.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the faculty dashboard
    header("Location: faculty_dashboard.php");
    exit();
} else {
    // Redirect if the script was accessed without a POST request
    header("Location: faculty_dashboard.php");
    exit();
}
?>
