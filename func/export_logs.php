<?php
require_once '../database.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="task_logs.xls"');
header('Cache-Control: max-age=0');

// Build search conditions (similar to task_allocation.php)
$searchCondition = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $searchCondition = " WHERE (log_id LIKE '%$search%' 
                        OR task_id LIKE '%$search%' 
                        OR emp_id LIKE '%$search%')";
}

if (isset($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $searchCondition .= $searchCondition ? " AND" : " WHERE";
    $searchCondition .= " DATE(log_time) = '$date'";
}

if (isset($_GET['emp_id'])) {
    $emp_id = $conn->real_escape_string($_GET['emp_id']);
    $searchCondition .= $searchCondition ? " AND" : " WHERE";
    $searchCondition .= " emp_id = '$emp_id'";
}

// Query to get logs
$logsQuery = "SELECT log_id, task_id, emp_id, action, change_details, log_time 
              FROM task_logs" . 
              ($searchCondition ? $searchCondition : "") . 
              " ORDER BY log_time DESC";

$result = $conn->query($logsQuery);

// Create Excel content
echo "<table border='1'>";
echo "<tr>
        <th>Log ID</th>
        <th>Task ID</th>
        <th>Employee ID</th>
        <th>Action</th>
        <th>Details</th>
        <th>Date</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['log_id'] . "</td>";
    echo "<td>" . $row['task_id'] . "</td>";
    echo "<td>" . $row['emp_id'] . "</td>";
    echo "<td>" . $row['action'] . "</td>";
    echo "<td>" . $row['change_details'] . "</td>";
    echo "<td>" . $row['log_time'] . "</td>";
    echo "</tr>";
}

echo "</table>";
$conn->close();
?>
