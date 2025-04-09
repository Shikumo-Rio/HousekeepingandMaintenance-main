<?php
session_start();
require_once("database.php");
require_once("func/user_logs.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Handle housekeeper assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_id'], $_POST['housekeeper'])) {
    $checkout_id = $_POST['checkout_id'];
    $housekeeper = $_POST['housekeeper'];
    $stmt = $conn->prepare("UPDATE checkout_notices SET status = 'Assigned', assigned_to = ? WHERE id = ?");
    $stmt->bind_param("si", $housekeeper, $checkout_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Log report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exportType'], $_POST['exportFormat'])) {
    $username = $_SESSION['username'];
    $reportType = $_POST['exportType'];
    $format = $_POST['exportFormat'];
    logReportGeneration($conn, $username, $reportType, $format);
    echo json_encode(['success' => true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/housekeepertasks.css">
    <link rel="icon" href="img/logo.webp">
    <style>
        .date-picker {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 6px 12px;
        }
        .filter-badge {
            font-size: 0.8rem;
            padding: 3px 8px;
            margin-left: 5px;
            border-radius: 10px;
        }
        .filter-button {
            margin-right: 10px;
            padding: 4px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
    <?php include('index.php'); ?>

    <div class="container mt-2">
        <!-- Header Section -->
        <div class="p-4 mb-4 task-allocation-heading card mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="">Guest Requests</h3>
                <button class="btn btn-success-export m-0" onclick="showExportModal()">
                    <i class="fas fa-file-export"></i> Generate Report
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mt-4 m-0 mb-4">
            <!-- Pending Notices Card -->
            <div class="col-md-4">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Pending Notices</h5>
                    <div class="d-flex justify-content-center align-items-center">
                        <h4 class="text-success mb-0">
                            <?php
                                $pendingNoticesQuery = "SELECT COUNT(*) as pending FROM checkout_notices WHERE status = 'Pending'";
                                $pendingNoticesResult = $conn->query($pendingNoticesQuery);
                                echo $pendingNoticesResult->fetch_assoc()['pending'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
            <!-- Pending Orders Card -->
            <div class="col-md-4">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Pending Orders</h5>
                    <div class="d-flex justify-content-center align-items-center">
                        <h4 class="text-success mb-0">
                            <?php
                                $pendingOrdersQuery = "SELECT COUNT(*) as pending FROM foodorders WHERE status = 'Pending'";
                                $pendingOrdersResult = $conn->query($pendingOrdersQuery);
                                echo $pendingOrdersResult->fetch_assoc()['pending'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
            <!-- Pending Room Service Card -->
            <div class="col-md-4">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Pending Room Service</h5>
                    <div class="d-flex justify-content-center align-items-center">
                        <h4 class="text-success mb-0">
                            <?php
                                $pendingRoomServiceQuery = "SELECT COUNT(*) as pending FROM customer_messages WHERE status = 'Pending'";
                                $pendingRoomServiceResult = $conn->query($pendingRoomServiceQuery);
                                echo $pendingRoomServiceResult->fetch_assoc()['pending'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notices Table Section -->
        <div class="row m-0">
            <!-- Checkout Notices -->
            <div class="col-md-6">
                <div class="p-4 task-allocation-heading card mb-4 m-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Checkout Notices</h3>
                        <button class="btn btn-sm btn-outline-secondary filter-button" data-table="checkout" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="card shadow-sm border-0 custom-card">
                    <div class="card-body">
                        <div class="table-responsive custom-table">
                            <table id="checkoutTable" class="table table-hover">
                                <thead class="table">
                                    <tr>
                                        <th>ID</th>
                                        <th>Room No</th>
                                        <th>Return Time</th>
                                        <th>Type</th>
                                        <th>Special Request</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM checkout_notices ORDER BY id DESC, created_at DESC";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>{$row['id']}</td>";
                                        echo "<td>{$row['room_no']}</td>";
                                        echo "<td>{$row['checkout_time']}</td>";
                                        echo "<td>{$row['request']}</td>";
                                        echo "<td>{$row['special_request']}</td>";
                                        echo "<td><span class='badge " . 
                                            (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                            (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                            (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                            (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 
                                            (strtolower($row['status']) == 'assigned' ? 'bg-secondary' : 'bg-secondary'))))) . 
                                            "'>{$row['status']}</span></td>";
                                        echo "<td>{$row['created_at']}</td>";
                                        echo "<td>";
                                        if ($row['status'] == 'Pending') {
                                            echo "<button class='btn btn-sm btn-success assign-btn' data-id='{$row['id']}' data-bs-toggle='modal' data-bs-target='#assignModal'>
                                                    Assign
                                                  </button>";
                                        } else {
                                            echo "<span class='badge bg-info'>Assigned</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Food Orders -->
            <div class="col-md-6">
                <div class="p-4 task-allocation-heading card mb-4 m-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Food Orders</h3>
                        <button class="btn btn-sm btn-outline-secondary filter-button" data-table="foodOrders" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="card shadow-sm border-0 custom-card">
                    <div class="card-body">
                        <div class="table-responsive custom-table">
                            <table id="foodOrdersTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Customer Name</th>
                                        <th>Food Item</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM foodorders ORDER BY id DESC, created_at DESC";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>{$row['id']}</td>";
                                        echo "<td>{$row['code']}</td>";
                                        echo "<td>{$row['customer_name']}</td>";
                                        echo "<td>{$row['food_item']}</td>";
                                        echo "<td>{$row['quantity']}</td>";
                                        echo "<td><span class='badge " . 
                                            (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                            (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                            (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                            (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 
                                            (strtolower($row['status']) == 'assigned' ? 'bg-secondary' : 'bg-secondary'))))) . 
                                            "'>{$row['status']}</span></td>";
                                        echo "<td>{$row['created_at']}</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Messages -->
        <div class="p-4 task-allocation-heading card mb-4 mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Customer Messages</h3>
                <button class="btn btn-sm btn-outline-secondary filter-button" data-table="customerMessages" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </div>
        <div class="card shadow-sm border-0 m-3 customer-msg">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="customerMessagesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Request</th>
                                <th>Details</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM customer_messages ORDER BY id DESC, created_at DESC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['uname']}</td>";
                                echo "<td>{$row['request']}</td>";
                                echo "<td>{$row['details']}</td>";
                                echo "<td>{$row['room']}</td>";
                                echo "<td><span class='badge " . 
                                    (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                    (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                    (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                    (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 
                                    (strtolower($row['status']) == 'assigned' ? 'bg-secondary' : 'bg-secondary'))))) . 
                                    "'>{$row['status']}</span></td>";
                                echo "<td>{$row['priority']}</td>";
                                echo "<td>{$row['created_at']}</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Enhanced Filter Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-semibold" id="filterModalLabel">
                            <i class="bx bx-filter-alt me-2"></i> Filter by Status
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body px-4 pb-4">
                        <input type="hidden" id="currentFilterTable" value="">
                        <div class="d-grid gap-2">
                            <button class="btn btn-light filter-btn fw-semibold text-dark shadow-sm" data-status="">
                                <i class="bx bx-list-ul me-2"></i> All Status
                            </button>
                            <button class="btn btn-warning filter-btn fw-semibold text-dark shadow-sm" data-status="Pending">
                                <i class="bx bx-time-five me-2"></i> Pending
                            </button>
                            <button class="btn btn-info filter-btn fw-semibold text-white shadow-sm" data-status="Working">
                                <i class="bx bx-cog me-2"></i> Working
                            </button>
                            <button class="btn btn-success filter-btn fw-semibold text-white shadow-sm" data-status="Complete">
                                <i class="bx bx-check-circle me-2"></i> Complete
                            </button>
                            <button class="btn btn-danger filter-btn fw-semibold text-white shadow-sm" data-status="Invalid">
                                <i class="bx bx-x-circle me-2"></i> Invalid
                            </button>
                            <button class="btn btn-secondary filter-btn fw-semibold text-white shadow-sm" data-status="Assigned">
                                <i class="bx bx-user-check me-2"></i> Assigned
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Export Modal -->
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <form id="exportForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">What would you like to export?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exportType" id="exportTypeCheckout" value="checkout" checked>
                                    <label class="form-check-label" for="exportTypeCheckout">Checkout Notices</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exportType" id="exportTypeFoodOrders" value="foodorders">
                                    <label class="form-check-label" for="exportTypeFoodOrders">Food Orders</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exportType" id="exportTypeMessages" value="messages">
                                    <label class="form-check-label" for="exportTypeMessages">Customer Messages</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">From</label>
                                        <input type="date" class="form-control date-picker" id="startDate" name="startDate" max="">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">To</label>
                                        <input type="date" class="form-control date-picker" id="endDate" name="endDate" max="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status Filter</label>
                                <select class="form-select" id="exportStatusFilter" name="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Working">Working</option>
                                    <option value="Complete">Complete</option>
                                    <option value="Invalid">Invalid</option>
                                    <option value="Assigned">Assigned</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Export Format</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatExcel" value="excel" checked>
                                    <label class="form-check-label" for="exportFormatExcel">Excel (.xls)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatPDF" value="pdf">
                                    <label class="form-check-label" for="exportFormatPDF">PDF</label>
                                </div>
                            </div>
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary px-2 rounded-3" style="font-size: 12px;" data-bs-dismiss="modal">
                                    <i class="bx bx-x-circle me-1"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-success px-2 rounded-3" style="font-size: 12px;" onclick="exportData()">
                                    <i class="bx bx-download me-1"></i> Export
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Verification Modal -->
        <div class="modal fade" id="passwordVerificationModal" tabindex="-1" aria-labelledby="passwordVerificationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="passwordVerificationModalLabel">Admin Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <p class="mb-3">Please enter your admin password to continue with the export.</p>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="adminPassword" placeholder="Enter your password">
                            <div id="passwordError" class="text-danger mt-2" style="display: none;">
                                Incorrect password. Please try again.
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary px-2 rounded-3" style="font-size: 12px;" data-bs-dismiss="modal">
                                <i class="bx bx-x-circle me-1"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-success px-2 rounded-3" style="font-size: 12px;" id="verifyPasswordBtn">
                                <i class="bx bx-check me-1"></i> Verify & Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Housekeeper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assignForm">
                        <input type="hidden" id="checkout_id" name="checkout_id">
                        <div class="mb-3">
                            <label for="housekeeper" class="form-label">Select Housekeeper</label>
                            <select class="form-select" id="housekeeper" name="housekeeper" required>
                                <option value="">Choose...</option>
                                <?php
                                $employeeQuery = "SELECT name FROM employee WHERE status = 'active'";
                                $employeeResult = $conn->query($employeeQuery);
                                while ($employee = $employeeResult->fetch_assoc()) {
                                    echo "<option value='{$employee['name']}'>{$employee['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Assign</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add this script to fix notification issues BEFORE loading script.js -->
    <script>
        // Define resetModal in global scope so it can be accessed by all functions
        function resetModal() {
            setTimeout(function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                $('body').css('overflow', '');
            }, 150);
        }

        $(document).ready(function() {
            // Initialize DataTables
            var tables = {
                checkout: $('#checkoutTable').DataTable({
                    dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12 d-flex justify-content-center"p>>',
                    language: {
                        search: "",
                        searchPlaceholder: "Search...",
                        paginate: {
                            first: "",
                            last: ""
                        }
                    },
                    pageLength: 5,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    order: [[0, 'desc']],
                    pagingType: "full_numbers",
                }),
                foodOrders: $('#foodOrdersTable').DataTable({
                    dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12"p>>',
                    language: {
                        search: "",
                        searchPlaceholder: "Search...",
                        paginate: {
                            first: "",
                            last: ""
                        }
                    },
                    pageLength: 5,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    order: [[0, 'desc']],
                    pagingType: "full_numbers",
                }),
                customerMessages: $('#customerMessagesTable').DataTable({
                    dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12"p>>',
                    language: {
                        search: "",
                        searchPlaceholder: "Search...",
                        paginate: {
                            first: "",
                            last: "",
                        }
                    },
                    pageLength: 5,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    order: [[0, 'desc']],
                    pagingType: "full_numbers",
                })
            };

            $.fn.DataTable.ext.pager.numbers_length = 5;
            
            function styleSearchBar() {
                let searchInput = $(".dataTables_filter input");
                
                searchInput.addClass("form-control stylish-search");
                searchInput.attr("placeholder", "Type to search...");
                searchInput.css({
                    "width": "250px",
                    "padding": "10px 10px",
                    "border-radius": "8px",
                    "border": "1px solid #ccc",
                    "outline": "none",
                    "transition": "0.3s ease-in-out",
                    "margin-bottom": "10px",
                    "font-size": "12px"
                });

                searchInput.hover(
                    function () {
                        $(this).css("border-color", "#4CAF50");
                    },
                    function () {
                        $(this).css("border-color", "#ccc");
                    }
                );

                searchInput.focus(function () {
                    $(this).css({
                        "border-color": "#28a745"
                    });
                });

                searchInput.blur(function () {
                    $(this).css({
                        "border-color": "#ccc",
                        "box-shadow": "none"
                    });
                });
            }

            var tableFilters = {
                checkout: '',
                foodOrders: '',
                customerMessages: ''
            };

            function resetModal() {
                setTimeout(function() {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    $('body').css('overflow', '');
                }, 150);
            }

            $('.filter-button').click(function() {
                var tableId = $(this).data('table');
                $('#currentFilterTable').val(tableId);
                resetModal();
            });

            $('.filter-btn').click(function() {
                var status = $(this).data('status');
                var tableId = $('#currentFilterTable').val();
                
                if (tableId && tables[tableId]) {
                    tableFilters[tableId] = status;
                    tables[tableId].column(5).search(status).draw();
                    
                    var filterText = status || 'All';
                    var statusClass = '';
                    
                    if (status === 'Pending') statusClass = 'bg-warning text-dark';
                    else if (status === 'Working') statusClass = 'bg-info text-white';
                    else if (status === 'Complete') statusClass = 'bg-success text-white';
                    else if (status === 'Assigned') statusClass = 'bg-secondary text-white';
                    else if (status === 'Invalid') statusClass = 'bg-danger text-white';
                    
                    $(`.filter-button[data-table="${tableId}"]`).html(
                        `<i class="bi bi-funnel"></i> Filter ${status ? 
                            `<span class="filter-badge ${statusClass}">${status}</span>` : ''}`
                    );
                }
                
                $('#filterModal').modal('hide');
                resetModal();
            });

            $('.modal .btn-close, .modal [data-bs-dismiss="modal"]').on('click', function() {
                resetModal();
            });

            $('#filterModal').on('hidden.bs.modal', function () {
                resetModal();
            });

            styleSearchBar();
            
            $('.assign-btn').click(function() {
                $('#checkout_id').val($(this).data('id'));
            });

            $('#assignForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'checkout-notices.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error assigning housekeeper');
                        }
                    }
                });
            });
            
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var yyyy = today.getFullYear();
            
            var todayStr = yyyy + '-' + mm + '-' + dd;
            $('#endDate').val(todayStr);
            // Set max date attribute to today
            $('#endDate').attr('max', todayStr);
            $('#startDate').attr('max', todayStr);
            
            var thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);
            var dd30 = String(thirtyDaysAgo.getDate()).padStart(2, '0');
            var mm30 = String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0');
            var yyyy30 = thirtyDaysAgo.getFullYear();
            
            var thirtyDaysAgoStr = yyyy30 + '-' + mm30 + '-' + dd30;
            $('#startDate').val(thirtyDaysAgoStr);

            // Add event listeners to date inputs to prevent future dates
            $('#startDate, #endDate').on('change', function() {
                var selectedDate = new Date($(this).val());
                
                // If selected date is in the future (after today), reset to today
                if (selectedDate > today) {
                    $(this).val(todayStr);
                    alert("You cannot select a future date");
                }
                
                // Ensure end date isn't before start date
                if ($(this).attr('id') === 'endDate') {
                    var startDate = new Date($('#startDate').val());
                    if (selectedDate < startDate) {
                        $(this).val($('#startDate').val());
                        alert("End date cannot be earlier than start date");
                    }
                }
                
                // Ensure start date isn't after end date
                if ($(this).attr('id') === 'startDate') {
                    var endDate = new Date($('#endDate').val());
                    if (selectedDate > endDate) {
                        $('#endDate').val($(this).val());
                    }
                }
            });

            // Store export parameters in global scope
            window.exportParameters = {};
            
            // Setup password verification handlers
            $('#verifyPasswordBtn').click(function() {
                var password = $('#adminPassword').val();
                
                if (!password) {
                    $('#passwordError').text('Password cannot be empty').show();
                    return;
                }
                
                // Verify the admin password
                $.ajax({
                    url: 'verify_admin_pass.php',
                    type: 'POST',
                    data: {
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Password is correct, proceed with export
                            $('#passwordVerificationModal').modal('hide');
                            
                            // Build the export URL with parameters
                            let url = `export_guest_data.php?type=${window.exportParameters.exportType}&format=${window.exportParameters.exportFormat}`;
                            
                            if (window.exportParameters.startDate) 
                                url += `&startDate=${window.exportParameters.startDate}`;
                            if (window.exportParameters.endDate) 
                                url += `&endDate=${window.exportParameters.endDate}`;
                            if (window.exportParameters.statusFilter) 
                                url += `&status=${window.exportParameters.statusFilter}`;
                            
                            if (window.exportParameters.exportFormat === 'excel') {
                                url += `&excel_password=${encodeURIComponent(password)}`;
                            }

                            url += `&encryption_password=${encodeURIComponent(password)}`;

                            window.open(url, '_blank');

                            $.ajax({
                                url: 'guest.php',
                                type: 'POST',
                                data: {
                                    exportType: window.exportParameters.exportType,
                                    exportFormat: window.exportParameters.exportFormat
                                },
                                success: function(response) {
                                    if (response.success) {
                                        console.log('Report generation logged successfully.');
                                    } else {
                                        console.error('Failed to log report generation.');
                                    }
                                },
                                error: function() {
                                    console.error('Error occurred while logging report generation.');
                                }
                            });

                            resetModal();
                        } else {
                            $('#passwordError').text(response.message).show();
                        }
                    },
                    error: function() {
                        $('#passwordError').text('Error verifying password. Please try again.').show();
                    }
                });
            });

            $('#adminPassword').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#verifyPasswordBtn').click();
                    e.preventDefault();
                }
            });
            
            $('#exportModal').on('hide.bs.modal', function() {
                window.exportParameters = {
                    exportType: $('input[name="exportType"]:checked').val(),
                    exportFormat: $('input[name="exportFormat"]:checked').val(),
                    startDate: $('#startDate').val(),
                    endDate: $('#endDate').val(), 
                    statusFilter: $('#exportStatusFilter').val()
                };
            });
        });

        function showExportModal() {
            resetModal();
            $('#exportModal').modal('show');
        }
        
        function exportData() {
            window.exportParameters = {
                exportType: $('input[name="exportType"]:checked').val(),
                exportFormat: $('input[name="exportFormat"]:checked').val(),
                startDate: $('#startDate').val(),
                endDate: $('#endDate').val(), 
                statusFilter: $('#exportStatusFilter').val()
            };
            
            $('#exportModal').modal('hide');
            
            setTimeout(function() {
                resetModal();
                $('#adminPassword').val('');
                $('#passwordError').hide();
                $('#passwordVerificationModal').modal('show');
            }, 300);
        }
    </script>
</body>
</html>
