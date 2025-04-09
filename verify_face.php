<?php
session_start();

// Debug log to track access to this page
file_put_contents(__DIR__ . '/face_verification_access.txt', date('[Y-m-d H:i:s] ') . "Verify face page accessed. Session data: " . json_encode($_SESSION) . "\n", FILE_APPEND);

// Check if there's a pending login
if (!isset($_SESSION['pending_login']) || $_SESSION['pending_login'] !== true) {
    file_put_contents(__DIR__ . '/face_verification_access.txt', date('[Y-m-d H:i:s] ') . "No pending login, redirecting to login.php\n", FILE_APPEND);
    header("Location: login.php");
    exit;
}

// Debug logging function
function logDebug($message) {
    file_put_contents(__DIR__ . '/face_verification_log.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Log the pending username for debugging
if (isset($_SESSION['pending_username'])) {
    logDebug("Pending verification for user: " . $_SESSION['pending_username']);
}

// If verification is successful via AJAX, complete the login process
if (isset($_POST['face_verified']) && $_POST['face_verified'] === 'true' && isset($_POST['username']) && isset($_POST['emp_id'])) {
    require_once 'database.php';
    
    // Get the username and emp_id from POST data
    $username = $_POST['username'];
    $emp_id = $_POST['emp_id'];
    
    // Look up the user with BOTH username and emp_id
    $stmt = $conn->prepare("SELECT * FROM login_accounts WHERE username = ? AND emp_id = ?");
    $stmt->bind_param("si", $username, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Check if user has face images registered
        $face_check = $conn->prepare("SELECT COUNT(*) as count FROM face_images WHERE username = ? AND emp_id = ?");
        $face_check->bind_param("si", $username, $emp_id);
        $face_check->execute();
        $face_result = $face_check->get_result()->fetch_assoc();
        
        if ($face_result['count'] > 0) {
            // User has face images registered, continue with authentication
            $user_type = $user['user_type'];
            
            // Complete the login process
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['emp_id'] = $emp_id;
            
            // Clear pending status
            unset($_SESSION['pending_login']);
            unset($_SESSION['pending_username']);
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['pending_user_type']);
            
            // Update login status in database
            $update_stmt = $conn->prepare("UPDATE login_accounts SET is_online = 1, last_activity = NOW() WHERE username = ?");
            $update_stmt->bind_param("s", $username);
            $update_stmt->execute();
            
            // Set employee status to active in employee table
            $update_employee_status = $conn->prepare("UPDATE employee SET status = 'active' WHERE emp_id = ?");
            $update_employee_status->bind_param("i", $emp_id);
            $update_employee_status->execute();
            
            $logQuery = "INSERT INTO login_logs (emp_id) VALUES (?)";
            $log_stmt = $conn->prepare($logQuery);
            $log_stmt->bind_param("i", $emp_id);
            $log_stmt->execute();
            
            $notificationQuery = "INSERT INTO notifications (emp_id, message) VALUES (?, '$emp_id have successfully logged in with facial verification.')";
            $notification_stmt = $conn->prepare($notificationQuery);
            $notification_stmt->bind_param("i", $emp_id);
            $notification_stmt->execute();
            
            // Response for AJAX
            echo json_encode(['success' => true, 'redirect' => getRedirectUrl($user_type)]);
        } else {
            // User has no registered face images
            echo json_encode(['success' => false, 'message' => 'No face images registered for this user. Please register your face first.', 'redirect' => 'login.php?face_error=1']);
        }
    } else {
        // User not found
        echo json_encode(['success' => false, 'message' => 'User not found', 'redirect' => 'login.php?face_error=1']);
    }
    exit;
}

// Function to determine redirect URL based on user type
function getRedirectUrl($user_type) {
    switch ($user_type) {
        case 'Employee':
            return "/housekeepingandmaintenance-main/housekeepers/index.php";
        case 'Admin':
            return "dashboard.php";
        case 'Maintenance':
            return "/housekeepingandmaintenance-main/maintenance-department/maintenance.php";
        case 'maintenance-staff':
            return "/housekeepingandmaintenance-main/maintenance-staff/staff.php";
        default:
            return "login.php";
    }
}

// New endpoint to get user's face images - Modified to include emp_id verification
if (isset($_GET['get_face_images']) && isset($_GET['username']) && isset($_GET['emp_id'])) {
    require_once 'database.php';
    $username = $_GET['username'];
    $emp_id = $_GET['emp_id'];
    
    // Get face images that match BOTH username AND emp_id
    $stmt = $conn->prepare("SELECT image_path, face_descriptor FROM face_images WHERE username = ? AND emp_id = ?");
    $stmt->bind_param("si", $username, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $face_images = [];
    $face_descriptors = [];
    
    while ($row = $result->fetch_assoc()) {
        $face_images[] = $row['image_path'];
        // Also get stored face descriptors if available
        if (!empty($row['face_descriptor'])) {
            $face_descriptors[] = $row['face_descriptor'];
        }
    }
    
    echo json_encode([
        'success' => true, 
        'images' => $face_images,
        'descriptors' => $face_descriptors
    ]);
    exit;
}

// If verification failed
if (isset($_POST['face_verified']) && $_POST['face_verified'] === 'false') {
    // Clear pending login
    unset($_SESSION['pending_login']);
    unset($_SESSION['pending_username']);
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['pending_user_type']);
    
    echo json_encode(['success' => false, 'redirect' => 'login.php?face_error=1']);
    exit;
}

$username = htmlspecialchars($_SESSION['pending_username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification - Paradise Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/webp" sizes="32x32" href="img/logo.webp">
    <style>
        body, html {
            height: 100%;
            background-position: center; 
            background-repeat: no-repeat; 
            background-size: cover;
            background-image: url(img/bgpd.jpg);
        }
        
        .verification-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .verification-card {
            width: 420px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 120px;
        }
        
        .webcam-container {
            position: relative;
            width: 100%;
            max-width: 370px;
            height: 280px;
            margin: 0 auto 20px;
            border-radius: 10px;
            overflow: hidden;
            background-color: #000;
        }
        
        #webcam {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            transform: scaleX(-1);
        }
        
        #overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
        }
        
        .btn-verify {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .btn-verify:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
        }
        
        .status-text {
            font-size: 1.1rem;
            margin-bottom: 15px;
            text-align: center;
            color: white;
        }
        
        .progress-container {
            height: 5px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #28a745;
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .instruction-text {
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border-radius: 5px;
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            z-index: 100;
        }
        
        .arrow {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 30px;
            color: yellow;
            animation: pulse 1.5s infinite;
            z-index: 101;
        }
        
        .arrow-up {
            top: 15px;
        }
        
        .arrow-down {
            bottom: 15px;
        }
        
        @keyframes pulse {
            0% { opacity: 0.3; }
            50% { opacity: 1; }
            100% { opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <img src="img/logo.webp" alt="Paradise Logo" class="logo">
            <h3 class="text-center text-light mb-3">Face Verification</h3>
            <p class="text-light text-center mb-4">
                Hello <strong><?php echo $username; ?></strong>, please look at the camera to verify your identity.
            </p>
            
            <div class="webcam-container">
                <video id="webcam" autoplay muted playsinline></video>
                <canvas id="overlay"></canvas>
                <div id="head-movement-instruction" class="instruction-text" style="display: none;">
                    Please move your head as directed
                </div>
                <div id="arrow-up" class="arrow arrow-up" style="display: none;">⬆</div>
                <div id="arrow-down" class="arrow arrow-down" style="display: none;">⬇</div>
                <div id="spinner" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            
            <div class="progress-container">
                <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
            </div>
            
            <div id="status-message" class="status-text">
                <div class="spinner-border spinner-border-sm text-light me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Loading face verification...
            </div>
            
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>
    
    <!-- Face-API.js library -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <script>
        // DOM elements
        const webcamElement = document.getElementById('webcam');
        const overlayCanvas = document.getElementById('overlay');
        const statusMessage = document.getElementById('status-message');
        const progressBar = document.getElementById('progress-bar');
        const spinner = document.getElementById('spinner');
        const headMovementInstruction = document.getElementById('head-movement-instruction');
        const arrowUp = document.getElementById('arrow-up');
        const arrowDown = document.getElementById('arrow-down');
        
        // Variables
        let stream = null;
        let isModelLoaded = false;
        let detectionInterval = null;
        let faceDetected = false;
        let liveDescriptor = null;
        let verificationProgress = 0;
        let referenceDescriptors = [];
        let checkingFace = false;
        let verificationAttempts = 0;
        let consecutiveMatches = 0;
        const MAX_VERIFICATION_ATTEMPTS = 5;
        const REQUIRED_CONSECUTIVE_MATCHES = 3; // Require multiple successful matches in a row
        const SIMILARITY_THRESHOLD = 0.45; // Lower threshold = stricter matching (was 0.7)
        
        // Liveness detection variables
        let livenessState = 'waiting'; // waiting -> face_detected -> move_up -> move_down -> verified
        let initialFaceY = null;
        let currentFaceY = null;
        let faceMovementHistory = [];
        const HISTORY_SIZE = 10;
        const MOVEMENT_THRESHOLD = 10; // Lowered from 15 to 8 - less movement required
        const STABLE_THRESHOLD = 5; // Lowered from 5 to 3 - more sensitive to stability
        let movementUpDetected = false;
        let movementDownDetected = false;
        let stablePositionDetected = false;
        let livenessVerified = false;
        
        // Initialize
        async function init() {
            try {
                await startWebcam();
                await loadFaceApiModels();
                await loadReferenceImages();
                startFaceDetection();
            } catch (error) {
                statusMessage.innerHTML = `<span class="text-danger">Error: ${error.message}</span>`;
                console.error('Face verification error:', error);
            }
        }
        
        // Start webcam
        async function startWebcam() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                });
                webcamElement.srcObject = stream;
                
                return new Promise(resolve => {
                    webcamElement.onloadedmetadata = () => resolve();
                });
            } catch (error) {
                statusMessage.innerHTML = `<span class="text-danger">Cannot access camera: ${error.message}</span>`;
                throw error;
            }
        }
        
        // Load Face-API models
        async function loadFaceApiModels() {
            statusMessage.innerHTML = '<div class="spinner-border spinner-border-sm text-light me-2" role="status"></div> Loading face recognition models...';
            
            try {
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                
                isModelLoaded = true;
                statusMessage.innerHTML = 'Models loaded. Loading your reference images...';
            } catch (error) {
                statusMessage.innerHTML = `<span class="text-danger">Error loading models: ${error.message}</span>`;
                throw error;
            }
        }
        
        // Load reference face images for comparison
        async function loadReferenceImages() {
            try {
                const username = '<?php echo $username; ?>';
                const emp_id = '<?php echo $_SESSION['pending_user_id']; ?>';
                
                const response = await fetch(`verify_face.php?get_face_images=true&username=${encodeURIComponent(username)}&emp_id=${encodeURIComponent(emp_id)}`);
                const data = await response.json();
                
                console.log("Reference images response:", data);
                
                if (!data.success || (!data.images || data.images.length === 0) && (!data.descriptors || data.descriptors.length === 0)) {
                    statusMessage.innerHTML = '<span class="text-warning">No reference face data found. Please register your face first.</span>';
                    setTimeout(() => {
                        window.location.href = 'login.php?face_error=2';
                    }, 3000);
                    return;
                }
                
                statusMessage.innerHTML = 'Loading your face profile...';
                
                // Use stored descriptors if available
                if (data.descriptors && data.descriptors.length > 0) {
                    console.log("Using stored face descriptors");
                    for (const descriptorData of data.descriptors) {
                        try {
                            // Convert stored descriptor string to Float32Array if necessary
                            let descriptor;
                            if (typeof descriptorData === 'string') {
                                descriptor = new Float32Array(JSON.parse(descriptorData));
                            } else {
                                descriptor = descriptorData;
                            }
                            referenceDescriptors.push(descriptor);
                        } catch (error) {
                            console.error("Error parsing stored descriptor:", error);
                        }
                    }
                }
                
                // If no stored descriptors, extract from images
                if (referenceDescriptors.length === 0 && data.images && data.images.length > 0) {
                    // Process each reference image to extract face descriptors
                    for (const imagePath of data.images) {
                        try {
                            console.log("Processing reference image:", imagePath);
                            const imgElement = document.createElement('img');
                            imgElement.src = imagePath;
                            
                            // Wait for image to load
                            await new Promise((resolve, reject) => {
                                imgElement.onload = resolve;
                                imgElement.onerror = () => reject(new Error(`Failed to load image: ${imagePath}`));
                            });
                            
                            // Detect face and get descriptor
                            const detection = await faceapi.detectSingleFace(imgElement, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor();
                            
                            if (detection) {
                                console.log("Face detected in reference image");
                                referenceDescriptors.push(detection.descriptor);
                            } else {
                                console.log("No face detected in reference image");
                            }
                        } catch (imgError) {
                            console.error(`Error processing reference image: ${imagePath}`, imgError);
                        }
                    }
                }
                
                if (referenceDescriptors.length === 0) {
                    statusMessage.innerHTML = '<span class="text-warning">Could not extract face data from your images. Please try again or register new images.</span>';
                    setTimeout(() => {
                        window.location.href = 'login.php?face_error=3';
                    }, 3000);
                    return;
                }
                
                statusMessage.innerHTML = `Loaded ${referenceDescriptors.length} face profiles. Please look at the camera.`;
                console.log(`Loaded ${referenceDescriptors.length} reference descriptors`);
                
            } catch (error) {
                statusMessage.innerHTML = `<span class="text-danger">Error loading reference images: ${error.message}</span>`;
                console.error('Error loading reference images:', error);
                setTimeout(() => {
                    window.location.href = 'login.php?face_error=4';
                }, 3000);
            }
        }
        
        // Start face detection loop
        function startFaceDetection() {
            if (!isModelLoaded) return;
            
            // Set up canvas
            overlayCanvas.width = webcamElement.videoWidth;
            overlayCanvas.height = webcamElement.videoHeight;
            
            detectionInterval = setInterval(detectFace, 100);
        }
        
        // Calculate face descriptor similarity (Euclidean distance)
        function calculateSimilarity(descriptor1, descriptor2) {
            return faceapi.euclideanDistance(descriptor1, descriptor2);
        }
        
        // Find best match among reference descriptors
        function findBestMatch(liveDescriptor) {
            if (referenceDescriptors.length === 0) return null;
            
            let bestMatch = {
                distance: Number.MAX_VALUE,
                index: -1
            };
            
            for (let i = 0; i < referenceDescriptors.length; i++) {
                const distance = calculateSimilarity(liveDescriptor, referenceDescriptors[i]);
                console.log(`Comparing with reference ${i}: distance = ${distance}`);
                if (distance < bestMatch.distance) {
                    bestMatch.distance = distance;
                    bestMatch.index = i;
                }
            }
            
            // Only return a match if it's below our threshold
            if (bestMatch.distance >= SIMILARITY_THRESHOLD) {
                console.log("Best match distance too high:", bestMatch.distance);
                return null;
            }
            
            return bestMatch;
        }
        
        // Update liveness state machine
        function updateLivenessState(face, landmarks) {
            if (!face) return;
            
            // Get face y-position (we use nose position as reference point for tracking movement)
            const nose = landmarks.getNose();
            const nosePosition = nose[0]; // Nose tip
            currentFaceY = nosePosition.y;
            
            // Add to history
            faceMovementHistory.push(currentFaceY);
            if (faceMovementHistory.length > HISTORY_SIZE) {
                faceMovementHistory.shift();
            }
            
            const ctx = overlayCanvas.getContext('2d');
            
            // State machine
            switch (livenessState) {
                case 'waiting':
                    if (faceDetected) {
                        livenessState = 'face_detected';
                        initialFaceY = currentFaceY;
                        faceMovementHistory = [initialFaceY]; // Reset history
                        statusMessage.innerHTML = 'Face detected! Starting liveness check...';
                        // Show the instruction
                        headMovementInstruction.style.display = 'block';
                        arrowUp.style.display = 'block';
                    }
                    break;
                
                case 'face_detected':
                    // Wait for the user to look up
                    statusMessage.innerHTML = 'Please move your head UP slowly';
                    headMovementInstruction.textContent = 'Please move your head UP';
                    
                    // Check if head has moved up significantly
                    if (initialFaceY - currentFaceY > MOVEMENT_THRESHOLD) {
                        movementUpDetected = true;
                        livenessState = 'move_up';
                        arrowUp.style.display = 'none';
                        arrowDown.style.display = 'block';
                        faceMovementHistory = []; // Reset history for the next move
                        statusMessage.innerHTML = 'Good! Now move your head DOWN';
                        headMovementInstruction.textContent = 'Please move your head DOWN';
                    }
                    break;
                
                case 'move_up':
                    // User has moved head up, now wait for down movement
                    // Check if head has moved down significantly
                    if (faceMovementHistory.length >= 3) {
                        const avgRecent = faceMovementHistory.slice(-3).reduce((a, b) => a + b, 0) / 3;
                        if (avgRecent > initialFaceY + MOVEMENT_THRESHOLD) {
                            movementDownDetected = true;
                            livenessState = 'move_down';
                            arrowDown.style.display = 'none';
                            statusMessage.innerHTML = 'Perfect! Face movement verified!';
                            headMovementInstruction.textContent = 'Liveness confirmed!';
                            livenessVerified = true;
                            
                            // Hide instructions after a short delay
                            setTimeout(() => {
                                headMovementInstruction.style.display = 'none';
                                statusMessage.innerHTML = 'Identity verification in progress...';
                            }, 1500);
                        }
                    }
                    break;
                
                case 'move_down':
                    // Already verified liveness, proceed to facial recognition
                    break;
            }
            
            // Debug drawing - show the head movement detection
            if (face) {
                // Draw nose position
                ctx.fillStyle = 'yellow';
                ctx.beginPath();
                ctx.arc(nosePosition.x, nosePosition.y, 5, 0, 2 * Math.PI);
                ctx.fill();
                
                // Text showing current state and position
                ctx.font = '14px Arial';
                ctx.fillStyle = 'white';
                ctx.fillText(`Liveness: ${livenessState}`, 10, 20);
            }
        }
        
        // Verify if the face matches any reference image
        async function verifyFace() {
            if (!liveDescriptor || checkingFace || !livenessVerified) return;
            
            checkingFace = true;
            verificationAttempts++;
            
            try {
                console.log("Verifying face, attempt:", verificationAttempts);
                const bestMatch = findBestMatch(liveDescriptor);
                
                console.log("Best match:", bestMatch);
                
                if (bestMatch) {
                    // Match found, increment consecutive matches counter
                    consecutiveMatches++;
                    console.log("Match found! Distance:", bestMatch.distance, "Consecutive matches:", consecutiveMatches);
                    
                    // We need multiple consecutive matches to be sure
                    if (consecutiveMatches >= REQUIRED_CONSECUTIVE_MATCHES) {
                        spinner.style.display = 'block';
                        statusMessage.innerHTML = '<div class="spinner-border spinner-border-sm text-light me-2" role="status"></div> Identity verified! Logging you in...';
                        
                        // Send successful verification to server
                        const username = '<?php echo $username; ?>';
                        const emp_id = '<?php echo $_SESSION['pending_user_id']; ?>';
                        const formData = new FormData();
                        formData.append('face_verified', 'true');
                        formData.append('username', username);
                        formData.append('emp_id', emp_id);
                        
                        const response = await fetch('verify_face.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            statusMessage.innerHTML = '<span class="text-success">Identity verified successfully!</span>';
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            statusMessage.innerHTML = `<span class="text-danger">${data.message || 'Verification failed. Please try again.'}</span>`;
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        }
                        
                        clearInterval(detectionInterval);
                        return;
                    } else {
                        // Still need more matches
                        statusMessage.innerHTML = `Face match found (${consecutiveMatches}/${REQUIRED_CONSECUTIVE_MATCHES}). Keep looking at the camera...`;
                        checkingFace = false;
                    }
                } else {
                    // No match found, reset consecutive matches
                    consecutiveMatches = 0; 
                    statusMessage.innerHTML = 'Face not recognized. Please ensure proper lighting and position.';
                    
                    if (verificationAttempts >= MAX_VERIFICATION_ATTEMPTS) {
                        // Too many failed attempts
                        console.log("Max verification attempts reached");
                        statusMessage.innerHTML = '<span class="text-danger">Could not verify your identity. Redirecting to login...</span>';
                        
                        const formData = new FormData();
                        formData.append('face_verified', 'false');
                        
                        await fetch('verify_face.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        setTimeout(() => {
                            window.location.href = 'login.php?face_error=5';
                        }, 2000);
                        
                        clearInterval(detectionInterval);
                        return;
                    }
                    
                    checkingFace = false;
                }
                
            } catch (error) {
                statusMessage.innerHTML = `<span class="text-danger">Verification error: ${error.message}</span>`;
                console.error("Verification error:", error);
                checkingFace = false;
            }
        }
        
        // Detect face in webcam feed
        async function detectFace() {
            if (!isModelLoaded || !webcamElement.srcObject || checkingFace) return;
            
            try {
                const options = new faceapi.TinyFaceDetectorOptions({ 
                    inputSize: 320, 
                    scoreThreshold: 0.5 
                });
                
                const result = await faceapi.detectSingleFace(webcamElement, options)
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                
                const ctx = overlayCanvas.getContext('2d');
                ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                
                if (result) {
                    // Face detected
                    faceDetected = true;
                    liveDescriptor = result.descriptor;
                    
                    // Update liveness detection
                    updateLivenessState(result.detection, result.landmarks);
                    
                    // Draw detection box
                    ctx.strokeStyle = livenessVerified ? '#28a745' : '#ffc107';
                    ctx.lineWidth = 2;
                    
                    const box = result.detection.box;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                    
                    // Add text showing verification progress
                    if (livenessVerified && consecutiveMatches > 0) {
                        ctx.fillStyle = '#28a745';
                        ctx.font = '14px Arial';
                        ctx.fillText(`Match progress: ${consecutiveMatches}/${REQUIRED_CONSECUTIVE_MATCHES}`, box.x, box.y - 5);
                    }
                    
                    // Update verification progress
                    if (livenessVerified && verificationProgress < 100) {
                        verificationProgress += 5;
                        progressBar.style.width = `${verificationProgress}%`;
                    }
                    
                    if (livenessVerified && verificationProgress >= 100 && !checkingFace) {
                        // Automatically verify without button click
                        verifyFace();
                    }
                } else {
                    // No face detected - reset consecutive matches if face disappears
                    if (consecutiveMatches > 0) {
                        consecutiveMatches = 0;
                    }
                    
                    // Reset progress if face disappears
                    if (verificationProgress > 0) {
                        verificationProgress = Math.max(0, verificationProgress - 10);
                        progressBar.style.width = `${verificationProgress}%`;
                    }
                    
                    statusMessage.innerHTML = 'No face detected. Please look directly at the camera.';
                    faceDetected = false;
                    
                    // Reset liveness state if we lose the face
                    if (livenessState !== 'waiting' && livenessState !== 'move_down') {
                        livenessState = 'waiting';
                        headMovementInstruction.style.display = 'none';
                        arrowUp.style.display = 'none';
                        arrowDown.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Face detection error:', error);
            }
        }
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            if (detectionInterval) {
                clearInterval(detectionInterval);
            }
        });
        
        // Brightness-based blink detection variables
        let irisC = []; // Store eye brightness values over time
        let nowBlinking = false; // Current blinking state
        let blinkCount = 0; // Number of blinks detected
        const irisBufferMax = 100; // Maximum values to store (matches script.js)
        const vThreshold = 1.5; // Brightness threshold multiplier (matches script.js)
        
        // Function to get eye brightness using the same approach as script.js
        function getEyeBrightness(landmarks, ctx) {
            try {
                // Get positions of eye landmarks
                const landmarkPositions = landmarks.positions;
                
                // Get left eye region (using same indices as script.js)
                const leftEyeX = landmarkPositions[37].x; // 38-1 for zero-based indexing
                const leftEyeY = landmarkPositions[37].y;
                const leftEyeWidth = landmarkPositions[38].x - landmarkPositions[37].x;
                const leftEyeHeight = landmarkPositions[41].y - landmarkPositions[37].y;
                
                // Get pixel data from video frame for eye region
                const canvas = document.createElement('canvas');
                canvas.width = webcamElement.videoWidth;
                canvas.height = webcamElement.videoHeight;
                const tempCtx = canvas.getContext('2d');
                tempCtx.drawImage(webcamElement, 0, 0, canvas.width, canvas.height);
                
                // Calculate pixel coordinates (matching script.js approach)
                const x = Math.floor(leftEyeX + leftEyeWidth/2);
                const y = Math.floor(leftEyeY + leftEyeHeight/2);
                
                // Get average RGB value from this pixel
                const frame = tempCtx.getImageData(0, 0, canvas.width, canvas.height);
                const pixelIndex = Math.floor(x + y * canvas.width);
                const r = frame.data[pixelIndex * 4];
                const g = frame.data[pixelIndex * 4 + 1];
                const b = frame.data[pixelIndex * 4 + 2];
                
                // Calculate brightness (average of RGB)
                return Math.floor((r + g + b) / 3);
            } catch (error) {
                console.error("Error calculating eye brightness:", error);
                return 0;
            }
        }
        
        // Enhanced update liveness state function with brightness-based blink detection
        function updateLivenessState(face, landmarks) {
            if (!face) return;
            
            const ctx = overlayCanvas.getContext('2d');
            
            // Get face position for head movement detection
            const nose = landmarks.getNose();
            const nosePosition = nose[0]; // Nose tip
            currentFaceY = nosePosition.y;
            
            // Get eye brightness for blink detection (using script.js approach)
            const brightness = getEyeBrightness(landmarks, ctx);
            
            // Add to history (same as script.js)
            irisC.push(brightness);
            if (irisC.length > irisBufferMax) {
                irisC.shift();
            }
            
            // Calculate mean brightness (same as script.js)
            let meanIrisC = irisC.reduce(function(sum, element) {
                return sum + element;
            }, 0);
            meanIrisC = meanIrisC / irisC.length;
            
            // Get current brightness
            let currentIrisC = irisC[irisC.length-1];
            
            // Detect blinks using exactly the same logic as script.js
            if (irisC.length >= 50) { // Need enough samples, but lower than script.js's 100 for faster response
                if (nowBlinking == false) {
                    if (currentIrisC >= meanIrisC * vThreshold) {
                        nowBlinking = true;
                        console.log("Blink started - eyes closed");
                    }
                } else {
                    if (currentIrisC < meanIrisC * vThreshold) {
                        nowBlinking = false;
                        blinkCount += 1;
                        console.log("Blink completed - count:", blinkCount);
                        
                        // If we're in the waiting or face_detected state, progress to next stage
                        if (livenessState === 'waiting' || livenessState === 'face_detected') {
                            movementUpDetected = true;
                            livenessState = 'move_up';
                            arrowUp.style.display = 'none';
                            arrowDown.style.display = 'block';
                            statusMessage.innerHTML = 'Blink detected! Now move your head DOWN';
                            headMovementInstruction.textContent = 'Please move your head DOWN';
                        }
                    }
                }
            }
            
            // Add face movement tracking to history
            faceMovementHistory.push(currentFaceY);
            if (faceMovementHistory.length > HISTORY_SIZE) {
                faceMovementHistory.shift();
            }
            
            // Existing state machine with modifications for blink detection
            switch (livenessState) {
                case 'waiting':
                    if (faceDetected) {
                        livenessState = 'face_detected';
                        initialFaceY = currentFaceY;
                        faceMovementHistory = [initialFaceY]; // Reset history
                        statusMessage.innerHTML = 'Face detected! Please blink once...';
                        headMovementInstruction.style.display = 'block';
                        headMovementInstruction.textContent = 'Please blink once';
                    }
                    break;
                
                case 'face_detected':
                    // Just wait for blink detection to trigger state change
                    break;
                
                case 'move_up':
                    // After blink, wait for head movement down
                    if (faceMovementHistory.length >= 3) {
                        const avgRecent = faceMovementHistory.slice(-3).reduce((a, b) => a + b, 0) / 3;
                        if (avgRecent > initialFaceY + MOVEMENT_THRESHOLD) {
                            movementDownDetected = true;
                            livenessState = 'move_down';
                            arrowDown.style.display = 'none';
                            statusMessage.innerHTML = 'Perfect! Face movement verified!';
                            headMovementInstruction.textContent = 'Liveness confirmed!';
                            livenessVerified = true;
                            
                            // Hide instructions after a short delay
                            setTimeout(() => {
                                headMovementInstruction.style.display = 'none';
                                statusMessage.innerHTML = 'Identity verification in progress...';
                            }, 1500);
                        }
                    }
                    break;
                
                case 'move_down':
                    // Already verified liveness, proceed to facial recognition
                    break;
            }
            
            // Debug drawing - show values from blink detection
          
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>