<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('database.php');

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if we have a password from POST
if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$password = $_POST['password'];
$username = $_SESSION['username'];

// Query to get the admin's password hash
$query = "SELECT password FROM login_accounts WHERE username = ? AND user_type = 'Admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Admin account not found']);
    exit;
}

$admin = $result->fetch_assoc();
$hashedPassword = $admin['password'];

// Verify the password
if (password_verify($password, $hashedPassword)) {
    echo json_encode(['success' => true]);
} else {
    // For older systems using md5 instead of password_hash
    if (md5($password) === $hashedPassword) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    }
}

$conn->close();
?>
