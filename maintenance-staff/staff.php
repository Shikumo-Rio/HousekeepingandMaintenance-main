<?php
require_once '../database.php';

// Check if user is logged in and is maintenance staff
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'maintenance-staff') {
    header("Location: ../login.php");
    exit;
}

$emp_id = $_SESSION['username']; // Use username as it might be stored there

// Get staff's task statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as working_count,
    COUNT(*) as total_count
    FROM maintenance_requests 
    WHERE workon = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get assigned tasks with specific fields
$tasks_query = "SELECT id, request_title, description, room_no, priority, workon, status, created_at, schedule 
                FROM maintenance_requests 
                WHERE workon = ? 
                ORDER BY 
                    CASE priority
                        WHEN 'High' THEN 1
                        WHEN 'Medium' THEN 2
                        WHEN 'Low' THEN 3
                    END,
                    created_at DESC";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <div class="mb-4 fw-semibold">
            <h5 class="fw-semibold ms-3">My Assigned Tasks</h5>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['total_count']; ?></div>
                <div class="stats-label">Total Tasks</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['working_count']; ?></div>
                <div class="stats-label">In Progress</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['completed_count']; ?></div>
                <div class="stats-label">Completed</div>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="tasks-list">
            <?php if ($tasks && $tasks->num_rows > 0): ?>
                <?php while ($task = $tasks->fetch_assoc()): ?>
                    <div class="card request-card priority-<?php echo $task['priority']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">Request #<?php echo $task['id']; ?></h5>
                                    <p class="mb-1"><strong>Room:</strong> <?php echo $task['room_no']; ?></p>
                                    <p class="mb-1"><strong>Title:</strong> <?php echo $task['request_title']; ?></p>
                                    <p class="mb-1"><strong>Description:</strong> <?php echo $task['description']; ?></p>
                                    <p class="mb-1"><strong>Schedule:</strong> <?php echo $task['schedule'] ? date('M d, Y h:i A', strtotime($task['schedule'])) : 'Not scheduled'; ?></p>
                                    <p class="mb-1">
                                        <strong>Being worked by:</strong>
                                        <?php 
                                        if ($task['workon']) {
                                            echo htmlspecialchars($task['workon']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </p>
                                    <small class="text-muted created-at">Created: <?php echo date('M d, Y h:i A', strtotime($task['created_at'])); ?></small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="badge bg-<?php 
                                        if ($task['status'] === 'Completed') {
                                            echo 'completed';
                                        } elseif ($task['status'] === 'In Progress') {
                                            echo 'working';
                                        } else {
                                            echo 'pending';
                                        }
                                    ?> mb-2">
                                        <?php echo $task['status']; ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        if ($task['priority'] === 'High') {
                                            echo 'danger';
                                        } elseif ($task['priority'] === 'Medium') {
                                            echo 'warning';
                                        } else {
                                            echo 'success';
                                        }
                                    ?>">
                                        Priority: <?php echo $task['priority']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($task['status'] !== 'Completed'): ?>
                                <div class="mt-3">
                                    <?php if ($task['status'] === 'Pending'): ?>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'In Progress')">
                                            <i class="fas fa-play me-1"></i> Start Work
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-success btn-sm" 
                                            onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'Completed')">
                                        <i class="fas fa-check me-1"></i> Mark Complete
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No tasks are currently assigned to you.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateTaskStatus(taskId, newStatus) {
        if (!confirm(`Are you sure you want to mark this task as ${newStatus}?`)) {
            return;
        }
        
        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${taskId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating status: ' + (data.message || 'Please try again'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status. Please try again.');
        });
    }
    </script>
</body>
</html>
