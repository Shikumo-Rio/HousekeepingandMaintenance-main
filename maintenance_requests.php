<?php
require_once('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Admin') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: unauthorized.php"); // You can create this page
    exit;
}

// Get counts for the cards
$total_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests")->fetch_assoc()['count'];
$confirmed_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'Completed'")->fetch_assoc()['count'];
$emailed_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE emailed = 1")->fetch_assoc()['count'];

// Pagination logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Fetch total number of records
$totalRecordsQuery = "SELECT COUNT(*) as total FROM maintenance_requests";
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated records
$sql = "SELECT mr.*, 
        GROUP_CONCAT(DISTINCT e.name, ' (', am.emp_id, ')') as assigned_employees,
        GROUP_CONCAT(DISTINCT am.emp_id) as assigned_emp_ids
      FROM maintenance_requests mr 
      LEFT JOIN assigned_maintenance am ON mr.id = am.maintenance_request_id
      LEFT JOIN employee e ON am.emp_id = e.emp_id 
      GROUP BY mr.id
      LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Pagination logic for guest maintenance table
$guestPage = isset($_GET['guestPage']) ? (int)$_GET['guestPage'] : 1;
$guestLimit = 5;
$guestOffset = ($guestPage - 1) * $guestLimit;

// Fetch total number of guest records
$totalGuestRecordsQuery = "SELECT COUNT(*) as total FROM guest_maintenance";
$totalGuestRecordsResult = $conn->query($totalGuestRecordsQuery);
$totalGuestRecords = $totalGuestRecordsResult->fetch_assoc()['total'];
$totalGuestPages = ceil($totalGuestRecords / $guestLimit);

// Fetch paginated guest records
$guestSql = "SELECT id, uname, title, description, room, status, created_at FROM guest_maintenance LIMIT $guestLimit OFFSET $guestOffset";
$guestResult = $conn->query($guestSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="icon" href="img/logo.webp">
    <title>Maintenance Management</title>
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
        .stylish-search {
            width: 250px;
            padding: 10px 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            transition: 0.3s ease-in-out;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .stylish-search:hover {
            border-color: #4CAF50;
        }
        .stylish-search:focus {
            border-color: #28a745;
        }
    </style>
</head>
<body>

    <?php include('index.php'); ?>

    
    <!-- Cards Row -->
    <div class="container">
        <!-- Title Heading -->
        <div class="p-4 mb-4 mt-4 title-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Maintenance Requests</h3>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-success-export me-3" data-bs-toggle="modal" data-bs-target="#emailModal">
                        <i class="fas fa-envelope me-1"></i> Email Request
                    </button>
                    <button class="btn btn-success-export m-0" onclick="showExportModal()">
                        <i class="fas fa-file-export"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>

        <div class="row m-0 text-center mb-4">
        <!-- Requests Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Requests</h5>
                        <h3 class="card-text"></i> <?php echo $total_requests; ?></h3>
                    </div>
                </div>
            </div>
    
            <!-- Confirmed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Confirmed</h5>
                        <h3 class="card-text"><?php echo $confirmed_requests; ?></h3>
                    </div>
                </div>
            </div>
    
            <!-- Emailed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Emailed</h5>
                        <h3 class="card-text"><?php echo $emailed_requests; ?></h3>
                    </div>
                </div>
            </div>
        </div>  
    </div>

    <!-- Maintenance Requests Table -->
    <div class="container">
        <div class="card shadow-lg rounded-3 m-2 custom-card">
            <div class="card-body m-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-2 mt-2">Requests Overview</h3>
                    <!-- Filter button moved to script for proper alignment with search bar -->
                </div>
                <div class='table-responsive task-table' id="requestsTable">
                    <table class="table table-hover" id="maintenanceTable">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th>Request Title</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Scheduled On</th>
                                <th>Being work by</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody">
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['request_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room_no']) . "</td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['priority'] == 'High' ? 'danger' :
                                        ($row['priority'] == 'Medium' ? 'warning' : 'success')) .
                                        "'>" . htmlspecialchars($row['priority']) . "</span></td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['status'] == 'Pending' ? 'secondary' : 
                                        ($row['status'] == 'Completed' ? 'success' : 'warning')) .
                                        "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    echo "<td>" . ($row['schedule'] ? htmlspecialchars($row['schedule']) : '<span class="text-muted">Not scheduled</span>') . "</td>";
                                    echo "<td>" . ($row['assigned_employees'] ? htmlspecialchars($row['assigned_employees']) : '<span class="text-muted">Not assigned</span>') . "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Maintenance Table -->
    <div class="container mt-4 mb-4">
        <div class="card shadow-lg rounded-3 m-2 custom-card">
            <div class="card-body m-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-2 mt-2">Guest Maintenance Requests</h3>
                    <!-- Filter button moved to script for proper alignment with search bar -->
                </div>
                <div class='table-responsive task-table' id="guestRequestsTable">
                    <table class="table table-hover" id="guestMaintenanceTable">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th>ID</th>
                                <th>Guest Name</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody id="guestRequestsTableBody">
                            <?php
                            if ($guestResult->num_rows > 0) {
                                while ($row = $guestResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['uname']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room']) . "</td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['status'] == 'Pending' ? 'secondary' : 
                                        ($row['status'] == 'Completed' ? 'success' : 'warning')) .
                                        "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No guest maintenance records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this new Filter Modal -->
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
                        <button class="btn btn-info filter-btn fw-semibold text-white shadow-sm" data-status="In Progress">
                            <i class="bx bx-cog me-2"></i> In Progress
                        </button>
                        <button class="btn btn-success filter-btn fw-semibold text-white shadow-sm" data-status="Completed">
                            <i class="bx bx-check-circle me-2"></i> Completed
                        </button>
                        <button class="btn btn-danger filter-btn fw-semibold text-white shadow-sm" data-status="Canceled">
                            <i class="bx bx-x-circle me-2"></i> Canceled
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="emailModalLabel">Email Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="emailForm" method="POST" action="email_request.php">
                        <div class="form-floating mb-3">
                            <select class="form-select" style="font-size: 12px;" id="requestID" name="requestID" required>
                                <option value="">Select Request</option>
                                <?php
                                $sql = "SELECT id, request_title, room_no FROM maintenance_requests";
                                $result = $conn->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>Request #" . $row['id'] . 
                                         " - Room " . $row['room_no'] . " - " . $row['request_title'] . "</option>";
                                }
                                ?>
                            </select>
                            <label for="requestID">Select Maintenance Request</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" style="font-size: 12px;" id="emailAddress" name="emailAddress" required>
                            <label for="emailAddress">Email To</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" style="font-size: 12px;" id="additionalNotes" name="additionalNotes" style="height: 100px"></textarea>
                            <label for="additionalNotes">Additional Notes (Optional)</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">Send Email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>  
    </div>

    <!-- Add this modal before the closing body tag -->
    <div class="modal fade" id="emailResponseModal" tabindex="-1" aria-labelledby="emailResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered p-0 w-25">
            <div class="modal-content shadow-lg rounded-4">
                <div class="d-flex justify-content-end border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <div id="emailResponseMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <form>
                        <div class="mb-3">
                            <label class="form-label fw-bold">What would you like to export?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportType" id="exportTypeMaintenance" value="maintenance_requests" checked>
                                <label class="form-check-label" for="exportTypeMaintenance">Maintenance Requests</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportType" id="exportTypeGuest" value="guest_maintenance">
                                <label class="form-check-label" for="exportTypeGuest">Guest Maintenance Requests</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportType" id="exportTypeBoth" value="both">
                                <label class="form-check-label" for="exportTypeBoth">Both Tables</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Filter by Status</label>
                            <select class="form-select rounded-3" id="statusFilter" name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control date-picker" id="startDate" name="startDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control date-picker" id="endDate" name="endDate" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
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
                            <button type="button" class="btn btn-success px-2 rounded-3" style="font-size: 12px;" onclick="nextStep()">
                                <i class="bx bx-download me-1"></i> Next
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
                        <input type="password" class="form-control rounded-3" id="adminPassword" placeholder="Enter your password">
                        <div id="passwordError" class="text-danger mt-2" style="display: none;">
                            Incorrect password. Please try again.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-3" data-bs-dismiss="modal">
                        <i class="bx bx-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success px-3 rounded-3" id="verifyPasswordBtn">
                        <i class="bx bx-check me-1"></i> Verify & Export
                    </button>
                </div>
            </div>
        </div>
    </div>

     <!-- Include Bootstrap JS and DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/script.js"></script>
    <script>
    // Utility function to clean up modals
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
            maintenanceTable: $('#maintenanceTable').DataTable({
                dom: '<"row align-items-center"<"col-md-12"<"d-flex justify-content-end filter-search-container"f>>>rt<"row"<"col-12 d-flex justify-content-center"p>>',
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
            guestMaintenanceTable: $('#guestMaintenanceTable').DataTable({
                dom: '<"row align-items-center"<"col-md-12"<"d-flex justify-content-end filter-search-container"f>>>rt<"row"<"col-12 d-flex justify-content-center"p>>',
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
            })
        };

        $.fn.DataTable.ext.pager.numbers_length = 5;

        // Function to style the search bar and align with filter button
        function styleSearchBar() {
            let filterButtons = `
                <button class="btn btn-sm btn-outline-secondary filter-button" data-table="maintenanceTable" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            `;
            
            let guestFilterButtons = `
                <button class="btn btn-sm btn-outline-secondary filter-button" data-table="guestMaintenanceTable" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            `;
            
            // Insert filter buttons before search input
            $("#maintenanceTable_filter").before(filterButtons);
            $("#guestMaintenanceTable_filter").before(guestFilterButtons);
            
            // Add spacing between filter button and search input
            $(".filter-button").css('margin-right', '15px');
            
            // Create filter-search containers
            $(".dataTables_filter").each(function() {
                $(this).parent().addClass('d-flex align-items-center justify-content-end');
            });
            
            // Style search input fields
            let searchInputs = $(".dataTables_filter input");
            searchInputs.addClass("form-control stylish-search");
            searchInputs.attr("placeholder", "Type to search...");
            searchInputs.css({
                "width": "250px",
                "padding": "8px 10px",
                "border-radius": "8px",
                "border": "1px solid #ccc",
                "outline": "none",
                "transition": "0.3s ease-in-out",
                "margin-bottom": "0px",
                "font-size": "12px"
            });

            searchInputs.hover(
                function () {
                    $(this).css("border-color", "#4CAF50");
                },
                function () {
                    $(this).css("border-color", "#ccc");
                }
            );

            searchInputs.focus(function () {
                $(this).css({
                    "border-color": "#28a745"
                });
            });

            searchInputs.blur(function () {
                $(this).css({
                    "border-color": "#ccc",
                    "box-shadow": "none"
                });
            });
            
            // Position the filter-search containers at the top of tables
            $(".filter-search-container").each(function() {
                // Get the parent card's title element
                let cardTitle = $(this).closest('.card-body').find('h3').first();
                
                // Create a container that fills the width and positions elements
                let container = $('<div class="d-flex justify-content-between align-items-center w-100 mb-3"></div>');
                
                // Clone the title and add it to the new container
                let titleClone = cardTitle.clone();
                container.append(titleClone);
                
                // Add the filter and search elements to the container
                container.append($(this));
                
                // Replace the existing title with the new container
                cardTitle.closest('.d-flex').replaceWith(container);
            });
        }

        // Track filter states
        var tableFilters = {
            maintenanceTable: '',
            guestMaintenanceTable: ''
        };

        // Set up filter modal
        $(document).on('click', '.filter-button', function() {
            var tableId = $(this).data('table');
            $('#currentFilterTable').val(tableId);
            resetModal();
        });

        // Handle filter button clicks
        $('.filter-btn').click(function() {
            var status = $(this).data('status');
            var tableId = $('#currentFilterTable').val();
            
            if (tableId && tables[tableId]) {
                tableFilters[tableId] = status;
                
                // For maintenance tables, status is in column 4
                tables[tableId].column(4).search(status).draw();
                
                var filterText = status || 'All';
                var statusClass = '';
                
                if (status === 'Pending') statusClass = 'bg-warning text-dark';
                else if (status === 'In Progress') statusClass = 'bg-info text-white';
                else if (status === 'Completed') statusClass = 'bg-success text-white';
                else if (status === 'Canceled') statusClass = 'bg-danger text-white';
                
                $(`.filter-button[data-table="${tableId}"]`).html(
                    `<i class="bi bi-funnel"></i> Filter ${status ? 
                        `<span class="filter-badge ${statusClass}">${status}</span>` : ''}`
                );
            }
            
            $('#filterModal').modal('hide');
            resetModal();
        });

        // Apply search bar styling and alignment
        styleSearchBar();

        // ...existing code...
    });

    // ...existing code...
    </script>
</body>
</html>
