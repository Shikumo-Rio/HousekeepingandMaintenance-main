<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/roomservice.css">   
    <link rel="icon" href="img/logo.webp">
    <title>Room Service</title>
    <style>
        /* Animation for status change */
        @keyframes statusChangeAnimation {
            0% { transform: scale(0.8); opacity: 0.5; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .status-change-animation {
            animation: statusChangeAnimation 1.2s ease-in-out;
        }
        
        /* Fade out animation for cards being removed */
        @keyframes fadeOutAnimation {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(0.8); }
        }
        
        .fade-out-animation {
            animation: fadeOutAnimation 0.8s ease-out forwards;
        }
        
        /* Movement animation between columns */
        @keyframes moveInAnimation {
            0% { opacity: 0; transform: translateY(-20px) scale(0.8); }
            70% { transform: translateY(5px) scale(1.05); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .move-in-animation {
            animation: moveInAnimation 1.2s ease-in-out;
        }
        
        /* Highlight color based on status */
        .status-highlight-pending { box-shadow: 0 0 8px 2px rgba(255, 193, 7, 0.7); }
        .status-highlight-working { box-shadow: 0 0 8px 2px rgba(0, 123, 255, 0.7); }
        .status-highlight-complete { box-shadow: 0 0 8px 2px rgba(40, 167, 69, 0.7); }
        .status-highlight-invalid { box-shadow: 0 0 8px 2px rgba(220, 53, 69, 0.7); }
    </style>
</head>
<body>
    <?php include('index.php'); ?>
    <?php
    require_once 'PHP_AItask/allocate_tasks.php';

    // Add this function near your other functions
    function checkAndAllocateTasks($conn) {
        // Check if there are pending tasks
        $pendingQuery = "SELECT COUNT(*) as pending FROM customer_messages WHERE status = 'pending'";
        $result = mysqli_query($conn, $pendingQuery);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['pending'] > 0) {
            // Call the AI allocation function
            allocateTasks($conn);
        }
    }

    // Add this where you handle form submissions or page loads
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ...existing form handling code...
        
        // Check for tasks to allocate after form submission
        checkAndAllocateTasks($conn);
    }

    // Also add this at the end of your page load
    checkAndAllocateTasks($conn);
    ?>

    <div class="container mt-2">
    <div class="card p-3 room-service-heading mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0 ms-2">Housekeeping Monitoring Panel</h3>
            <div class="d-flex">
                <button id="viewToggle" class="btn btn-table btn-sm d-flex align-items-center justify-content-center me-2 mt-0">
                    <i class="bi bi-table mb-2"></i>
                </button>
                <button class="btn btn-add btn-sm d-flex align-items-center justify-content-center mt-0" 
                        style="width: 40px; height: 40px; border-radius: 50%; border: none; " 
                        data-bs-toggle="modal" data-bs-target="#requestModal">
                    <i class="bx bx-plus-circle fs-3 mb-0 mt-0 "></i>
                </button>
            </div>
        </div>
    </div>
    <div class="row m-0">
    <div class="col-md-8">
        <div class="row mt-4" id="roomServiceCards">

        <!-- Pending Section -->
        <div class="col-md-3 status-column" data-status="pending">
            <h4 class="status-title p-2 text-light d-inline-flex">Pending</h4>
            <div class="status-grid">
                <?php
                // Fetch pending tasks from database
                $pendingTasks = $conn->query("SELECT * FROM customer_messages WHERE status = 'pending' LIMIT 5");
                if ($pendingTasks->num_rows > 0):
                    while ($task = $pendingTasks->fetch_assoc()):
                ?>
                    <div class="status-card card mb-0" data-task-id="<?php echo $task['id']; ?>" onclick="showDetails(<?php echo $task['id']; ?>)">
                        <p>ID: <?php echo $task['id']; ?></p>
                        <p>Name: <?php echo htmlspecialchars($task['uname']); ?></p>
                        <p>Type: <?php echo htmlspecialchars($task['request']); ?></p>
                        <p>Date: <?php echo $task['created_at']; ?></p>
                    </div>
                    <?php
                        endwhile;
                    else:
                        echo "<p>No pending tasks.</p>";
                    endif;
                    ?>
            </div>
        </div>

        <!-- Working Section -->
        <div class="col-md-3 status-column" data-status="working">
            <h4 class="status-title p-2 text-light d-inline-flex">Working</h4>
            <div class="status-grid">
                <?php
                // Fetch working tasks from database
                $workingTasks = $conn->query("SELECT * FROM customer_messages WHERE status = 'working' LIMIT 5");
                if ($workingTasks->num_rows > 0):
                    while ($task = $workingTasks->fetch_assoc()):
                ?>
                    <div class="status-card card mb-0" data-task-id="<?php echo $task['id']; ?>" onclick="showDetails(<?php echo $task['id']; ?>)">
                        <p>ID: <?php echo $task['id']; ?></p>
                        <p>Name: <?php echo htmlspecialchars($task['uname']); ?></p>
                        <p>Type: <?php echo htmlspecialchars($task['request']); ?></p>
                        <p>Date: <?php echo $task['created_at']; ?></p>
                    </div>
                    <?php
                    endwhile;
                else:
                    echo "<p>No working tasks.</p>";
                endif;
                ?>
            </div>
        </div>

        <!-- Completed Section -->
        <div class="col-md-3 status-column" data-status="complete">
            <h4 class="status-title p-2 text-light d-inline-flex">Completed</h4>
            <div class="status-grid">
                <?php
                // Fetch completed tasks from database with DESC order
                $completedTasks = $conn->query("SELECT * FROM customer_messages WHERE status = 'complete' ORDER BY created_at DESC LIMIT 5");
                if ($completedTasks->num_rows > 0):
                    while ($task = $completedTasks->fetch_assoc()):
                ?>
                    <div class="status-card card mb-0" data-task-id="<?php echo $task['id']; ?>" onclick="showDetails(<?php echo $task['id']; ?>)">
                        <p>ID: <?php echo $task['id']; ?></p>
                        <p>Name: <?php echo htmlspecialchars($task['uname']); ?></p>
                        <p>Type: <?php echo htmlspecialchars($task['request']); ?></p>
                        <p>Date: <?php echo $task['created_at']; ?></p>
                    </div>
                    <?php
                        endwhile;
                    else:
                        echo "<p>No completed tasks.</p>";
                    endif;
                    ?>
            </div>
        </div>

        <!-- Invalid Section -->
        <div class="col-md-3 status-column" data-status="invalid">
            <h4 class="status-title p-2 text-light d-inline-flex">Invalid</h4>
            <div class="status-grid">
                <?php
                // Fetch invalid tasks from database with DESC order
                $invalidTasks = $conn->query("SELECT * FROM customer_messages WHERE status = 'invalid' ORDER BY created_at DESC LIMIT 5");
                if ($invalidTasks->num_rows > 0):
                    while ($task = $invalidTasks->fetch_assoc()):
                ?>
                    <div class="status-card card mb-0" data-task-id="<?php echo $task['id']; ?>" onclick="showDetails(<?php echo $task['id']; ?>)">
                        <p>ID: <?php echo $task['id']; ?></p>
                        <p>Name: <?php echo htmlspecialchars($task['uname']); ?></p>
                        <p>Type: <?php echo htmlspecialchars($task['request']); ?></p>
                        <p>Date: <?php echo $task['created_at']; ?></p>
                    </div>
                    <?php
                        endwhile;
                    else:
                        echo "<p>No invalid tasks.</p>";
                    endif;
                    ?>
            </div>
        </div>
    </div>
</div>

<!-- Table View (initially hidden) -->
<div id="tableView" class="col-md-8" style="display: none;">
    <div class="card mt-4">
        <div class="card-body table-view">
            <!-- Add search input -->
            <div class="mb-3">
                <input type="text" id="searchTable" class="form-control" placeholder="Search tasks...">
            </div>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Request</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $allTasks = $conn->query("SELECT * FROM customer_messages ORDER BY created_at DESC");
                    while ($task = $allTasks->fetch_assoc()):
                    ?>
                    <tr class="task-row" data-task-id="<?php echo $task['id']; ?>" onclick="showDetails(<?php echo $task['id']; ?>)">
                        <td><?php echo $task['id']; ?></td>
                        <td><?php echo htmlspecialchars($task['uname']); ?></td>
                        <td><?php echo htmlspecialchars($task['request']); ?></td>
                        <td><?php echo htmlspecialchars($task['room']); ?></td>
                        <td><span class="badge bg-<?php echo getStatusColor($task['status']); ?>"><?php echo ucfirst($task['status']); ?></span></td>
                        <td><?php echo $task['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="d-flex justify-content-center p-2"></div>
    </div>
</div>

<!-- Right Section for Task Details -->
    <div class="col-md-4 mt-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h6 class="mt-2 fw-semibold">Task Details</h6>
            </div>
            <div class="card-body m-0" id="taskDetailsContent">
                <p>Select a task to see details.</p>
            </div>

            <!-- Action buttons for task at the bottom -->
            <div class="d-flex justify-content-center p-2 mt-2">
                <!-- Change Status Dropdown -->
                <div class="dropdown me-2 mb-2">
                    <button class="btn dropdown-toggle" type="button" id="changeStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Change Status
                    </button>
                    <ul class="dropdown-menu" style="font-size: 12px;" aria-labelledby="changeStatusDropdown">
                        <li><a class="dropdown-item" href="#" onclick="changeStatus('complete')">Complete</a></li>
                        <li><a class="dropdown-item" href="#" onclick="changeStatus('invalid')">Invalid</a></li>
                    </ul>
                </div>

                <!-- Assign Employee Dropdown -->
                <div class="dropdown mb-2">
                    <button class="btn dropdown-toggle" type="button" id="assignEmployeeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Assign Employee
                    </button>
                    <ul class="dropdown-menu" style="font-size: 12px;" aria-labelledby="assignEmployeeDropdown">
                        <?php
                        // Fetch employees for assignment
                        $employeeQuery = "SELECT emp_id, name FROM employee";
                        $result = $conn->query($employeeQuery);
                        while ($row = $result->fetch_assoc()) {
                            echo '<li><a class="dropdown-item" href="#" onclick="assignEmployee(' . $row['emp_id'] . ', \'' . htmlspecialchars($row['name'], ENT_QUOTES) . '\')">' . htmlspecialchars($row['name']) . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
        </div>
    </div>
</div>


    <!-- Toast notification container -->
    <div id="assignmentToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <small class="text-muted">Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <span id="toastMessage"></span>
            <span id="toastStatus"></span>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered p-0 w-25">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold" id="successModalLabel">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p class="mb-0">Request has been successfully added!</p>
                    <p class="fw-semibold fs-6 mb-4" id="requestDetails"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold" id="requestModalLabel">
                        <i class="bx bx-edit-alt me-2"></i> New Service Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-3">
                    <form id="requestForm" method="POST" action="func/add_service_request.php">
                        
                        <!-- Guest Name -->
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control rounded-3 shadow-sm" style="font-size: 12px;" id="uname" name="uname" required>
                            <label for="uname">Guest Name</label>
                        </div>

                        <!-- Room Number -->
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control rounded-3 shadow-sm" style="font-size: 12px;" id="room" name="room" required>
                            <label for="room">Room Number</label>
                        </div>

                        <!-- Request Type Selection -->
                        <div class="form-floating mb-3">
                            <select class="form-select rounded-3 shadow-sm" style="font-size: 12px;" id="requestType" onchange="handleRequestTypeChange()">
                                <option value="predefined" selected>Select from common types</option>
                                <option value="custom">Enter custom request type</option>
                            </select>
                            <label for="requestType">Request Type</label>
                        </div>

                        <!-- Predefined Request Dropdown -->
                        <div class="form-floating mb-3" id="predefinedRequestContainer">
                            <select class="form-select rounded-3 shadow-sm" style="font-size: 12px;" id="predefinedRequest" name="request" required>
                                <option value="" selected disabled>Select request type</option>
                                <option value="Room Cleaning">Room Cleaning</option>
                                <option value="Towel Service">Towel Service</option>
                                <option value="Bed Making">Bed Making</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                            <label for="predefinedRequest">Select Request</label>
                        </div>

                        <!-- Custom Request Input (Initially Hidden) -->
                        <div class="form-floating mb-3" id="customRequestContainer" style="display: none;">
                            <input type="text" class="form-control rounded-3 shadow-sm" style="font-size: 12px;" id="customRequest" name="custom_request">
                            <label for="customRequest">Enter Custom Request</label>
                        </div>

                        <!-- Details -->
                        <div class="form-floating mb-3">
                            <textarea class="form-control rounded-3 shadow-sm" style="font-size: 12px;" id="details" name="details" style="height: 100px;" required></textarea>
                            <label for="details">Details</label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary px-2 rounded-3" style="font-size: 12px;" data-bs-dismiss="modal">
                                <i class="bx bx-x-circle me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success px-2 rounded-3" style="font-size: 12px;">
                                <i class="bx bx-send me-1"></i> Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <script>
    // Add this to check for success parameter in URL and show modal
    document.addEventListener('DOMContentLoaded', function() {
        // Parse URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const message = urlParams.get('message');
        
        // If there's a success status in the URL
        if (status === 'success') {
            // Set request details message
            document.getElementById('requestDetails').textContent = message || 'Request added successfully';
            
            // Show the success modal
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            
            // Hide the modal after 2 seconds
            setTimeout(() => {
                successModal.hide();
            }, 5000);
            
            // Clean the URL without refreshing the page
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: newUrl}, '', newUrl);
        }
    });
    
    // Function to handle switching between predefined and custom request types
    function handleRequestTypeChange() {
        const requestType = document.getElementById('requestType').value;
        const predefinedRequestField = document.getElementById('predefinedRequest');
        const customRequestField = document.getElementById('customRequest');
        
        if (requestType === 'predefined') {
            predefinedRequestField.style.display = 'block';
            customRequestField.style.display = 'none';
            predefinedRequestField.name = 'request';
            customRequestField.name = 'custom_request';
            predefinedRequestField.required = true;
            customRequestField.required = false;
        } else {
            predefinedRequestField.style.display = 'none';
            customRequestField.style.display = 'block';
            predefinedRequestField.name = 'predefined_request';
            customRequestField.name = 'request';
            predefinedRequestField.required = false;
            customRequestField.required = true;
        }
    }
    
    let currentTaskId; // Global variable to hold the current task ID// 
    // Function to show task details
    function showDetails(taskId) {
    // Fetch task details using AJAX
    fetch(`func/get_task_details.php?id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            const taskDetailsContent = document.getElementById('taskDetailsContent');

            // Populate the details in the UI
            taskDetailsContent.innerHTML = `
                <p><strong>ID:</strong> ${data.id}</p>
                <p><strong>Name:</strong> ${data.uname}</p>
                <p><strong>Request:</strong> ${data.request}</p>
                <p><strong>Details:</strong> ${data.details}</p>
                <p><strong>Room:</strong> ${data.room}</p>
                <p><strong>Date:</strong> ${data.created_at}</p>
            `;

            // Add data-* attributes for easier access
            taskDetailsContent.dataset.uname = data.uname;
            taskDetailsContent.dataset.room = data.room;
            taskDetailsContent.dataset.request = data.request;
            taskDetailsContent.dataset.details = data.details;
        })
        .catch(error => console.error('Error fetching task details:', error));
}

    function setCurrentTaskId(taskId) {
        currentTaskId = taskId; // Set the current task ID
        console.log("Current Task ID set to:", currentTaskId); // Debugging line
    }

    function assignEmployee(empId, empName) {
    const taskId = currentTaskId; // Ensure currentTaskId is set properly
    const taskDetailsContent = document.querySelector('#taskDetailsContent');
    
    const taskDetails = {
        task_id: taskId, // Match the backend's expected key names
        emp_id: empId,   // Match the backend's expected key names
        emp_name: empName, // Include empName to match the backend
        uname: document.querySelector('#taskDetailsContent').dataset.uname || 'Guest', // Example placeholder
        room: document.querySelector('#taskDetailsContent').dataset.room || 'N/A',    // Example placeholder
        request: document.querySelector('#taskDetailsContent').dataset.request || 'N/A', // Example placeholder
        details: document.querySelector('#taskDetailsContent').dataset.details || 'N/A', // Example placeholder
    };

    fetch('func/assign_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskDetails)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show toast notification with success styling
            showToast(`Task #${taskId} successfully assigned to ${empName}.`, 'bg-success');
            
            // Create notification for the assigned employee
            createAssignmentNotification(empId, taskId, taskDetails.request, empName, taskDetails.uname, taskDetails.room);
            
            // Use a timeout to allow the toast to be seen before reload
            setTimeout(() => {
                location.reload(); // Reload the page to reflect changes
            }, 1500);
        } else {
            // Show error toast
            showToast(`Error: ${data.error}`, 'bg-danger');
        }
    })
    .catch(error => {
        console.error("Error during task assignment:", error);
        showToast("An error occurred during task assignment.", 'bg-danger');
    });
}

    // Function to create a notification entry in the database
    function createAssignmentNotification(empId, taskId, requestType, empName, guestName, roomNumber) {
        // Create message for notification
        const message = `New task assigned: ${requestType} for room ${roomNumber}`;
        
        // Create notification data object
        const notificationData = {
            emp_id: empId,
            message: message,
            link: `roomservice.php?task_id=${taskId}`,
            item_name: requestType,
            notif_type: 'task_assignment',
            is_read: 0,
            task_id: taskId
        };
        
        // Send notification data to server
        fetch('func/create_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(notificationData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Notification created successfully');
            } else {
                console.error('Failed to create notification:', data.error);
            }
        })
        .catch(error => {
            console.error('Error creating notification:', error);
        });
    }

    // Function to change task status
    function changeStatus(newStatus) {
    const taskId = document.querySelector('.status-card.selected')?.dataset.taskId;

    if (!taskId) {
        alert('Please select a task first.');
        return;
    }

    // Debugging: Check the task ID and new status before making the fetch call
    console.log("Changing status for Task ID:", taskId, "to:", newStatus);

    fetch('func/change_task_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: taskId, status: newStatus }),
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data); // Debugging: See what the server is returning

        if (data.success) {
            // Show toast notification with animation for better visibility
            showToast(`Task ${taskId} status changed to ${newStatus}.`, 'bg-success');
            
            // Use a timeout to allow the toast to be seen before reload
            setTimeout(() => {
                location.reload(); // Reload the page to reflect changes
            }, 1000);
        } else {
            showToast(`Failed to change status: ${data.error || 'Unknown error'}`, 'bg-danger');
        }
    })
    .catch(error => {
        console.error('Error changing task status:', error);
        showToast("An error occurred while changing status. Please try again later.", 'bg-danger');
    });
}


    // Function to show toast notifications
    function showToast(message, bgClass = '') {
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.innerHTML = message;
        
        const toast = document.getElementById('assignmentToast');
        
        // Apply background class if provided
        if (bgClass) {
            toast.classList.add(bgClass);
        }
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove background class after toast is hidden
        toast.addEventListener('hidden.bs.toast', function () {
            if (bgClass) {
                toast.classList.remove(bgClass);
            }
        }, { once: true });
    }

    let taskStatusMap = {};
    
    // Function to build initial status map
    function buildInitialStatusMap() {
        document.querySelectorAll('.status-card').forEach(card => {
            const taskId = card.dataset.taskId;
            const statusColumn = card.closest('.status-column');
            if (taskId && statusColumn) {
                taskStatusMap[taskId] = statusColumn.dataset.status;
            }
        });
        console.log("Initial status map:", taskStatusMap);
    }
    
    // Call this function on initial page load
    buildInitialStatusMap();
    
    // Keep track of whether an animation is in progress
    let animationInProgress = false;
    
    // Modified function to refresh task cards with improved animation control
    function refreshTaskCards() {
        // If an animation is already in progress, delay this refresh
        if (animationInProgress) {
            console.log("Animation in progress, skipping this refresh cycle");
            return;
        }
        
        // Store current state of cards before refresh
        const currentCards = {};
        document.querySelectorAll('.status-card').forEach(card => {
            const taskId = card.dataset.taskId;
            const statusColumn = card.closest('.status-column');
            if (taskId && statusColumn) {
                currentCards[taskId] = {
                    status: statusColumn.dataset.status,
                    element: card
                };
            }
        });
        
        // Fetch updated cards
        fetch('func/get_task_cards.php')
            .then(response => response.text())
            .then(html => {
                // Create temporary div to parse the new HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract new cards data
                const newCards = {};
                const movedCards = {};
                let hasMovedCards = false;
                
                tempDiv.querySelectorAll('.status-card').forEach(card => {
                    const taskId = card.dataset.taskId;
                    const statusColumn = card.closest('.status-column');
                    if (taskId && statusColumn) {
                        newCards[taskId] = {
                            status: statusColumn.dataset.status,
                            element: card
                        };
                        
                        // Check if card has moved
                        if (currentCards[taskId] && currentCards[taskId].status !== newCards[taskId].status) {
                            movedCards[taskId] = {
                                from: currentCards[taskId].status,
                                to: newCards[taskId].status
                            };
                            hasMovedCards = true;
                            
                            // Add animation class to the new card
                            card.classList.add('move-in-animation');
                            card.classList.add(`status-highlight-${newCards[taskId].status}`);
                            console.log(`Task ${taskId} moved from ${movedCards[taskId].from} to ${movedCards[taskId].to}`);
                        }
                    }
                });
                
                // If no cards have moved, just update the DOM and skip animations
                if (!hasMovedCards) {
                    updateDOM(tempDiv.innerHTML, newCards);
                    return;
                }
                
                // Set animation flag
                animationInProgress = true;
                
                // First, apply fade-out animation to cards that are moving
                let fadeOutApplied = false;
                for (const taskId in movedCards) {
                    if (currentCards[taskId] && currentCards[taskId].element) {
                        currentCards[taskId].element.classList.add('fade-out-animation');
                        fadeOutApplied = true;
                    }
                }
                
                // If no fade-out was applied (cards might be new), skip to DOM update
                if (!fadeOutApplied) {
                    updateDOM(tempDiv.innerHTML, newCards);
                    return;
                }
                
                // Wait for fade-out animation to complete before updating the DOM
                setTimeout(() => {
                    updateDOM(tempDiv.innerHTML, newCards);
                    
                    // Reset animation flag after all animations complete
                    setTimeout(() => {
                        animationInProgress = false;
                    }, 1500); // Match this with the move-in animation duration
                }, 800); // Match this with the fadeOutAnimation duration
            })
            .catch(error => {
                console.error('Error refreshing tasks:', error);
                animationInProgress = false; // Reset flag in case of error
            });
    }
    
    // Helper function to update DOM with new content
    function updateDOM(html, newCards) {
        // Update the DOM
        document.getElementById('roomServiceCards').innerHTML = html;
        
        // Update our tracking map for the next refresh
        taskStatusMap = {};
        Object.keys(newCards).forEach(taskId => {
            taskStatusMap[taskId] = newCards[taskId].status;
        });
        
        attachCardEvents();
        
        // Restore the selected task if it still exists
        if (selectedTaskId) {
            const previouslySelectedCard = document.querySelector(`.status-card[data-task-id="${selectedTaskId}"]`);
            if (previouslySelectedCard) {
                previouslySelectedCard.classList.add('selected');
                
                // If the selected card moved and we're still displaying its details,
                // highlight it in its new location
                if (document.getElementById('taskDetailsContent').innerHTML.includes(`ID: ${selectedTaskId}`)) {
                    previouslySelectedCard.classList.add('status-highlight-' + 
                        previouslySelectedCard.closest('.status-column').dataset.status);
                }
            }
        }
        
        // Remove animation classes after they've played
        setTimeout(() => {
            document.querySelectorAll('.move-in-animation, .status-highlight-pending, .status-highlight-working, .status-highlight-complete, .status-highlight-invalid').forEach(card => {
                card.classList.remove('move-in-animation');
                card.classList.remove('status-highlight-pending');
                card.classList.remove('status-highlight-working');
                card.classList.remove('status-highlight-complete');
                card.classList.remove('status-highlight-invalid');
            });
        }, 1500);
    }
    
    // Function to reattach event listeners after refresh
    function attachCardEvents() {
        document.querySelectorAll('.status-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.status-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedTaskId = this.dataset.taskId; // Store the selected task ID
                showDetails(this.dataset.taskId);
                setCurrentTaskId(this.dataset.taskId);
            });
        });
    }

    // Set up auto-refresh every 5 seconds
    setInterval(refreshTaskCards, 5000);

    // Initial attachment of event listeners
    attachCardEvents();

    <?php
    // Helper function for status colors
    function getStatusColor($status) {
        switch($status) {
            case 'pending': return 'warning';
            case 'working': return 'primary';
            case 'complete': return 'success';
            case 'invalid': return 'danger';
            default: return 'secondary';
        }
    }
    ?>

    // View toggle functionality
    document.getElementById('viewToggle').addEventListener('click', function() {
        const cardView = document.getElementById('roomServiceCards').parentElement;
        const tableView = document.getElementById('tableView');
        const icon = this.querySelector('i');

        if (cardView.style.display !== 'none') {
            cardView.style.display = 'none';
            tableView.style.display = 'block';
            icon.classList.replace('bi-table', 'bi-grid');
        } else {
            cardView.style.display = 'block';
            tableView.style.display = 'none';
            icon.classList.replace('bi-grid', 'bi-table');
        }
    });

    // Modified click handler for both views
    function initializeClickHandlers() {
        const cards = document.querySelectorAll('.status-card');
        const rows = document.querySelectorAll('.task-row');
        
        const handleClick = (element) => {
            document.querySelectorAll('.status-card, .task-row').forEach(el => 
                el.classList.remove('selected'));
            element.classList.add('selected');
            showDetails(element.dataset.taskId);
            setCurrentTaskId(element.dataset.taskId);
        };

        cards.forEach(card => card.addEventListener('click', () => handleClick(card)));
        rows.forEach(row => row.addEventListener('click', () => handleClick(row)));
    }

    // Initialize handlers when document loads
    document.addEventListener('DOMContentLoaded', initializeClickHandlers);

    // Search functionality
    document.getElementById('searchTable')?.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tableView tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });

        // Table pagination variables and functions
        let currentPage = 1;
        const rowsPerPage = 10;
        let taskData = [];

        function updateTableView(data) {
            taskData = data; // Store full dataset
            renderTablePage(currentPage);
            renderPaginationControls();
        }

        function renderTablePage(page) {
            const tableBody = document.querySelector('#tableView tbody');
            if (!tableBody) return;

            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = taskData.slice(start, end);

            tableBody.innerHTML = paginatedData.map(task => `
                <tr class="task-row" data-task-id="${task.id}" onclick="showDetails(${task.id})">
                    <td>${task.id}</td>
                    <td>${escapeHtml(task.uname)}</td>
                    <td>${escapeHtml(task.request)}</td>
                    <td>${escapeHtml(task.room)}</td>
                    <td><span class="badge bg-${getStatusColorClass(task.status)}">${capitalize(task.status)}</span></td>
                    <td>${task.created_at}</td>
                </tr>
            `).join('');
        }

        function renderPaginationControls() {
            const paginationContainer = document.querySelector('#pagination');
            if (!paginationContainer) return;

            const totalPages = Math.ceil(taskData.length / rowsPerPage);
            paginationContainer.innerHTML = '';

            // Previous button
            const prevButton = document.createElement('button');
            prevButton.innerText = 'Previous';
            prevButton.className = 'pagination-btn';
            prevButton.disabled = currentPage === 1;
            prevButton.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTablePage(currentPage);
                    renderPaginationControls();
                }
            };
            paginationContainer.appendChild(prevButton);

            for (let i = 1; i <= totalPages; i++) {
                const button = document.createElement('button');
                button.innerText = i;
                button.className = `pagination-btn ${i === currentPage ? 'active' : ''}`;
                button.onclick = () => {
                    currentPage = i;
                    renderTablePage(currentPage);
                    renderPaginationControls();
                };
                paginationContainer.appendChild(button);
            }

            // Next button
            const nextButton = document.createElement('button');
            nextButton.innerText = 'Next';
            nextButton.className = 'pagination-btn';
            nextButton.disabled = currentPage === totalPages;
            nextButton.onclick = () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTablePage(currentPage);
                    renderPaginationControls();
                }
            };
            paginationContainer.appendChild(nextButton);
        }

// Helper functions for table view
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getStatusColorClass(status) {
    const colors = {
        pending: 'warning',
        working: 'primary',
        complete: 'success',
        invalid: 'danger'
    };
    return colors[status] || 'secondary';
}

// When you refresh data, update both card and table views
function refreshData() {
    fetch('func/get_all_tasks.php', {
        headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
        }
    })
    .then(async response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Raw server response:', text);
            throw new Error('Invalid JSON response');
        }
    })
    .then(data => {
        if (!Array.isArray(data)) {
            if (data.error) {
                throw new Error(data.error);
            }
            throw new Error('Invalid data structure');
        }

        // Update table view
        updateTableView(data);

        // Reinitialize click handlers
        initializeClickHandlers();
    })
    .catch(error => {
        console.error('Refresh error:', error);
        showToast(`Error: ${error.message}`);
    });

    // Refresh current task details if one is selected
    if (currentTaskId) {
        showDetails(currentTaskId);
    }
}

// Modify refreshTaskCards to also update the table view
const originalRefreshTaskCards = refreshTaskCards;
refreshTaskCards = function() {
    originalRefreshTaskCards();
    
    // Also refresh the table data
    fetch('func/get_all_tasks.php')
        .then(response => response.json())
        .then(data => {
            updateTableView(data);
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
        });
};

// Initialize the table view on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('func/get_all_tasks.php')
        .then(response => response.json())
        .then(data => {
            updateTableView(data);
        })
        .catch(error => {
            console.error('Error loading table data:', error);
        });
});
    </script>
</body>
</html>