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

    // Variable to store the currently selected task ID
    let selectedTaskId = null;

    // Add event listener to status cards
    document.querySelectorAll('.status-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.status-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedTaskId = this.dataset.taskId; // Store the selected task ID
            showDetails(this.dataset.taskId);
            setCurrentTaskId(this.dataset.taskId);
        });
    });

    // Object to track task statuses
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
    </script>
</body>
</html>