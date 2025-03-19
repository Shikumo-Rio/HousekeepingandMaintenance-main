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

// Fetch inventory items from the API
$api_url = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/table.php?api=1&api_key=20054d820a3ba1bae07591397d8cacdf";
$inventory_data = file_get_contents($api_url);
$inventory_items = json_decode($inventory_data, true);

if ($inventory_items === null || !isset($inventory_items['items2'])) {
    echo "Error fetching inventory data.";
    $inventory_items = ['items2' => []];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        @media (max-width: 768px) {
            .table-responsive-stack tr {
                display: flex;
                flex-direction: column;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                padding: 0.5rem;
            }
            .table-responsive-stack td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem;
                border: none;
            }
            .table-responsive-stack td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 0.5rem;
            }
            .table-responsive-stack thead {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <!-- Inventory List -->
    <h3 class="mt-5">Inventory Items</h3>
    <div class="table-responsive">
        <table id="inventoryTable" class="table table-striped table-bordered table-responsive-stack" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Expiration Date</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($inventory_items['items2'] as $item) {
                if ($item['type'] === 'hotel') { // Filter items by type 'hotel'
                    echo "<tr>";
                    echo "<td data-label='ID'>" . htmlspecialchars($item['id'] ?? 'N/A') . "</td>";
                    echo "<td data-label='Category'>" . htmlspecialchars($item['category'] ?? 'N/A') . "</td>";
                    echo "<td data-label='Item Name'>" . htmlspecialchars($item['item_name'] ?? 'N/A') . "</td>";
                    echo "<td data-label='Quantity'>" . htmlspecialchars($item['quantity'] ?? 'N/A') . "</td>";
                    echo "<td data-label='Expiration Date'>" . htmlspecialchars($item['expiration_date'] ?? 'N/A') . "</td>";
                    echo "<td data-label='Description'>" . htmlspecialchars($item['description'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#inventoryTable').DataTable({
            responsive: true,
            paging: true,
            searching: true
        });
    });
</script>

<!-- Trigger the modal only if insertion was successful -->
<?php if ($showSuccessModal): ?>
    <script>
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    </script>
<?php endif; ?>

</body>
</html>
