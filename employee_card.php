<div class="col-md-3 mb-0 mt-4">
    <div class="card housekeeper-card shadow-sm">
        <div class="card-body text-center">
            <div class="dropdown position-absolute" style="top: 10px; right: 10px;">
                <button class="btn btn-link text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><span class="dropdown-item" onclick="editEmployee(<?= $employee['emp_id']; ?>)">Edit</span></li>
                    <li><span class="dropdown-item text-danger" onclick="deleteEmployee(<?= $employee['emp_id']; ?>)">Delete</span></li>
                </ul>
            </div>

            <div class="icon">
                <i class="fas fa-user"></i>
            </div>

            <h5 class="card-title"><?= htmlspecialchars($employee['name']); ?></h5>
            <p class="card-text">Status: <?= htmlspecialchars($employee['status']); ?></p>
            <p class="card-text text-muted"><?= ucfirst(str_replace('_', ' ', $employee['role'])); ?></p>
        </div>
    </div>
</div>
