<?php
session_start();
require_once 'database.php';

// Debug logging function
function logDebug($message) {
    file_put_contents(__DIR__ . '/controller_log.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Log that the controller was accessed
logDebug("Login controller accessed. Session data: " . json_encode($_SESSION));

// Check if there's a pending login
if (!isset($_SESSION['pending_login']) || $_SESSION['pending_login'] !== true) {
    logDebug("No pending login, redirecting to login.php");
    header("Location: login.php");
    exit;
}

$username = $_SESSION['pending_username'] ?? '';
$user_id = $_SESSION['pending_user_id'] ?? '';
$user_type = $_SESSION['pending_user_type'] ?? '';

logDebug("Processing login for user: $username, ID: $user_id, Type: $user_type");

// Determine which verification method to use
// Admin users or specific employee ID use OTP verification
if ($user_type === 'Admin' || $user_id === '21015005') {
    logDebug("User requires OTP verification");
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM employee WHERE emp_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && !empty($user['email'])) {
        // User has an email, proceed to OTP verification
        logDebug("User has email, redirecting to OTP verification");
        header("Location: verify_otp.php");
        exit;
    } else {
        // No email found, cannot do OTP verification
        logDebug("No email found for user, cannot do OTP verification");
        $_SESSION['login_error'] = "No email found for your account. Please contact an administrator.";
        
        // Clear pending login
        unset($_SESSION['pending_login']);
        unset($_SESSION['pending_username']);
        unset($_SESSION['pending_user_id']);
        unset($_SESSION['pending_user_type']);
        
        header("Location: login.php?otp_error=1");
        exit;
    }
} else {
    // Regular users use face verification
    logDebug("User requires face verification");
    // Check if user has registered faces
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM face_images WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // User has registered faces, proceed to face verification
        logDebug("User has registered faces, redirecting to face verification");
        header("Location: verify_face.php");
        exit;
    } else {
        // No face registered, show error
        logDebug("No faces registered for user");
        $_SESSION['login_error'] = "No face registered for your account. Please contact an administrator.";
        
        // Clear pending login
        unset($_SESSION['pending_login']);
        unset($_SESSION['pending_username']);
        unset($_SESSION['pending_user_id']);
        unset($_SESSION['pending_user_type']);
        
        header("Location: login.php?face_error=1");
        exit;
    }
}
?>
