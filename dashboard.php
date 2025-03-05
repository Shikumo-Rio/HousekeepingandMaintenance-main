<?php
require_once 'database.php';


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Fetch total tasks
$taskQuery = $conn->query("SELECT COUNT(id) AS total FROM customer_messages") or die(mysqli_error($conn));
$totalTasks = $taskQuery->fetch_assoc();

// Fetch task counts by status
$statusQuery = $conn->query("SELECT status, COUNT(*) AS total FROM customer_messages GROUP BY status") or die(mysqli_error($conn));
$statusCounts = array("complete" => 0, "working" => 0, "pending" => 0, "invalid" => 0);
while ($row = $statusQuery->fetch_assoc()) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = $row['total'];
    }
}

//For Pending maintenance requests
$requests = [];

$query = "SELECT * FROM maintenance_requests";  // Replace with your actual query
$result = mysqli_query($conn, $query);

if ($result) {
    // Fetch all the maintenance requests and store them in the $requests array
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    // If the query fails or returns no data, $requests remains an empty array
    echo "Error fetching maintenance requests.";
}

$pendingCount = 0;

foreach ($requests as $request) {
    if ($request['status'] === 'Pending') {
        $pendingCount++;
    }
}

//For Completed maintenance requests
$requests = [];

$query = "SELECT * FROM maintenance_requests";  // Replace with your actual query
$result = mysqli_query($conn, $query);

if ($result) {
    // Fetch all the maintenance requests and store them in the $requests array
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    // If the query fails or returns no data, $requests remains an empty array
    echo "Error fetching maintenance requests.";
}

$completedMaintenanceCount = 0;

foreach ($requests as $request) {
    if ($request['status'] === 'Pending') {
        $completedMaintenanceCount++;
    }
}

$query = "SELECT COUNT(*) AS total_housekeepers FROM login_accounts WHERE user_type = 'employee'";

// Execute the query
$result = $conn->query($query);
$row = $result->fetch_assoc();

// Get the total count
$total_housekeepers = $row['total_housekeepers'];


// Query to count the total number of lost items reported
$sql_lost_items = "SELECT COUNT(id) AS total_lost_items FROM lost_and_found WHERE status = 'Pending'";
$result_lost_items = $conn->query($sql_lost_items);

// Initialize a variable to store the count of lost items
$total_lost_items = 0;

if ($result_lost_items && $result_lost_items->num_rows > 0) {
    $row = $result_lost_items->fetch_assoc();
    $total_lost_items = $row['total_lost_items'];
}

// Fetch top employees with the most completed tasks
$topEmployeesQuery = $conn->query(
    "SELECT emp_name, COUNT(*) AS completed_tasks 
     FROM assigntasks 
     WHERE status = 'complete' 
     GROUP BY emp_name 
     ORDER BY completed_tasks DESC 
     LIMIT 5"
) or die(mysqli_error($conn));


// For lost and found items for the chart
$lostFoundData = $conn->query("SELECT date, COUNT(*) AS count_items FROM lost_and_found GROUP BY date ORDER BY date ASC") or die(mysqli_error($conn));
// Query to get lost and found items count by date
$sql = "SELECT date, type, COUNT(*) AS count_items 
        FROM lost_and_found 
        GROUP BY date, type 
        ORDER BY date ASC";
$result = $conn->query($sql);

// Prepare arrays for counting Lost and Found items
$lostItemsCount = [];
$foundItemsCount = [];
while ($row = $result->fetch_assoc()) {
    $date = htmlspecialchars($row['date']);
    $status = htmlspecialchars($row['type']);
    $count = (int)$row['count_items'];

    if (!isset($lostItemsCount[$date])) {
        $lostItemsCount[$date] = 0;
    }
    if (!isset($foundItemsCount[$date])) {
        $foundItemsCount[$date] = 0;
    }

    if ($status === 'Lost') {
        $lostItemsCount[$date] += $count;
    } elseif ($status === 'Found') {
        $foundItemsCount[$date] += $count;
    }
}

// Calculate Average Completion time for completed tasks in minutes
$completionTimesQuery = $conn->query(
    "SELECT TIMESTAMPDIFF(MINUTE, create_at, completed_at) AS completion_time 
     FROM assigntasks 
     WHERE status = 'complete'"
) or die(mysqli_error($conn));

$totalCompletionTime = 0;
$completedCount = 0;

while ($row = $completionTimesQuery->fetch_assoc()) {
    $totalCompletionTime += $row['completion_time']; // Sum up the total minutes
    $completedCount++;
}

$averageCompletionTime = $completedCount > 0 ? $totalCompletionTime / $completedCount : 0;

$averageCompletionTimeInMinutes = round($averageCompletionTime);


//Maintenance Request by Priority
$requests = [];
$sql = "SELECT * FROM maintenance_requests";
$result = mysqli_query($conn, $sql);

if ($result) {
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC); // Fetch as associative array
}

$highPendingCount = 0;
$mediumPendingCount = 0;
$lowPendingCount = 0;

foreach ($requests as $request) {
    if ($request['status'] === 'Pending') {
        switch ($request['priority']) {
            case 'High':
                $highPendingCount++;
                break;
            case 'Medium':
                $mediumPendingCount++;
                break;
            case 'Low':
                $lowPendingCount++;
                break;
        }
    }
}

// Prepare JS arrays for dates and datasets
$dates = array_keys($lostItemsCount); // Assuming dates are the same for both
$lostData = array_values($lostItemsCount);
$foundData = array_values($foundItemsCount);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" href="img/logo.webp">
    <title>Dashboard</title>
    
</head> 
<body>

<?php include('index.php'); ?>

<div class="container mt-1 py-4">
    <div class="p-4 card dashboard-heading">
        <h3>Dashboard</h3>
    </div>
    <div class="container-fluid">
        <!-- Task Summary Section -->
        <div class="col-lg-12 col-md-12 col-sm-12">
            <section id="content" class="d-flex align-items-center vh-20 p-0 m-0">
                <main class="p-0 m-0 w-100">
                    <ul class="box-info d-flex flex-wrap justify-content-start p-0 mx-0">
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-task icon'></i>
                            <span class="text">
                                <h3><?php echo $totalTasks['total']; ?></h3>
                                <p>Total Tasks</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-user-check icon'></i>
                            <span class="text">
                                <h3><?php echo $statusCounts['complete']; ?></h3>
                                <p>Completed</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-time-five icon'></i>
                            <span class="text">
                                <h3><?php echo $statusCounts['working']; ?></h3>
                                <p>Working</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bxs-timer icon'></i>
                            <span class="text">
                                <h3><?php echo $statusCounts['pending']; ?></h3>
                                <p>Pending</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-task-x icon'></i>
                            <span class="text">
                                <h3><?php echo $statusCounts['invalid']; ?></h3>
                                <p>Invalid</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-loader icon'></i>
                            <span class="text">
                                <h3><?php echo $pendingCount; ?></h3>
                                <p>Pending Maintenance Requests</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-user icon'></i>
                            <span class="text">
                                <h3><?php echo $total_housekeepers; ?></h3> <!-- Display the housekeeper count -->
                                <p>Housekeepers Available</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bxs-report icon'></i>
                            <span class="text">
                                <h3><?php echo $total_lost_items; ?></h3>
                                <p>Lost Items Reported</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-git-pull-request icon'></i>
                            <span class="text">
                                <h3><?php echo $completedMaintenanceCount; ?></h3>
                                <p>Maintenance Requests Completed</p>
                            </span>
                        </li>
                        <li class="col-6 col-sm-6 col-md-4 col-lg-2 my-2 text-center card">
                            <i class='bx bx-run icon'></i>
                            <span class="text">
                                <h3>50</h3>
                                <p>Rooms Cleaned Today</p>
                            </span>
                        </li>
                    </ul>
                </main>    
            </section>
        </div>

            
        <!-- Lost and Found Items Chart -->
        <div class="row gx-6 justify-content-start">
            <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-start">
                <div class="card w-100 lost-found-card card">
                    <div class="card-header card">
                        <h5>Lost and Found Items Over Time</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="lostFoundChart" height="235"></canvas>
                    </div>
                </div>  
            </div>

            <!-- Top Employees with Completed Tasks -->
            <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-start">
                <div class="card w-100 top-employee">
                    <div class="card-header card">
                        <h5>Top Employees with Most Completed Tasks</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topEmployeesChart" height="235" style="width: 100%; height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gx-6 mt-0 justify-content-start">
            <!-- Donut Chart for Maintenance Requests by Priority -->
            <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-start">
                <div class="card w-100 mt-0 maintenance-card">
                    <div class="card-header card">
                        <h5>Maintenance Requests (Pending)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="priorityDonutChart" height="100"></canvas>
                    </div>
                </div>
            </div>

             <!-- Container for Room Service Schedule -->
            <div class="col-lg-6 col-md-6 col-sm-12 d-flex align-items-start">
                <div class="card w-100 mt-0 shadow-sm room-service-schedule-card">
                    <div class="card-header card">
                        <h5>Room Service Schedule</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <!-- Room Service List Item Example -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="service-info">
                                    <h6 class="room-number">Room 101</h6>
                                    <p class="service-type">Cleaning</p>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i> <span>10:00 AM</span>
                                </div>
                                <div class="service-status pending">
                                    <i class="bi bi-hourglass-split"></i> Pending
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="service-info">
                                    <h6 class="room-number">Room 102</h6>
                                    <p class="service-type">Meal Delivery</p>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i> <span>11:30 AM</span>
                                </div>
                                <div class="service-status completed">
                                    <i class="bi bi-check-circle"></i> Completed
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="service-info">
                                    <h6 class="room-number">Room 103</h6>
                                    <p class="service-type">Laundry Pickup</p>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i> <span>1:00 PM</span>
                                </div>
                                <div class="service-status in-progress">
                                    <i class="bi bi-arrow-repeat"></i> In Progress
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="service-info">
                                    <h6 class="room-number">Room 104</h6>
                                    <p class="service-type">Turndown Service</p>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i> <span>7:00 PM</span>
                                </div>
                                <div class="service-status pending">
                                    <i class="bi bi-hourglass-split"></i> Pending
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="service-info">
                                    <h6 class="room-number">Room 105</h6>
                                    <p class="service-type">Special Request</p>
                                </div>
                                <div>
                                    <i class="bi bi-clock"></i> <span>5:00 PM</span>
                                </div>
                                <div class="service-status completed">
                                    <i class="bi bi-check-circle"></i> Completed
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for top employees chart
        var topEmployeesData = {
            labels: [],
            data: []
        };

        // Update this line to reflect the average completion time in minutes
        var averageCompletionTime = <?php echo $averageCompletionTimeInMinutes; ?>; // Use the calculated average time in minutes
        
        <?php while ($row = $topEmployeesQuery->fetch_assoc()) { ?>
            topEmployeesData.labels.push('<?php echo $row['emp_name']; ?>');
            topEmployeesData.data.push(<?php echo $row['completed_tasks']; ?>);
        <?php } ?>

        // Create Top Employees Chart
        var ctxTopEmployees = document.getElementById('topEmployeesChart').getContext('2d');
        new Chart(ctxTopEmployees, {
            type: 'bar',
            data: { 
                labels: topEmployeesData.labels,
                datasets: [{
                    label: 'Completed Tasks',
                    data: topEmployeesData.data,
                    backgroundColor: 'green',
                    borderWidth: 1,
                    barThickness: 15,
                    maxBarThickness: 15,
                    barPercentage: 1,     
                    categoryPercentage: 1,
                },
                {
                    label: 'Average Completion Time (minutes)',
                    data: Array(topEmployeesData.data.length).fill(averageCompletionTime), // Fill the average time for each employee
                    backgroundColor: 'lightgreen',
                    borderWidth: 1,
                    barThickness: 15,
                    maxBarThickness: 15,
                    barPercentage: 1,      
                    categoryPercentage: 1,
                }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                display: false,
                },
                tooltips: {
                enabled: true,
                mode: "index",
                intersect: false,
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Counts / Minutes', // X-axis title
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Employees' // Y-axis title
                        }
                    }
                }
            }
        });

        // Data for lost and found items chart
        var lostFoundData = {
            labels: [],
            data: []
        };

        <?php while ($row = $lostFoundData->fetch_assoc()) { ?>
            lostFoundData.labels.push('<?php echo $row['date']; ?>');
            lostFoundData.data.push(<?php echo $row['count_items']; ?>);
        <?php } ?>

        // Create the Line Chart for Lost and Found Items Over Time
        const lostFoundCtx = document.getElementById('lostFoundChart').getContext('2d');
        const lostFoundChart = new Chart(lostFoundCtx, {
            type: 'line',  // Line chart
            data: {
                labels: <?php echo json_encode($dates); ?>,  // X-axis labels for dates
                datasets: [
                    {
                        label: 'Lost Items',
                        data: <?php echo json_encode($lostData); ?>,  // Y-axis data for lost items count
                        backgroundColor: 'rgba(0, 128, 0, 0.2)',  // Green with some transparency for wave-like fill
                        borderColor: 'green',  // Solid green border for the line
                        borderWidth: 2,  // Thicker line
                        fill: true,  // Fill the area under the line for a wave-like appearance
                        pointRadius: 0,
                        pointHoverRadius: 5, // Show larger dots on hover
                        pointHoverBorderWidth: 2, // Thickness of the hover circle
                        tension: 0.4  // This creates the smooth, wave-like curves
                    },
                    {
                        label: 'Found Items',
                        data: <?php echo json_encode($foundData); ?>,  // Y-axis data for found items count
                        backgroundColor: 'rgba(144, 238, 144, 0.2)',  // Light green with some transparency
                        borderColor: 'lightgreen',  // Lighter green border for the line
                        borderWidth: 2,  // Thicker line
                        fill: true,  // Fill the area under the line for a wave-like appearance
                        pointRadius: 0,
                        pointHoverRadius: 5, // Show larger dots on hover
                        pointHoverBorderWidth: 2, // Thickness of the hover circle
                        tension: 0.4  // This creates the smooth, wave-like curves
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                elements: {
                    line: {
                        tension: 0.4  // Smooth curve between points
                    }
                },
                interaction: {
                    mode: 'nearest', // Ensure the hover follows the nearest point
                    axis: 'x', // Hover along the x-axis
                    intersect: false, // Show the hover effect only near the line
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement.length ? 'pointer' : 'default';
                }
            }
        });

        // Maintenance Requests by Priority Donut Chart
        const ctx = document.getElementById('priorityDonutChart').getContext('2d');
        const priorityDonutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['High Priority', 'Medium Priority', 'Low Priority'],
                datasets: [{
                    label: 'Pending Maintenance Requests',
                    data: [
                        <?php echo $highPendingCount; ?>, 
                        <?php echo $mediumPendingCount; ?>, 
                        <?php echo $lowPendingCount; ?>
                    ],
                    backgroundColor: ['#ff4d4d', '#ffcc00', '#33cc33'], // Colors for each priority
                    borderColor: ['#ff4d4d', '#ffcc00', '#33cc33'],     // Border colors
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>
</body>
</html>
