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

// Update staff query to join with employee table
$staff_query = "SELECT l.username, l.emp_id, e.name 
                FROM login_accounts l
                JOIN employee e ON l.emp_id = e.emp_id 
                WHERE l.user_type = 'Maintenance-Staff'";
$staff_result = $conn->query($staff_query);
$maintenance_staff = [];
$staff_display = [];
while ($staff = $staff_result->fetch_assoc()) {
    $maintenance_staff[] = $staff['emp_id'];
    // Format: Name (Employee ID)
    $staff_display[$staff['emp_id']] = $staff['name'] . ' (' . $staff['emp_id'] . ')';
}

// Modify the requests query to include employee names
$requests_query = "SELECT mr.*, 
                    GROUP_CONCAT(DISTINCT e.name, ' (', am.emp_id, ')') as assigned_employees,
                    GROUP_CONCAT(DISTINCT am.emp_id) as assigned_emp_ids
                  FROM maintenance_requests mr 
                  LEFT JOIN assigned_maintenance am ON mr.id = am.maintenance_request_id
                  LEFT JOIN employee e ON am.emp_id = e.emp_id 
                  WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $requests_query .= " AND (mr.request_title LIKE '%$search%' 
                        OR mr.description LIKE '%$search%' 
                        OR mr.room_no LIKE '%$search%')";
}
if (!empty($priority_filter)) {
    $priority_filter = $conn->real_escape_string($priority_filter);
    $requests_query .= " AND mr.priority = '$priority_filter'";
}
if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $requests_query .= " AND mr.status = '$status_filter'";
}
$requests_query .= " GROUP BY mr.id ORDER BY mr.created_at DESC";
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
    <link rel="stylesheet" href="maintenance.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>

</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mt-4">
        <h5 class="mb-4 fw-semibold">Maintenance Requests</h5>
        
        <!-- Statistics -->
        <div class="stats-container p-0">
            <div class="stats-card">
                <div class="stats-label fw-semibold">Total Requests</div>
                <div class="stats-number"><?php echo $stats['total_count']; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label fw-semibold">In Progress</div>
                <div class="stats-number"><?php echo $stats['working_count']; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label fw-semibold">Completed</div>
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
                    <button type="button" class="btn btn-filter text-light" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                        <i class="fas fa-filter text-light"></i> Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Offcanvas -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
            <div class="offcanvas-header">
                <h6 class="offcanvas-title fw-semibold" id="filterOffcanvasLabel">Filter Requests</h6>
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
                        <button type="submit" class="btn btn-apply-filters">Apply</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset-filters">Reset</a>
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
                            <?php if ($request['needs_assistance'] == 1): ?>
                                <div class="alert alert-warning mb-3" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    This task requires assistance!
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">Request #<?php echo $request['id']; ?></h5>
                                    <p class="mb-1"><strong>Room:</strong> <?php echo $request['room_no']; ?></p>
                                    <p class="mb-1"><strong>Title:</strong> <?php echo $request['request_title']; ?></p>
                                    <p class="mb-1"><strong>Description:</strong> <?php echo $request['description']; ?></p>
                                    <p class="mb-1">
                                        <strong>Assigned to:</strong>
                                        <?php 
                                        $assigned_ids = explode(',', $request['assigned_emp_ids']);
                                        $assigned_names = explode(',', $request['assigned_employees']);
                                        if (!empty($assigned_names[0])) {
                                            echo '<div class="assigned-employees">';
                                            foreach($assigned_names as $emp) {
                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($emp) . '</span>';
                                            }
                                            echo '</div>';
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
                                        if ($request['status'] === 'Completed') {
                                            echo 'completed';
                                        } elseif ($request['status'] === 'In Progress') {
                                            echo 'working';
                                        } else {
                                            echo 'pending';
                                        }
                                    ?> mb-2">
                                        <?php echo $request['status']; ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        if ($request['priority'] === 'High') {
                                            echo 'danger';
                                        } elseif ($request['priority'] === 'Medium') {
                                            echo 'warning';
                                        } else {
                                            echo 'success';
                                        }
                                    ?>">
                                        Priority: <?php echo $request['priority']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($request['status'] !== 'Completed'): ?>
                                <div class="mt-3 action-buttons">
                                    <div class="dropdown staff-dropdown">
                                        <button class="btn dropdown-toggle <?php echo !empty($assigned_ids[0]) ? 'assigned' : ''; ?>" 
                                                type="button" 
                                                id="staffDropdown_<?php echo $request['id']; ?>" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Assign Staff
                                        </button>
                                        <div class="dropdown-menu p-2" style="width: 250px;" aria-labelledby="staffDropdown_<?php echo $request['id']; ?>">
                                            <div class="mb-2">
                                                <small class="text-muted">Select multiple staff members:</small>
                                            </div>
                                            <?php foreach ($maintenance_staff as $staff): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input staff-checkbox" 
                                                           type="checkbox" 
                                                           value="<?php echo htmlspecialchars($staff); ?>"
                                                           id="staff_<?php echo $request['id'] . '_' . $staff; ?>"
                                                           <?php echo in_array($staff, $assigned_ids) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="staff_<?php echo $request['id'] . '_' . $staff; ?>">
                                                        <?php echo htmlspecialchars($staff_display[$staff]); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-primary w-100" 
                                                        onclick="assignMultipleEmployees(<?php echo $request['id']; ?>)">
                                                    Update Assignments
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!$request['schedule']): ?>
                                    <button class="btn btn-schedule" onclick="scheduleTask(<?php echo $request['id']; ?>, '<?php echo $request['assigned_employees']; ?>')">
                                        <i class="fas fa-calendar-plus me-2"></i>Schedule
                                    </button>
                                    <?php endif; ?>
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
        <div class="modal-dialog modal-dialog-centered">
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
                    <button type="button" class="btn btn-scheduleSave" onclick="submitSchedule()">Save Schedule</button>
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

        function assignMultipleEmployees(requestId) {
            const checkboxes = document.querySelectorAll(`input[id^=staff_${requestId}_]:checked`);
            const employees = Array.from(checkboxes).map(cb => cb.value);
            
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=${requestId}&employees=${JSON.stringify(employees)}&status=In Progress`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Failed to update assignments');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update assignments');
            });
        }
    </script>
</body>
</html>