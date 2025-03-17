<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    header("Location: ../login.php");
    exit;
}

// Update staff query to include online status
$staff_query = "SELECT 
    la.username,
    la.is_online,
    e.name as employee_name,
    e.emp_id,
    COUNT(mr.id) as total_tasks,
    COUNT(CASE WHEN mr.status = 'Completed' THEN 1 END) as completed_tasks,
    COUNT(CASE WHEN mr.status = 'In Progress' THEN 1 END) as ongoing_tasks
    FROM login_accounts la
    LEFT JOIN employee e ON la.emp_id = e.emp_id
    LEFT JOIN maintenance_requests mr ON la.username = mr.workon
    WHERE la.user_type = 'Maintenance-Staff'
    GROUP BY la.username, la.is_online, e.name, e.emp_id";
$staff_result = $conn->query($staff_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Staff Overview</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>

</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h5 class="mb-4 fw-semibold mt-4">Maintenance Staff Overview</h5>
        
        <div class="row">
            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="staff-card card">
                        <div class="staff-info">
                            <div class="staff-name">
                                <span class="online-indicator <?php echo $staff['is_online'] ? 'online' : 'offline'; ?>"></span>
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($staff['employee_name']); ?>
                                <small class="text-muted d-block">ID: <?php echo htmlspecialchars($staff['emp_id']); ?></small>
                            </div>
                            
                            <div class="task-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $staff['total_tasks']; ?></div>
                                    <div class="stat-label">Total Tasks</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $staff['ongoing_tasks']; ?></div>
                                    <div class="stat-label">In Progress</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $staff['completed_tasks']; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStaffTasks(username) {
            // You can implement this to show tasks for specific staff
            window.location.href = `maintenance.php?staff=${encodeURIComponent(username)}`;
        }
    </script>
</body>
</html>
