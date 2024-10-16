<?php
include('db.php');

if(isset($_POST['submit']))
{
// Get the submitted form data
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
//$semester = $_POST['semester'];
$branch = $_POST['branch'];
$division = $_POST['division'];
$admission_year = $_POST['admission_year'];
$ktu_register_no = $_POST['ktu_register_no'];
$student='student';
$approval='pending';
// Hash the password for security

// Check if email or KTU register number already exists
$query = "SELECT * FROM users WHERE email = ? OR ktu_register_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email, $ktu_register_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Email or KTU register number already exists
    $row = $result->fetch_assoc();
    if ($row['email'] == $email) {
        echo '<script>alert("Email already registered."); window.location.href="index.php";</script>';
    } elseif ($row['ktu_register_no'] == $ktu_register_no) {
        echo '<script>alert("KTU Register Number already registered."); window.location.href="index.php";</script>';
    }
}else {

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute SQL query to insert the new user into the 'users' table
    $sql ="INSERT INTO `users`(`name`, `email`, `password`,  `usertype`,  `branch`,`division`, `admission_year`, `ktu_register_no`, `approval`) 
            VALUES (?,?,?,?,?,?,?,?,?)";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("ssssssiss", $name, $email, $hashed_password, $student,  $branch, $division, $admission_year, $ktu_register_no,$approval);
// $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");

// if ($stmt === false) {
//     die("Error preparing statement: " . $conn->error);
// }

// $stmt->bind_param("ss", $name, $email);


    if ($stmt->execute()) {
        // Registration successful, redirect to login page
        header("Location: index.php?register_success=1");
        exit();
    } else {
        // Registration failed, redirect back to the register form with error
        header("Location: index.php?register_error=1");
        exit();
    }
}
// Close the connection
$stmt->close();
$conn->close();
}
?>
