<?php
session_start();
require 'db.php'; // Include your database connection file

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $eventId = $_POST['event_id'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $points = isset($_POST['points']) ? $_POST['points'] : 0;

    if ($action === 'approve') {
        // Update the event status to approved
        $sql = "UPDATE events SET status = 'approved', points = ?, remarks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $points, $remarks, $eventId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Event approved successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error approving event.']);
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        // Update the event status to rejected
        $sql = "UPDATE events SET status = 'rejected', remarks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $remarks, $eventId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Event rejected successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error rejecting event.']);
        }
        $stmt->close();
    }
}

// Close the database connection
$conn->close();
?>
