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

// Validate parameters
$validTypes = ['maintenance_requests', 'guest_maintenance', 'both'];
$validFormats = ['excel', 'pdf']; // Removed 'csv'

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
             LEFT JOIN employee e ON am.emp_id = e.emp_id 
             GROUP BY mr.id";
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
    exportToExcel($maintenanceData, $guestMaintenanceData, $type, $filename);
} elseif ($format === 'pdf') {
    exportToPDF($maintenanceData, $guestMaintenanceData, $type, $filename);
} else {
    echo "Invalid export format";
    exit;
}

/**
 * Export data to Excel format
 */
function exportToExcel($maintenanceData, $guestMaintenanceData, $type, $filename) {
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
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report title
    echo '<h1>Maintenance Requests Export</h1>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
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
                echo '<td>' . ($row['priority'] ? htmlspecialchars($row['priority']) : 'N/A') . '</td>';
                echo '<td>' . ($row['status'] ? htmlspecialchars($row['status']) : 'N/A') . '</td>';
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
                foreach ($row as $key => $value) {
                    echo '<td>' . ($value ? htmlspecialchars($value) : 'N/A') . '</td>';
                }
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
 * Export data to PDF
 */
function exportToPDF($maintenanceData, $guestMaintenanceData, $type, $filename) {
    try {
        // Check if any PDF library is available
        if (class_exists('FPDF')) {
            // Use FPDF
            useFPDF($maintenanceData, $guestMaintenanceData, $type, $filename);
        } elseif (class_exists('TCPDF')) {
            // Use TCPDF
            useTCPDF($maintenanceData, $guestMaintenanceData, $type, $filename);
        } else {
            // Fallback to Excel if PDF libraries are not available
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>PDF Export Error</h1>";
            echo "<p>PDF libraries not found. Please try exporting to Excel format instead.</p>";
            echo "<p><a href='maintenance_requests.php'>Return to Maintenance Requests</a></p>";
            exit;
        }
    } catch (Exception $e) {
        // If PDF generation fails, show error
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='maintenance_requests.php'>Return to Maintenance Requests</a></p>";
        exit;
    }
}

/**
 * Use FPDF to generate PDF
 */
function useFPDF($maintenanceData, $guestMaintenanceData, $type, $filename) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Initialize FPDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Add title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Maintenance Request', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Maintenance Requests Table
    if ($type === 'maintenance_requests' || $type === 'both') {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Maintenance Requests', 0, 1, 'L');
        
        // Set column widths
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
        
        // Table data
        $pdf->SetFont('Arial', '', 7);
        if (count($maintenanceData) > 0) {
            foreach ($maintenanceData as $row) {
                $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                $pdf->Cell($colWidth[1], 7, substr($row['request_title'], 0, 20), 1, 0, 'L');
                $pdf->Cell($colWidth[2], 7, substr($row['description'], 0, 25), 1, 0, 'L');
                $pdf->Cell($colWidth[3], 7, $row['room_no'], 1, 0, 'C');
                $pdf->Cell($colWidth[4], 7, $row['priority'], 1, 0, 'C');
                $pdf->Cell($colWidth[5], 7, $row['status'], 1, 0, 'C');
                $pdf->Cell($colWidth[6], 7, substr($row['created_at'], 0, 10), 1, 0, 'C');
                $pdf->Cell($colWidth[7], 7, $row['schedule'] ?: 'N/A', 1, 0, 'C');
                $pdf->Cell($colWidth[8], 7, $row['assigned_employees'] ?: 'Not Assigned', 1, 1, 'L');
                
                // Check if we need a new page
                if ($pdf->GetY() > 260) {
                    $pdf->AddPage();
                    
                    // Repeat header on the new page
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
                    $pdf->SetFont('Arial', '', 7);
                }
            }
            
            // Add summary after the table
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, 'Maintenance Requests Summary', 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);
            
            // Total requests
            $pdf->Cell(80, 7, 'Total Requests:', 0, 0, 'L');
            $pdf->Cell(0, 7, count($maintenanceData), 0, 1, 'L');
            
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
                $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                $pdf->Cell(0, 7, $count, 0, 1, 'L');
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
            
            $pdf->Ln(5);
            foreach ($priorityCounts as $priority => $count) {
                $pdf->Cell(80, 7, $priority . ' Priority:', 0, 0, 'L');
                $pdf->Cell(0, 7, $count, 0, 1, 'L');
            }
        } else {
            $pdf->Cell(0, 10, 'No maintenance requests found', 1, 1, 'C');
        }
        
        $pdf->Ln(5);
    }
    
    // Guest Maintenance Requests Table
    if ($type === 'guest_maintenance' || $type === 'both') {
        if ($type === 'both' && $pdf->GetY() > 200) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Guest Maintenance Requests', 0, 1, 'L');
        
        // Set column widths
        $colWidth = [10, 30, 40, 50, 15, 20, 25];
        
        // Table header
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell($colWidth[1], 8, 'Guest Name', 1, 0, 'C', true);
        $pdf->Cell($colWidth[2], 8, 'Title', 1, 0, 'C', true);
        $pdf->Cell($colWidth[3], 8, 'Description', 1, 0, 'C', true);
        $pdf->Cell($colWidth[4], 8, 'Room', 1, 0, 'C', true);
        $pdf->Cell($colWidth[5], 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell($colWidth[6], 8, 'Created At', 1, 1, 'C', true);
        
        // Table data
        $pdf->SetFont('Arial', '', 7);
        if (count($guestMaintenanceData) > 0) {
            foreach ($guestMaintenanceData as $row) {
                $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                $pdf->Cell($colWidth[1], 7, $row['uname'], 1, 0, 'L');
                $pdf->Cell($colWidth[2], 7, substr($row['title'], 0, 25), 1, 0, 'L');
                $pdf->Cell($colWidth[3], 7, substr($row['description'], 0, 40), 1, 0, 'L');
                $pdf->Cell($colWidth[4], 7, $row['room'], 1, 0, 'C');
                $pdf->Cell($colWidth[5], 7, $row['status'], 1, 0, 'C');
                $pdf->Cell($colWidth[6], 7, $row['created_at'], 1, 1, 'C');
                
                // Check if we need a new page
                if ($pdf->GetY() > 260) {
                    $pdf->AddPage();
                    
                    // Repeat header on the new page
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[1], 8, 'Guest Name', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[2], 8, 'Title', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[3], 8, 'Description', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[4], 8, 'Room', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[5], 8, 'Status', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[6], 8, 'Created At', 1, 1, 'C', true);
                    $pdf->SetFont('Arial', '', 7);
                }
            }
            
            // Add summary for Guest Maintenance if that section is included
            if ($type === 'guest_maintenance' || $type === 'both') {
                // Add code to display guest maintenance summary after the guest table
                if (count($guestMaintenanceData) > 0) {
                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 8, 'Guest Maintenance Summary', 0, 1, 'L');
                    $pdf->SetFont('Arial', '', 10);
                    
                    // Total requests
                    $pdf->Cell(80, 7, 'Total Guest Requests:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($guestMaintenanceData), 0, 1, 'L');
                    
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
                        $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $count, 0, 1, 'L');
                    }
                }
            }
        } else {
            $pdf->Cell(0, 10, 'No guest maintenance requests found', 1, 1, 'C');
        }
    }
    
    // Output PDF
    $pdf->Output('D', $filename . '.pdf');
    exit;
}

/**
 * Use TCPDF to generate PDF
 */
function useTCPDF($maintenanceData, $guestMaintenanceData, $type, $filename) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Initialize TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Housekeeping System');
    $pdf->SetAuthor('Paradise Hotel');
    $pdf->SetTitle('Maintenance Export');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    $pdf->AddPage();
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Maintenance Export', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Maintenance Requests Table
    if ($type === 'maintenance_requests' || $type === 'both') {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Maintenance Requests', 0, 1, 'L');
        
        // Set column widths
        $colWidth = [10, 30, 35, 15, 15, 15, 20, 20, 30];
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 8);
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
        
        // Table data
        $pdf->SetFont('helvetica', '', 7);
        if (count($maintenanceData) > 0) {
            foreach ($maintenanceData as $row) {
                $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                $pdf->Cell($colWidth[1], 7, substr($row['request_title'], 0, 20), 1, 0, 'L');
                $pdf->Cell($colWidth[2], 7, substr($row['description'], 0, 25), 1, 0, 'L');
                $pdf->Cell($colWidth[3], 7, $row['room_no'], 1, 0, 'C');
                $pdf->Cell($colWidth[4], 7, $row['priority'], 1, 0, 'C');
                $pdf->Cell($colWidth[5], 7, $row['status'], 1, 0, 'C');
                $pdf->Cell($colWidth[6], 7, substr($row['created_at'], 0, 10), 1, 0, 'C');
                $pdf->Cell($colWidth[7], 7, $row['schedule'] ?: 'N/A', 1, 0, 'C');
                $pdf->Cell($colWidth[8], 7, $row['assigned_employees'] ?: 'Not Assigned', 1, 1, 'L');
                
                // Check if we need a new page
                if ($pdf->GetY() > 260) {
                    $pdf->AddPage();
                    
                    // Repeat header on the new page
                    $pdf->SetFont('helvetica', 'B', 8);
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
                $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                $pdf->Cell(0, 7, $count, 0, 1, 'L');
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
            
            $pdf->Ln(5);
            foreach ($priorityCounts as $priority => $count) {
                $pdf->Cell(80, 7, $priority . ' Priority:', 0, 0, 'L');
                $pdf->Cell(0, 7, $count, 0, 1, 'L');
            }
        } else {
            $pdf->Cell(0, 10, 'No maintenance requests found', 1, 1, 'C');
        }
        
        $pdf->Ln(5);
    }
    
    // Guest Maintenance Requests Table
    if ($type === 'guest_maintenance' || $type === 'both') {
        if ($type === 'both' && $pdf->GetY() > 200) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Guest Maintenance Requests', 0, 1, 'L');
        
        // Set column widths
        $colWidth = [10, 30, 40, 50, 15, 20, 25];
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell($colWidth[1], 8, 'Guest Name', 1, 0, 'C', true);
        $pdf->Cell($colWidth[2], 8, 'Title', 1, 0, 'C', true);
        $pdf->Cell($colWidth[3], 8, 'Description', 1, 0, 'C', true);
        $pdf->Cell($colWidth[4], 8, 'Room', 1, 0, 'C', true);
        $pdf->Cell($colWidth[5], 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell($colWidth[6], 8, 'Created At', 1, 1, 'C', true);
        
        // Table data
        $pdf->SetFont('helvetica', '', 7);
        if (count($guestMaintenanceData) > 0) {
            foreach ($guestMaintenanceData as $row) {
                $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                $pdf->Cell($colWidth[1], 7, $row['uname'], 1, 0, 'L');
                $pdf->Cell($colWidth[2], 7, substr($row['title'], 0, 25), 1, 0, 'L');
                $pdf->Cell($colWidth[3], 7, substr($row['description'], 0, 40), 1, 0, 'L');
                $pdf->Cell($colWidth[4], 7, $row['room'], 1, 0, 'C');
                $pdf->Cell($colWidth[5], 7, $row['status'], 1, 0, 'C');
                $pdf->Cell($colWidth[6], 7, $row['created_at'], 1, 1, 'C');
                
                // Check if we need a new page
                if ($pdf->GetY() > 260) {
                    $pdf->AddPage();
                    
                    // Repeat header on the new page
                    $pdf->SetFont('helvetica', 'B', 8);
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[1], 8, 'Guest Name', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[2], 8, 'Title', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[3], 8, 'Description', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[4], 8, 'Room', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[5], 8, 'Status', 1, 0, 'C', true);
                    $pdf->Cell($colWidth[6], 8, 'Created At', 1, 1, 'C', true);
                    $pdf->SetFont('helvetica', '', 7);
                }
            }
            
            // Add summary for Guest Maintenance if that section is included
            if ($type === 'guest_maintenance' || $type === 'both') {
                // Add code to display guest maintenance summary after the guest table
                if (count($guestMaintenanceData) > 0) {
                    $pdf->Ln(10);
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->Cell(0, 8, 'Guest Maintenance Summary', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    
                    // Total requests
                    $pdf->Cell(80, 7, 'Total Guest Requests:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($guestMaintenanceData), 0, 1, 'L');
                    
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
                        $pdf->Cell(80, 7, $status . ' Requests:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $count, 0, 1, 'L');
                    }
                }
            }
        } else {
            $pdf->Cell(0, 10, 'No guest maintenance requests found', 1, 1, 'C');
        }
    }
    
    // Output PDF
    $pdf->Output('D', $filename . '.pdf');
    exit;
}
?>
