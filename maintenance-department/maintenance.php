<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    header("Location: ../login.php");
    exit;
}

// Get maintenance request statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as working_count,
    COUNT(*) as total_count
    FROM maintenance_requests";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get maintenance requests
$requests_query = "SELECT * FROM maintenance_requests ORDER BY created_at DESC";
$requests = $conn->query($requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests</title>
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
        .request-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .request-card:hover {
            transform: translateX(5px);
        }
        .priority-High { border-left-color: #dc3545; }
        .priority-Medium { border-left-color: #ffc107; }
        .priority-Low { border-left-color: #198754; }
        
        .badge.bg-completed { background-color: #198754 !important; }
        .badge.bg-working { background-color: #ffc107 !important; color: black !important; }
        .badge.bg-pending { background-color: #6c757d !important; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Maintenance Requests</h2>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-label">Total Requests</div>
                <div class="stats-number"><?php echo $stats['total_count']; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label">In Progress</div>
                <div class="stats-number"><?php echo $stats['working_count']; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label">Completed</div>
                <div class="stats-number"><?php echo $stats['completed_count']; ?></div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="requests-list">
            <?php if ($requests && $requests->num_rows > 0): ?>
                <?php while ($request = $requests->fetch_assoc()): ?>
                    <div class="card request-card priority-<?php echo $request['priority']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">Request #<?php echo $request['id']; ?></h5>
                                    <p class="mb-1"><strong>Room:</strong> <?php echo $request['room_no']; ?></p>
                                    <p class="mb-1"><strong>Title:</strong> <?php echo $request['request_title']; ?></p>
                                    <p class="mb-1"><strong>Description:</strong> <?php echo $request['description']; ?></p>
                                    <small class="text-muted">Created: <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="badge bg-<?php 
                                        echo match($request['status']) {
                                            'Completed' => 'completed',
                                            'In Progress' => 'working',
                                            default => 'pending'
                                        };
                                    ?> mb-2">
                                        <?php echo $request['status']; ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        echo match($request['priority']) {
                                            'High' => 'danger',
                                            'Medium' => 'warning',
                                            'Low' => 'success'
                                        };
                                    ?>">
                                        Priority: <?php echo $request['priority']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($request['status'] !== 'Completed'): ?>
                                <div class="mt-3 d-flex gap-2">
                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="updateStatus(<?php echo $request['id']; ?>, 'In Progress')">
                                            Start Work
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-success btn-sm" 
                                            onclick="updateStatus(<?php echo $request['id']; ?>, 'Completed')">
                                        Mark Complete
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No maintenance requests found</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(requestId, newStatus) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
            });
        }
    </script>
</body>
</html>
