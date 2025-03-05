//<?php
// Include the database connection
require_once 'database.php';

// Example of a username, password, user type, and employee ID (you'd normally get these from a form)
$username = "21015455"; // Replace with form input
$password = "reorio345"; // Replace with form input
$user_type = "maintenance"; // Ensure this is correctly set
$emp_id = 1; // Replace with a valid employee ID that exists in the employee table

// Check if the username already exists
$stmt = $conn->prepare("SELECT id FROM login_accounts WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
echo "Error: Username already exists.";
} else {
// Hash the password before storing it
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare the SQL query to insert the username, hashed password, user type, and emp_id into the database
$stmt = $conn->prepare("INSERT INTO login_accounts (username, password, user_type, emp_id) VALUES (?, ?, ?, ?)");

// Bind the parameters (username, hashed password, user type, and emp_id)
$stmt->bind_param("sssi", $username, $hashedPassword, $user_type, $emp_id);

// Execute the query
if ($stmt->execute()) {
echo "User registered successfully with hashed password.";
} else {
echo "Error: " . $stmt->error;
}
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
