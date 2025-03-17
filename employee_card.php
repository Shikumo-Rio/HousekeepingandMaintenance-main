<div class="col-md-3 mb-0 mt-4">
    <div class="card housekeeper-card shadow-sm" onclick="showEmployeeDetails(<?= $employee['emp_id']; ?>)" style="cursor: pointer;">
        <div class="card-body text-center">
            

            <div class="icon">
                <i class="fas fa-user"></i>
            </div>

            <h5 class="card-title"><?= htmlspecialchars($employee['name']); ?></h5>
            <p class="card-text">Status: <?= htmlspecialchars($employee['status']); ?></p>
            <p class="card-text text-muted"><?= ucfirst(str_replace('_', ' ', $employee['role'])); ?></p>
        </div>
    </div>
</div>
