<?php
// Include database connection
include('db.php'); // Ensure this file contains your database connection

if (isset($_POST['submit'])) {
    $forgotEmail = $_POST['forgotEmail'];

    // Check if the email exists in the database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $forgotEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate a password reset token
        $token = bin2hex(random_bytes(50)); // Generate a random token
        $expires = date("U") + 3600; // Set expiration time to 1 hour

        // Store the token in the database
        $updateQuery = "UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sis", $token, $expires, $forgotEmail);
        $updateStmt->execute();

        // Send email (replace the following with your own email sending logic)
        $resetLink = "http://localhost/activitytracker/reset_password.php?token=" . $token;
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n" . $resetLink;
        $headers = "From: no-reply@yourwebsite.com";

        if (mail($forgotEmail, $subject, $message, $headers)) {
            header("Location: index.php?reset=success");
            exit();
        } else {
            echo "Failed to send email.";
        }
    } else {
        // Email not found
        header("Location: index.php?reset=failed");
        exit();
    }
}
?>
