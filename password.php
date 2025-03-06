<?php
// Include the database connection
require_once 'database.php';

// Example input (replace with form input)
$username = "21015000"; // Replace with form input
$password = "reorio345"; // Replace with form input
$user_type = "maintenance-staff"; // Ensure this is correctly set
$emp_id = 21015000; // Replace with a valid employee ID that exists in the employee table

// Step 1: Ensure emp_id exists in employee table
$stmt = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo "Error: Employee ID does not exist in the employee table.";
} else {
    // Step 2: Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM login_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Error: Username already exists.";
    } else {
        // Step 3: Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Step 4: Insert user into login_accounts
        $stmt = $conn->prepare("INSERT INTO login_accounts (username, password, user_type, emp_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hashedPassword, $user_type, $emp_id);

        if ($stmt->execute()) {
            echo "User registered successfully with hashed password.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
