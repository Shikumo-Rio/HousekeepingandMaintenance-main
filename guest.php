<?php
session_start();
require_once("database.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Handle housekeeper assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkout_id = $_POST['checkout_id'] ?? '';
    $housekeeper = $_POST['housekeeper'] ?? '';
    $stmt = $conn->prepare("UPDATE checkout_notices SET status = 'Assigned', assigned_to = ? WHERE id = ?");
    $stmt->bind_param("si", $housekeeper, $checkout_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
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
                    <i class="fas fa-file-export"></i> Export
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
                    <h3>Checkout Notices</h3>
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
                                        // Updated status badge styling with multiple status options
                                        echo "<td><span class='badge " . 
                                            (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                            (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                            (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                            (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 'bg-secondary')))) . 
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
                    <h3>Food Orders</h3>
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
                                        // Updated status badge styling with multiple status options
                                        echo "<td><span class='badge " . 
                                            (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                            (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                            (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                            (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 'bg-secondary')))) . 
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
            <h3>Customer Messages</h3>
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
                                // Updated status badge styling with multiple status options
                                echo "<td><span class='badge " . 
                                    (strtolower($row['status']) == 'pending' ? 'bg-secondary' : 
                                    (strtolower($row['status']) == 'working' ? 'bg-primary' : 
                                    (strtolower($row['status']) == 'complete' || strtolower($row['status']) == 'completed' ? 'bg-success' : 
                                    (strtolower($row['status']) == 'invalid' ? 'bg-danger' : 'bg-secondary')))) . 
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

        <!-- Filter Modal -->
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
                        </div>
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
        // Create safe fallbacks for elements script.js might expect
        document.addEventListener('DOMContentLoaded', function() {
            // Safely get elements with null check handling
            function safeGetElement(id) {
                const element = document.getElementById(id);
                if (!element) {
                    console.log(`Element with ID '${id}' not found, creating placeholder`);
                    const placeholder = document.createElement('div');
                    placeholder.id = id;
                    placeholder.style.display = 'none';
                    document.body.appendChild(placeholder);
                    return placeholder;
                }
                return element;
            }
            
            // Create safe versions of elements script.js depends on
            const elementsToCheck = ['searchForm', 'searchInput', 'notificationBell', 'toggleSidebar'];
            elementsToCheck.forEach(safeGetElement);
            
            // Add robust modal cleanup function
            window.cleanupModal = function() {
                setTimeout(() => {
                    document.body.classList.remove('modal-open');
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        if (backdrop && backdrop.parentNode) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                    });
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
            };
            
            // Ensure modal close buttons work properly
            const closeButtons = document.querySelectorAll('.modal .btn-close, .modal [data-bs-dismiss="modal"]');
            closeButtons.forEach(button => {
                button.addEventListener('click', window.cleanupModal);
            });
        });
    </script>
    
    <!-- Load script.js after the fallbacks are created -->
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        $(document).ready(function() {
            // Function to style search bars
            function styleSearchBar() {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_filter').addClass('d-flex justify-content-end');
            }

            // Initialize tables and apply styling
            var checkoutTable = $('#checkoutTable').DataTable({
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
                order: [[0, 'desc']], // Order by first column (ID) descending
                pagingType: "full_numbers", // Ensures number pagination
            });

            $.fn.DataTable.ext.pager.numbers_length = 5; // Limits page number buttons to 5

            // Style the search bar
            function styleSearchBar() {
                let searchInput = $(".dataTables_filter input");
                
                searchInput.addClass("form-control stylish-search"); // Add custom styling class
                searchInput.attr("placeholder", "Type to search..."); // Add a stylish placeholder
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

                // Add hover & focus effects
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

            var foodOrdersTable = $('#foodOrdersTable').DataTable({
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
                order: [[0, 'desc']], // Order by first column (ID) descending
                pagingType: "full_numbers", // Ensures number pagination
            });

            $.fn.DataTable.ext.pager.numbers_length = 5; // Limits page number buttons to 5

            var customerMessagesTable = $('#customerMessagesTable').DataTable({
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
                order: [[0, 'desc']], // Order by first column (ID) descending
                pagingType: "full_numbers", // Ensures number pagination
            });

            $.fn.DataTable.ext.pager.numbers_length = 5; // Limits page number buttons to 5


            // Add filter functionality for customer messages
            $('#messageStatusFilter').on('change', function() {
                var status = $(this).val();
                customerMessagesTable.column(5).search(status).draw();
            });

            // Apply styling to all search bars
            styleSearchBar();

            $(document).ready(function() {
                // Add filter button next to search bar
                if (!$('#customerMessagesTable_filter .filter-button').length) {
                    var filterButton = $('<button class="btn filter-button mb-2" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-funnel mb-4"></i> Filter</button>');
                    $('#customerMessagesTable_filter').prepend(filterButton);
                }

                // Handle filter button clicks
                $('.filter-btn').off('click').on('click', function() {
                    var status = $(this).data('status');
                    customerMessagesTable.column(5).search(status).draw();

                    // Close modal without affecting scroll behavior
                    $('#filterModal').modal('hide');

                    setTimeout(function() {
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                    }, 200);

                    // Update button text dynamically
                    var filterText = status || 'All Status';
                    $('.filter-button').html('<i class="bi bi-funnel"></i> ' + filterText);
                });
            });

        });

        // Export functionality
        function showExportModal() {
            // Open the modal
            $('#exportModal').modal('show');
        }
        
        function exportData() {
            const exportType = document.querySelector('input[name="exportType"]:checked').value;
            const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
            
            let url = '';
            
            if (exportType === 'checkout') {
                // Export checkout notices
                url = `export_guest_data.php?type=checkout&format=${exportFormat}`;
            } else if (exportType === 'foodorders') {
                // Export food orders
                url = `export_guest_data.php?type=foodorders&format=${exportFormat}`;
            } else if (exportType === 'messages') {
                // Export customer messages
                url = `export_guest_data.php?type=messages&format=${exportFormat}`;
            }
            
            // Open in new window/tab
            window.open(url, '_blank');
            
            // Close the modal
            $('#exportModal').modal('hide');
            window.cleanupModal();
        }
    </script>
</body>
</html>
