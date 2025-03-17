<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM employee_requests";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

$query = "SELECT 
    request_id,
    role,
    quantity,
    status,
    DATE_FORMAT(request_date, '%Y-%m-%d %H:%i') as request_date,
    response_notes
    FROM employee_requests 
    ORDER BY request_date DESC
    LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

echo json_encode([
    'requests' => $requests,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
