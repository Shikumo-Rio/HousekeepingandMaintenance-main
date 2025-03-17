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
    <link rel="stylesheet" href="css/lostfoundItems.css">
    <link rel="icon" href="img/logo.webp">
    <title>Lost and Found Management</title>
</head>
<body>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            <h3>Lost and Found Management</h3>
            <button class="btn btn-success add-btn" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fa-solid fa-plus"></i>Create
            </button>
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
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="registerModalLabel">Submit Lost/Found</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data"> <!-- Add enctype -->
                        <div class="form-floating form-floating-sm mb-3">
                            <input type="text" class="form-control" id="found_by" name="found_by" placeholder="Found by" required>
                            <label for="found_by">Found by</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="type" name="type" placeholder="Select type" required>
                                <option value="">Select Type</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>
                            <label for="type">Type</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="room" name="room" placeholder="Enter room" required>
                            <label for="room">Room/Area</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="date" name="date" placeholder="Date" required>
                            <label for="date">Date</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="item" name="item" placeholder="Item" required>
                            <label for="item">Item</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="description" name="description" placeholder="Description" required>
                            <label for="description">Description</label>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="picture" name="picture" accept="image/*" required>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Proof of Ownership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="proofImage" src="" class="img-fluid" alt="Proof of Ownership">
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
                window.location.hash = target; // Update the URL hash
            });
        });

        // Check for initial hash on page load
        const currentHash = window.location.hash;
        if (currentHash) {
            activateTab(currentHash); // Activate the tab based on the hash
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
    </script>
    
</body>
</html>
