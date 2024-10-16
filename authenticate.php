<?php
session_start();
ob_start(); // Start output buffering

// Include the database connection file
include('db.php');

// Check if the form is submitted via POST method
if (isset($_POST['submit'])) {
    // Get the submitted email and password from the form
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute a query to find the user by email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    // Check if the prepared statement was created successfully
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if the user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Compare the hashed password with the one in the database
            if (password_verify($password, $user['password'])) {
                // Store user information in session
                $_SESSION['username'] = $user['name'];
                $_SESSION['usertype'] = $user['usertype'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['branch'] = $user['branch'];
                $_SESSION['division'] = $user['division'];
                $_SESSION['admission_year'] = $user['admission_year'];
                

                // Redirect user to the appropriate dashboard based on usertype
                if ($user['usertype'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['usertype'] === 'faculty') {
                    header("Location: faculty_dashboard.php");
                } elseif ($user['usertype'] === 'student') {
                    header("Location: studentdashboard.php");
                }
                elseif ($user['usertype'] === 'hod') {
                    header("Location: hod_dashboard.php");
                }
                exit();
            } else {
                // Incorrect password
                header("Location: index.php?error=Incorrect Password");
                exit();
            }
        } else {
            // User not found
            header("Location: index.php?error=User not found");
            exit();
        }
    } else {
        // Error preparing SQL statement
        die("Error preparing SQL query.");
    }

    // Close the prepared statement
    $stmt->close();
} else {
    // Redirect to login if accessed without form submission
    header("Location: index.php");
    exit();
}

// Close the database connection
$conn->close();
ob_end_flush(); // End output buffering and send the output
?>