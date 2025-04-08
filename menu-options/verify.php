<?php
session_start();
require_once("../database.php");

// Define API key and URL
$api_key = "f5230f2a7c74ff308dbd06998debf755b9fa279d5beba28e9f0a86fd79e42383";
$api_url = "https://core2.paradisehoteltomasmorato.com/integ/checkin.php?api_key={$api_key}";

// Function to determine status based on dates
function getStatusInfo($check_in, $check_out, $current_date) {
    // Convert all dates to timestamps for reliable comparison
    $check_in_ts = strtotime($check_in);
    $check_out_ts = strtotime($check_out);
    $current_ts = strtotime($current_date);
    
    if ($current_ts < $check_in_ts) {
        return [
            'status' => 'future',
            'message' => 'Not checked in yet',
            'can_login' => true,
            'is_checked_in' => false,
            'is_checked_out' => false,
            'booking_status' => 'upcoming',
            'booking_message' => "Your check-in date is $check_in. Limited services are available before check-in."
        ];
    } elseif ($current_ts > $check_out_ts) {
        return [
            'status' => 'past',
            'message' => 'Already checked out',
            'can_login' => true,
            'is_checked_in' => true,
            'is_checked_out' => true,
            'booking_status' => 'past',
            'booking_message' => "Your stay ended on $check_out. Limited services are available after checkout."
        ];
    } else {
        return [
            'status' => 'active',
            'message' => 'Active stay',
            'can_login' => true,
            'is_checked_in' => true,
            'is_checked_out' => false,
            'booking_status' => 'active',
            'booking_message' => "Welcome! Your stay is from $check_in to $check_out."
        ];
    }
}

// Get the entered username and extract room number if present
$entered_uname = strtolower(mysqli_real_escape_string($conn, $_POST['uname']));
$room_from_username = '';

// Try to extract room number from username if it follows the pattern nameRxxxx
if (preg_match('/^([a-z]+)([a-z]\d+-\d+|\d+-\d+|[a-z]\d+|\d+)$/i', $entered_uname, $matches)) {
    $name_part = $matches[1];
    $room_part = $matches[2];
    
    // Check if room part has dash; if not, try to format it
    if (strpos($room_part, '-') === false) {
        // Try to insert dash if it might be a room format like "D1106" -> "D1-106"
        if (preg_match('/^([a-z]?\d+)(\d{3})$/i', $room_part, $room_matches)) {
            $room_part = $room_matches[1] . '-' . $room_matches[2];
        }
    }
    
    $room_from_username = strtoupper($room_part);
    error_log("Extracted room from username: $room_from_username, Name part: $name_part");
}

// Create context for API call with timeout
$context = stream_context_create([
    'http' => [
        'timeout' => 10 // seconds
    ]
]);

// Fetch data from the API
$api_response = file_get_contents($api_url, false, $context);
$response = json_decode($api_response, true);

// Store raw API response for debugging
$_SESSION['raw_api_response'] = $api_response;

// Extract bookings from the data array in the JSON structure
$bookings = [];
if (isset($response['data']) && is_array($response['data'])) {
    $bookings = $response['data'];
} elseif (is_array($response)) {
    // Fallback if the response doesn't have a data property
    $bookings = $response;
}

// Current date using same format as API (dd-mm-yyyy)
date_default_timezone_set('Asia/Manila');
$current_date = date('d-m-Y');

// Flag to track if user was found
$user_found = false;
$exact_room_match = false;

// Debug: Log all available bookings
error_log("Total bookings found in API: " . count($bookings));
foreach ($bookings as $index => $b) {
    error_log("Booking $index: Name: {$b['customer_name']}, Room: {$b['room_no']}, Check-in: {$b['check_in']}, Check-out: {$b['check_out']}");
}

// First look for exact match with both username and room number
foreach ($bookings as $booking) {
    // Skip incomplete records
    if (!isset($booking['customer_name']) || !isset($booking['room_no']) || 
        !isset($booking['check_in']) || !isset($booking['check_out'])) {
        continue;
    }
    
    // Extract first word from customer name and combine with room number to create username
    $customer_name_parts = explode(' ', $booking['customer_name']);
    $first_word = $customer_name_parts[0];
    $room_no = $booking['room_no'];
    
    // Create usernames both with and without dash for more flexible matching
    $expected_username = strtolower($first_word . $room_no);
    $expected_username_no_dash = strtolower($first_word . str_replace('-', '', $room_no));
    
    // Log the matching attempt for debugging
    error_log("Trying to match: entered='$entered_uname', expected='$expected_username', expected_no_dash='$expected_username_no_dash', room_from_username='$room_from_username', API room='$room_no'");
    
    // EXACT MATCH: Check if username exactly matches OR if extracted room matches API room
    if ($entered_uname === $expected_username || 
        $entered_uname === $expected_username_no_dash || 
        ($room_from_username && strtoupper($room_from_username) === strtoupper($room_no))) {
        
        // If we extracted a room from username, ensure it matches this booking's room
        if ($room_from_username && strtoupper($room_from_username) !== strtoupper($room_no)) {
            error_log("Username contains room $room_from_username but API returned different room $room_no - skipping");
            continue; // Skip to next booking if rooms don't match
        }
        
        $user_found = true;
        $exact_room_match = true;
        error_log("EXACT MATCH FOUND: Username=$entered_uname, Room={$booking['room_no']}, Check-in={$booking['check_in']}, Check-out={$booking['check_out']}");
        
        // Save the exact booking record from API - no modification at all
        $_SESSION['debug_booking_record'] = $booking;
        
        // Store the exact dates from API without any manipulation
        $check_in = $booking['check_in'];
        $check_out = $booking['check_out'];
        
        // Calculate timestamps once to ensure consistency
        $check_in_ts = strtotime($check_in);
        $check_out_ts = strtotime($check_out);
        $current_ts = strtotime($current_date);
        
        // Use the getStatusInfo function with the original API values
        $statusInfo = getStatusInfo($check_in, $check_out, $current_date);
        
        // Store all relevant data in session - using exact API values
        $_SESSION['verified'] = true;
        $_SESSION['uname'] = $booking['customer_name'];
        $_SESSION['room_number'] = $booking['room_no'];
        
        // Store original dates from API exactly as received
        $_SESSION['check_in'] = $check_in;
        $_SESSION['check_out'] = $check_out;
        $_SESSION['check_in_raw'] = $check_in;
        $_SESSION['check_out_raw'] = $check_out;
        
        // Store formatted Y-m-d dates for database compatibility
        $check_in_date = DateTime::createFromFormat('d-m-Y', $check_in);
        $check_out_date = DateTime::createFromFormat('d-m-Y', $check_out);
        
        if ($check_in_date && $check_out_date) {
            $_SESSION['check_in_ymd'] = $check_in_date->format('Y-m-d');
            $_SESSION['check_out_ymd'] = $check_out_date->format('Y-m-d');
        }
        
        // Store booking status flags
        $_SESSION['is_checked_in'] = $statusInfo['is_checked_in'];
        $_SESSION['is_checked_out'] = $statusInfo['is_checked_out'];
        $_SESSION['booking_status'] = $statusInfo['booking_status'];
        $_SESSION['booking_message'] = $statusInfo['booking_message'];
        
        // Save timestamps for easier comparison
        $_SESSION['check_in_timestamp'] = $check_in_ts;
        $_SESSION['check_out_timestamp'] = $check_out_ts;
        $_SESSION['current_timestamp'] = $current_ts;
        
        // Store consistent debug info using raw API values
        $_SESSION['date_debug'] = [
            'api_check_in' => $check_in,
            'api_check_out' => $check_out,
            'current_date' => $current_date,
            'status' => $statusInfo['status'],
            'message' => $statusInfo['message'],
            'can_login' => $statusInfo['can_login'], 
            'is_checked_in' => $statusInfo['is_checked_in'],
            'is_checked_out' => $statusInfo['is_checked_out'],
            'check_in_timestamp' => $check_in_ts,
            'check_out_timestamp' => $check_out_ts,
            'current_timestamp' => $current_ts,
            'debug_formula' => "Current ($current_ts) < Check-in ($check_in_ts): " . ($current_ts < $check_in_ts ? 'True' : 'False'),
            'debug_formula2' => "Current ($current_ts) > Check-out ($check_out_ts): " . ($current_ts > $check_out_ts ? 'True' : 'False'),
            'match_type' => 'exact_room_match'
        ];
        
        error_log("Login successful: User " . $_SESSION['uname'] . " with Room: " . $_SESSION['room_number'] . ", Check-in: " . $check_in . ", Check-out: " . $check_out);
        header("Location: services.php");
        exit();
    }
}

// Only if no exact room match was found, try looser matching by name only
if (!$exact_room_match) {
    error_log("No exact room match found, trying name-only match");
    foreach ($bookings as $booking) {
        // Skip incomplete records
        if (!isset($booking['customer_name']) || !isset($booking['room_no']) || 
            !isset($booking['check_in']) || !isset($booking['check_out'])) {
            continue;
        }
        
        // Extract first word from customer name and combine with room number to create username
        $customer_name_parts = explode(' ', $booking['customer_name']);
        $first_word = $customer_name_parts[0];
        
        // Only match by the name part
        if (stripos($entered_uname, strtolower($first_word)) === 0) {
            $user_found = true;
            error_log("NAME-ONLY MATCH FOUND: Username=$entered_uname matches name '{$first_word}' from room {$booking['room_no']}");
            
            // Process this booking using the same logic as above
            $_SESSION['debug_booking_record'] = $booking;
            
            $check_in = $booking['check_in'];
            $check_out = $booking['check_out'];
            
            $check_in_ts = strtotime($check_in);
            $check_out_ts = strtotime($check_out);
            $current_ts = strtotime($current_date);
            
            $statusInfo = getStatusInfo($check_in, $check_out, $current_date);
            
            $_SESSION['verified'] = true;
            $_SESSION['uname'] = $booking['customer_name'];
            $_SESSION['room_number'] = $booking['room_no'];
            
            $_SESSION['check_in'] = $check_in;
            $_SESSION['check_out'] = $check_out;
            $_SESSION['check_in_raw'] = $check_in;
            $_SESSION['check_out_raw'] = $check_out;
            
            $check_in_date = DateTime::createFromFormat('d-m-Y', $check_in);
            $check_out_date = DateTime::createFromFormat('d-m-Y', $check_out);
            
            if ($check_in_date && $check_out_date) {
                $_SESSION['check_in_ymd'] = $check_in_date->format('Y-m-d');
                $_SESSION['check_out_ymd'] = $check_out_date->format('Y-m-d');
            }
            
            $_SESSION['is_checked_in'] = $statusInfo['is_checked_in'];
            $_SESSION['is_checked_out'] = $statusInfo['is_checked_out'];
            $_SESSION['booking_status'] = $statusInfo['booking_status'];
            $_SESSION['booking_message'] = $statusInfo['booking_message'];
            
            $_SESSION['check_in_timestamp'] = $check_in_ts;
            $_SESSION['check_out_timestamp'] = $check_out_ts;
            $_SESSION['current_timestamp'] = $current_ts;
            
            $_SESSION['date_debug'] = [
                'api_check_in' => $check_in,
                'api_check_out' => $check_out,
                'current_date' => $current_date,
                'status' => $statusInfo['status'],
                'message' => $statusInfo['message'],
                'can_login' => $statusInfo['can_login'], 
                'is_checked_in' => $statusInfo['is_checked_in'],
                'is_checked_out' => $statusInfo['is_checked_out'],
                'check_in_timestamp' => $check_in_ts,
                'check_out_timestamp' => $check_out_ts,
                'current_timestamp' => $current_ts,
                'debug_formula' => "Current ($current_ts) < Check-in ($check_in_ts): " . ($current_ts < $check_in_ts ? 'True' : 'False'),
                'debug_formula2' => "Current ($current_ts) > Check-out ($check_out_ts): " . ($current_ts > $check_out_ts ? 'True' : 'False'),
                'match_type' => 'name_only_match'
            ];
            
            error_log("Login successful (name-only match): User " . $_SESSION['uname'] . " with Room: " . $_SESSION['room_number'] . ", Check-in: " . $check_in . ", Check-out: " . $check_out);
            header("Location: services.php");
            exit();
        }
    }
}

// If no matching user was found
if (!$user_found) {
    error_log("Username not found: " . $entered_uname);
    echo "<script>alert('Username not found. Please check your username and try again.'); window.history.back();</script>";
    exit();
}

// Close database connection
$conn->close();
?>
