<?php
require_once('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

$message = "";

// Handle status update
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $guest_name = isset($_POST['guest_name']) ? htmlspecialchars(trim($_POST['guest_name'])) : '';
    $room_no = isset($_POST['room_no']) ? htmlspecialchars(trim($_POST['room_no'])) : '';
    $contact_info = isset($_POST['contact_info']) ? htmlspecialchars(trim($_POST['contact_info'])) : '';
    $area_lost = isset($_POST['area_lost']) ? htmlspecialchars(trim($_POST['area_lost'])) : '';
    $item_description = isset($_POST['item_description']) ? htmlspecialchars(trim($_POST['item_description'])) : '';
    $proof_id = isset($_POST['proof_id']) ? htmlspecialchars(trim($_POST['proof_id'])) : '';
    $date_lost = isset($_POST['date_lost']) ? htmlspecialchars(trim($_POST['date_lost'])) : '';
    $date_claimed = date('Y-m-d'); // Current date for date_claimed
    $validated_by = $_SESSION['username']; // Current admin user
    $claim_status = 'pending';

    try {
        // Start transaction
        $conn->begin_transaction();

        // Handle file upload for proof of ownership
        $proof_ownership = '';
        if (isset($_FILES['proof_ownership']) && $_FILES['proof_ownership']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/proofs/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . time() . '_' . basename($_FILES["proof_ownership"]["name"]);
            if (move_uploaded_file($_FILES["proof_ownership"]["tmp_name"], $target_file)) {
                $proof_ownership = $target_file;
            }
        }

        // Insert into claims table
        $stmt = $conn->prepare("INSERT INTO claims (lost_item_id, guest_name, room_no, contact_info, area_lost, description, proof_ownership, proof_id, claim_status, validated_by, date_claimed, date_lost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssssssss', 
            $id, 
            $guest_name, 
            $room_no,
            $contact_info,
            $area_lost, 
            $item_description, 
            $proof_ownership, 
            $proof_id, 
            $claim_status,
            $validated_by,
            $date_claimed,
            $date_lost
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting into claims: " . $stmt->error);
        }

        // Update status in lost_and_found table
        $stmt = $conn->prepare("UPDATE lost_and_found SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating lost_and_found: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();
        $message = "Claim submitted successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }

    // Output any error messages for debugging
    if ($message !== "Claim submitted successfully!") {
        error_log("Claim submission error: " . $message);
    }
}

// Pagination settings
$limit = 3; // Number of records per page
$page = isset($_GET['claimedPage']) ? (int)$_GET['claimedPage'] : 1;
$offset = ($page - 1) * $limit;

// Fetch all 'Claimed' items with pagination (type can be anything, remove 'claimed')
$sql = "SELECT * FROM lost_and_found LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM lost_and_found");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="lost.css">
    <title>Claimed/Return Items</title>
    <link rel="stylesheet" href="css/lost.css">
    <link rel="icon" href="img/logo.webp">
</head>
<body>

    <div class="container mt-0">
        <h5>Claimed/Return Items</h5>
        <table class="table table-hover border table-bordered">
           <thead class="striky-top">
                 <tr>
                    <th>ID</th>
                    <th>Found by</th>
                    <th>Room/Area</th>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']); ?></td>
                        <td><?= htmlspecialchars($row['found_by']); ?></td>
                        <td><?= htmlspecialchars($row['room']); ?></td>
                        <td><?= htmlspecialchars($row['date']); ?></td>
                        <td><?= htmlspecialchars($row['item']); ?></td>
                        <td><?= htmlspecialchars($row['description']); ?></td>
                        <td>
                            <span class='badge status-badge bg-<?= ($row['status'] == 'pending') ? 'secondary' : 'success'; ?>'>
                                <?= htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-update" data-bs-toggle="modal" data-bs-target="#claimValidationModal" 
                                    data-id="<?= htmlspecialchars($row['id']); ?>"
                                    data-item="<?= htmlspecialchars($row['item']); ?>"
                                    data-description="<?= htmlspecialchars($row['description']); ?>">
                                Update
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr><td colspan='8'>No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <nav class="pagination-container">
            <ul class="pagination custom-pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=claimed&claimedPage=<?= $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=claimed&claimedPage=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=claimed&claimedPage=<?= $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Claim Validation Modal -->
    <div class="modal fade" id="claimValidationModal" tabindex="-1" aria-labelledby="claimValidationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4 modal-dark">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="claimValidationModalLabel">Claim Validation</h5>
                    <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= ($message === "Claim submitted successfully!") ? 'success' : 'danger' ?> mb-3">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="modalItemId">
                        <input type="hidden" name="status" value="claimed">
                        
                        <div class="item-details mb-3 p-3 bg-light rounded item-details-dark">
                            <h6>Item Details:</h6>
                            <p id="modalItemDetails" class="mb-0"></p>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="guest_name" name="guest_name" required>
                            <label>Guest Name</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="room_no" name="room_no" required>
                            <label>Room Number</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="contact_info" name="contact_info" required>
                            <label>Contact Information</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="area_lost" name="area_lost" required>
                            <label>Area Where Lost</label>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control form-control-dark" id="item_description" name="item_description" style="height: 100px" required></textarea>
                            <label>Description of the Item</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proof of Ownership (Optional)</label>
                            <input type="file" class="form-control form-control-dark" name="proof_ownership" accept="image/*">
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control form-control-dark" id="proof_id" name="proof_id" required>
                            <label>ID Number/Proof Reference</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="date" class="form-control form-control-dark" id="date_lost" name="date_lost" required>
                            <label>Date Lost</label>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-success">Submit Claim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Dark mode styles for claim validation modal */
    body.dark-mode .item-details-dark {
        background-color: #2a2a2a !important;
        color: #f8f9fa !important;
    }
    
    body.dark-mode .item-details-dark h6 {
        color: #f8f9fa !important;
    }
    
    /* Form label in dark mode */
    body.dark-mode .form-label {
        color: #f8f9fa !important;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var claimValidationModal = document.getElementById('claimValidationModal');
        claimValidationModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-id');
            var itemName = button.getAttribute('data-item');
            var itemDesc = button.getAttribute('data-description');
            
            // Set values in modal
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalItemDetails').textContent = itemName + ' - ' + itemDesc;
            
            // Set current date as max date for date_lost
            document.getElementById('date_lost').max = new Date().toISOString().split('T')[0];
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
