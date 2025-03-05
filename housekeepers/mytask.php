<?php
session_start();
require_once '../database.php';

// Basic authentication
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

// Get logged in employee's ID
$emp_id = $_SESSION['emp_id'];

// Query to get tasks grouped by date
$sql = "SELECT 
    DATE(create_at) as task_date,
    COUNT(*) as total_tasks,
    COUNT(CASE WHEN status = 'completed' OR status = 'complete' THEN 1 END) as completed_tasks,
    GROUP_CONCAT(task_id) as task_ids
    FROM assigntasks 
    WHERE emp_id = '$emp_id'
    GROUP BY DATE(create_at)
    ORDER BY task_date DESC";
$date_groups = mysqli_query($conn, $sql);

// Function to get tasks for a specific date
function getTasksByDate($emp_id, $date) {
    global $conn;
    $sql = "SELECT * FROM assigntasks 
            WHERE emp_id = ? AND DATE(create_at) = ? 
            ORDER BY create_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $emp_id, $date);
    $stmt->execute();
    return $stmt->get_result();
}

// Calculate statistics for the specific employee
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'completed' OR status = 'complete' THEN 1 END) as completed_count,
    AVG(TIMESTAMPDIFF(MINUTE, create_at, completed_at)) as avg_completion_time
    FROM assigntasks 
    WHERE emp_id = '$emp_id' 
    AND (status = 'completed' OR status = 'complete')
    AND completed_at IS NOT NULL";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Format completion time
$avg_time = '';
if (!is_null($stats['avg_completion_time'])) {
    $minutes = round($stats['avg_completion_time']);
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    $avg_time = $hours . 'h ' . $remaining_minutes . 'm';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .task-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .task-type {
            font-size: 0.9rem;
            color: #6c757d;
        }
        /* Add custom status colors */
        .status-invalid { background-color: #6c757d !important; color: white !important; }
        .status-complete, .status-completed { background-color: #198754 !important; color: white !important; }
        .status-working { background-color: #ffc107 !important; color: white !important; }
        .stats-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }
        .stats-container {
            display: flex;
            justify-content: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stats-card {
            flex: 0 0 200px;
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color:rgb(24, 117, 40);
            margin: 5px 0;
        }
        .stats-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }
        .date-summary {
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            padding: 15px;
        }
        .date-summary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .task-count {
            font-size: 1.2rem;
            font-weight: bold;
            color: #198754;
        }
        .date-header {
            color: #495057;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .task-list-item {
            border-left: 3px solid #198754;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">My Tasks</h2>
        
        <!-- Statistics Section -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-label">Completed Tasks</div>
                <div class="stats-number"><?php echo $stats['completed_count'] ?? '0'; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label">Avg. Time</div>
                <div class="stats-number"><?php echo $avg_time ?: 'N/A'; ?></div>
            </div>
        </div>

        <!-- Tasks by Date -->
        <div class="tasks-container">
            <?php 
            if ($date_groups && mysqli_num_rows($date_groups) > 0) {
                while ($date_group = mysqli_fetch_assoc($date_groups)) {
                    $formatted_date = date('F d, Y', strtotime($date_group['task_date']));
                    ?>
                    <div class="date-summary" onclick="window.location.href='daily_tasks.php?date=<?php echo $date_group['task_date']; ?>'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="date-header"><?php echo $formatted_date; ?></div>
                            <div class="task-count">
                                <?php echo $date_group['completed_tasks']; ?>/<?php echo $date_group['total_tasks']; ?> Tasks
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="alert alert-info">No tasks found</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>