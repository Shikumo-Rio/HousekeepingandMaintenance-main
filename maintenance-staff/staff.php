<?php
require_once '../database.php';

// Check if user is logged in and is maintenance staff
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'maintenance-staff') {
    header("Location: ../login.php");
    exit;
}

$emp_id = $_SESSION['username']; // Use username as it might be stored there
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'In Progress'; // Default filter

// Get staff's task statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN am.status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN am.status = 'In Progress' THEN 1 END) as working_count,
    COUNT(*) as total_count
    FROM assigned_maintenance am
    JOIN maintenance_requests mr ON am.maintenance_request_id = mr.id
    WHERE am.emp_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Modify the tasks query to include status filter
$tasks_query = "SELECT 
    mr.*,
    am.status as current_status,
    am.emp_id as assigned_emp_id,
    am.assigned_at
    FROM assigned_maintenance am
    JOIN maintenance_requests mr ON am.maintenance_request_id = mr.id
    WHERE am.emp_id = ? AND am.status = ?
    ORDER BY 
        CASE mr.priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
        END,
        mr.created_at DESC";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("ss", $emp_id, $status_filter);
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

        <!-- Add Filter Buttons -->
        <div class="filter-container mb-4">
            <div class="btn-group" role="group">
                <button type="button" class="btn <?php echo $status_filter === 'In Progress' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="filterStatus('In Progress')">In Progress</button>
                <button type="button" class="btn <?php echo $status_filter === 'Completed' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="filterStatus('Completed')">Completed</button>
            </div>
        </div>

        <?php
        function renderTaskButtons($task) {
            $buttons = '';
            if ($task['current_status'] === 'Pending') {
                $buttons .= '<button class="btn btn-warning btn-sm me-2" title="Start Task" onclick="updateTaskStatus(' . $task['id'] . ', \'In Progress\')">
                                <i class="fas fa-play"></i>
                             </button>';
            }
            if ($task['current_status'] !== 'Completed') {
                $buttons .= '<button class="btn btn-success btn-sm me-2" title="Upload Proof" onclick="uploadProof(' . $task['id'] . ')">
                                <i class="fas fa-upload"></i>
                             </button>';
                $buttons .= '<button class="btn btn-danger btn-sm" title="Request Assistance" onclick="requestAssistance(' . $task['id'] . ')">
                                <i class="fas fa-hands-helping"></i>
                             </button>';
            }
            return $buttons;
        }
        ?>

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
                                    <p class="mb-1"><strong>Schedule:</strong> 
                                        <span class="badge bg-info text-dark">
                                            <?php echo $task['schedule'] ? date('M d, Y h:i A', strtotime($task['schedule'])) : 'Not scheduled'; ?>
                                        </span>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Being worked by:</strong>
                                        <?php 
                                        if ($task['assigned_emp_id']) {
                                            echo htmlspecialchars($task['assigned_emp_id']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </p>
                                    <small class="text-muted created-at">Created: <?php echo date('M d, Y h:i A', strtotime($task['created_at'])); ?></small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="badge bg-<?php 
                                        if ($task['current_status'] === 'Completed') {
                                            echo 'completed';
                                        } elseif ($task['current_status'] === 'In Progress') {
                                            echo 'working';
                                        } else {
                                            echo 'pending';
                                        }
                                    ?> mb-2">
                                        <?php echo htmlspecialchars($task['current_status']); ?>
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
                            
                            <?php if ($task['current_status'] !== 'Completed'): ?>
                                <div class="mt-3 d-flex flex-wrap">
                                    <?php echo renderTaskButtons($task); ?>
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

    <!-- Upload Proof Modal -->
    <div class="modal fade" id="uploadProofModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Completion Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadProofForm" enctype="multipart/form-data">
                        <input type="hidden" id="taskIdInput" name="request_id">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo Proof</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitProof()">Submit</button>
                </div>
            </div>
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

    function uploadProof(taskId) {
        document.getElementById('taskIdInput').value = taskId;
        new bootstrap.Modal(document.getElementById('uploadProofModal')).show();
    }

    function submitProof() {
        const form = document.getElementById('uploadProofForm');
        const formData = new FormData(form);

        fetch('upload_proof.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Please try again'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading proof. Please try again.');
        });
    }

    function filterStatus(status) {
        window.location.href = `staff.php?status=${status}`;
    }

    function viewAssignedSchedule() {
        alert('View assigned schedule functionality.');
    }

    function viewMaintenanceHistory() {
        alert('View maintenance history functionality.');
    }

    function changePassword() {
        alert('Change password functionality.');
    }

    function requestAssistance(taskId) {
        if (!confirm('Are you sure you want to request assistance for this task?')) {
            return;
        }
        
        fetch('request_assistance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Assistance request has been sent successfully.');
                location.reload();
            } else {
                alert('Error requesting assistance: ' + (data.message || 'Please try again'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error requesting assistance. Please try again.');
        });
    }
    </script>
</body>
</html>
