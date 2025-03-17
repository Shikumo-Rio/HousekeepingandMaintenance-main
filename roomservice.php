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
    <div class="card p-4 room-service-heading d-flex justify-content-between">
        <h3>Housekeeping Monitoring Panel</h3>
    </div>
    <div class="row m-0">
    <!-- Left Sections for Room Service Status Cards -->
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

    <!-- Right Section for Task Details -->
    <div class="col-md-4 mt-4">
        <div class="card">
            <div class="card-header text-dark">
                <h5>Task Details</h5>
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
                    <ul class="dropdown-menu" aria-labelledby="changeStatusDropdown">
                        <li><a class="dropdown-item" href="#" onclick="changeStatus('complete')">Complete</a></li>
                        <li><a class="dropdown-item" href="#" onclick="changeStatus('invalid')">Invalid</a></li>
                    </ul>
                </div>

                <!-- Assign Employee Dropdown -->
                <div class="dropdown mb-2">
                    <button class="btn dropdown-toggle" type="button" id="assignEmployeeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Assign Employee
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="assignEmployeeDropdown">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            showToast(`Task successfully assigned to ${empName}.`);
            
            location.reload();
        } else {
            showToast(`Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error("Error during task assignment:", error);
        showToast("An error occurred during task assignment.");
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
            showToast(`Task ${taskId} status changed to ${newStatus}.`);
            location.reload(); // Reload the page to reflect changes
        } else {
            alert('Failed to change status: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error changing task status:', error);
        alert('An error occurred while changing status. Please try again later.');
    });
}


    // Function to show toast notifications
    function showToast(message) {
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.innerHTML = message;

        const toast = new bootstrap.Toast(document.getElementById('assignmentToast'));
        toast.show();
    }

    // Add event listener to status cards
    document.querySelectorAll('.status-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.status-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            showDetails(this.dataset.taskId);
            setCurrentTaskId(this.dataset.taskId); // Make sure to set current task ID here
        });
    });

    // Function to refresh task cards
    function refreshTaskCards() {
        // Directly fetch updated cards which includes allocation check
        fetch('func/get_task_cards.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('roomServiceCards').innerHTML = html;
                attachCardEvents();
            })
            .catch(error => console.error('Error refreshing tasks:', error));
    }

    // Function to reattach event listeners after refresh
    function attachCardEvents() {
        document.querySelectorAll('.status-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.status-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                showDetails(this.dataset.taskId);
                setCurrentTaskId(this.dataset.taskId);
            });
        });
    }

    // Set up auto-refresh every 5 seconds
    setInterval(refreshTaskCards, 5000);

    // Initial attachment of event listeners
    attachCardEvents();
    </script>
</body>
</html>