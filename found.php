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

// Pagination settings
$limit = 3; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch 'Found' items with pagination
$sql = "SELECT * FROM lost_and_found WHERE type = 'Found' LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM lost_and_found WHERE type = 'Found'");
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
    <link rel="stylesheet" href="css/lost.css">
    <title>Found Items</title>

</head>
<body>
    <div class="container mt-0">
        <h5>Found Items</h5>
        <div class="table-responsive">
            <table class="table table-hover border table-bordered">
            <thead class="sticky-top">
                        <tr>
                        <th>ID</th>
                        <th>Found by</th>
                        <th>Room/Area</th>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Picture</th>
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
                                <td><span class='badge text-bg-<?= ($row['status'] == 'pending') ? 'secondary' : 'success'; ?>'>
                                    <?= htmlspecialchars($row['status']); ?>
                                </span></td>
                                <td>
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="<?= htmlspecialchars($row['picture']); ?>" 
                                            class="img-thumbnail" width="50" height="50" 
                                            data-bs-toggle="modal" data-bs-target="#imageModalFound" 
                                            onclick="showImageInModal('<?= htmlspecialchars($row['picture']); ?>')">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan='8'>No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal for Image Preview -->
        <div class="modal fade" id="imageModalFound" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content-pic">
                    <div class="modal-header">
                        <div class="position-relative">
                            <img src="" id="modalImageFound" class="img-fluid w-100" alt="Found Item Image">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-2 p-2 rounded-circle bg-light" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination Controls -->
        <nav class="pagination-container">
            <ul class="pagination custom-pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=found&page=<?= $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=found&page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=found&page=<?= $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
     <script>
        function showImageInModal(imageUrl) {
            document.getElementById('modalImageFound').src = imageUrl;
        }
    </script>
</body>
</html>
