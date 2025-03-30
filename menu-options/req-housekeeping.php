<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_SESSION['uname'];
    $room = $_SESSION['room_number'];
    $request = "Housekeeping";
    $status = "pending";
    
    // Get selected services and additional request
    $data = json_decode(file_get_contents('php://input'), true);
    $selectedServices = $data['services'] ?? [];
    $additionalRequest = $data['additionalRequest'] ?? '';
    
    // Build details string
    $details = implode(", ", $selectedServices);
    if ($additionalRequest) {
        $details = $details . ($details ? " | " : "") . $additionalRequest;
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO customer_messages (uname, request, details, room, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $uname, $request, $details, $room, $status);
    
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
    <title>Request Housekeeping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../img/logo.webp">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="menu-container container p-2 m-0">
        <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0 fw-semibold">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center mb-4">
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 
                    </a>
                </div>
        <h5 class="mb-4 mt-4 m-2 fw-semibold">Housekeeping Services</h5>
        <!-- Housekeeping Request Form -->
        <div class="card shadow-lg border-0 rounded-4 p-4 text-center">
            <p class="text-muted mb-4">Select the services you need, and our team will take care of the rest.</p>
            <form id="housekeepingForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="fullCleaning" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-broom me-2"></i> Full Cleaning</span>
                            <input type="checkbox" id="fullCleaning" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="towelReplacement" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-tshirt me-2"></i> Towel Replacement</span>
                            <input type="checkbox" id="towelReplacement" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="bedMaking" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-bed me-2"></i> Bed Making</span>
                            <input type="checkbox" id="bedMaking" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="bathroomCleaning" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-bath me-2"></i> Bathroom Cleaning</span>
                            <input type="checkbox" id="bathroomCleaning" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="trashRemoval" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-trash-alt me-2"></i> Trash Removal</span>
                            <input type="checkbox" id="trashRemoval" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="spillClean" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-fill-drip me-2"></i> Spill Clean Up</span>
                            <input type="checkbox" id="spillClean" class="form-check-input">
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label for="vacuuming" class="btn btn-outline w-100 py-2 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="fas fas fa-wind me-2"></i> Vacuuming</span>
                            <input type="checkbox" id="vacuuming" class="form-check-input">
                        </label>
                    </div>
                </div>          
                <!-- Submit Request Button (Opens Modal) -->
                <button type="button" class="btn btn-success mt-4 w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#requestModal">
                    Submit Request
                </button>
            </form>

            <!-- Modal -->
            <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header bg-body text-dark rounded-top-4">
                            <h5 class="modal-title fw-bold" id="requestModalLabel">
                                <i class="fas fa-concierge-bell me-2"></i> Confirm Your Request
                            </h5>
                            <button type="button" class="btn-close btn-close-secondary" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p class="text-muted mb-4">Review your housekeeping requests and add any additional details if needed.</p>
                            <label for="modalAdditionalRequest" class="form-label fw-semibold mb-2 d-flex align-items-right">Additional Requests:</label>
                            <textarea id="modalAdditionalRequest" class="form-control rounded-3 border-success" rows="3" placeholder="Type your request here..."></textarea>
                        </div>
                        <div class="modal-footer d-flex justify-content-center border-0">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold" id="confirmSubmit">
                                <i class="fas fa-check-circle me-2"></i> Confirm & Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#confirmSubmit').click(function() {
                // Collect all checked services
                const selectedServices = [];
                $('input[type="checkbox"]:checked').each(function() {
                    selectedServices.push($(this).closest('label').find('span').text().trim());
                });

                const additionalRequest = $('#modalAdditionalRequest').val();

                // Send request to server
                fetch('req-housekeeping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        services: selectedServices,
                        additionalRequest: additionalRequest
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#housekeepingForm').trigger('reset');
                        $('#requestModal').modal('hide');
                        alert('Your housekeeping request has been submitted successfully!');
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
