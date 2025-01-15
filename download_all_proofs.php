<?php
session_start();
require 'db.php'; // Include database connection

// Check if the user is logged in and has the 'faculty' role
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}
$faculty_branch = $_SESSION['branch'];
$faculty_division = $_SESSION['division'];

// Query to get the events data along with the corresponding KTU Register No from the users table
$sql = "
    SELECT 
        events.id, 
        users.ktu_register_no, 
        events.event_type, 
        events.from_date, 
        events.pdf_file
    FROM 
        events
    JOIN 
        users 
    ON 
        events.student_id = users.id
    WHERE users.approval = 'approved' 
    AND users.branch = '$faculty_branch' 
    AND users.division = '$faculty_division'
    AND events.stat != 'Pending'
    ORDER BY
    users.ktu_register_no,
    events.from_date
";

// Execute the query
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Create a ZIP file
    $zip = new ZipArchive();
    $zipFileName = 'proof_files_' . date('Y-m-d_H-i-s') . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        die("Could not open archive");
    }

    $slno = 1;
    // Loop through each event
    while ($row = $result->fetch_assoc()) {
        $ktu_register_no = $row['ktu_register_no'];
        $event_type = $row['event_type'];
        $from_date = $row['from_date'];
        $pdf_file = $row['pdf_file'];

        // Format the filename using slno, ktu_register_no, event_type, and from_date
        $formattedFileName = "{$slno}_{$ktu_register_no}_{$event_type}_{$from_date}.pdf";

        // Add the proof file to the ZIP (only if the file exists)
        if (file_exists($pdf_file)) {
            $zip->addFile($pdf_file, $formattedFileName);
        }

        $slno++; // Increment the serial number
    }

    // Close the ZIP file
    $zip->close();

    // Serve the file for download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . $zipFileName);
    header('Content-Length: ' . filesize($zipFilePath));
    readfile($zipFilePath);

    // Delete the temporary ZIP file after download
    unlink($zipFilePath);
    exit();
} else {
    echo "No proof files found.";
}
?>
