<?php
session_start();
require 'db.php'; // Include your database connection file

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php'); // Redirect if not logged in
    exit();
}

// Check if action is set
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'approve' && isset($_GET['student_id'])) {
        $studentId = (int)$_GET['student_id']; // Get the student ID and ensure it's an integer

        // Update the approval status in the database
        $stmt = $conn->prepare("UPDATE users SET approval = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $studentId);

        if ($stmt->execute()) {
            // Redirect back to faculty_dashboard.php with a success message
            header('Location: faculty_dashboard.php?message=Student approved successfully.');
            exit();
        } else {
            // Redirect back with an error message
            header('Location: faculty_dashboard.php?error=Error updating approval status.');
            exit();
        }
    } elseif ($action === 'delete' && isset($_GET['student_id'])) {
        $studentId = (int)$_GET['student_id'];

        // Prepare and execute delete statement
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $studentId);

        if ($stmt->execute()) {
            // Redirect back with a success message
            header('Location: faculty_dashboard.php?message=Student deleted successfully.');
            exit();
        } else {
            // Redirect back with an error message
            header('Location: faculty_dashboard.php?error=Error deleting student.');
            exit();
        }
    } else {
        header('Location: faculty_dashboard.php?error=Invalid action.');
        exit();
    }
} else {
    header('Location: faculty_dashboard.php?error=No action specified.');
    exit();
}
