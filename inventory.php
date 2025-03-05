<?php
require_once 'database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Handle the request for necessities
$successMessage = ""; // Initialize a variable for the success message

if (isset($_POST['request_item'])) {
    // Get the item details from the form
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    // Fetch the item details from the 'inventory' table
    $query = "SELECT item_name, category FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $item_id); // 'i' means integer
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $item_name = $item['item_name'];
        $category = $item['category'];

        // Insert into 'requested_stocks' table
        $insertQuery = "INSERT INTO requested_stocks (item_name, quantity, category) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('sis', $item_name, $quantity, $category); // 's' = string, 'i' = integer
        if ($insertStmt->execute()) {
            $successMessage = "Stock request for {$quantity} of {$item_name} submitted successfully!";
        } else {
            echo "Error submitting stock request: " . $conn->error;
        }

        $insertStmt->close();
    } else {
        echo "Item not found.";
    }
}

// Fetch an admin's emp_id
$adminQuery = "SELECT emp_id FROM login_accounts WHERE user_type = 'admin' LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();
$emp_id = $admin['emp_id']; // Use this emp_id for notifications

// Check stock levels and add notifications for low or zero stock
$stockCheckSQL = "SELECT id, item_name, available_stock FROM inventory WHERE available_stock < 10";
$stockResult = $conn->query($stockCheckSQL);

while ($stockRow = $stockResult->fetch_assoc()) {
    if ($stockRow['available_stock'] == 0) {
        addStockNotification($conn, $stockRow['item_name'], $emp_id, 'out');
    } elseif ($stockRow['available_stock'] < 10) {
        addStockNotification($conn, $stockRow['item_name'], $emp_id, 'low');
    }
}
    // Close the database connection

$requestedQuery = "SELECT COUNT(*) AS total_requested FROM requested_stocks";
$resultRequested = mysqli_query($conn, $requestedQuery);
$totalRequested = 0; // Initialize variable
if ($resultRequested && $row = mysqli_fetch_assoc($resultRequested)) {
    $totalRequested = $row['total_requested'];
}

// Query to get total unique categories
$categoryQuery = "SELECT COUNT(DISTINCT category) AS total_categories FROM inventory";
$resultCategory = mysqli_query($conn, $categoryQuery);
$totalCategories = 0; // Initialize variable
if ($resultCategory && $row = mysqli_fetch_assoc($resultCategory)) {
    $totalCategories = $row['total_categories'];
}

// Query to get total items
$itemsQuery = "SELECT COUNT(*) AS total_items FROM inventory";
$resultItems = mysqli_query($conn, $itemsQuery);
$totalItems = 0; // Initialize variable
if ($resultItems && $row = mysqli_fetch_assoc($resultItems)) {
    $totalItems = $row['total_items'];
}


// Fetch inventory items
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);

// Fetch requested stock items
$requested_sql = "SELECT * FROM requested_stocks";
$requested_result = $conn->query($requested_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="icon" href="img/logo.webp">
</head>
<body>
<?php include 'index.php'; ?>
<div class="container">
    
    <div class="p-4 title-heading card">
        <h3>Inventory Management</h3>
    </div>
    <?php if (!empty($successMessage)):?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
    <div class="row text-center mb-4 pt-4 m-0"> 
    <!-- Total Requested Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="underline"></div>
            <div class="card-body">
                <h5 class="card-title">Total Requested</h5>
                <h3 class="card-text"><i class="fas fa-list"></i> <?php echo $totalRequested; ?></h3>
            </div>
        </div>
    </div>
    <!-- Category Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="underline"></div>
            <div class="card-body">
                <h5 class="card-title">Category</h5>
                <h3 class="card-text"><i class="fas fa-tags"></i> <?php echo $totalCategories; ?></h3>
            </div>
        </div>
    </div>
    <!-- Items Card -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card">
            <div class="underline"></div>
            <div class="card-body">
                <h5 class="card-title">Items</h5>
                <h3 class="card-text"><i class="fas fa-box"></i> <?php echo $totalItems; ?></h3>
            </div>
        </div>
    </div>
</div>

    <!-- Inventory and Requested Stocks Tables Side by Side -->
    <div class="row m-0">
        <div class="col-lg-7 mb-4">
            <div class="card centered-div">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0"><i class="fas fa-box"></i> Inventory</h3>
                        <div class="input-group mb-3">
                            <input type="text" id="searchInput" placeholder="Search items..." class="form-control small-input" onkeyup="filterTable()">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <table class="table table-hover border table-bordered" id="inventoryTable">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th scope="col">ID</th>
                                <th scope="col">Item Name</th>
                                <th scope="col">Available Stock</th>
                                <th scope="col">Category</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {

                                     // Check stock levels and apply different styles and labels
                                    if ($row['available_stock'] == 0) {
                                        $lowStockStyle = 'style="color: red;"'; // Danger for 0 stock
                                        $lowStockLabel = ' (Out of Stock)';     // Label for out of stock
                                    } elseif ($row['available_stock'] < 10) {
                                        $lowStockStyle = 'style="color: orange;"'; // Warning for low stock
                                        $lowStockLabel = ' (Low)';
                                    } else {
                                        $lowStockStyle = '';  // Default style if stock is sufficient
                                        $lowStockLabel = '';  // No label for sufficient stock
                                    }

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                                    echo "<td $lowStockStyle>" . htmlspecialchars($row['available_stock']) . "$lowStockLabel </td>";
                                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                    echo "<td>
                                        <button class='btn btn-dark btn-sm' data-toggle='modal' data-target='#requestModal" . $row['id'] . "'>
                                            <i class='fas fa-hand-paper'></i>
                                        </button>
                                    </td>";
                                    echo "</tr>";

                                    // Request Modal
                                    echo "
                                    <div class='modal fade' id='requestModal" . $row['id'] . "' tabindex='-1' role='dialog' aria-labelledby='requestModalLabel' aria-hidden='true'>
                                        <div class='modal-dialog' role='document'>
                                            <div class='modal-content'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title' id='requestModalLabel'>Request " . htmlspecialchars($row['item_name']) . "</h5>
                                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form method='POST' action=''>
                                                        <input type='hidden' name='item_id' value='" . $row['id'] . "'>
                                                        <div class='form-group'>
                                                            <label for='quantity'>Quantity</label>
                                                            <input type='number' name='quantity' class='form-control' min='1' required>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                            <button type='submit' name='request_item' class='btn btn-success'>Request</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr id='noResultsRow' style='display: none;'>
                                    <td colspan='5'>No results found</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Requested Stocks Table -->
        <div class="col-lg-5 mx-auto">
            <div class="card centered-div">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-4"><i class="fas fa-box-open"></i> Requested Stocks</h3>
                    </div>
                    <table class="table table-hover border table-bordered">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th scope="col">ID</th>
                                <th scope="col">Item Name</th>
                                <th scope="col">Requested Quantity</th>
                                <th scope="col">Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($requested_result->num_rows > 0) {
                                while ($row = $requested_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["category"]) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No requested stocks.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS and Bootstrap dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
   function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('inventoryTable');
    const tr = table.getElementsByTagName('tr');
    let noResult = true; // Track if any row is shown

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 1; j < td.length - 1; j++) { // Check item name and category
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    noResult = false; // Found a match
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none'; // Show or hide the row
    }

    // Handle no results case
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResult) {
        noResultsRow.style.display = ''; // Show the "No results" row
    } else {
        noResultsRow.style.display = 'none'; // Hide it if results are found
    }
}

setTimeout(function() {
            var successMessage = document.getElementById('success-message');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 2000);

</script>
</body>
</html>
