<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    header("Location: ../login.php");
    exit;
}

// Update staff query to count tasks from assigned_maintenance
$staff_query = "SELECT 
    la.username,
    la.is_online,
    e.name as employee_name,
    e.emp_id,
    e.role,
    COUNT(DISTINCT am.maintenance_request_id) as total_tasks,
    COUNT(DISTINCT CASE WHEN mr.status = 'Completed' THEN am.maintenance_request_id END) as completed_tasks,
    COUNT(DISTINCT CASE WHEN mr.status = 'In Progress' THEN am.maintenance_request_id END) as ongoing_tasks
    FROM login_accounts la
    LEFT JOIN employee e ON la.emp_id = e.emp_id
    LEFT JOIN assigned_maintenance am ON e.emp_id = am.emp_id
    LEFT JOIN maintenance_requests mr ON am.maintenance_request_id = mr.id
    WHERE la.user_type = 'Maintenance-Staff'
    GROUP BY la.username, la.is_online, e.name, e.emp_id, e.role";
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
    <style>
        .staff-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .staff-card:hover {
            transform: scale(1.02);
        }
        .task-photo {
            max-width: 100%;
            height: auto;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            .table td, .table th {
                min-width: 100px;
                max-width: 200px;
                white-space: normal;
                word-wrap: break-word;
            }
            .table td:last-child, .table th:last-child {
                min-width: 150px;
            }
        }
        .task-card {
            margin-bottom: 0.5rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .task-card .card-body {
            padding: 0.75rem;
        }
        .task-id {
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
        }
        .task-date {
            font-size: 0.8rem;
            color: #888;
        }
        .task-card .card-text {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .task-card .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .photo-modal-content {
            max-width: 90vw;
            max-height: 90vh;
        }
        .photo-modal-img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        .task-table {
            width: 100%;
            margin-bottom: 0;
        }
        .task-table th {
            background: #f8f9fa;
            font-size: 0.9rem;
        }
        .task-table td, .task-table th {
            padding: 0.5rem;
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .task-table td, .task-table th {
                font-size: 0.85rem;
                padding: 0.4rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h5 class="mb-4 fw-semibold mt-4">Maintenance Staff Overview</h5>
        
        <div class="row">
            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="staff-card card" onclick="viewCompletedTasks('<?php echo $staff['emp_id']; ?>')">
                        <div class="staff-info">
                            <div class="staff-name">
                                <span class="online-indicator <?php echo $staff['is_online'] ? 'online' : 'offline'; ?>"></span>
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($staff['employee_name']); ?>
                                <small class="text-muted d-block">ID: <?php echo htmlspecialchars($staff['emp_id']); ?></small>
                                <small class="text-muted d-block" style="font-size: 0.85rem;"> <?php echo htmlspecialchars($staff['role']); ?></small>
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

    <!-- Modal for completed tasks -->
    <div class="modal fade" id="tasksModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Completed Tasks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <div id="completedTasksList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content photo-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Task Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="photoModalImg" src="" alt="Task photo" class="photo-modal-img">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let photoModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
        });

        function viewPhoto(photoUrl) {
            document.getElementById('photoModalImg').src = photoUrl;
            photoModal.show();
        }

        function viewCompletedTasks(empId) {
            fetch(`get_completed_tasks.php?emp_id=${encodeURIComponent(empId)}`)
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <table class="table task-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Completed At</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Photo</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    
                    data.forEach(task => {
                        html += `
                            <tr>
                                <td>${task.maintenance_request_id}</td>
                                <td>${task.completed_at}</td>
                                <td>${task.remarks}</td>
                                <td class="text-center">
                                    ${task.photo ? 
                                        `<button class="btn btn-sm btn-outline-primary" onclick="viewPhoto('${task.photo}')">
                                            <i class="fas fa-image"></i>
                                        </button>` : 
                                        '<small class="text-muted">-</small>'
                                    }
                                </td>
                            </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    document.getElementById('completedTasksList').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('tasksModal')).show();
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
