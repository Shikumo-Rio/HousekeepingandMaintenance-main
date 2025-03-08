<?php
require_once("../database.php");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the form data
$uname = mysqli_real_escape_string($conn, $_POST['uname']);

// Query the database to check if the user exists
$sql = "SELECT * FROM guess WHERE uname = '$uname'";
$result = $conn->query($sql);

// Check if the user exists
if ($result->num_rows > 0) {
    // Set session variables for successful login and store username
    $_SESSION['verified'] = true;
    $_SESSION['uname'] = $uname; // Store the username in session
    
    // Redirect to bot.php
    header("Location: services.php");
    exit();
} else {
    // Show an error if the user is not found
    echo "<script>alert('Username not found. Please try again.'); window.history.back();</script>";
}

// Close the connection
$conn->close();
?>
