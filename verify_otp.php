<?php
session_start();
require_once 'database.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if there's a pending login
if (!isset($_SESSION['pending_login']) || $_SESSION['pending_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Generate and send OTP
function generateAndSendOTP($email, $username) {
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Store OTP in session with expiration time (5 minutes)
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + (5 * 60);
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'paradisehotelmaintenance@gmail.com';
        $mail->Password = 'fcbt duql lpzt xrmy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('paradisehotelmaintenance@gmail.com', 'Paradise Hotel');
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = 'Your Paradise Hotel Login Verification Code';
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #2E8B57; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .otp-code { font-size: 32px; font-weight: bold; text-align: center; 
                                letter-spacing: 5px; margin: 20px 0; color: #2E8B57; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <h1>Paradise Hotel</h1>
                        <p>Security Verification</p>
                    </div>
                    <div class='content'>
                        <h2>Login Verification Code</h2>
                        <p>Hello {$username},</p>
                        <p>Your one-time verification code for logging into Paradise Hotel system is:</p>
                        <div class='otp-code'>{$otp}</div>
                        <p>This code will expire in 5 minutes.</p>
                        <p>If you did not attempt to log in, please contact the system administrator immediately.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message from Paradise Hotel. Do not reply.</p>
                        <p>© " . date('Y') . " Paradise Hotel. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email Error: " . $e->getMessage());
        return false;
    }
}

// Function to determine redirect URL based on user type
function getRedirectUrl($user_type) {
    switch ($user_type) {
        case 'Employee':
            return "/housekeepingandmaintenance-main/housekeepers/index.php";
        case 'Admin':
            return "dashboard.php";
        case 'Maintenance':
            return "/housekeepingandmaintenance-main/maintenance-department/maintenance.php";
        case 'maintenance-staff':
            return "/housekeepingandmaintenance-main/maintenance-staff/staff.php";
        default:
            return "login.php";
    }
}

// Verify OTP and complete login
if (isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);
    $stored_otp = $_SESSION['otp'] ?? '';
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;
    
    // Check if OTP is valid and not expired
    if ($entered_otp === $stored_otp && time() <= $otp_expiry) {
        // OTP is valid, complete login
        $username = $_SESSION['pending_username'];
        $user_type = $_SESSION['pending_user_type'];
        $emp_id = $_SESSION['pending_user_id'];
        
        // Set session variables
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['emp_id'] = $emp_id;
        
        // Clear pending status and OTP
        unset($_SESSION['pending_login']);
        unset($_SESSION['pending_username']);
        unset($_SESSION['pending_user_id']);
        unset($_SESSION['pending_user_type']);
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        
        // Update login status in database
        $update_stmt = $conn->prepare("UPDATE login_accounts SET is_online = 1, last_activity = NOW() WHERE username = ?");
        $update_stmt->bind_param("s", $username);
        $update_stmt->execute();
        
        // Set employee status to active in employee table
        $update_employee_status = $conn->prepare("UPDATE employee SET status = 'active' WHERE emp_id = ?");
        $update_employee_status->bind_param("i", $emp_id);
        $update_employee_status->execute();
        
        $logQuery = "INSERT INTO login_logs (emp_id) VALUES (?)";
        $log_stmt = $conn->prepare($logQuery);
        $log_stmt->bind_param("i", $emp_id);
        $log_stmt->execute();
        
        $notificationQuery = "INSERT INTO notifications (emp_id, message) VALUES (?, '$emp_id have successfully logged in with OTP verification.')";
        $notification_stmt = $conn->prepare($notificationQuery);
        $notification_stmt->bind_param("i", $emp_id);
        $notification_stmt->execute();
        
        // Redirect based on user type
        header("Location: " . getRedirectUrl($user_type));
        exit;
    } else {
        // Invalid or expired OTP
        $error_message = (time() > $otp_expiry) ? "OTP has expired. Please request a new one." : "Invalid OTP. Please try again.";
    }
}

// Resend OTP
if (isset($_POST['resend_otp'])) {
    $username = $_SESSION['pending_username'];
    $emp_id = $_SESSION['pending_user_id'];
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM employee WHERE emp_id = ?");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && !empty($user['email'])) {
        $sent = generateAndSendOTP($user['email'], $username);
        $message = $sent ? "A new OTP has been sent to your email." : "Failed to send OTP. Please try again.";
    } else {
        $message = "Could not find email address for your account.";
    }
}

// On initial load, send OTP
if (!isset($_SESSION['otp']) && !isset($_POST['verify_otp']) && !isset($_POST['resend_otp'])) {
    $username = $_SESSION['pending_username'];
    $emp_id = $_SESSION['pending_user_id'];
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM employee WHERE emp_id = ?");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && !empty($user['email'])) {
        $sent = generateAndSendOTP($user['email'], $username);
        if (!$sent) {
            $error_message = "Failed to send OTP. Please try again or contact support.";
        }
    } else {
        $error_message = "Could not find email address for your account. Please contact support.";
    }
}

$username = htmlspecialchars($_SESSION['pending_username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - Paradise Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/webp" sizes="32x32" href="img/logo.webp">
    <style>
        body, html {
            height: 100%;
            background-position: center; 
            background-repeat: no-repeat; 
            background-size: cover;
            background-image: url(img/bgpd.jpg);
        }
        
        .verification-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .verification-card {
            width: 420px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 120px;
        }
        
        .otp-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5em;
        }
        
        .btn-verify {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .btn-verify:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
        }
        
        .text-light {
            color: #fff !important;
        }
        
        .countdown {
            font-size: 0.9rem;
            color: #ffc107;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <img src="img/logo.webp" alt="Paradise Logo" class="logo">
            <h3 class="text-center text-light mb-3">Email Verification</h3>
            <p class="text-light text-center mb-4">
                Hello <strong><?php echo $username; ?></strong>, please enter the verification code sent to your email.
            </p>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="verify_otp.php">
                <div class="mb-4">
                    <label for="otp" class="form-label text-light">Verification Code</label>
                    <input type="text" class="form-control otp-input" id="otp" name="otp" 
                           placeholder="000000" maxlength="6" required>
                    <small class="form-text text-light">
                        Enter the 6-digit code sent to your email
                    </small>
                </div>
                
                <div class="countdown" id="timer">
                    Code expires in: <span id="countdown">5:00</span>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="verify_otp" class="btn btn-verify">
                        <i class="bi bi-shield-check me-2"></i>Verify Code
                    </button>
                    <button type="submit" name="resend_otp" class="btn btn-outline-light">
                        <i class="bi bi-envelope me-2"></i>Resend Code
                    </button>
                    <a href="login.php" class="btn btn-link text-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Countdown timer for OTP expiration
        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expired";
                    document.getElementById('timer').style.color = '#dc3545';
                }
            }, 1000);
        }

        window.onload = function () {
            // 5 minutes countdown
            const fiveMinutes = 60 * 5,
                display = document.querySelector('#countdown');
            startTimer(fiveMinutes, display);
        };
    </script>
</body>
</html>
