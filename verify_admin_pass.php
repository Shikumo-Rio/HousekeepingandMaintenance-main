<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// Log the request for debugging
error_log("Admin password verification request received");

// Validate session and user type
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    error_log("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate password input
if (!isset($_POST['password']) || empty($_POST['password'])) {
    error_log("Password is missing in the request");
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$submittedPassword = $_POST['password'];
$adminUsername = $_SESSION['username'];
$adminEmpId = $_SESSION['emp_id'];

error_log("Verifying password for admin: $adminUsername (ID: $adminEmpId)");

// Check password in the database
$stmt = $conn->prepare("SELECT password FROM login_accounts WHERE emp_id = ? AND username = ? AND user_type = 'Admin'");
$stmt->bind_param("is", $adminEmpId, $adminUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($submittedPassword, $row['password'])) {
        error_log("Password verification successful");
        echo json_encode(['success' => true, 'message' => 'Authentication successful']);
    } else {
        error_log("Password verification failed: Incorrect password");
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    }
} else {
    error_log("Admin account not found");
    echo json_encode(['success' => false, 'message' => 'Admin account not found']);
}
?>
