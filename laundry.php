<?php
session_start();
require_once("database.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Handle laundry status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $laundry_id = $_POST['laundry_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $old_status = '';
    
    // Get current status and item details before updating
    $stmt = $conn->prepare("SELECT status, item_type, quantity FROM laundry WHERE id = ?");
    $stmt->bind_param("i", $laundry_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $old_status = $row['status'];
        $item_type = $row['item_type'];
        $quantity = $row['quantity'];
    }
    
    // Update laundry status
    $stmt = $conn->prepare("UPDATE laundry SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $laundry_id);
    
    if ($stmt->execute()) {
        // If status changed to "Received from Laundry", add quantity back to inventory
        if ($status === 'Received from Laundry' && $old_status !== 'Received from Laundry') {
            // Check if item exists in inventory
            $stmt = $conn->prepare("SELECT id, quantity FROM inventory WHERE item_name = ? AND category = 'Guest Room Supplies' LIMIT 1");
            $stmt->bind_param("s", $item_type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $inventory = $result->fetch_assoc();
                $new_quantity = $inventory['quantity'] + $quantity;
                
                // Update inventory quantity
                $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_quantity, $inventory['id']);
                $stmt->execute();
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Handle supply quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    $supply_id = $_POST['supply_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $supply_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// Handle adding items to laundry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_laundry') {
    $item_type = $_POST['item_type'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $status = 'Ready for Pickup';
    
    // Check if item exists in inventory
    $stmt = $conn->prepare("SELECT id, quantity FROM inventory WHERE item_name = ? AND category = 'Guest Room Supplies' LIMIT 1");
    $stmt->bind_param("s", $item_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Item not found in inventory']);
        exit();
    }
    
    $inventory = $result->fetch_assoc();
    
    // Check if quantity is valid
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'error' => 'Quantity must be greater than zero']);
        exit();
    }
    
    // Check if there's enough quantity in inventory
    if ($quantity > $inventory['quantity']) {
        echo json_encode(['success' => false, 'error' => 'Not enough items in inventory. Available: ' . $inventory['quantity']]);
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Add to laundry
        $stmt = $conn->prepare("INSERT INTO laundry (item_type, quantity, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $item_type, $quantity, $status);
        $stmt->execute();
        
        // Reduce inventory
        $new_quantity = $inventory['quantity'] - $quantity;
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $inventory['id']);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laundry Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/housekeepertasks.css">
    <link rel="icon" href="img/logo.webp">
    <style>
        .low-stock {
            background-color: #ffecef !important;
        }
        .low-stock td {
            color: #dc3545;
        }
        .warning-icon {
            color: #dc3545;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include('index.php'); ?>

    <div class="container mt-2">
        <!-- Header Section -->
        <div class="p-4 mb-4 task-allocation-heading card mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Laundry Management</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addLaundryModal">
                    <i class="fas fa-plus"></i> Add to Laundry
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mt-4 m-0 mb-4">
            <!-- Ready for Pickup -->
            <div class="col-md-3">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Ready for Pickup</h5>
                    <h4 class="text-warning mb-0">
                        <?php
                        $readyPickupQuery = "SELECT COUNT(*) as count FROM laundry WHERE status = 'Ready for Pickup'";
                        $readyPickupResult = $conn->query($readyPickupQuery);
                        echo $readyPickupResult->fetch_assoc()['count'];
                        ?>
                    </h4>
                </div>
            </div>
            <!-- Processed by Laundry -->
            <div class="col-md-3">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Processed by Laundry</h5>
                    <h4 class="text-primary mb-0">
                        <?php
                        $processedQuery = "SELECT COUNT(*) as count FROM laundry WHERE status = 'Processed by Laundry'";
                        $processedResult = $conn->query($processedQuery);
                        echo $processedResult->fetch_assoc()['count'];
                        ?>
                    </h4>
                </div>
            </div>
            <!-- Received from Laundry -->
            <div class="col-md-3">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Received from Laundry</h5>
                    <h4 class="text-success mb-0">
                        <?php
                        $receivedQuery = "SELECT COUNT(*) as count FROM laundry WHERE status = 'Received from Laundry'";
                        $receivedResult = $conn->query($receivedQuery);
                        echo $receivedResult->fetch_assoc()['count'];
                        ?>
                    </h4>
                </div>
            </div>
            <!-- Low Stock Items -->
            <div class="col-md-3">
                <div class="card-with-line p-3 text-center card">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h4 class="text-danger mb-0">
                        <?php
                        $lowStockQuery = "SELECT COUNT(*) as count FROM inventory WHERE quantity < 10 AND category = 'Guest Room Supplies'";
                        $lowStockResult = $conn->query($lowStockQuery);
                        echo $lowStockResult->fetch_assoc()['count'];
                        ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Guest Room Supplies Section -->
        <div class="row m-0">
            <div class="col-md-12">
                <div class="p-4 task-allocation-heading card mb-4 m-0">
                    <h3>Guest Room Supplies Inventory</h3>
                </div>
                <div class="card shadow-sm border-0 custom-card">
                    <div class="card-body">
                        <div class="table-responsive custom-table">
                            <table id="suppliesTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Inventory ID</th>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // First get laundry quantities for all items
                                    $laundryItems = [];
                                    $laundryQuery = "SELECT item_type, SUM(quantity) as laundry_qty FROM laundry 
                                                    WHERE status != 'Received from Laundry' 
                                                    GROUP BY item_type";
                                    $laundryResult = $conn->query($laundryQuery);
                                    while ($laundryRow = $laundryResult->fetch_assoc()) {
                                        $laundryItems[$laundryRow['item_type']] = $laundryRow['laundry_qty'];
                                    }
                                    
                                    $query = "SELECT * FROM inventory WHERE category = 'Guest Room Supplies' ORDER BY item_name";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        $lowStock = $row['quantity'] < 10;
                                        $inLaundry = isset($laundryItems[$row['item_name']]) && $laundryItems[$row['item_name']] > 0;
                                        $rowClass = $lowStock ? 'low-stock' : '';
                                        
                                        echo "<tr class='$rowClass'>";
                                        echo "<td>{$row['id']}</td>";
                                        echo "<td>{$row['inventory_id']}</td>";
                                        echo "<td>";
                                        if ($lowStock) {
                                            echo "<i class='fas fa-exclamation-triangle warning-icon'></i>";
                                        }
                                        echo $row['item_name'];
                                        if ($inLaundry) {
                                            echo " <span class='badge bg-info'>In Laundry: {$laundryItems[$row['item_name']]}</span>";
                                        }
                                        echo "</td>";
                                        echo "<td>{$row['category']}</td>";
                                        echo "<td>{$row['sku']}</td>";
                                        echo "<td>{$row['quantity']}</td>";
                                        echo "<td><span class='badge " . 
                                            ($row['quantity'] < 10 ? 'bg-danger' : 
                                            ($row['quantity'] < 30 ? 'bg-warning' : 'bg-success')) . 
                                            "'>" . ($row['quantity'] < 10 ? 'Low Stock' : 
                                            ($row['quantity'] < 30 ? 'Moderate' : 'Sufficient')) . "</span></td>";
                                        echo "<td>
                                                <button class='btn btn-sm btn-primary me-1 update-quantity-btn' data-id='{$row['id']}' data-bs-toggle='modal' data-bs-target='#updateQuantityModal'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-sm btn-success add-laundry-btn' data-item='{$row['item_name']}' data-bs-toggle='modal' data-bs-target='#addLaundryModal'>
                                                    <i class='fas fa-tshirt'></i>
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laundry Table Section -->
        <div class="p-4 task-allocation-heading card mb-4 mt-4">
            <h3>Laundry Items</h3>
        </div>
        <div class="card shadow-sm border-0 custom-card">
            <div class="card-body">
                <div class="table-responsive custom-table">
                    <table id="laundryTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Type</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM laundry ORDER BY id DESC, updated_at DESC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['item_type']}</td>";
                                echo "<td>{$row['quantity']}</td>";
                                echo "<td><span class='badge " . 
                                    (strtolower($row['status']) == 'ready for pickup' ? 'bg-warning' : 
                                    (strtolower($row['status']) == 'processed by laundry' ? 'bg-primary' : 
                                    (strtolower($row['status']) == 'received from laundry' ? 'bg-success' : 'bg-secondary'))) . 
                                    "'>{$row['status']}</span></td>";
                                echo "<td>{$row['updated_at']}</td>";
                                echo "<td>";
                                echo "<button class='btn btn-sm btn-primary update-status-btn' data-id='{$row['id']}' data-bs-toggle='modal' data-bs-target='#updateStatusModal'>
                                        <i class='fas fa-sync-alt'></i> Update Status
                                      </button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div class="modal fade" id="updateStatusModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Laundry Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateStatusForm">
                            <input type="hidden" id="laundry_id" name="laundry_id">
                            <input type="hidden" name="action" value="update_status">
                            <div class="mb-3">
                                <label for="status" class="form-label">Select Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Choose...</option>
                                    <option value="Ready for Pickup">Ready for Pickup</option>
                                    <option value="Processed by Laundry">Processed by Laundry</option>
                                    <option value="Received from Laundry">Received from Laundry</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Quantity Modal -->
        <div class="modal fade" id="updateQuantityModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Supply Quantity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateQuantityForm">
                            <input type="hidden" id="supply_id" name="supply_id">
                            <input type="hidden" name="action" value="update_quantity">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">New Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                            </div>
                            <button type="submit" class="btn btn-success">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add to Laundry Modal -->
        <div class="modal fade" id="addLaundryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add to Laundry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addLaundryForm">
                            <input type="hidden" name="action" value="add_to_laundry">
                            <div class="mb-3">
                                <label for="item_type" class="form-label">Item Type</label>
                                <select class="form-select" id="item_type" name="item_type" required>
                                    <option value="">Select Item...</option>
                                    <?php
                                    $itemQuery = "SELECT item_name, quantity FROM inventory WHERE category = 'Guest Room Supplies' ORDER BY item_name";
                                    $itemResult = $conn->query($itemQuery);
                                    while ($item = $itemResult->fetch_assoc()) {
                                        echo "<option value='{$item['item_name']}' data-quantity='{$item['quantity']}'>
                                              {$item['item_name']} (Available: {$item['quantity']})
                                              </option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="laundry_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="laundry_quantity" name="quantity" min="1" required>
                                <div class="form-text text-muted">Available: <span id="available_quantity">0</span></div>
                            </div>
                            <button type="submit" class="btn btn-success">Add to Laundry</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#suppliesTable').DataTable({
                dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12 d-flex justify-content-center"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search supplies...",
                },
                pageLength: 5,
                ordering: true,
                info: false,
                lengthChange: false,
            });

            $('#laundryTable').DataTable({
                dom: '<"row"<"col-md-12"f>>rt<"row"<"col-12 d-flex justify-content-center"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search laundry...",
                },
                pageLength: 5,
                ordering: true,
                info: false,
                lengthChange: false,
            });

            // Update status button handlers
            $('.update-status-btn').click(function() {
                $('#laundry_id').val($(this).data('id'));
            });

            // Update quantity button handlers
            $('.update-quantity-btn').click(function() {
                $('#supply_id').val($(this).data('id'));
            });

            // Add to laundry button handlers
            $('.add-laundry-btn').click(function() {
                $('#item_type').val($(this).data('item'));
            });

            // Update available quantity when item is selected
            $('#item_type').change(function() {
                const selectedOption = $(this).find('option:selected');
                const availableQuantity = selectedOption.data('quantity') || 0;
                $('#available_quantity').text(availableQuantity);
                $('#laundry_quantity').attr('max', availableQuantity);
            });

            // Validate quantity on input
            $('#laundry_quantity').on('input', function() {
                const maxQuantity = parseInt($('#item_type').find('option:selected').data('quantity') || 0);
                const enteredQuantity = parseInt($(this).val() || 0);
                
                if (enteredQuantity > maxQuantity) {
                    $(this).addClass('is-invalid');
                    $(this).val(maxQuantity);
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Form submission handlers
            $('#updateStatusForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'laundry.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating status: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request');
                    }
                });
            });

            $('#updateQuantityForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'laundry.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating quantity: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request');
                    }
                });
            });

            $('#addLaundryForm').submit(function(e) {
                e.preventDefault();
                
                // Validate quantity before submission
                const selectedOption = $('#item_type').find('option:selected');
                const availableQuantity = parseInt(selectedOption.data('quantity') || 0);
                const enteredQuantity = parseInt($('#laundry_quantity').val() || 0);
                
                if (enteredQuantity <= 0) {
                    alert('Please enter a valid quantity greater than zero');
                    return;
                }
                
                if (enteredQuantity > availableQuantity) {
                    alert('Not enough items in inventory. Available: ' + availableQuantity);
                    return;
                }
                
                $.ajax({
                    url: 'laundry.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error adding to laundry: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request');
                    }
                });
            });

            // Style search fields
            $('.dataTables_filter input').addClass('form-control stylish-search');
            $('.dataTables_filter input').css({
                "width": "250px",
                "padding": "10px 10px",
                "border-radius": "8px",
                "border": "1px solid #ccc",
                "outline": "none",
                "transition": "0.3s ease-in-out",
                "margin-bottom": "10px",
                "font-size": "12px"
            });
        });
    </script>
</body>
</html>
