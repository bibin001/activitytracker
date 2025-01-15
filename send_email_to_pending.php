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
    $subject = "Pending Approval Notification";
    $message = "Dear $username,\n\nYou have pending request for student approval. Kindly login to https://tistactivity.co.in/ and approve the same.\n\nBest regards,\nAdmin Team";
    $headers = "From: admin@tistactivity.co.in";
    
    // Connect to the server's SMTP
        ini_set("SMTP", "mail.tistactivity.co.in");
        ini_set("smtp_port", "465");
        ini_set("sendmail_from", "sender@example.com");
    return mail($email, $subject, $message, $headers);
}

// Logic to send emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch faculties with pending approval and admission_year matching student admission_year
    $query = "
        SELECT faculty.email AS faculty_email, faculty.name AS faculty_username 
        FROM users AS faculty
        INNER JOIN users AS students 
        ON faculty.admission_year = students.admission_year 
        WHERE faculty.usertype = 'faculty' 
        AND students.usertype = 'student'
        AND students.approval = 'pending'
    ";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $successCount = 0;
        $failCount = 0;
        $dupemail="";
        while ($row = mysqli_fetch_assoc($result)) {
            $email = $row['faculty_email'];
            $username = $row['faculty_username'];
            
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
