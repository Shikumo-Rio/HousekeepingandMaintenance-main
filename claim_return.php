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
    
    $stmt = $conn->prepare("UPDATE lost_and_found SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    
    if ($stmt->execute()) {
        $message = "Status updated successfully.";
    } else {
        $message = "Error updating status.";
    }
}

// Pagination settings
$limit = 3; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
                    <th>Room</th>
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
                            <form method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                                <select name="status" class="form-select status-select" required>
                                    <option value="">Update Status</option>
                                    <option value="claimed">Claimed</option>
                                    <option value="not_claimed">Not Claimed</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-update">Update</button>
                            </form>
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
                    <a class="page-link" href="?tab=claimed&page=<?= $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=claimed&page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=claimed&page=<?= $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
