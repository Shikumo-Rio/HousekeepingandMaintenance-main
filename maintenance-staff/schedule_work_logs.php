<?php
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'maintenance-staff') {
    header("Location: ../login.php");
    exit;
}

$emp_id = $_SESSION['username'];

// Fetch upcoming maintenance jobs
$schedule_query = "SELECT 
    mr.*,
    am.status as current_status,
    am.emp_id as assigned_emp_id,
    am.assigned_at
    FROM assigned_maintenance am
    JOIN maintenance_requests mr ON am.maintenance_request_id = mr.id
    WHERE am.emp_id = ? AND mr.schedule IS NOT NULL AND am.status != 'Completed'
    ORDER BY mr.schedule ASC";
$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$schedule = $stmt->get_result();

// Fetch maintenance history
$history_query = "SELECT 
    mr.*,
    am.status as current_status,
    am.emp_id as assigned_emp_id,
    am.assigned_at
    FROM assigned_maintenance am
    JOIN maintenance_requests mr ON am.maintenance_request_id = mr.id
    WHERE am.emp_id = ? AND am.status = 'Completed'
    ORDER BY mr.created_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule & Work Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            min-width: 1000px; /* Minimum width to ensure proper display */
        }
        .table thead {
            background-color: #f8f9fa;
        }
        .table th {
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            font-weight: 600;
            color: #495057;
        }
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-progress { background-color: #b8daff; color: #004085; }
        .status-completed { background-color: #c3e6cb; color: #155724; }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .table-container {
                padding: 10px;
                margin: 10px;
            }
            .table td, .table th {
                white-space: nowrap;
                min-width: 100px;
            }
            .description-cell {
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .description-cell:hover {
                white-space: normal;
                overflow: visible;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h3 class="mb-4">Schedule & Work Logs</h3>

        <!-- Upcoming Maintenance Jobs -->
        <div class="table-container">
            <h5 class="section-title">Upcoming Maintenance Jobs</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Room</th>
                            <th>Priority</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedule->num_rows > 0): ?>
                            <?php while ($job = $schedule->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $job['id']; ?></td>
                                    <td><?php echo htmlspecialchars($job['request_title']); ?></td>
                                    <td class="description-cell"><?php echo htmlspecialchars($job['description']); ?></td>
                                    <td><?php echo htmlspecialchars($job['room_no']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            if ($job['priority'] === 'High') echo 'danger';
                                            elseif ($job['priority'] === 'Medium') echo 'warning';
                                            else echo 'success';
                                        ?>">
                                            <?php echo $job['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($job['schedule'])); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php 
                                            echo strtolower(str_replace(' ', '-', $job['current_status'])); 
                                        ?>">
                                            <?php echo htmlspecialchars($job['current_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="far fa-clock me-2"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($job['assigned_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No upcoming jobs scheduled.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Maintenance History -->
        <div class="table-container">
            <h5 class="section-title">Maintenance History</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Room</th>
                            <th>Priority</th>
                            <th>Schedule</th>
                            <th>Completed At</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history->num_rows > 0): ?>
                            <?php while ($job = $history->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $job['id']; ?></td>
                                    <td><?php echo htmlspecialchars($job['request_title']); ?></td>
                                    <td class="description-cell"><?php echo htmlspecialchars($job['description']); ?></td>
                                    <td><?php echo htmlspecialchars($job['room_no']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            if ($job['priority'] === 'High') echo 'danger';
                                            elseif ($job['priority'] === 'Medium') echo 'warning';
                                            else echo 'success';
                                        ?>">
                                            <?php echo $job['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo $job['schedule'] ? date('M d, Y h:i A', strtotime($job['schedule'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <i class="far fa-check-circle me-2"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($job['assigned_at'])); ?>
                                    </td>
                                    <td>
                                        <i class="far fa-clock me-2"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($job['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No completed tasks found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add viewport meta tag if not present -->
    <script>
        if (!document.querySelector('meta[name="viewport"]')) {
            const meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            document.getElementsByTagName('head')[0].appendChild(meta);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
