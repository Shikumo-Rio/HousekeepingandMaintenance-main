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
    <link rel="icon" href="img/logo.webp">
    <title>Maintenance Management</title>
</head>
<body>

    <?php include('index.php'); ?>

    
    <!-- Cards Row -->
    <div class="container">
        <!-- Title Heading -->
        <div class="p-4 mb-4 title-heading card">
            <h3>Maintenance Requests</h3>
        </div>

        <div class="row m-0 text-center mb-4">
        <!-- Requests Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Requests</h5>
                        <h3 class="card-text"><i class="fas fa-list"></i> 25</h3>
                    </div>
                </div>
            </div>
    
            <!-- Confirmed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Confirmed</h5>
                        <h3 class="card-text"><i class="fas fa-check"></i> 15</h3>
                    </div>
                </div>
            </div>
    
            <!-- Emailed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Emailed</h5>
                        <h3 class="card-text"><i class="fas fa-envelope"></i> 10</h3>
                    </div>
                </div>
            </div>
        </div>  
    </div>

    <!-- Maintenance Requests Table -->
    <div class="container">
        <div class="card shadow-lg rounded-3">
            <div class="card-body m-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-2 mt-4 m-3">Requests Overview</h3>
                    <div class="email me-4 mt-2">
                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#emailModal">
                         Email Request
                        </button>
                     </div>
                </div>
                <div class='table-responsive'>
                    <table class="table table-hover">
                            <thead class="striky-top">
                                <tr class="bg-dark text-light">
                                <th>Request Title</th>
                                <th>Description</th>
                                <th>Room Number</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT request_title, description, room_no, priority, status FROM maintenance_requests";
                            $result = $conn->query($sql);

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
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
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
                            <input type="number" class="form-control" id="requestID" name="requestID" required>
                            <label for="requestID">Request ID</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="emailAddress" name="emailAddress" required>
                            <label for="emailAddress">Email To</label>
                        </div>
                         <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 py-2">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>  
    </div>

     <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

</body>
</html>
