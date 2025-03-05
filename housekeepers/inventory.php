<?php
// Connect to the database
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}
if ($_SESSION['user_type'] !== 'Employee') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}

// Initialize modal flag to false
$showSuccessModal = false;

// Insert inventory item into requested_stocks table if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];

    // Insert into requested_stocks table
    $sql_requested = "INSERT INTO requested_stocks (item_name, quantity, category) VALUES ('$item_name', '$quantity', '$category')";

    if ($conn->query($sql_requested) === TRUE) {
        $showSuccessModal = true; // Set the flag to true if insertion is successful
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch inventory items from the database
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'nav.php'; ?>
<div class="container mt-5">
    <h2>Inventory</h2>
    
    <!-- Add Inventory Form -->
    <form method="POST" action="">
        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Item Name" required>
            <label for="item_name" class="form-label">Enter Item Name</label>
        </div>
        <div class="form-floating mb-3">
            <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Quantity" required>
            <label for="quantity" class="form-label">Enter Quantity</label>
        </div>
        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="category" name="category" placeholder="Category" required>
            <label for="category" class="form-label">Enter Category</label>
        </div>
        <button type="submit" class="btn btn-primary">Request</button>
    </form>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    New requested stock added successfully!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory List -->
    <h3 class="mt-5">Inventory Items</h3>
    <table class="table table-bordered">
        <thead>
            <tr class="">
                <th>ID</th>
                <th>Item Name</th>
                <th>Available Stock</th>
                <th>Category</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch inventory items with correct column names
        $sql = "SELECT id, item_name, available_stock, category FROM inventory";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['available_stock']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No items found</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Trigger the modal only if insertion was successful -->
<?php if ($showSuccessModal): ?>
    <script>
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    </script>
<?php endif; ?>

</body>
</html>
