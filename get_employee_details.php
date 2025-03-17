<?php
error_reporting(0);
require_once 'database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['emp_id'])) {
        throw new Exception('No employee ID provided');
    }

    $emp_id = $conn->real_escape_string($_GET['emp_id']);

    // First query to get basic employee info and total tasks
    $query = "
        SELECT 
            e.emp_id, 
            e.name, 
            e.role,
            COUNT(a.task_id) as total_tasks
        FROM employee e
        LEFT JOIN assigntasks a ON e.emp_id = a.emp_id
        WHERE e.emp_id = ?
        GROUP BY e.emp_id, e.name, e.role";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if (!$data) {
        throw new Exception('Employee not found');
    }

    // Second query to get completion stats
    $stats_query = "
        SELECT 
            COUNT(CASE WHEN status = 'completed' OR status = 'complete' THEN 1 END) as completed_count,
            AVG(TIMESTAMPDIFF(MINUTE, create_at, completed_at)) as avg_completion_time
        FROM assigntasks 
        WHERE emp_id = ? 
        AND (status = 'completed' OR status = 'complete')
        AND completed_at IS NOT NULL";

    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // Add stats to data array
    $data['completed_tasks'] = (int)$stats['completed_count'];
    
    // Format completion time
    if (!is_null($stats['avg_completion_time'])) {
        $minutes = round($stats['avg_completion_time']);
        $data['avg_completion_time'] = $minutes;
    } else {
        $data['avg_completion_time'] = 0;
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
