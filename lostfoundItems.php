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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    // Sanitize and assign form inputs
    $found_by = isset($_POST['found_by']) ? htmlspecialchars(trim($_POST['found_by'])) : '';
    $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : '';
    $room = isset($_POST['room']) ? htmlspecialchars(trim($_POST['room'])) : '';
    $date = isset($_POST['date']) ? htmlspecialchars(trim($_POST['date'])) : '';
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
</head>
<body>

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
        <div class="p-4 mt-0 lostfound-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Lost and Found</h3>
                <div class="action-buttons">
                    <button class="btn btn-success add-btn" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fa-solid fa-plus"></i> Create
                    </button>
                    <button class="btn btn-success export-btn" onclick="showExportModal()" title="Export Data">
                        <i class="fas fa-file-export"></i>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Claims History</h5>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" 
                           placeholder="Search claims..." onkeyup="filterTable()">
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
                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $limit = 5; // Items per page
                            $offset = ($page - 1) * $limit;

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
            </div>
        </div>
    </div>
    
    <!-- Modal for Submitting Lost/Found Item -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4 modal-dark">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="registerModalLabel">Submit Lost/Found</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data"> <!-- Add enctype -->
                        <div class="form-floating form-floating-sm mb-3">
                            <input type="text" class="form-control form-control-dark" id="found_by" name="found_by" placeholder="Found by" required>
                            <label for="found_by">Found by</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select form-control-dark" id="type" name="type" placeholder="Select type" required>
                                <option value="">Select Type</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>
                            <label for="type">Type</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="room" name="room" placeholder="Enter room" required>
                            <label for="room">Room/Area</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control form-control-dark" id="date" name="date" placeholder="Date" required>
                            <label for="date">Date</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="item" name="item" placeholder="Item" required>
                            <label for="item">Item</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="description" name="description" placeholder="Description" required>
                            <label for="description">Description</label>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control form-control-dark" id="picture" name="picture" accept="image/*" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="submit_request" class="btn btn-success btn-sm rounded-pill px-4 py-2">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Proof of Ownership</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="proofImage" src="" class="img-fluid" alt="Proof of Ownership">
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                    
                    <!-- Date range section -->
                    <div id="dateRangeSection">
                        <hr>
                        <h6 class="mb-3">Date Range</h6>
                        
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control form-control-dark" id="startDate">
                        </div>
                        
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control form-control-dark" id="endDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="exportData()">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
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
            });
        });

        // Handle clicks on nav tabs
        document.querySelectorAll('.nav-tab-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent default anchor behavior
                const target = this.getAttribute('href'); // Get the target tab
                activateTab(target); // Activate the corresponding tab
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

    // Export functionality
    function showExportModal() {
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
        
        // Show the modal
        new bootstrap.Modal(document.getElementById('exportModal')).show();
    }

    function exportData() {
        const exportType = document.querySelector('input[name="exportType"]:checked').value;
        const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
        
        // Get date range
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        // Validate dates
        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be after end date.');
            return;
        }
        
        // Set the appropriate title
        let title = '';
        if (exportType === 'lost_and_found') {
            title = 'Lost and Found Items';
        } else if (exportType === 'claims') {
            title = 'Claims History';
        }
        
        // Construct URL
        const url = `export_data.php?table=${exportType}&format=${exportFormat}&start=${startDate}&end=${endDate}&title=${encodeURIComponent(title)}`;
        
        // Open in new window/tab
        window.open(url, '_blank');
        
        // Close the modal
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
    }

    // Check if export button is visible on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, checking export button...');
        
        // Force all Font Awesome icons to be visible
        document.querySelectorAll('.fas, .fa-solid').forEach(icon => {
            icon.style.display = 'inline-block';
        });
    });
    </script>

    <style>
    /* Dark mode styles for modals */
    .modal-dark {
        background-color: #2c2c2c;
        color: #fff;
    }
    .modal-dark .modal-header {
        border-bottom: 1px solid #444;
    }
    .modal-dark .modal-footer {
        border-top: 1px solid #444;
    }
    .modal-dark .form-control-dark {
        background-color: #444;
        color: #fff;
        border: 1px solid #555;
    }
    .modal-dark .form-control-dark::placeholder {
        color: #bbb;
    }
    .modal-dark .form-control-dark:focus {
        background-color: #555;
        border-color: #666;
    }
    .modal-dark .btn-close {
        background-color: #fff;
    }
    .modal-dark .btn-close:hover {
        background-color: #ddd;
    }
    .modal-dark .modal-close-btn {
        background-color: #444;
        color: #fff;
    }
    .modal-dark .modal-close-btn:hover {
        background-color: #555;
    }

    /* Export button styling */
    .export-btn {
        width: 38px;
        height: 38px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .export-btn i {
        font-size: 16px;
    }

    /* Ensure Font Awesome icons display properly */
    .fas, .fa-solid {
        display: inline-block !important;
    }

    body.dark-mode .export-btn {
        background-color: #0d6efd;
        border-color: #0a58ca;
    }

    /* Add pulse animation to draw attention to the export button */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .export-btn:hover {
        animation: pulse 1s infinite;
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
    }

    .btn-success.add-btn {
        background-color: #198754;
    }

    .btn-success.add-btn:hover {
        background-color: #157347;
    }

    .btn-primary.export-btn {
        background-color: #0d6efd;
        border-color: #0a58ca;
    }

    .export-btn i, .add-btn i {
        display: inline-block !important;
    }

    /* Responsive fixes */
    @media (max-width: 576px) {
        .action-buttons {
            flex-direction: row;
            gap: 5px;
        }
        
        .add-btn {
            font-size: 12px;
            padding: 6px 12px;
        }
        
        .export-btn {
            width: 32px;
            height: 32px;
        }
    }
    </style>
</body>
</html>
