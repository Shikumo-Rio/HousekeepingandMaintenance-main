<?php
// Turn off warnings and notices to prevent any output before PDF generation
error_reporting(E_ERROR | E_PARSE);

require_once 'database.php';
require 'vendor/autoload.php';
// Check if session already started to avoid warning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get export parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'maintenance_requests';
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

// Get password from request or use default
$encryptionPassword = $_GET['encryption_password'] ?? "paradisehotel2025";

// Store who generated the report
$generatedBy = $_SESSION['username'] ?? 'Unknown User';

// Validate dates
if (!strtotime($startDate) || !strtotime($endDate)) {
    echo "Invalid date range";
    exit;
}

// Add one day to end date for inclusive filtering
$endDateForQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));

// Validate parameters
$validTypes = ['maintenance_requests', 'guest_maintenance', 'both'];
$validFormats = ['excel', 'pdf'];

if (!in_array($type, $validTypes) || !in_array($format, $validFormats)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid export parameters'
    ]);
    exit;
}

// Fetch maintenance requests data if needed
$maintenanceData = [];
if ($type === 'maintenance_requests' || $type === 'both') {
    $sql = "SELECT mr.*, 
                GROUP_CONCAT(DISTINCT e.name, ' (', am.emp_id, ')') as assigned_employees,
                GROUP_CONCAT(DISTINCT am.emp_id) as assigned_emp_ids
             FROM maintenance_requests mr 
             LEFT JOIN assigned_maintenance am ON mr.id = am.maintenance_request_id
             LEFT JOIN employee e ON am.emp_id = e.emp_id";
             
    // Add date and status filters
    $conditions = [];
    if (!empty($startDate) && !empty($endDate)) {
        $conditions[] = "mr.created_at BETWEEN '$startDate' AND '$endDateForQuery'";
    }
    if (!empty($status)) {
        $conditions[] = "mr.status = '$status'";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY mr.id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $maintenanceData[] = $row;
        }
    }
}

// Fetch guest maintenance data if needed
$guestMaintenanceData = [];
if ($type === 'guest_maintenance' || $type === 'both') {
    $sql = "SELECT id, uname, title, description, room, status, created_at 
            FROM guest_maintenance";
            
    // Add date and status filters
    $conditions = [];
    if (!empty($startDate) && !empty($endDate)) {
        $conditions[] = "created_at BETWEEN '$startDate' AND '$endDateForQuery'";
    }
    if (!empty($status)) {
        $conditions[] = "status = '$status'";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $guestMaintenanceData[] = $row;
        }
    }
}

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "maintenance_export_{$timestamp}";

// Export based on format
if ($format === 'excel') {
    exportToExcel($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy);
} elseif ($format === 'pdf') {
    exportToPDF($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy, $encryptionPassword);
} else {
    echo "Invalid export format";
    exit;
}

/**
 * Export data to Excel format
 */
function exportToExcel($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start output
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; }';
    echo 'th, td { border: 1px solid #000000; padding: 5px; }';
    echo 'th { background-color: #f0f0f0; font-weight: bold; }';
    echo '.summary-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '.High { color: #dc3545; }';  // Red for high priority
    echo '.Medium { color: #fd7e14; }'; // Orange for medium priority
    echo '.Low { color: #28a745; }';    // Green for low priority
    echo '.Pending { color: #ffc107; }'; // Yellow for pending status
    echo '.Completed { color: #28a745; }'; // Green for completed status
    echo '.Canceled { color: #dc3545; }'; // Red for canceled status
    echo '.report-header { font-size: 14pt; font-weight: bold; margin-bottom: 10px; }';
    echo '.report-meta { font-size: 10pt; margin-bottom: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report title and metadata
    echo '<div class="report-header">Maintenance Requests Export</div>';
    echo '<div class="report-meta">';
    echo 'Generated on: ' . date('Y-m-d H:i:s') . '<br>';
    echo 'Generated by: ' . $generatedBy . '<br>';
    echo 'Period: ' . $startDate . ' to ' . $endDate . '<br>';
    if ($status) {
        echo 'Status Filter: ' . $status . '<br>';
    }
    echo '</div>';
    
    // Maintenance Requests table
    if ($type === 'maintenance_requests' || $type === 'both') {
        echo '<h2>Maintenance Requests</h2>';
        echo '<table border="1">';
        
        // Header row
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Request Title</th>';
        echo '<th>Description</th>';
        echo '<th>Room No</th>';
        echo '<th>Priority</th>';
        echo '<th>Status</th>';
        echo '<th>Created At</th>';
        echo '<th>Scheduled</th>';
        echo '<th>Assigned To</th>';
        echo '</tr>';
        
        // Data rows
        if (count($maintenanceData) > 0) {
            foreach ($maintenanceData as $row) {
                echo '<tr>';
                echo '<td>' . ($row['id'] ? htmlspecialchars($row['id']) : 'N/A') . '</td>';
                echo '<td>' . ($row['request_title'] ? htmlspecialchars($row['request_title']) : 'N/A') . '</td>';
                echo '<td>' . ($row['description'] ? htmlspecialchars($row['description']) : 'N/A') . '</td>';
                echo '<td>' . ($row['room_no'] ? htmlspecialchars($row['room_no']) : 'N/A') . '</td>';
                
                // Apply priority class for color
                echo '<td class="' . htmlspecialchars($row['priority']) . '">' . 
                     ($row['priority'] ? htmlspecialchars($row['priority']) : 'N/A') . '</td>';
                
                // Apply status class for color
                echo '<td class="' . htmlspecialchars($row['status']) . '">' . 
                     ($row['status'] ? htmlspecialchars($row['status']) : 'N/A') . '</td>';
                
                echo '<td>' . ($row['created_at'] ? htmlspecialchars($row['created_at']) : 'N/A') . '</td>';
                echo '<td>' . ($row['schedule'] ? htmlspecialchars($row['schedule']) : 'N/A') . '</td>';
                echo '<td>' . ($row['assigned_employees'] ? htmlspecialchars($row['assigned_employees']) : 'Not Assigned') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9">No maintenance requests found</td></tr>';
        }
        
        echo '</table>';
        
        // Add summary section
        echo '<h3>Summary</h3>';
        echo '<table border="1">';
        echo '<tr class="summary-row">';
        echo '<th>Total Requests</th>';
        echo '<td>' . count($maintenanceData) . '</td>';
        echo '</tr>';
        
        // Count by status
        $statusCounts = [];
        foreach ($maintenanceData as $row) {
            $status = $row['status'] ?? 'Unknown';
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;
        }
        
        foreach ($statusCounts as $status => $count) {
            echo '<tr class="summary-row">';
            echo '<th>' . htmlspecialchars($status) . ' Requests</th>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
        
        // Count by priority
        $priorityCounts = [];
        foreach ($maintenanceData as $row) {
            $priority = $row['priority'] ?? 'Unknown';
            if (!isset($priorityCounts[$priority])) {
                $priorityCounts[$priority] = 0;
            }
            $priorityCounts[$priority]++;
        }
        
        foreach ($priorityCounts as $priority => $count) {
            echo '<tr class="summary-row">';
            echo '<th>' . htmlspecialchars($priority) . ' Priority</th>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    // Guest Maintenance table
    if ($type === 'guest_maintenance' || $type === 'both') {
        echo '<h2>Guest Maintenance Requests</h2>';
        echo '<table border="1">';
        
        // Header row
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Guest Name</th>';
        echo '<th>Title</th>';
        echo '<th>Description</th>';
        echo '<th>Room</th>';
        echo '<th>Status</th>';
        echo '<th>Created At</th>';
        echo '</tr>';
        
        // Data rows
        if (count($guestMaintenanceData) > 0) {
            foreach ($guestMaintenanceData as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['uname']) . '</td>';
                echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                echo '<td>' . htmlspecialchars($row['room']) . '</td>';
                
                // Apply status class for color
                echo '<td class="' . htmlspecialchars($row['status']) . '">' . 
                     htmlspecialchars($row['status']) . '</td>';
                
                echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">No guest maintenance requests found</td></tr>';
        }
        
        echo '</table>';
        
        // Add summary after the guest maintenance table
        echo '<h3>Summary</h3>';
        echo '<table border="1">';
        echo '<tr class="summary-row">';
        echo '<th>Total Guest Requests</th>';
        echo '<td>' . count($guestMaintenanceData) . '</td>';
        echo '</tr>';
        
        // Count by status
        $statusCounts = [];
        foreach ($guestMaintenanceData as $row) {
            $status = $row['status'] ?? 'Unknown';
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;
        }
        
        foreach ($statusCounts as $status => $count) {
            echo '<tr class="summary-row">';
            echo '<th>' . htmlspecialchars($status) . ' Requests</th>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}

/**
 * Export data to PDF with password protection
 */
function exportToPDF($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy, $encryptionPassword) {
    try {
        // Clear all previous output and buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check if TCPDF is available (preferred for security features)
        if (class_exists('TCPDF')) {
            // Create new PDF document with TCPDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Paradise Hotel');
            $pdf->SetAuthor($generatedBy);
            $pdf->SetTitle('Maintenance Export');
            $pdf->SetSubject('Maintenance Requests');
            
            // Set PDF protection
            $pdf->SetProtection(array('print', 'copy'), $encryptionPassword, null, 0, null);
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Add title and metadata
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Maintenance Export', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Cell(0, 6, 'Generated by: ' . $generatedBy, 0, 1, 'C');
            $pdf->Cell(0, 6, 'Period: ' . $startDate . ' to ' . $endDate, 0, 1, 'C');
            
            if ($status) {
                $pdf->Cell(0, 6, 'Status Filter: ' . $status, 0, 1, 'C');
            }
            
            $pdf->Ln(5);
            
            // Maintenance Requests table
            if ($type === 'maintenance_requests' || $type === 'both') {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Maintenance Requests', 0, 1, 'L');
                
                // Define column widths
                $pageWidth = $pdf->getPageWidth() - 30; // Adjust for margins
                $columnWidths = [
                    0.05 * $pageWidth, // ID
                    0.15 * $pageWidth, // Title
                    0.20 * $pageWidth, // Description
                    0.07 * $pageWidth, // Room
                    0.07 * $pageWidth, // Priority
                    0.10 * $pageWidth, // Status
                    0.10 * $pageWidth, // Created
                    0.10 * $pageWidth, // Scheduled
                    0.16 * $pageWidth  // Assigned To
                ];
                
                // Table header
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell($columnWidths[0], 8, 'ID', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[1], 8, 'Title', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[2], 8, 'Description', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[3], 8, 'Room', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[4], 8, 'Priority', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[5], 8, 'Status', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[6], 8, 'Created', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[7], 8, 'Scheduled', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[8], 8, 'Assigned To', 1, 1, 'C', true);
                
                // Table data
                $pdf->SetFont('helvetica', '', 7);
                
                // For counting purposes
                $statusCounts = [];
                $priorityCounts = [];
                
                if (count($maintenanceData) > 0) {
                    foreach ($maintenanceData as $row) {
                        // Count for the summary
                        $status = $row['status'] ?? 'Unknown';
                        $priority = $row['priority'] ?? 'Unknown';
                        
                        if (!isset($statusCounts[$status])) $statusCounts[$status] = 0;
                        if (!isset($priorityCounts[$priority])) $priorityCounts[$priority] = 0;
                        
                        $statusCounts[$status]++;
                        $priorityCounts[$priority]++;
                        
                        // Prepare data with truncation for long fields
                        $id = $row['id'];
                        $title = isset($row['request_title']) ? 
                                 (strlen($row['request_title']) > 25 ? substr($row['request_title'], 0, 22) . '...' : $row['request_title']) : 
                                 'N/A';
                        $description = isset($row['description']) ? 
                                      (strlen($row['description']) > 35 ? substr($row['description'], 0, 32) . '...' : $row['description']) : 
                                      'N/A';
                        $room = $row['room_no'] ?? 'N/A';
                        $priority = $row['priority'] ?? 'N/A';
                        $status = $row['status'] ?? 'N/A';
                        $created = substr($row['created_at'] ?? 'N/A', 0, 10);
                        $scheduled = $row['schedule'] ?? 'N/A';
                        $assigned = isset($row['assigned_employees']) && !empty($row['assigned_employees']) ? 
                                    (strlen($row['assigned_employees']) > 30 ? substr($row['assigned_employees'], 0, 27) . '...' : $row['assigned_employees']) : 
                                    'Not Assigned';
                        
                        // Set text color based on priority and status
                        if (strtolower($priority) === 'high') {
                            $pdf->SetTextColor(220, 53, 69); // Red for high priority
                        } elseif (strtolower($priority) === 'medium') {
                            $pdf->SetTextColor(253, 126, 20); // Orange for medium
                        } elseif (strtolower($priority) === 'low') {
                            $pdf->SetTextColor(40, 167, 69); // Green for low
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black default
                        }
                        
                        // Print row
                        $pdf->Cell($columnWidths[0], 7, $id, 1, 0, 'C');
                        
                        $pdf->SetTextColor(0, 0, 0); // Reset to black
                        $pdf->Cell($columnWidths[1], 7, $title, 1, 0, 'L');
                        $pdf->Cell($columnWidths[2], 7, $description, 1, 0, 'L');
                        $pdf->Cell($columnWidths[3], 7, $room, 1, 0, 'C');
                        
                        // Set text color again for priority
                        if (strtolower($priority) === 'high') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } elseif (strtolower($priority) === 'medium') {
                            $pdf->SetTextColor(253, 126, 20); // Orange
                        } elseif (strtolower($priority) === 'low') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell($columnWidths[4], 7, $priority, 1, 0, 'C');
                        
                        // Set text color for status
                        if (strtolower($status) === 'pending') {
                            $pdf->SetTextColor(255, 193, 7); // Yellow
                        } elseif (strtolower($status) === 'completed') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } elseif (strtolower($status) === 'canceled') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell($columnWidths[5], 7, $status, 1, 0, 'C');
                        
                        $pdf->SetTextColor(0, 0, 0); // Reset to black
                        $pdf->Cell($columnWidths[6], 7, $created, 1, 0, 'C');
                        $pdf->Cell($columnWidths[7], 7, $scheduled, 1, 0, 'C');
                        $pdf->Cell($columnWidths[8], 7, $assigned, 1, 1, 'L');
                        
                        // Check if we need a new page
                        if ($pdf->GetY() > 265) {
                            $pdf->AddPage();
                            
                            // Repeat header on the new page
                            $pdf->SetFont('helvetica', 'B', 8);
                            $pdf->SetFillColor(240, 240, 240);
                            $pdf->Cell($columnWidths[0], 8, 'ID', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[1], 8, 'Title', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[2], 8, 'Description', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[3], 8, 'Room', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[4], 8, 'Priority', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[5], 8, 'Status', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[6], 8, 'Created', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[7], 8, 'Scheduled', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[8], 8, 'Assigned To', 1, 1, 'C', true);
                            $pdf->SetFont('helvetica', '', 7);
                        }
                    }
                    
                    // Add summary after the table
                    $pdf->Ln(10);
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Maintenance Requests Summary', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    
                    // Total requests
                    $pdf->Cell(80, 7, 'Total Requests:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($maintenanceData), 0, 1, 'L');
                    
                    // Status counts
                    foreach ($statusCounts as $status => $count) {
                        // Set color based on status
                        if (strtolower($status) === 'pending') {
                            $pdf->SetTextColor(255, 193, 7); // Yellow
                        } elseif (strtolower($status) === 'completed') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } elseif (strtolower($status) === 'canceled') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $count, 0, 1, 'L');
                        $pdf->SetTextColor(0, 0, 0); // Reset color
                    }
                    
                    // Priority counts
                    $pdf->Ln(5);
                    foreach ($priorityCounts as $priority => $count) {
                        // Set color based on priority
                        if (strtolower($priority) === 'high') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } elseif (strtolower($priority) === 'medium') {
                            $pdf->SetTextColor(253, 126, 20); // Orange
                        } elseif (strtolower($priority) === 'low') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell(80, 7, $priority . ' Priority:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $count, 0, 1, 'L');
                        $pdf->SetTextColor(0, 0, 0); // Reset color
                    }
                } else {
                    $pdf->Cell(array_sum($columnWidths), 10, 'No maintenance requests found', 1, 1, 'C');
                }
                
                $pdf->Ln(5);
            }
            
            // Guest Maintenance Requests table
            if ($type === 'guest_maintenance' || $type === 'both') {
                if ($type === 'both' && $pdf->GetY() > 200) {
                    $pdf->AddPage();
                }
                
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Guest Maintenance Requests', 0, 1, 'L');
                
                // Define column widths
                $pageWidth = $pdf->getPageWidth() - 30; // Adjust for margins
                $columnWidths = [
                    0.05 * $pageWidth, // ID
                    0.15 * $pageWidth, // Guest Name
                    0.20 * $pageWidth, // Title
                    0.30 * $pageWidth, // Description
                    0.10 * $pageWidth, // Room
                    0.10 * $pageWidth, // Status
                    0.10 * $pageWidth  // Created At
                ];
                
                // Table header
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell($columnWidths[0], 8, 'ID', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[1], 8, 'Guest Name', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[2], 8, 'Title', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[3], 8, 'Description', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[4], 8, 'Room', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[5], 8, 'Status', 1, 0, 'C', true);
                $pdf->Cell($columnWidths[6], 8, 'Created At', 1, 1, 'C', true);
                
                // Table data
                $pdf->SetFont('helvetica', '', 7);
                
                // For counting purposes
                $statusCounts = [];
                
                if (count($guestMaintenanceData) > 0) {
                    foreach ($guestMaintenanceData as $row) {
                        // Count for the summary
                        $status = $row['status'] ?? 'Unknown';
                        if (!isset($statusCounts[$status])) $statusCounts[$status] = 0;
                        $statusCounts[$status]++;
                        
                        // Prepare data with truncation for long fields
                        $id = $row['id'];
                        $uname = isset($row['uname']) ? 
                                (strlen($row['uname']) > 20 ? substr($row['uname'], 0, 17) . '...' : $row['uname']) : 
                                'N/A';
                        $title = isset($row['title']) ? 
                                (strlen($row['title']) > 25 ? substr($row['title'], 0, 22) . '...' : $row['title']) : 
                                'N/A';
                        $description = isset($row['description']) ? 
                                      (strlen($row['description']) > 40 ? substr($row['description'], 0, 37) . '...' : $row['description']) : 
                                      'N/A';
                        $room = $row['room'] ?? 'N/A';
                        $status = $row['status'] ?? 'N/A';
                        $created = $row['created_at'] ?? 'N/A';
                        
                        // Print row
                        $pdf->Cell($columnWidths[0], 7, $id, 1, 0, 'C');
                        $pdf->Cell($columnWidths[1], 7, $uname, 1, 0, 'L');
                        $pdf->Cell($columnWidths[2], 7, $title, 1, 0, 'L');
                        $pdf->Cell($columnWidths[3], 7, $description, 1, 0, 'L');
                        $pdf->Cell($columnWidths[4], 7, $room, 1, 0, 'C');
                        
                        // Set text color for status
                        if (strtolower($status) === 'pending') {
                            $pdf->SetTextColor(255, 193, 7); // Yellow
                        } elseif (strtolower($status) === 'completed') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } elseif (strtolower($status) === 'canceled') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell($columnWidths[5], 7, $status, 1, 0, 'C');
                        
                        $pdf->SetTextColor(0, 0, 0); // Reset to black
                        $pdf->Cell($columnWidths[6], 7, $created, 1, 1, 'C');
                        
                        // Check if we need a new page
                        if ($pdf->GetY() > 265) {
                            $pdf->AddPage();
                            
                            // Repeat header on the new page
                            $pdf->SetFont('helvetica', 'B', 8);
                            $pdf->SetFillColor(240, 240, 240);
                            $pdf->Cell($columnWidths[0], 8, 'ID', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[1], 8, 'Guest Name', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[2], 8, 'Title', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[3], 8, 'Description', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[4], 8, 'Room', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[5], 8, 'Status', 1, 0, 'C', true);
                            $pdf->Cell($columnWidths[6], 8, 'Created At', 1, 1, 'C', true);
                            $pdf->SetFont('helvetica', '', 7);
                        }
                    }
                    
                    // Add summary after the table
                    $pdf->Ln(10);
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Guest Maintenance Summary', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    
                    // Total requests
                    $pdf->Cell(80, 7, 'Total Guest Requests:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($guestMaintenanceData), 0, 1, 'L');
                    
                    // Status counts
                    foreach ($statusCounts as $status => $count) {
                        // Set color based on status
                        if (strtolower($status) === 'pending') {
                            $pdf->SetTextColor(255, 193, 7); // Yellow
                        } elseif (strtolower($status) === 'completed') {
                            $pdf->SetTextColor(40, 167, 69); // Green
                        } elseif (strtolower($status) === 'canceled') {
                            $pdf->SetTextColor(220, 53, 69); // Red
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $count, 0, 1, 'L');
                        $pdf->SetTextColor(0, 0, 0); // Reset color
                    }
                } else {
                    $pdf->Cell(array_sum($columnWidths), 10, 'No guest maintenance requests found', 1, 1, 'C');
                }
            }
            
            // Output PDF
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        }
        // Fall back to FPDF if TCPDF is not available
        else if (class_exists('FPDF')) {
            // Use existing FPDF implementation (without password protection)
            useFPDF($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy);
        } else {
            // Fallback to simple HTML output if no PDF libraries are available
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>PDF Export Error</h1>";
            echo "<p>PDF generation requires a PDF library like FPDF or TCPDF.</p>";
            echo "<p>Please try exporting to Excel format instead or install a PDF library.</p>";
            echo "<p><a href='maintenance_requests.php'>Return to Maintenance Requests</a></p>";
            exit;
        }
    } catch (Exception $e) {
        // Error handling
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='maintenance_requests.php'>Return to Maintenance Requests</a></p>";
        exit;
    }
}

/**
 * Use FPDF to generate PDF (fallback)
 */
function useFPDF($maintenanceData, $guestMaintenanceData, $type, $filename, $startDate, $endDate, $status, $generatedBy) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Initialize FPDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Add title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Maintenance Export', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated by: ' . $generatedBy, 0, 1, 'C');
    $pdf->Cell(0, 6, 'Period: ' . $startDate . ' to ' . $endDate, 0, 1, 'C');
    
    if ($status) {
        $pdf->Cell(0, 6, 'Status Filter: ' . $status, 0, 1, 'C');
    }
    
    $pdf->Ln(5);
    
    // Maintenance Requests table
    if ($type === 'maintenance_requests' || $type === 'both') {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Maintenance Requests', 0, 1, 'L');
        
        // Set column widths (simplified for FPDF)
        $colWidth = [10, 30, 35, 15, 15, 15, 20, 20, 30];
        
        // Table header
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell($colWidth[1], 8, 'Title', 1, 0, 'C', true);
        $pdf->Cell($colWidth[2], 8, 'Description', 1, 0, 'C', true);
        $pdf->Cell($colWidth[3], 8, 'Room', 1, 0, 'C', true);
        $pdf->Cell($colWidth[4], 8, 'Priority', 1, 0, 'C', true);
        $pdf->Cell($colWidth[5], 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell($colWidth[6], 8, 'Created', 1, 0, 'C', true);
        $pdf->Cell($colWidth[7], 8, 'Scheduled', 1, 0, 'C', true);
        $pdf->Cell($colWidth[8], 8, 'Assigned To', 1, 1, 'C', true);
        
        // Complete the rest of the FPDF implementation as in existing code
        // ...existing code...
    }
    
    // Output PDF
    $pdf->Output('D', $filename . '.pdf');
    exit;
}
?>
