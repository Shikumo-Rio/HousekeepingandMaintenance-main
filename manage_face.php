<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$success_message = '';
$error_message = '';
$user_faces = [];
$all_users = [];
$username = $_SESSION['username'];
$emp_id = $_SESSION['emp_id'];
$is_admin = ($_SESSION['user_type'] === 'Admin');

// Create directory for face images if it doesn't exist
$face_dir = __DIR__ . '/faces';
if (!file_exists($face_dir)) {
    mkdir($face_dir, 0755, true);
}

// If admin, get all users for the dropdown
if ($is_admin) {
    $users_query = $conn->query("SELECT emp_id, username FROM login_accounts ORDER BY username");
    while ($user = $users_query->fetch_assoc()) {
        $all_users[] = $user;
    }
    
    // If a user is selected from dropdown, use that user's info
    if (isset($_POST['selected_user']) && !empty($_POST['selected_user'])) {
        $selected_user_id = intval($_POST['selected_user']);
        $user_query = $conn->prepare("SELECT emp_id, username FROM login_accounts WHERE emp_id = ?");
        $user_query->bind_param("i", $selected_user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        
        if ($user_data = $user_result->fetch_assoc()) {
            $emp_id = $user_data['emp_id'];
            $target_username = $user_data['username'];
        }
    }
}

// Ensure the user directory exists
$user_face_dir = $face_dir . '/' . $emp_id;
if (!file_exists($user_face_dir)) {
    mkdir($user_face_dir, 0755, true);
}

// Handle face image upload
if (isset($_POST['upload_face'])) {
    // Get the target user
    $target_emp_id = $is_admin && isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : $emp_id;
    $target_username = $is_admin && isset($_POST['target_username']) ? $_POST['target_username'] : $username;
    
    // Ensure directory exists for the target user
    $target_face_dir = $face_dir . '/' . $target_emp_id;
    if (!file_exists($target_face_dir)) {
        mkdir($target_face_dir, 0755, true);
    }
    
    // Handle multiple file uploads
    $uploaded_count = 0;
    $failed_count = 0;
    
    // Check if files were uploaded
    if (isset($_FILES['face_images']) && !empty($_FILES['face_images']['name'][0])) {
        // Count total files
        $total_files = count($_FILES['face_images']['name']);
        
        // Loop through each file
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['face_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['face_images']['type'][$i];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($file_type, $allowed_types)) {
                    $failed_count++;
                    continue;
                }
                
                // Generate a unique filename
                $timestamp = time() + $i;
                $filename = $target_emp_id . '_face_' . $timestamp . '.jpg';
                $filepath = $target_face_dir . '/' . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['face_images']['tmp_name'][$i], $filepath)) {
                    // Insert record in database
                    $stmt = $conn->prepare("INSERT INTO face_images (emp_id, username, image_path, uploaded_at) VALUES (?, ?, ?, NOW())");
                    $rel_path = 'faces/' . $target_emp_id . '/' . $filename;
                    $stmt->bind_param("iss", $target_emp_id, $target_username, $rel_path);
                    
                    if ($stmt->execute()) {
                        $uploaded_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }
        
        if ($uploaded_count > 0) {
            $success_message = "$uploaded_count face image(s) uploaded successfully for $target_username!";
            if ($failed_count > 0) {
                $error_message = "$failed_count file(s) could not be uploaded. Please check the file types and try again.";
            }
        } else {
            $error_message = "No files were uploaded successfully. Please check the file types and try again.";
        }
    } else {
        $error_message = "Please select at least one image to upload.";
    }
}

// Handle face image deletion
if (isset($_POST['delete_face']) && isset($_POST['image_id'])) {
    $image_id = intval($_POST['image_id']);
    
    // Get image path (check if admin or image belongs to current user)
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT emp_id, username, image_path FROM face_images WHERE id = ?");
        $stmt->bind_param("i", $image_id);
    } else {
        $stmt = $conn->prepare("SELECT emp_id, username, image_path FROM face_images WHERE id = ? AND emp_id = ?");
        $stmt->bind_param("ii", $image_id, $emp_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = __DIR__ . '/' . $row['image_path'];
        $deleted_username = $row['username'];
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM face_images WHERE id = ?");
        $delete_stmt->bind_param("i", $image_id);
        
        if ($delete_stmt->execute()) {
            // Delete file
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $success_message = "Face image for $deleted_username deleted successfully!";
        } else {
            $error_message = "Error deleting image: " . $conn->error;
        }
    } else {
        $error_message = "Image not found or you don't have permission to delete it.";
    }
}

// Get face images (for admin get all or filtered, for user get only their own)
if ($is_admin && isset($_POST['selected_user'])) {
    $selected_user_id = intval($_POST['selected_user']);
    $stmt = $conn->prepare("SELECT id, emp_id, username, image_path, uploaded_at FROM face_images WHERE emp_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $selected_user_id);
} elseif ($is_admin) {
    $stmt = $conn->prepare("SELECT id, emp_id, username, image_path, uploaded_at FROM face_images ORDER BY username, uploaded_at DESC");
} else {
    $stmt = $conn->prepare("SELECT id, emp_id, username, image_path, uploaded_at FROM face_images WHERE emp_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $emp_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $user_faces[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Face Images - Paradise Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/webp" sizes="32x32" href="img/logo.webp">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #28a745;
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-green {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        .btn-green:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
        }
        .face-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .face-image:hover {
            transform: scale(1.03);
        }
        .custom-file-upload {
            display: block;
            border: 1px dashed #28a745;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .custom-file-upload:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-count {
            display: inline-block;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            line-height: 24px;
            text-align: center;
            margin-left: 5px;
        }
        .user-badge {
            background-color: #28a745;
            color: white;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
            margin-bottom: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>Manage Face Images</h4>
                        <?php if ($is_admin): ?>
                            <span class="badge bg-danger">Admin Mode</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_admin): ?>
                            <!-- Admin User Selection Form -->
                            <form method="POST" class="mb-4 p-3 bg-light rounded">
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <label for="selected_user" class="form-label"><i class="bi bi-people-fill me-2"></i>Select User</label>
                                        <select name="selected_user" id="selected_user" class="form-select">
                                            <option value="">All Users</option>
                                            <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['emp_id']; ?>" <?php echo (isset($_POST['selected_user']) && $_POST['selected_user'] == $user['emp_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['emp_id']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-filter me-2"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <p class="lead">
                            Upload clear photos of faces for facial recognition login. For best results:
                        </p>
                        <ul class="mb-4">
                            <li>Ensure good lighting conditions</li>
                            <li>Face the camera directly</li>
                            <li>Avoid wearing sunglasses or hats</li>
                            <li>Upload multiple images from slightly different angles</li>
                        </ul>
                        
                        <form method="POST" enctype="multipart/form-data" id="faceUploadForm">
                            <?php if ($is_admin && isset($_POST['selected_user']) && !empty($_POST['selected_user'])): ?>
                                <!-- Hidden fields for admin uploading face for specific user -->
                                <input type="hidden" name="target_user_id" value="<?php echo htmlspecialchars($emp_id); ?>">
                                <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($target_username ?? ''); ?>">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> 
                                    You are uploading face images for user: <strong><?php echo htmlspecialchars($target_username ?? ''); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <label for="face_images" class="custom-file-upload">
                                <i class="bi bi-cloud-arrow-up fs-3 text-success"></i>
                                <p class="mb-0 mt-2">Click to select face images or drag and drop here</p>
                                <p class="text-muted small">You can select multiple images</p>
                                <div id="preview-container" class="preview-container"></div>
                                <div id="file-counter" class="mt-2" style="display: none;">
                                    Selected: <span id="file-count">0</span> images
                                </div>
                            </label>
                            <input type="file" name="face_images[]" id="face_images" accept="image/jpeg, image/png, image/jpg" class="d-none" multiple required>
                            
                            <div class="text-center">
                                <button type="submit" name="upload_face" class="btn btn-green">
                                    <i class="bi bi-upload me-2"></i>Upload Face Images
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-images me-2"></i>
                            <?php if ($is_admin && !isset($_POST['selected_user'])): ?>
                                All User Face Images
                            <?php elseif ($is_admin): ?>
                                Face Images for <?php echo htmlspecialchars($target_username ?? 'Selected User'); ?>
                            <?php else: ?>
                                Your Face Images
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_faces)): ?>
                            <div class="alert alert-info">
                                <?php if ($is_admin && !isset($_POST['selected_user'])): ?>
                                    No face images have been uploaded yet.
                                <?php elseif ($is_admin): ?>
                                    No face images found for this user.
                                <?php else: ?>
                                    You haven't uploaded any face images yet. Upload some images to enable facial recognition login.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($user_faces as $face): ?>
                                    <div class="col-md-4 col-sm-6 mb-4">
                                        <div class="card h-100">
                                            <?php if ($is_admin): ?>
                                                <div class="user-badge m-2">
                                                    <i class="bi bi-person-fill me-1"></i>
                                                    <?php echo htmlspecialchars($face['username']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <img src="<?php echo htmlspecialchars($face['image_path']); ?>" class="face-image" alt="Face Image">
                                            <div class="card-body">
                                                <p class="card-text small text-muted">
                                                    Uploaded: <?php echo date('M d, Y g:i A', strtotime($face['uploaded_at'])); ?>
                                                </p>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this face image?');">
                                                    <input type="hidden" name="image_id" value="<?php echo $face['id']; ?>">
                                                    <button type="submit" name="delete_face" class="btn btn-sm btn-danger w-100">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="<?php echo ($_SESSION['user_type'] === 'Admin') ? 'dashboard.php' : 'index.php'; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview images before upload
        const fileInput = document.getElementById('face_images');
        const uploadLabel = document.querySelector('.custom-file-upload');
        const previewContainer = document.getElementById('preview-container');
        const fileCounter = document.getElementById('file-counter');
        const fileCountDisplay = document.getElementById('file-count');
        
        fileInput.addEventListener('change', function() {
            // Clear previous previews
            previewContainer.innerHTML = '';
            
            if (this.files.length > 0) {
                fileCounter.style.display = 'block';
                fileCountDisplay.textContent = this.files.length;
                
                // Create preview for each file
                Array.from(this.files).forEach((file, index) => {
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'preview-item';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'preview-image';
                            
                            previewItem.appendChild(img);
                            previewContainer.appendChild(previewItem);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                });
            } else {
                fileCounter.style.display = 'none';
            }
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadLabel.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadLabel.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadLabel.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadLabel.style.backgroundColor = 'rgba(40, 167, 69, 0.2)';
            uploadLabel.style.borderColor = '#28a745';
        }
        
        function unhighlight() {
            uploadLabel.style.backgroundColor = '';
            uploadLabel.style.borderColor = '#28a745';
        }
        
        uploadLabel.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                fileInput.files = files;
                
                // Trigger the change event manually
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        }
    </script>
</body>
</html>
