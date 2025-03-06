<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    header("Location: ../login.php");
    exit;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get maintenance request statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as working_count,
    COUNT(*) as total_count
    FROM maintenance_requests";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get maintenance staff
$staff_query = "SELECT username FROM login_accounts WHERE user_type = 'Maintenance-Staff'";
$staff_result = $conn->query($staff_query);
$maintenance_staff = [];
while ($staff = $staff_result->fetch_assoc()) {
    $maintenance_staff[] = $staff['username'];
}

// Modify the requests query to include search and filter
$requests_query = "SELECT * FROM maintenance_requests WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $requests_query .= " AND (request_title LIKE '%$search%' 
                        OR description LIKE '%$search%' 
                        OR room_no LIKE '%$search%')";
}
if (!empty($priority_filter)) {
    $priority_filter = $conn->real_escape_string($priority_filter);
    $requests_query .= " AND priority = '$priority_filter'";
}
if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $requests_query .= " AND status = '$status_filter'";
}
$requests_query .= " ORDER BY created_at DESC";
$requests = $conn->query($requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 0 10px;
        }
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
        @media (max-width: 768px) {
            .stats-container {
                padding: 0 5px;
            }
            .stats-label {
                font-size: 0.8rem;
            }
            .stats-number {
                font-size: 1.2rem;
            }
        }
        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            .stats-card {
                padding: 10px 5px;
            }
        }
        .request-card {
            border-left: 4px solid;
            margin-bottom: 20px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .request-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .priority-High { border-left-color: #dc3545; }
        .priority-Medium { border-left-color: #ffc107; }
        .priority-Low { border-left-color: #198754; }
        
        .badge.bg-completed { background-color: #198754 !important; }
        .badge.bg-working { background-color: #ffc107 !important; color: black !important; }
        .badge.bg-pending { background-color: #6c757d !important; }

        .search-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .search-container .form-control:focus,
        .search-container .form-select:focus {
            box-shadow: none;
            border-color: #198754;
        }
        .search-container .btn-filter {
            background-color: #198754;
            color: white;
        }
        .search-container .btn-reset {
            background-color: #6c757d;
            color: white;
        }
        .btn-filter {
            background-color: #198754;
            color: white;
            min-width: 100px;
        }
        .btn-filter:hover {
            background-color: #157347;
            color: white;
        }
        .offcanvas {
            border-left: 4px solid #198754;
        }
        .filter-section {
            margin-bottom: 1.5rem;
        }
        .filter-section h5 {
            color: #198754;
            margin-bottom: 1rem;
        }
        .btn-apply-filters {
            background-color: #198754;
            color: white;
            width: 100%;
            padding: 10px;
        }
        .btn-reset-filters {
            background-color: #6c757d;
            color: white;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        .staff-dropdown .dropdown-toggle {
            min-width: 200px;
            text-align: left;
            background-color: #fff;
            border: 1px solid #dee2e6;
            color: #212529;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .staff-dropdown .dropdown-toggle.assigned {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }
        .staff-dropdown .dropdown-item.active {
            background-color: #198754;
            color: white;
        }
        .staff-dropdown .dropdown-item i {
            width: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }
        .btn-schedule {
            background-color: #6f42c1;
            color: white;
        }
        .btn-schedule:hover {
            background-color: #5a32a3;
            color: white;
        }
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

        <!-- Search and Filter Controls -->
        <div class="search-container">
            <form method="GET" class="row g-3 align-items-center" id="searchForm">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" id="searchInput"
                               placeholder="Search requests..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-filter" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Offcanvas -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="filterOffcanvasLabel">Filter Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form method="GET" id="filterForm">
                    <!-- Keep the search value when filter is applied -->
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    
                    <div class="filter-section">
                        <h5>Priority</h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="priority" id="priorityAll" value="" <?php echo $priority_filter === '' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="priorityAll">All Priorities</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="priority" id="priorityHigh" value="High" <?php echo $priority_filter === 'High' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="priorityHigh">High Priority</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="priority" id="priorityMedium" value="Medium" <?php echo $priority_filter === 'Medium' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="priorityMedium">Medium Priority</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="priority" id="priorityLow" value="Low" <?php echo $priority_filter === 'Low' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="priorityLow">Low Priority</label>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h5>Status</h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="status" id="statusAll" value="" <?php echo $status_filter === '' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusAll">All Status</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="status" id="statusPending" value="Pending" <?php echo $status_filter === 'Pending' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusPending">Pending</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="status" id="statusInProgress" value="In Progress" <?php echo $status_filter === 'In Progress' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusInProgress">In Progress</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="status" id="statusCompleted" value="Completed" <?php echo $status_filter === 'Completed' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusCompleted">Completed</label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-apply-filters">Apply Filters</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset-filters">Reset Filters</a>
                    </div>
                </form>
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
                                    <p class="mb-1"><strong>Priority:</strong> <?php echo $request['priority']; ?></p>
                                    <p class="mb-1">
                                        <strong>Being worked by:</strong>
                                        <?php 
                                        if ($request['workon']) {
                                            echo htmlspecialchars($request['workon']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </p>
                                    <?php if ($request['schedule']): ?>
                                    <p class="mb-1">
                                        <strong>Scheduled:</strong>
                                        <span class="text-success">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('M d, Y h:i A', strtotime($request['schedule'])); ?>
                                        </span>
                                    </p>
                                    <?php endif; ?>
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
                                <div class="mt-3 action-buttons">
                                    <div class="dropdown staff-dropdown">
                                        <button class="btn dropdown-toggle <?php echo $request['workon'] ? 'assigned' : ''; ?>" 
                                                type="button" 
                                                id="staffDropdown_<?php echo $request['id']; ?>" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="fas fa-user-plus me-2"></i>
                                            <?php echo $request['workon'] ? 'Assigned to: ' . htmlspecialchars($request['workon']) : 'Assign to Staff'; ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="staffDropdown_<?php echo $request['id']; ?>">
                                            <?php foreach ($maintenance_staff as $staff): ?>
                                                <li>
                                                    <a class="dropdown-item <?php echo $request['workon'] === $staff ? 'active' : ''; ?>" 
                                                       href="#" 
                                                       onclick="assignEmployee(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($staff, ENT_QUOTES); ?>')">
                                                        <i class="fas fa-user"></i>
                                                        <?php echo htmlspecialchars($staff); ?>
                                                        <?php if ($request['workon'] === $staff): ?>
                                                            <i class="fas fa-check float-end mt-1"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <button class="btn btn-schedule" onclick="scheduleTask(<?php echo $request['id']; ?>, '<?php echo $request['workon'] ? htmlspecialchars($request['workon']) : ''; ?>')">
                                        <i class="fas fa-calendar-plus me-2"></i>Schedule
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

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Schedule Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm">
                        <input type="hidden" id="requestId">
                        <input type="hidden" id="employeeId">
                        <div class="mb-3">
                            <label for="scheduleDateTime" class="form-label">Schedule Date and Time</label>
                            <input type="datetime-local" class="form-control" id="scheduleDateTime" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-schedule" onclick="submitSchedule()">Save Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p class="mb-0">Task has been successfully scheduled for:</p>
                    <p class="fw-bold fs-5 mb-0" id="scheduledDateTime"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function assignEmployee(requestId, employee) {
            if (!employee) return;
            
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}&employee=${employee}&status=In Progress`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
            });
        }

        // Existing updateStatus function
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

        // Add auto-search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                // Clear the previous timeout
                clearTimeout(searchTimeout);

                // Set a new timeout to delay the search
                searchTimeout = setTimeout(() => {
                    // Get current URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    
                    // Update search parameter
                    if (this.value) {
                        urlParams.set('search', this.value);
                    } else {
                        urlParams.delete('search');
                    }
                    
                    // Preserve other filters if they exist
                    const newUrl = window.location.pathname + '?' + urlParams.toString();
                    window.location.href = newUrl;
                }, 500); // 500ms delay after user stops typing
            });
        });

        function scheduleTask(requestId, employee) {
            if (!employee) {
                alert('Please assign a staff member first before scheduling.');
                return;
            }
            
            // Set values in modal
            document.getElementById('requestId').value = requestId;
            document.getElementById('employeeId').value = employee;
            
            // Show modal
            const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            scheduleModal.show();
        }

        function submitSchedule() {
            const requestId = document.getElementById('requestId').value;
            const employee = document.getElementById('employeeId').value;
            const scheduleDate = document.getElementById('scheduleDateTime').value;

            if (!scheduleDate) {
                alert('Please select a date and time');
                return;
            }

            updateSchedule(requestId, employee, scheduleDate);
            
            // Hide modal
            const scheduleModal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
            scheduleModal.hide();
        }

        function updateSchedule(requestId, employee, scheduleDate) {
            fetch('schedule-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}&employee=${employee}&schedule=${scheduleDate}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Format date for display
                    const formattedDate = new Date(scheduleDate).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
                    });
                    
                    // Update success modal and show it
                    document.getElementById('scheduledDateTime').textContent = formattedDate;
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Reload page after modal is closed
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                        location.reload();
                    });
                } else {
                    alert('Failed to schedule task: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error scheduling task: ' + error);
            });
        }
    </script>
</body>
</html>