<?php
session_start();
//file_put_contents('log.txt', print_r($_POST, true), FILE_APPEND);

require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}

// Check if an action is specified
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Approve event action
    if ($action == 'approve' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the event ID, points, and remarks from the POST request
        if (isset($_POST['event_id'], $_POST['points'], $_POST['remarks'])) {
            $event_id = (int)$_POST['event_id'];
            $points = (int)$_POST['points'];
            $remarks = $conn->real_escape_string($_POST['remarks']); // Sanitize the remarks

            // Update the event in the database
            $sql_update = "UPDATE events 
                           SET stat = 'approved', points = '$points', remarks = '$remarks'
                           WHERE id = '$event_id'";

            if ($conn->query($sql_update) === TRUE) {
                $_SESSION['success'] = "Event approved successfully.";
            } else {
                $_SESSION['error'] = "Error approving event: " . $conn->error;
            }

            // Redirect back to faculty dashboard
            header('Location: faculty_dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Invalid input data.";
            header('Location: faculty_dashboard.php');
            exit();
        }
    }

    // Delete event action
    elseif ($action == 'delete' && isset($_GET['event_id']) && isset($_GET['file_path'])) {
        $event_id = (int)$_GET['event_id'];
        $filePath=$_GET['file_path'];
        // Delete the event from the database
        $sql_delete = "DELETE FROM events WHERE id = '$event_id'";

        if ($conn->query($sql_delete) === TRUE) {
            if (file_exists($filePath)) {
                unlink($filePath); // This will delete the file from the server
            }
            $_SESSION['success'] = "Event deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting event: " . $conn->error;
        }

        // Redirect back to faculty dashboard
        header('Location: faculty_dashboard.php');
        exit();
    }

    // Edit event action
    elseif ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the event ID and updated values from the POST request
        if (isset($_POST['event_id'], $_POST['event_type'], $_POST['from_date'], $_POST['to_date'], $_POST['event_level'])) {
            $event_id = (int)$_POST['event_id'];
            $event_type = $conn->real_escape_string($_POST['event_type']); // Sanitize the event type
            $from_date = $conn->real_escape_string($_POST['from_date']); // Sanitize the from date
            $to_date = $conn->real_escape_string($_POST['to_date']); // Sanitize the to date
            $event_level = $conn->real_escape_string($_POST['event_level']); // Sanitize the event level

            // Update the event in the database
            $sql_edit = "UPDATE events 
                         SET event_type = '$event_type', from_date = '$from_date', to_date = '$to_date', event_level = '$event_level'
                         WHERE id = '$event_id'";

            if ($conn->query($sql_edit) === TRUE) {
                $_SESSION['success'] = "Event updated successfully.";
            } else {
                $_SESSION['error'] = "Error updating event: " . $conn->error;
            }

            // Redirect back to faculty dashboard
            header('Location: faculty_dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Invalid input data.";
            header('Location: faculty_dashboard.php');
            exit();
        }
    }

    // If action is unknown or not provided
    else {
        $_SESSION['error'] = "Invalid action.";
        header('Location: faculty_dashboard.php');
        exit();
    }
} else {
    // If no action is provided, redirect back to the dashboard
    $_SESSION['error'] = "No action specified.";
    header('Location: faculty_dashboard.php');
    exit();
}
