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

// Fetch 'Lost' items
$sql = "SELECT * FROM lost_and_found WHERE type = 'Lost'";
$result = $conn->query($sql);

// Pagination settings
$limit = 5; // Number of records per page
$page = isset($_GET['lostPage']) ? (int)$_GET['lostPage'] : 1;
$offset = ($page - 1) * $limit;

// Fetch 'Lost' items with pagination
$sql = "SELECT * FROM lost_and_found WHERE type = 'Lost' LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM lost_and_found WHERE type = 'Lost'");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Assuming you fetched the image path from the database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/lost.css">
    <link rel="icon" href="img/logo.webp">
    <title>Found Items</title>
</head>
<body>
    <div class="container mt-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Lost Items</h5>
            <div class="d-flex align-items-center">
                <!-- Filter Dropdown -->
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="lostFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="lostFilterDropdown">
                        <li><a class="dropdown-item filter-option" href="#" data-filter="all">All Status</a></li>
                        <li><a class="dropdown-item filter-option" href="#" data-filter="pending">Pending</a></li>
                        <li><a class="dropdown-item filter-option" href="#" data-filter="claimed">Claimed</a></li>
                        <li><a class="dropdown-item filter-option" href="#" data-filter="lost">Lost</a></li>
                        <li><a class="dropdown-item filter-option" href="#" data-filter="found">Found</a></li>
                    </ul>
                </div>
                
                <!-- Search Input -->
                <input type="text" id="lostSearchInput" class="form-control form-control-sm" style="width: 200px;" placeholder="Search items...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover border-0 table-bordered" id="lostItemsTable">
                <thead class="striky-top">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
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
                                <td>
                                    <?php
                                    switch ($row['status']) {
                                        case 'pending':
                                            echo "<span class='status-badge status-pending'>Pending</span>";
                                            break;
                                        case 'claimed':
                                            echo "<span class='status-badge status-claimed'>Claimed</span>";
                                            break;
                                        case 'lost':
                                            echo "<span class='status-badge status-lost'>Lost</span>";
                                            break;
                                        case 'found':
                                            echo "<span class='status-badge status-found'>Found</span>";
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="<?= htmlspecialchars($row['picture']); ?>" 
                                            class="img-thumbnail" width="50" height="50" 
                                            data-bs-toggle="modal" data-bs-target="#imageModalLost" 
                                            onclick="showImageInModalLost('<?= htmlspecialchars($row['picture']); ?>')">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan='8' class="text-center">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal for Image Preview -->
        <div class="modal fade" id="imageModalLost" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content-pic border-0">
                    <div class="modal-header border-0">
                        <div class="position-relative">
                            <img src="" id="modalImageLost" class="img-fluid w-100" alt="Lost Item Image">
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
                    <a class="page-link" href="?tab=lost&lostPage=<?= $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=lost&lostPage=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=lost&lostPage=<?= $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showImageInModalLost(imageUrl) {
            document.getElementById('modalImageLost').src = imageUrl;
        }
        
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('lostSearchInput');
            const table = document.getElementById('lostItemsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            searchInput.addEventListener('keyup', function() {
                const searchText = searchInput.value.toLowerCase();
                
                for (let i = 0; i < rows.length; i++) {
                    let found = false;
                    const cells = rows[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        
                        if (cellText.toLowerCase().indexOf(searchText) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = found ? '' : 'none';
                }
            });
            
            // Filter functionality
            const filterOptions = document.querySelectorAll('.filter-option');
            filterOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filterValue = this.getAttribute('data-filter');
                    
                    for (let i = 0; i < rows.length; i++) {
                        if (filterValue === 'all') {
                            rows[i].style.display = '';
                        } else {
                            const statusCell = rows[i].querySelector('td:nth-child(7)');
                            const statusText = statusCell.textContent.toLowerCase().trim();
                            
                            rows[i].style.display = statusText.includes(filterValue) ? '' : 'none';
                        }
                    }
                    
                    // Update dropdown button text to show current filter
                    document.getElementById('lostFilterDropdown').innerHTML = 
                        `<i class="bi bi-funnel"></i> Filter: ${filterValue === 'all' ? 'All' : filterValue.charAt(0).toUpperCase() + filterValue.slice(1)}`;
                });
            });
        });
    </script>
</body>
</html>