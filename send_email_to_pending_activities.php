<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
include('db.php');

// Function to send email
function sendEmail($email, $username) {
    $subject = "Students Events Approval Pending";
    $message = "Dear $username,\n\nYou have pending event request from students. Kindly login to https://tistactivity.co.in/ and assign points.\n\nBest regards,\nAdmin Team";
    $headers = "From: admin@tistactivity.co.in";
    
    // Connect to the server's SMTP
        ini_set("SMTP", "mail.tistactivity.co.in");
        ini_set("smtp_port", "465");
        ini_set("sendmail_from", "sender@example.com");
        //$email='bibin001@gmail.com';
    return mail($email, $subject, $message, $headers);
}

// Logic to send emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch faculties with pending approval and admission_year matching student admission_year
    $query = "
        SELECT 
        faculty.name AS faculty_name,
        faculty.email AS faculty_email,
        COUNT(events.id) AS pending_count
        FROM 
            events
        JOIN 
            users AS students ON events.student_id = students.id
        JOIN 
            users AS faculty ON students.admission_year = faculty.admission_year AND students.division = faculty.division
        WHERE 
            faculty.usertype = 'faculty' 
            AND events.stat = 'Pending'
        GROUP BY 
            faculty.id;
    ";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $successCount = 0;
        $failCount = 0;
        $dupemail="";
        while ($row = mysqli_fetch_assoc($result)) {
            $email = $row['faculty_email'];
            $username = $row['faculty_name'];
            
            if($dupemail != $email)  {  
                $dupemail=$email;
                // Send email
                if (sendEmail($email, $username)) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
        }

        $_SESSION['email_status'] = "Emails sent successfully to $successCount faculties. Failed for $failCount faculties.";
    } else {
        $_SESSION['email_status'] = "No faculties found with pending approval and matching admission year.";
    }

    header("Location: admin_dashboard.php");
    exit();
}
?>
