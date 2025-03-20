<?php
session_start();
require_once 'database.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requestEmployee'])) {
    // Debug log
    error_log('Received employee request');
    
    try {
        $role = $conn->real_escape_string($_POST['role']);
        $quantity = $conn->real_escape_string($_POST['quantity']);
        $reason = $conn->real_escape_string($_POST['reason']);
        $preferred_shift = $conn->real_escape_string($_POST['preferred_shift']);
        $urgency_level = $conn->real_escape_string($_POST['urgency_level']);
        $requested_by = $_SESSION['emp_id'];

        // Debug log
        error_log("Processing request: Role=$role, Quantity=$quantity");

        $insertRequest = "INSERT INTO employee_requests (
            role, quantity, reason, preferred_shift, 
            urgency_level, status, requested_by, request_date
        ) VALUES (
            ?, ?, ?, ?, ?, 'Pending', ?, NOW()
        )";
        
        $stmt = $conn->prepare($insertRequest);
        $stmt->bind_param("sisssi", $role, $quantity, $reason, $preferred_shift, $urgency_level, $requested_by);
        
        if ($stmt->execute()) {
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
                $mail->addAddress('shekkarhena@gmail.com', 'HR Department');

                $mail->isHTML(true);
                $mail->Subject = 'New Employee Request';
                $mail->Body = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #2E8B57; color: white; padding: 20px; text-align: center; }
                            .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                            .detail-row { margin: 10px 0; border-bottom: 1px solid #eee; padding: 5px 0; }
                            .label { font-weight: bold; color: #2E8B57; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h1>Paradise Hotel</h1>
                                <p>New Employee Request</p>
                            </div>
                            <div class='content'>
                                <h2>Employee Request Details</h2>
                                <div class='detail-row'>
                                    <span class='label'>Role:</span> {$role}
                                </div>
                                <div class='detail-row'>
                                    <span class='label'>Quantity:</span> {$quantity}
                                </div>
                                <div class='detail-row'>
                                    <span class='label'>Shift:</span> {$preferred_shift}
                                </div>
                                <div class='detail-row'>
                                    <span class='label'>Urgency Level:</span> {$urgency_level}
                                </div>
                                <div class='detail-row'>
                                    <span class='label'>Reason:</span> {$reason}
                                </div>
                            </div>
                            <div class='footer'>
                                <p>This is an automated message from Paradise Hotel Housekeeping Department</p>
                                <p>© " . date('Y') . " Paradise Hotel. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'Request submitted successfully and notification sent!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Request submitted but email notification failed: ' . $mail->ErrorInfo]);
            }
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode([
                'success' => false, 
                'message' => 'Error submitting request to database.'
            ]);
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestID = $_POST['requestID'] ?? '';
    $emailTo = $_POST['emailAddress'] ?? '';
    $additionalNotes = $_POST['additionalNotes'] ?? '';

    try {
        // Get request details
        $sql = "SELECT * FROM maintenance_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        if (!$request) {
            throw new Exception('Request not found');
        }

        // Create new PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'paradisehotelmaintenance@gmail.com';
        $mail->Password = 'fcbt duql lpzt xrmy'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('paradisehotelmaintenance@gmail.com', 'Paradise Hotel Maintenance');
        $mail->addAddress($emailTo);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Maintenance Request #" . $requestID;
        
        // Create HTML message
        $htmlMessage = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #2E8B57; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .detail-row { margin: 10px 0; border-bottom: 1px solid #eee; padding: 5px 0; }
                    .label { font-weight: bold; color: #2E8B57; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <h1>Paradise Hotel</h1>
                        <p>Maintenance Request Notification</p>
                    </div>
                    <div class='content'>
                        <h2>Maintenance Request #{$request['id']}</h2>
                        <div class='detail-row'>
                            <span class='label'>Title:</span> {$request['request_title']}
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Room:</span> {$request['room_no']}
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Description:</span> {$request['description']}
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Priority:</span> {$request['priority']}
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Status:</span> {$request['status']}
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Created At:</span> {$request['created_at']}
                        </div>";

        if (!empty($additionalNotes)) {
            $htmlMessage .= "
                        <div class='detail-row'>
                            <span class='label'>Additional Notes:</span><br>
                            " . nl2br(htmlspecialchars($additionalNotes)) . "
                        </div>";
        }

        $htmlMessage .= "
                    </div>
                    <div class='footer'>
                        <p>This is an automated message from Paradise Hotel Housekeeping Department</p>
                        <p>© " . date('Y') . " Paradise Hotel. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->Body = $htmlMessage;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $htmlMessage));

        // Send email
        $mail->send();

        // Update database
        $sql = "UPDATE maintenance_requests SET emailed = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestID);
        $stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Email sent successfully!',
            'request_id' => $requestID
        ]);

    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email',
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?>
