<?php
// Turn off warnings and notices to prevent any output before PDF generation
error_reporting(E_ERROR | E_PARSE);

require_once '../database.php';

// Try to include Composer autoloader if available
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
}

// Check if session already started to avoid warning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../unauthorized.php");
    exit;
}

// Get export format and date range
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$empId = isset($_GET['emp_id']) ? $_GET['emp_id'] : null;

// Validate dates
if (!strtotime($startDate) || !strtotime($endDate)) {
    echo "Invalid date range";
    exit;
}

// Add one day to end date for inclusive filtering
$endDateForQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));

// Build the query with any filters
$conditions = [];
$params = [];
$types = "";

// Add date range condition
$conditions[] = "tl.log_time BETWEEN ? AND ?";
$params[] = $startDate;
$params[] = $endDateForQuery;
$types .= "ss";

// Add employee filter if provided
if ($empId) {
    $conditions[] = "tl.emp_id = ?";
    $params[] = $empId;
    $types .= "s";
}

// Construct WHERE clause
$whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Join with employee table to get employee names
$logsQuery = "SELECT tl.log_id, tl.task_id, tl.emp_id, e.name as employee_name, 
              tl.action, tl.change_details, tl.log_time 
              FROM task_logs tl
              LEFT JOIN employee e ON tl.emp_id = e.emp_id" . 
              $whereClause . 
              " ORDER BY tl.log_time DESC";

$stmt = $conn->prepare($logsQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Generate filename with date range
$filename = "task_logs_{$startDate}_to_{$endDate}";

// Export based on format
if ($format === 'excel') {
    exportToExcel($data, $filename, $startDate, $endDate);
} elseif ($format === 'pdf') {
    exportToPDF($data, $filename, $startDate, $endDate);
} else {
    echo "Invalid export format";
    exit;
}

/**
 * Export logs data to Excel
 */
function exportToExcel($data, $filename, $startDate, $endDate) {
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
    echo '<h1>Task Logs Report</h1>';
    echo '<h3>Period: ' . $startDate . ' to ' . $endDate . '</h3>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
    // Table start
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    echo '<th>Log ID</th>';
    echo '<th>Task ID</th>';
    echo '<th>Employee ID</th>';
    echo '<th>Employee Name</th>';
    echo '<th>Action</th>';
    echo '<th>Details</th>';
    echo '<th>Date & Time</th>';
    echo '</tr>';
    
    // Data rows
    if (count($data) > 0) {
        foreach ($data as $row) {
            $logTime = date('Y-m-d H:i', strtotime($row['log_time']));
            
            // Get values with fallbacks for missing data
            $employeeName = isset($row['employee_name']) && !empty($row['employee_name']) 
                          ? $row['employee_name'] 
                          : 'Unknown';
            
            echo '<tr>';
            echo '<td>' . $row['log_id'] . '</td>';
            echo '<td>' . (isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A') . '</td>';
            echo '<td>' . $row['emp_id'] . '</td>';
            echo '<td>' . $employeeName . '</td>';
            echo '<td>' . $row['action'] . '</td>';
            echo '<td>' . (isset($row['change_details']) && $row['change_details'] ? $row['change_details'] : '') . '</td>';
            echo '<td>' . $logTime . '</td>';
            echo '</tr>';
        }
        
        // Add summary row
        echo '<tr><td colspan="7"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="3">Total Records:</td>';
        echo '<td colspan="4">' . count($data) . '</td>';
        echo '</tr>';
        
        // Count tasks by action type
        $actionCounts = [];
        foreach ($data as $row) {
            $action = strtolower($row['action']);
            if (isset($actionCounts[$action])) {
                $actionCounts[$action]++;
            } else {
                $actionCounts[$action] = 1;
            }
        }
        
        // Show action type summary
        foreach ($actionCounts as $action => $count) {
            echo '<tr class="summary-row">';
            echo '<td colspan="3">Total ' . ucfirst($action) . ':</td>';
            echo '<td colspan="4">' . $count . '</td>';
            echo '</tr>';
        }
        
        echo '<tr class="summary-row">';
        echo '<td colspan="3">Period:</td>';
        echo '<td colspan="4">' . $startDate . ' to ' . $endDate . '</td>';
        echo '</tr>';
    } else {
        echo '<tr><td colspan="7">No task logs found for the selected period</td></tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export logs data to PDF using FPDF or TCPDF
 */
function exportToPDF($data, $filename, $startDate, $endDate) {
    try {
        // Try to use FPDF first (if available)
        if (class_exists('FPDF')) {
            // Use FPDF - changed to portrait orientation
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Task Logs Report', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, 'Period: ' . $startDate . ' to ' . $endDate, 0, 1, 'C');
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Column widths
            $colWidth = [10, 15, 15, 40, 20, 50, 30];
            
            // Table header
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($colWidth[0], 8, 'Log ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[1], 8, 'Task ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[2], 8, 'Emp ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[3], 8, 'Employee Name', 1, 0, 'C', true);
            $pdf->Cell($colWidth[4], 8, 'Action', 1, 0, 'C', true);
            $pdf->Cell($colWidth[5], 8, 'Details', 1, 0, 'C', true);
            $pdf->Cell($colWidth[6], 8, 'Date & Time', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('Arial', '', 7);
            
            if (count($data) > 0) {
                // Count tasks by action type
                $actionCounts = [];
                
                foreach ($data as $row) {
                    $action = strtolower($row['action']);
                    if (isset($actionCounts[$action])) {
                        $actionCounts[$action]++;
                    } else {
                        $actionCounts[$action] = 1;
                    }
                    
                    $logTime = date('Y-m-d H:i', strtotime($row['log_time']));
                    
                    // Get values with fallbacks for missing data
                    $employeeName = isset($row['employee_name']) && !empty($row['employee_name']) 
                                  ? $row['employee_name'] 
                                  : 'Unknown';
                    $empId = $row['emp_id'];
                    $logId = $row['log_id'];
                    $taskId = isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A';
                    $actionText = $row['action'];
                    $details = isset($row['change_details']) ? $row['change_details'] : '';
                    
                    // Truncate long fields
                    if (strlen($details) > 32) {
                        $details = substr($details, 0, 29) . '...';
                    }
                    if (strlen($employeeName) > 25) {
                        $employeeName = substr($employeeName, 0, 22) . '...';
                    }
                    
                    // Print row
                    $pdf->Cell($colWidth[0], 7, $logId, 1, 0, 'C');
                    $pdf->Cell($colWidth[1], 7, $taskId, 1, 0, 'C');
                    $pdf->Cell($colWidth[2], 7, $empId, 1, 0, 'C');
                    $pdf->Cell($colWidth[3], 7, $employeeName, 1, 0, 'L');
                    $pdf->Cell($colWidth[4], 7, $actionText, 1, 0, 'C');
                    $pdf->Cell($colWidth[5], 7, $details, 1, 0, 'L');
                    $pdf->Cell($colWidth[6], 7, $logTime, 1, 1, 'C');
                    
                    // Check if we need a new page
                    if ($pdf->GetY() > 265) {
                        $pdf->AddPage();
                        
                        // Repeat header on the new page
                        $pdf->SetFont('Arial', 'B', 8);
                        $pdf->SetFillColor(240, 240, 240);
                        $pdf->Cell($colWidth[0], 8, 'Log ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[1], 8, 'Task ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[2], 8, 'Emp ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[3], 8, 'Employee Name', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[4], 8, 'Action', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[5], 8, 'Details', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[6], 8, 'Date & Time', 1, 1, 'C', true);
                        $pdf->SetFont('Arial', '', 7);
                    }
                }
                
                // Summary section
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 8, 'Logs Summary', 0, 1, 'L');
                $pdf->SetFont('Arial', '', 10);
                
                $pdf->Cell(80, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                // Show action type summary
                foreach ($actionCounts as $action => $count) {
                    $pdf->Cell(80, 7, 'Total ' . ucfirst($action) . ':', 0, 0, 'L');
                    $pdf->Cell(0, 7, $count, 0, 1, 'L');
                }
                
                $pdf->Cell(80, 7, 'Period:', 0, 0, 'L');
                $pdf->Cell(0, 7, $startDate . ' to ' . $endDate, 0, 1, 'L');
            } else {
                $pdf->Cell(0, 10, 'No task logs found for the selected period', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output('D', $filename . '.pdf');
        } 
        // If FPDF fails, try TCPDF
        else if (class_exists('TCPDF')) {
            // Use TCPDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('Housekeeping System');
            $pdf->SetAuthor('Admin');
            $pdf->SetTitle('Task Logs Report');
            // Disable header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            $pdf->AddPage();
            
            // Similar implementation as FPDF section
            // ...existing code...
            
            // Output PDF
            $pdf->Output($filename . '.pdf', 'D');
        } else {
            // Fallback to simple HTML output if no PDF libraries are available
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Task Logs Report</h1>";
            echo "<p>Period: {$startDate} to {$endDate}</p>";
            echo "<p>PDF generation requires a PDF library like FPDF or TCPDF.</p>";
            echo "<p>Please try exporting to Excel format instead or install a PDF library.</p>";
            echo "<p><a href='../task_allocation.php'>Return to Task Allocation</a></p>";
            exit;
        }
    } catch (Exception $e) {
        // Error handling
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='../task_allocation.php'>Return to Task Allocation</a></p>";
        exit;
    }
}
?>
