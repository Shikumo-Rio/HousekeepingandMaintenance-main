<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

$emp_id = $_SESSION['emp_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get daily statistics
$daily_stats_query = "SELECT 
    COUNT(CASE WHEN status = 'completed' OR status = 'complete' THEN 1 END) as completed_count,
    AVG(TIMESTAMPDIFF(MINUTE, create_at, completed_at)) as avg_completion_time
    FROM assigntasks 
    WHERE emp_id = ? 
    AND DATE(create_at) = ?
    AND (status = 'completed' OR status = 'complete')
    AND completed_at IS NOT NULL";

$stmt = $conn->prepare($daily_stats_query);
$stmt->bind_param("is", $emp_id, $selected_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_assoc();

// Format daily completion time
$daily_avg_time = 'N/A';
if (!is_null($daily_stats['avg_completion_time'])) {
    $minutes = round($daily_stats['avg_completion_time']);
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    $daily_avg_time = $hours . 'h ' . $remaining_minutes . 'm';
}

// Get tasks for selected date
$tasks_query = "SELECT * FROM assigntasks 
                WHERE emp_id = ? 
                AND DATE(create_at) = ?
                ORDER BY create_at DESC";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("is", $emp_id, $selected_date);
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tasks - <?php echo date('F d, Y', strtotime($selected_date)); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stats-container {
            display: flex;
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
            color: #198754;
        }
        .task-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateX(5px);
        }
        .status-completed, .status-complete { border-left-color: #198754; }
        .status-working { border-left-color: #ffc107; }
        .status-invalid { border-left-color: #6c757d; }
        .back-button {
            text-decoration: none;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        .back-button:hover {
            color: #495057;
        }
        
        /* Update status badge colors */
        .badge.bg-completed, 
        .badge.bg-complete,
        .badge[class*='bg-'][class*='completed'],
        .badge[class*='bg-'][class*='complete'] { 
            background-color: #198754 !important; 
            color: white !important; 
        }
        .badge.bg-working { 
            background-color: #ffc107 !important; 
            color: black !important; 
        }
        .badge.bg-invalid { 
            background-color: #6c757d !important; 
            color: white !important; 
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <a href="mytask.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to All Tasks
        </a>
        
        <h2 class="mb-4">Tasks for <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
        
        <!-- Daily Statistics -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-label">Completed tasks</div>
                <div class="stats-number"><?php echo $daily_stats['completed_count'] ?? '0'; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label">Avg. Time</div>
                <div class="stats-number"><?php echo $daily_avg_time; ?></div>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="tasks-list">
            <?php
            if ($tasks->num_rows > 0) {
                while ($task = $tasks->fetch_assoc()) {
                    $status_class = 'status-' . strtolower($task['status']);
                    ?>
                    <div class="card task-card <?php echo $status_class; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">Task #<?php echo $task['task_id']; ?></h5>
                                    <p class="mb-1"><strong>Room:</strong> <?php echo $task['room']; ?></p>
                                    <p class="mb-1"><strong>Request:</strong> <?php echo $task['request']; ?></p>
                                    <p class="mb-1"><strong>Details:</strong> <?php echo $task['details']; ?></p>
                                    <small class="text-muted">Created: <?php echo date('h:i A', strtotime($task['create_at'])); ?></small>
                                    <?php if ($task['completed_at']) { ?>
                                        <br><small class="text-success">Completed: <?php echo date('h:i A', strtotime($task['completed_at'])); ?></small>
                                    <?php } ?>
                                </div>
                                <span class="badge <?php 
                                    echo match(strtolower($task['status'])) {
                                        'completed', 'complete' => 'bg-completed',
                                        'working' => 'bg-working',
                                        default => 'bg-invalid'
                                    };
                                ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="alert alert-info">No tasks found for this date</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
