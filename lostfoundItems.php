<?php
session_start();
require_once('database.php');

// Check user session
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

$message = ""; // Initialize message variable

// Get counts for the cards
$total_lost = $conn->query("SELECT COUNT(*) as count FROM lost_and_found WHERE type = 'Lost'")->fetch_assoc()['count'];
$total_found = $conn->query("SELECT COUNT(*) as count FROM lost_and_found WHERE type = 'Found'")->fetch_assoc()['count'];
$total_claimed = $conn->query("SELECT COUNT(*) as count FROM lost_and_found WHERE status = 'claimed'")->fetch_assoc()['count'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $item = isset($_POST['item']) ? htmlspecialchars(trim($_POST['item'])) : '';
    $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : '';

    // File Upload Handling
    $picture_path = ''; // Default empty
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image
        $check = getimagesize($_FILES["picture"]["tmp_name"]);
        if ($check === false) {
            $message = "File is not an image.";
            $status = "error";
        } elseif (file_exists($target_file)) {
            $message = "File already exists. Try renaming it.";
            $status = "error";
        } elseif ($_FILES["picture"]["size"] > 5000000) {
            $message = "File size too large.";
            $status = "error";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $message = "Only JPG, JPEG, and PNG formats are allowed.";
            $status = "error";
        } elseif (!move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
            $message = "Error uploading file.";
            $status = "error";
        } else {
            $picture_path = $target_file;
        }
    }

    // Ensure all fields are filled
    if (empty($message)) { // Proceed only if no errors from file upload
        if (!empty($found_by) && !empty($room) && !empty($date) && !empty($item) && !empty($description) && !empty($type)) {
            $status = 'pending';

            // Prepare the SQL statement
            $stmt = $conn->prepare("INSERT INTO lost_and_found (found_by, type, room, date, item, description, status, picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $found_by, $type, $room, $date, $item, $description, $status, $picture_path);

            if ($stmt->execute()) {
                $message = "Ticket created successfully!";
                $status = "success";
            } else {
                $message = "Database Error: " . $stmt->error;
                $status = "error";
            }
        } else {
            $message = "Please fill out all required fields.";
            $status = "error";
        }
    }

    // Set modal data and trigger the modal
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('messageBody').innerText = '" . addslashes($message) . "';
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
            
            // Auto-close after 3 seconds
            setTimeout(() => {
                messageModal.hide();
                " . ($status === "success" ? "window.location.href = 'lostfoundItems.php';" : "") . "
            }, 3000);
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/lostfounditems.css">
    <link rel="icon" href="img/logo.webp">
    <title>Lost and Found Management</title>
    <style>
        /* Filter button styles */
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
        
        /* Search input styles */
        .search-input {
            width: 200px;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            outline: none;
            transition: 0.3s ease-in-out;
            font-size: 0.8rem;
        }
        .search-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Export button styling from guest.php */
        .btn-success-export {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .btn-success-export:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-success-export:active {
            transform: translateY(0);
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-success-export i {
            font-size: 15px;
        }

        /* Date picker styling */
        .date-picker {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Additional styles to ensure the buttons work correctly */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-btn {
            position: relative !important;
            right: auto !important;
            margin-bottom: 0 !important;
            background-color: #198754;
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-btn:hover {
            background-color: rgba(0, 128, 0, 0.8);
            color: white;
        }

        .add-btn i {
            font-size: 14px;
        }

        /* Responsive fixes */
        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
                gap: 5px;
                align-items: flex-end;
            }
            
            .add-btn {
                font-size: 12px;
                padding: 5px 12px;
            }
        }
    </style>
</head>
<body onload="window.scrollTo(0, 0);"> <!-- Add onload attribute to body tag -->

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4 modal-dark">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="messageBody">
                    <!-- Message will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'index.php'; ?>

    <div class="container p-4">
        <div class="p-3 mt-0 lostfound-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="ms-2">Lost and Found</h3>
                <div class="action-buttons">
                    <button class="btn btn-success add-btn" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fa-solid fa-plus"></i> Create
                    </button>
                    <button class="btn btn-success add-btn" onclick="showExportModal()" title="Export Data">
                        <i class="fas fa-file-export"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>
        <ul class="nav nav-tabs mt-4">
            <li class="nav-item">
                <a class="nav-link nav-tab-link" data-tab="lost" href="#lost">Lost</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-tab-link" data-tab="found" href="#found">Found</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-tab-link" data-tab="claimed" href="#claimed">Claim/Return</a>
            </li>
        </ul>

        <div class="tab-content card mt-4" id="itemTabsContent">
            <div class="tab-pane fade" id="lost" role="tabpanel" aria-labelledby="lost-tab">
                <?php include 'lost.php'; ?>
            </div>
            <div class="tab-pane fade" id="found" role="tabpanel" aria-labelledby="found-tab">
                <?php include 'found.php'; ?>
            </div>
            <div class="tab-pane fade" id="claimed" role="tabpanel" aria-labelledby="claimed-tab">
                <?php include 'claim_return.php'; ?>
            </div>
        </div>

        <!-- Claims Table Section -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0">
                <h5 class="mb-0 mt-4">Claims History</h5>
                <div class="d-flex align-items-center mt-4">
                    <!-- Filter Dropdown -->
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-button" type="button" id="claimsFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="claimsFilterDropdown">
                            <li><a class="dropdown-item claims-filter-option" href="#" data-filter="all">All Status</a></li>
                            <li><a class="dropdown-item claims-filter-option" href="#" data-filter="pending">Pending</a></li>
                            <li><a class="dropdown-item claims-filter-option" href="#" data-filter="approved">Approved</a></li>
                            <li><a class="dropdown-item claims-filter-option" href="#" data-filter="rejected">Rejected</a></li>
                        </ul>
                    </div>
                    
                    <!-- Search Input -->
                    <input type="text" id="claimsSearchInput" class="form-control form-control-sm search-input" placeholder="Search claims...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="claimsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Lost Item ID</th>
                                <th>Guest Name</th>
                                <th>Room No</th>
                                <th>Contact Info</th>
                                <th>Area Lost</th>
                                <th>Date Lost</th>
                                <th>Date Claimed</th>
                                <th>Description</th>
                                <th>Validated By</th>
                                <th>Proof ID</th>
                                <th>Proof of Ownership</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get the current page number
                            $page2 = isset($_GET['page2']) ? (int)$_GET['page2'] : 1;
                            $limit = 5; // Items per page
                            $offset = ($page2 - 1) * $limit;

                            // Get search term if any
                            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                            
                            // Build the search condition
                            $searchCondition = '';
                            if (!empty($search)) {
                                $searchCondition = " WHERE guest_name LIKE '%$search%' 
                                                   OR room_no LIKE '%$search%' 
                                                   OR description LIKE '%$search%'";
                            }

                            // Count total records for pagination
                            $countQuery = "SELECT COUNT(*) as total FROM claims" . $searchCondition;
                            $countResult = $conn->query($countQuery);
                            $totalRows = $countResult->fetch_assoc()['total'];
                            $totalPages = ceil($totalRows / $limit);

                            // Main query with limit
                            $query = "SELECT * FROM claims" . $searchCondition . 
                                    " ORDER BY date_claimed DESC LIMIT $offset, $limit";
                            $result = $conn->query($query);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>".$row['id']."</td>";
                                    echo "<td>".$row['lost_item_id']."</td>";
                                    echo "<td>".$row['guest_name']."</td>";
                                    echo "<td>".$row['room_no']."</td>";
                                    echo "<td>".$row['contact_info']."</td>";
                                    echo "<td>".$row['area_lost']."</td>";
                                    echo "<td>".date('Y-m-d', strtotime($row['date_lost']))."</td>";
                                    echo "<td>".date('Y-m-d', strtotime($row['date_claimed']))."</td>";
                                    echo "<td>".$row['description']."</td>";
                                    echo "<td>".$row['validated_by']."</td>";
                                    echo "<td>".$row['proof_id']."</td>";
                                    echo "<td>";
                                    if (!empty($row['proof_ownership'])) {
                                        echo "<button class='btn btn-primary btn-sm' onclick='viewImage(\"".$row['proof_ownership']."\")'>
                                                <i class='fas fa-image'></i> View
                                              </button>";
                                    } else {
                                        echo "No image";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='11' class='text-center'>No claims found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // Define how many page numbers to show
                $claimLimit = 5;
                $claimStart = max(1, $page2 - floor($claimLimit / 2));
                $claimEnd = min($totalPages, $claimStart + $claimLimit - 1);

                // Adjust start if end is less than limit
                if ($claimEnd - $claimStart < $claimLimit - 1) {
                    $claimStart = max(1, $claimEnd - $claimLimit + 1);
                }

                // Add pagination controls
                echo "<nav aria-label='Page navigation' class='mt-3 mb-4'>
                        <ul class='pagination justify-content-center' id='claimPaginationControls'>";

                // Previous button
                if ($page2 > 1) {
                    echo "<li class='page-item'>
                            <a class='page-link claim-pagination' href='?page2' data-page='" . ($page2 - 1) . "'>
                                Previous
                            </a>
                        </li>";
                } else {
                    echo "<li class='page-item disabled'>
                            <a class='page-link'>
                                Previous
                            </a>
                        </li>";
                }

                // Display limited page numbers
                for ($i = $claimStart; $i <= $claimEnd; $i++) {
                    $active = $page2 === $i ? 'active' : '';
                    echo "<li class='page-item $active'>
                            <a class='page-link claim-pagination' href='?page2' data-page='$i'>$i</a>
                        </li>";
                }

                // Next button
                if ($page2 < $totalPages) {
                    echo "<li class='page-item'>
                            <a class='page-link claim-pagination' href='?page2' data-page='" . ($page2 + 1) . "'>
                                Next
                            </a>
                        </li>";
                } else {
                    echo "<li class='page-item disabled'>
                            <a class='page-link'>
                                Next 
                            </a>
                        </li>";
                }

                echo "</ul></nav>";
                ?>
            </div>
        </div>
    </div>
    
    <!-- Modal for Submitting Lost/Found Item -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="registerModalLabel">Submit Lost/Found</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data"> <!-- Add enctype -->
                        <div class="form-floating form-floating-sm mb-3">
                            <input type="text" class="form-control form-control-dark" style="font-size: 12px;" id="found_by" name="found_by" placeholder="Found by" required>
                            <label for="found_by">Found by</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select form-control-dark" style="font-size: 12px;" id="type" name="type" placeholder="Select type" required>
                                <option value="">Select Type</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>
                            <label for="type">Type</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" style="font-size: 12px;" id="room" name="room" placeholder="Enter room" required>
                            <label for="room">Room/Area</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control form-control-dark" style="font-size: 12px;" id="date" name="date" placeholder="Date" required>
                            <label for="date">Date</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" style="font-size: 12px;" id="item" name="item" placeholder="Item" required>
                            <label for="item">Item</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" style="font-size: 12px;" id="description" name="description" placeholder="Description" required>
                            <label for="description">Description</label>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control form-control-dark" style="font-size: 12px;" id="picture" name="picture" accept="image/*" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="submit_request" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true" data-bs-backdrop="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content-pic border-0">
                <div class="modal-header border-0">
                    <div class="position-relative border-0">
                        <img id="proofImage" src="" class="img-fluid rounded-4 custom-img" alt="Proof of Ownership">
                        
                        <!-- Title Overlay -->
                        <h5 class="image-overlay text-white text-center p-2">Proof of Ownership</h5>

                        <!-- Close Button Overlay -->
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2 p-2 rounded-circle bg-light" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">What would you like to export?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportType" id="exportTypeLost" value="lost_and_found" checked>
                                <label class="form-check-label" for="exportTypeLost">Lost & Found Items</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportType" id="exportTypeClaims" value="claims">
                                <label class="form-check-label" for="exportTypeClaims">Claims History</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control date-picker" id="startDate" name="startDate">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control date-picker" id="endDate" name="endDate">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Filter</label>
                            <select class="form-select" id="exportStatusFilter" name="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="found">Found</option>
                                <option value="lost">Lost</option>
                                <option value="claimed">Claimed</option>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Function to activate a tab and show the corresponding content
        const activateTab = (target) => {
            // Hide all tab panes and deactivate all tabs
            document.querySelectorAll('.tab-pane').forEach(tab => {
                tab.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(tabLink => {
                tabLink.classList.remove('active');
            });

            // Show the clicked tab and activate it
            const tabPane = document.querySelector(target);
            if (tabPane) {
                tabPane.classList.add('show', 'active');
            }

            // Activate the corresponding nav link
            const navLink = document.querySelector(`.nav-link[href="${target}"]`);
            if (navLink) {
                navLink.classList.add('active');
            }

            // Update URL with tab but preserve other parameters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', target.substring(1)); // Remove the # character
            
            // Don't change page parameters when switching tabs
            history.replaceState(null, '', '?' + urlParams.toString() + location.hash);
        };

        // Handle clicks on sidebar items
        document.querySelectorAll('.sidebar-link[data-target]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent default anchor behavior
                const target = this.getAttribute('data-target'); // Get the target tab
                activateTab(target); // Activate the corresponding tab
                window.location.hash = target; // Update the URL hash
            });
        });

        // Handle clicks on nav tabs
        document.querySelectorAll('.nav-tab-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent default anchor behavior
                const target = this.getAttribute('href'); // Get the target tab
                activateTab(target); // Activate the corresponding tab
                history.replaceState(null, null, target); // Update the URL hash without scrolling
            });
        });


        // Check for initial tab parameter on page load
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab');
        if (currentTab) {
            activateTab('#' + currentTab); // Activate the tab based on URL parameter
        } else {
            activateTab('#lost'); // Default to 'Lost' tab
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
    const activateTab = (target) => {
        document.querySelectorAll('.tab-pane').forEach(tab => {
            tab.classList.remove('show', 'active');
        });
        document.querySelectorAll('.nav-link').forEach(tabLink => {
            tabLink.classList.remove('active');
        });

        const tabPane = document.querySelector(target);
        if (tabPane) {
            tabPane.classList.add('show', 'active');
        }

        const navLink = document.querySelector(`.nav-link[href="${target}"]`);
        if (navLink) {
            navLink.classList.add('active');
        }
    };

    const currentTab = new URLSearchParams(window.location.search).get('tab');
    const hashTab = currentTab ? `#${currentTab}` : '#lost';

    // Activate tab based on URL parameter or default to 'lost'
    activateTab(hashTab);

    // Handle clicks on nav tabs
    document.querySelectorAll('.nav-tab-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            activateTab(target);
            window.location.hash = target;
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('darkModeToggle');

    toggleButton.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');

        // Optionally save the user's preference
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

    // Check for saved theme preference
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
});

    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('claimsTable');
        const tr = table.getElementsByTagName('tr');
        let noResult = true;

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    const txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        noResult = false;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? '' : 'none';
        }

        // Show no results message if nothing found
        const noResultsRow = table.querySelector('.no-results');
        if (noResult && tr.length > 1) {
            if (!noResultsRow) {
                const tbody = table.getElementsByTagName('tbody')[0];
                const newRow = tbody.insertRow();
                newRow.className = 'no-results';
                const cell = newRow.insertCell();
                cell.colSpan = 11;
                cell.className = 'text-center';
                cell.textContent = 'No matching records found';
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }

    // Add this new function for image viewing
    function viewImage(imagePath) {
        document.getElementById('proofImage').src = imagePath;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }

    // Function to properly reset modals
    function resetModal() {
        setTimeout(function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
            $('body').css('overflow', '');
        }, 150);
    }

    // Export functionality
    function showExportModal() {
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
        
        // Show the modal
        resetModal();
        new bootstrap.Modal(document.getElementById('exportModal')).show();
    }

    // Store export parameters in global scope
    window.exportParameters = {};

    function exportData() {
        // Save export parameters before closing modal
        window.exportParameters = {
            exportType: document.querySelector('input[name="exportType"]:checked').value,
            exportFormat: document.querySelector('input[name="exportFormat"]:checked').value,
            startDate: document.getElementById('startDate').value,
            endDate: document.getElementById('endDate').value,
            statusFilter: document.getElementById('exportStatusFilter').value
        };
        
        // Close export modal
        const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        if (exportModal) {
            exportModal.hide();
        }
        
        // Show password verification modal with a delay to ensure proper modal cleanup
        setTimeout(function() {
            resetModal();
            // Clear any previous password input and error message
            document.getElementById('adminPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
            // Show the password verification modal
            new bootstrap.Modal(document.getElementById('passwordVerificationModal')).show();
        }, 300);
    }

    // Setup password verification handlers
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('verifyPasswordBtn').addEventListener('click', function() {
            const password = document.getElementById('adminPassword').value;
            
            if (!password) {
                document.getElementById('passwordError').textContent = 'Password cannot be empty';
                document.getElementById('passwordError').style.display = 'block';
                return;
            }
            
            // Verify the admin password
            fetch('verify_admin_pass.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Password is correct, proceed with export
                    const passwordModal = bootstrap.Modal.getInstance(document.getElementById('passwordVerificationModal'));
                    if (passwordModal) {
                        passwordModal.hide();
                    }
                    
                    // Build the export URL with parameters
                    let url = `export_data.php?table=${window.exportParameters.exportType}&format=${window.exportParameters.exportFormat}`;
                    
                    if (window.exportParameters.startDate) 
                        url += `&start=${window.exportParameters.startDate}`;
                    if (window.exportParameters.endDate) 
                        url += `&end=${window.exportParameters.endDate}`;
                    if (window.exportParameters.statusFilter) 
                        url += `&status=${window.exportParameters.statusFilter}`;
                    
                    // Add title parameter
                    let title = '';
                    if (window.exportParameters.exportType === 'lost_and_found') {
                        title = 'Lost and Found Items';
                    } else if (window.exportParameters.exportType === 'claims') {
                        title = 'Claims History';
                    }
                    url += `&title=${encodeURIComponent(title)}`;
                    
                    // Add the encryption password (same as admin password for simplicity)
                    url += `&encryption_password=${encodeURIComponent(password)}`;
                    
                    // Open in new window/tab
                    window.open(url, '_blank');
                    
                    // Clean up modal
                    resetModal();
                } else {
                    // Show error message
                    document.getElementById('passwordError').textContent = data.message || 'Incorrect password. Please try again.';
                    document.getElementById('passwordError').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('passwordError').textContent = 'Error verifying password. Please try again.';
                document.getElementById('passwordError').style.display = 'block';
                console.error('Error:', error);
            });
        });

        // Allow Enter key to trigger verification
        document.getElementById('adminPassword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('verifyPasswordBtn').click();
                e.preventDefault();
            }
        });
    });

    // Check if export button is visible on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, checking export button...');
        
        // Force all Font Awesome icons to be visible
        document.querySelectorAll('.fas, .fa-solid').forEach(icon => {
            icon.style.display = 'inline-block';
        });
    });

    // Add this new function to handle claim pagination via AJAX
    function loadClaimPage(page2) {
        fetch('lostfoundItems.php?page2=' + page2)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const newClaimTableBody = doc.getElementById('claimsTable').getElementsByTagName('tbody')[0].innerHTML;
                const newClaimPaginationControls = doc.getElementById('claimPaginationControls').innerHTML;

                document.getElementById('claimsTable').getElementsByTagName('tbody')[0].innerHTML = newClaimTableBody;
                document.getElementById('claimPaginationControls').innerHTML = newClaimPaginationControls;

                // Reattach event listeners to new pagination links
                attachClaimPaginationListeners();
            })
            .catch(error => console.error('Error:', error));
    }

    function attachClaimPaginationListeners() {
        document.querySelectorAll('.claim-pagination').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page2 = this.getAttribute('data-page');
                loadClaimPage(page2);
            });
        });
    }

    // Initialize claim pagination listeners on page load
    document.addEventListener('DOMContentLoaded', function() {
        attachClaimPaginationListeners();
    });

    // Claims table search functionality
    const searchInput = document.getElementById('claimsSearchInput');
    const claimsTable = document.getElementById('claimsTable');
    
    if (searchInput && claimsTable) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = claimsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let noResult = true;
            
            for (let i = 0; i < rows.length; i++) {
                let found = false;
                const cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    
                    if (cellText.toLowerCase().indexOf(searchText) > -1) {
                        found = true;
                        noResult = false;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
            
            // Show no results message if nothing found
            const noResultsRow = claimsTable.querySelector('.no-results');
            if (noResult && rows.length > 1) {
                if (!noResultsRow) {
                    const tbody = claimsTable.getElementsByTagName('tbody')[0];
                    const newRow = tbody.insertRow();
                    newRow.className = 'no-results';
                    const cell = newRow.insertCell();
                    cell.colSpan = 12;
                    cell.className = 'text-center';
                    cell.textContent = 'No matching records found';
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        });
    }
    
    // Claims table filter functionality
    const filterOptions = document.querySelectorAll('.claims-filter-option');
    
    filterOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const filterValue = this.getAttribute('data-filter');
            const rows = claimsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].classList.contains('no-results')) continue;
                
                if (filterValue === 'all') {
                    rows[i].style.display = '';
                } else {
                    // Column index might need adjustment based on your table structure
                    // Assuming status is in column 9 (index 8)
                    const statusCell = rows[i].querySelector('td:nth-child(9)');
                    if (statusCell) {
                        const statusText = statusCell.textContent.toLowerCase().trim();
                        rows[i].style.display = statusText.includes(filterValue) ? '' : 'none';
                    }
                }
            }
            
            // Update filter button to show current filter
            let statusClass = '';
            if (filterValue === 'pending') statusClass = 'bg-warning text-dark';
            else if (filterValue === 'approved') statusClass = 'bg-success text-white';
            else if (filterValue === 'rejected') statusClass = 'bg-danger text-white';
            
            document.getElementById('claimsFilterDropdown').innerHTML = 
                `<i class="bi bi-funnel"></i> Filter ${filterValue !== 'all' ? 
                    `<span class="filter-badge ${statusClass}">${filterValue.charAt(0).toUpperCase() + filterValue.slice(1)}</span>` : ''}`;
        });
    });
    </script>
</body>
</html>
