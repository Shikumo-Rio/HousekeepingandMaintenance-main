<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = $_SESSION['uname'];
    $room_no = $_SESSION['room_number'];
    $checkout_time = $_POST['checkout_time'] ?? '';
    $special_request = $_POST['special_request'] ?? '';
    $request = $_POST['request_type'] ?? 'General'; // Add request type
    $status = "Pending";
    
    $stmt = $conn->prepare("INSERT INTO checkout_notices (room_no, checkout_time, special_request, request, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $room_no, $checkout_time, $special_request, $request, $status);
    
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
    <title>Stepping Out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../img/logo.webp">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="menu-container container p-2">
        <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center mb-4">
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
        <h5 class="mt-4 m-2 mb-4 fw-semibold">Stepping Out</h5>
        <p class="text-center text-muted mb-4">Let us know if you need any services while you're away.</p>

        <!-- Housekeeping Request -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-broom me-2"></i> Housekeeping While Away</h5>
            <p>Would you like our housekeeping team to clean your room while you're out? You can request fresh towels, bed making, or a full room cleaning.</p>
            <button class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#housekeepingModal">Request Housekeeping</button>
        </div>

        <!-- Maintenance Issue Reporting -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-tools me-2"></i> Report Maintenance Issue</h5>
            <p>If you noticed any maintenance issues in your room (e.g., air conditioning not working, a leaking faucet), let us know. We can address it while you're out.</p>
            <button class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#maintenanceModal">Report an Issue</button>
        </div>

        <!-- Securing Belongings -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-lock me-2"></i> Secure Your Belongings</h5>
            <p>Before stepping out, ensure that valuable items are safely stored. If you need assistance with securing items, our front desk is happy to help.</p>
        </div>

        <!-- Notify Front Desk -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-concierge-bell me-2"></i> Notify Front Desk</h5>
            <p>If you'll be away for an extended period, you may inform the front desk for security and service purposes.</p>
            <button class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#notifyModal">Notify Front Desk</button>
        </div>

        <!-- Return Reminder -->
        <div class="text-center mt-4">
            <p class="text-muted">Enjoy your time outside! Let us know if you need anything upon your return.</p>
        </div>
    </div>

    <!-- Modals -->
    <!-- Housekeeping Modal -->
    <div class="modal fade" id="housekeepingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Housekeeping Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="housekeepingForm" class="checkout-form">
                        <input type="hidden" name="request_type" value="Housekeeping">
                        <div class="mb-3">
                            <label class="form-label">Return Time</label>
                            <input type="time" name="checkout_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Special Requests</label>
                            <textarea name="special_request" class="form-control" rows="3" placeholder="Any specific cleaning requests?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="maintenanceForm" class="checkout-form">
                        <input type="hidden" name="request_type" value="Maintenance">
                        <div class="mb-3">
                            <label class="form-label">Return Time</label>
                            <input type="time" name="checkout_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Issue Details</label>
                            <textarea name="special_request" class="form-control" rows="3" placeholder="Describe the maintenance issue"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notify Front Desk Modal -->
    <div class="modal fade" id="notifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notify Front Desk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="notifyForm" class="checkout-form">
                        <input type="hidden" name="request_type" value="Front Desk Notification">
                        <div class="mb-3">
                            <label class="form-label">Expected Return Time</label>
                            <input type="time" name="checkout_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="special_request" class="form-control" rows="3" placeholder="Any notes for the front desk?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.checkout-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('steppingOut.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request submitted successfully!');
                        this.reset();
                        $(this).closest('.modal').modal('hide');
                    } else {
                        alert('Error submitting request');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting request');
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
