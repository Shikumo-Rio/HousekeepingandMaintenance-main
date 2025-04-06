<?php
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $message = htmlspecialchars($_POST['message']);

    // Save notification to the database (optional)
    $stmt = $conn->prepare("INSERT INTO notifications (title, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $title, $message);
    $stmt->execute();

    // Send notification via WebSocket
    $websocketUrl = "ws://localhost:8080/chat";
    $data = json_encode([
        'type' => 'notification',
        'title' => $title,
        'message' => $message
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $websocketUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(['success' => true, 'message' => 'Notification sent']);
}
?>
