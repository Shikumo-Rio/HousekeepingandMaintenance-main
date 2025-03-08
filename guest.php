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
    <title>Guest Checkout Notices</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/housekeepertasks.css">
    <link rel="icon" href="img/logo.webp">
    <style>
        .dataTables_wrapper .dataTables_paginate {
            text-align: center !important;
            float: none !important;
            margin-top: 20px !important;
        }
        
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
        }
        
        .table-responsive {
            overflow-y: hidden !important;
        }
        
        .dataTables_wrapper {
            overflow: hidden;
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .table-responsive::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .table-responsive {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        @media screen and (max-width: 767px) {
            .dataTables_wrapper .dataTables_filter {
                text-align: left;
            }
            
            .table-responsive {
                border: 0;
                margin-bottom: 0;
                overflow-x: auto;
                overflow-y: hidden !important;
            }
            
            .form-select {
                width: 100% !important;
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            table.dataTable {
                margin-bottom: 0 !important;
            }
        }

    </style>
</head>
<body>
    <?php include('index.php'); ?>

    <div class="container mt-2">
        <!-- Header Section -->
        <div class="p-4 mb-4 task-allocation-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Stepping out</h3>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mt-4 m-0 mb-4">
            <!-- Total Notices Card -->
            <div class="col-md-6">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Total Notices</h5>
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-bell-fill text-success fs-4 me-2"></i>
                        <h4 class="text-success mb-0">
                            <?php
                                $countQuery = "SELECT COUNT(*) as total FROM checkout_notices";
                                $countResult = $conn->query($countQuery);
                                echo $countResult->fetch_assoc()['total'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
            <!-- Pending Notices Card -->
            <div class="col-md-6">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Pending Notices</h5>
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-hourglass-split text-success fs-4 me-2"></i>
                        <h4 class="text-success mb-0">
                            <?php
                                $pendingQuery = "SELECT COUNT(*) as pending FROM checkout_notices WHERE status = 'Pending'";
                                $pendingResult = $conn->query($pendingQuery);
                                echo $pendingResult->fetch_assoc()['pending'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notices Table Section -->
        <div class="p-4 task-allocation-heading card mb-4">
            <h3>Checkout Notices</h3>
        </div>

        <!-- Table Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <!-- Add Filter Options -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <select class="form-select w-auto" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Assigned">Assigned</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="checkoutTable" class="table table-hover">
                        <thead class="table-dark">
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
                            $query = "SELECT * FROM checkout_notices ORDER BY created_at DESC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['room_no']}</td>";
                                echo "<td>{$row['checkout_time']}</td>";
                                echo "<td>{$row['request']}</td>";
                                echo "<td>{$row['special_request']}</td>";
                                echo "<td><span class='badge " . ($row['status'] == 'Pending' ? 'bg-secondary' : 'bg-success') . "'>{$row['status']}</span></td>";
                                echo "<td>{$row['created_at']}</td>";
                                echo "<td>";
                                if ($row['status'] == 'Pending') {
                                    echo "<button class='btn btn-success btn-sm assign-btn' data-id='{$row['id']}' data-bs-toggle='modal' data-bs-target='#assignModal'>
                                            <i class='fas fa-user-plus'></i> Assign
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

    <script src="https://code.jquery.c
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
om/jquery-3.6.0.min.js"></script>
    c
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with custom options
            var table = $('#checkoutTable').DataTable({
                dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search...",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                },
                pageLength: 10,
                ordering: true,
                info: false,
                lengthChange: false,
                scrollX: false,
                responsive: true,
                scrollY: false,
                scrollCollapse: true
            });

            // Add custom filter functionality
            $('#statusFilter').on('change', function() {
                var status = $(this).val();
                table.column(5).search(status).draw();
            });

            // Style the search bar
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_filter').addClass('d-flex justify-content-end');

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
        });
    </script>
</body>
</html>
