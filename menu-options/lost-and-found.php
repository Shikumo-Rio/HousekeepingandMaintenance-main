<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $found_by = $_POST['foundBy'];
    $type = $_POST['selectType'];
    $room = $_SESSION['room_number'];
    $item = $_POST['itemName'];
    $description = $_POST['description'];
    $status = "Pending";
    $action = "Processing";
    
    // Handle image upload
    $picture = "";
    if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_extension = pathinfo($_FILES["imageUpload"]["name"], PATHINFO_EXTENSION);
        $picture = uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_dir . $picture);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO lost_and_found (found_by, type, room, date, item, description, status, picture, action) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $found_by, $type, $room, $item, $description, $status, $picture, $action);
    
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
    <title>Lost and Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        <h5 class="mt-4 m-2 mb-2 fw-semibold">Lost and Found</h5>
        <!-- Lost and Found Request Form -->
        <div class="card p-4 mt-4">
            <p class="text-muted mb-4 text-center">Please fill out the details of the lost or found item.</p>
            <form id="lostFoundForm" enctype="multipart/form-data">
                <!-- Found By -->
                <div class="form-floating mb-3">
                    <input type="text" id="foundBy" name="foundBy" class="form-control" placeholder="Enter your name" required>
                    <label for="foundBy">Found By</label>
                </div>

                <!-- Select Type (Lost/Found) -->
                <div class="form-floating mb-3">
                    <select id="selectType" name="selectType" class="form-select" required>
                        <option selected disabled>Select type</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                    </select>
                    <label for="selectType">Select Type</label>
                </div>

                <!-- Room Number (readonly, from session) -->
                <div class="form-floating mb-3">
                    <input type="text" id="roomNumber" class="form-control" value="<?php echo $_SESSION['room_number']; ?>" readonly>
                    <label for="roomNumber">Room Number</label>
                </div>

                <!-- Item Name -->
                <div class="form-floating mb-3">
                    <input type="text" id="itemName" name="itemName" class="form-control" placeholder="Enter item name" required>
                    <label for="itemName">Item</label>
                </div>

                <!-- Description -->
                <div class="form-floating mb-3">
                    <textarea id="description" name="description" class="form-control" placeholder="Describe the item" style="height: 120px;" required></textarea>
                    <label for="description">Description</label>
                </div>

                <!-- Upload Image -->
                <div class="mb-3">
                    <label for="imageUpload" class="form-label fw-semibold">Upload Image (Optional)</label>
                    <input type="file" id="imageUpload" name="imageUpload" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-success fw-semibold w-100">Submit Request</button>
            </form>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#lostFoundForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);

                fetch('lost-and-found.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Report submitted successfully!');
                        this.reset();
                        document.getElementById('roomNumber').value = '<?php echo $_SESSION['room_number']; ?>';
                    } else {
                        alert('Error submitting report');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting report');
                });
            });
        });
    </script>
</body>
</html>
