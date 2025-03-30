<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

$emp_id = $_SESSION['emp_id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Version agnostic error handling
function handleError($conn) {
    if (is_object($conn)) {
        return $conn->errno ? error_log("MySQL Error ({$conn->errno}): {$conn->error}") : false;
    }
    return mysqli_errno($conn) ? error_log("MySQL Error (" . mysqli_errno($conn) . "): " . mysqli_error($conn)) : false;
}

// Version agnostic query execution
function executeQuery($conn, $query, $params = [], $types = '') {
    $result = false;
    
    try {
        if (is_object($conn)) {
            // Object-oriented style
            $stmt = $conn->prepare($query);
            if ($stmt && $params) {
                $stmt->bind_param($types, ...$params);
            }
        } else {
            // Procedural style
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt && $params) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return $result;
}

$daily_stats_query = "SELECT 
    SUM(CASE WHEN LOWER(status) IN ('completed', 'complete') THEN 1 ELSE 0 END) as completed_count,
    SEC_TO_TIME(AVG(
        CASE WHEN completed_at IS NOT NULL 
        THEN TIME_TO_SEC(TIMEDIFF(completed_at, create_at))
        ELSE NULL END
    )) as avg_completion_time
    FROM assigntasks 
    WHERE emp_id = ? 
    AND DATE(create_at) = ?";

$daily_stats = ['completed_count' => 0, 'avg_completion_time' => null];

$result = executeQuery(
    $conn, 
    $daily_stats_query, 
    [$emp_id, $selected_date], 
    "is"
);

if ($result) {
    $daily_stats = is_object($result) ? 
        $result->fetch_assoc() : 
        mysqli_fetch_assoc($result);
    is_object($result) ? $result->free() : mysqli_free_result($result);
}

// Format daily completion time using modern null handling
$daily_avg_time = match(true) {
    isset($daily_stats['avg_completion_time']) => (function() use ($daily_stats) {
        $time_parts = explode(':', $daily_stats['avg_completion_time']);
        $hours = (int)($time_parts[0] ?? 0);
        $minutes = (int)($time_parts[1] ?? 0);
        return "{$hours}h {$minutes}m";
    })(),
    default => 'N/A'
};

// Get tasks with modern error handling
$tasks_query = "SELECT *, 
    IFNULL(
        TIMESTAMPDIFF(MINUTE, create_at, completed_at),
        TIMESTAMPDIFF(MINUTE, create_at, NOW())
    ) as duration_minutes 
    FROM assigntasks 
    WHERE emp_id = ? 
    AND DATE(create_at) = ?
    ORDER BY create_at DESC";

$tasks = executeQuery(
    $conn, 
    $tasks_query, 
    [$emp_id, $selected_date], 
    "is"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tasks - <?php echo date('F d, Y', strtotime($selected_date)); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="daily-task.css">
    <link rel="icon" href="img/logo.webp">

</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <a href="mytask.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <h4 class="mb-4">Tasks for <?php echo date('F d, Y', strtotime($selected_date)); ?></h4>
        
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
            if ($tasks && mysqli_num_rows($tasks) > 0) {
                while ($task = mysqli_fetch_assoc($tasks)) {
                    $status_class = 'status-' . strtolower($task['status']);
                    ?>
                    <div class="task-card <?php echo $status_class; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title-task fw-semibold">Task #<?php echo $task['task_id']; ?></h5>
                                    <p class="mb-1"><strong>Room <?php echo $task['room']; ?></strong></p>
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
