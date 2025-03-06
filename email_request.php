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
            <h2>Maintenance Request Details</h2>
            <p><strong>Request ID:</strong> {$request['id']}</p>
            <p><strong>Title:</strong> {$request['request_title']}</p>
            <p><strong>Room:</strong> {$request['room_no']}</p>
            <p><strong>Description:</strong> {$request['description']}</p>
            <p><strong>Priority:</strong> {$request['priority']}</p>
            <p><strong>Status:</strong> {$request['status']}</p>
            <p><strong>Created At:</strong> {$request['created_at']}</p>
        ";

        if (!empty($additionalNotes)) {
            $htmlMessage .= "<p><strong>Additional Notes:</strong><br>{$additionalNotes}</p>";
        }

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
