<?php
// Turn off warnings and notices to prevent any output before PDF generation
error_reporting(E_ERROR | E_PARSE);

require_once 'database.php';

// Include Composer autoloader
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

// Get export format and date range
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Validate dates
if (!strtotime($startDate) || !strtotime($endDate)) {
    echo "Invalid date range";
    exit;
}

// Add one day to end date for inclusive filtering
$endDateForQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));

// Use the same query structure as in fetch_inventory_usage.php
$sql = "SELECT u.id, u.task_id, u.item_id, i.item_name, u.quantity, 
               u.used_by, a.emp_name, u.used_at, u.notes
        FROM inventory_usage u
        LEFT JOIN inventory i ON u.item_id = i.id
        LEFT JOIN assigntasks a ON u.task_id = a.task_id
        WHERE u.used_at BETWEEN ? AND ? 
        ORDER BY u.used_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDateForQuery);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Use emp_name from assigntasks if available, otherwise use used_by field
        if (!empty($row['emp_name'])) {
            $row['used_by'] = $row['emp_name'];
        }
        
        // Remove extra fields to keep response clean
        unset($row['emp_name']);
        
        $data[] = $row;
    }
}

// Generate filename with date range
$filename = "inventory_usage_{$startDate}_to_{$endDate}";

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
 * Export usage data to Excel
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
    echo '<h1>Inventory Usage Report</h1>';
    echo '<h3>Period: ' . $startDate . ' to ' . $endDate . '</h3>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
    // Table start
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Task ID</th>';
    echo '<th>Item Name</th>';
    echo '<th>Quantity</th>';
    echo '<th>Used By</th>';
    echo '<th>Date Used</th>';
    echo '<th>Notes</th>';
    echo '</tr>';
    
    // Data rows
    if (count($data) > 0) {
        foreach ($data as $row) {
            $usedDate = date('Y-m-d H:i', strtotime($row['used_at']));
            
            // Use item_name from the query join
            $itemName = isset($row['item_name']) && !empty($row['item_name']) 
                      ? $row['item_name'] 
                      : 'Unknown Item';
            
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . (isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A') . '</td>';
            echo '<td>' . $itemName . '</td>';
            echo '<td>' . (isset($row['quantity']) ? $row['quantity'] : '0') . '</td>';
            echo '<td>' . (isset($row['used_by']) ? $row['used_by'] : 'Unknown') . '</td>';
            echo '<td>' . $usedDate . '</td>';
            echo '<td>' . (isset($row['notes']) && $row['notes'] ? $row['notes'] : '') . '</td>';
            echo '</tr>';
        }
        
        // Calculate totals - safely handle potentially missing keys
        $totalQuantity = 0;
        foreach ($data as $row) {
            $totalQuantity += isset($row['quantity']) ? (int)$row['quantity'] : 0;
        }
        
        // Add summary row
        echo '<tr><td colspan="7"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="3">Total Records:</td>';
        echo '<td colspan="4">' . count($data) . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="3">Total Quantity Used:</td>';
        echo '<td colspan="4">' . $totalQuantity . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="3">Period:</td>';
        echo '<td colspan="4">' . $startDate . ' to ' . $endDate . '</td>';
    } else {
        echo '<tr><td colspan="7">No usage data found for the selected period</td></tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export usage data to PDF using Composer's FPDF or TCPDF
 */
function exportToPDF($data, $filename, $startDate, $endDate) {
    try {
        // Try to use FPDF first (setasign/fpdf)
        if (class_exists('FPDF')) {
            // Use FPDF - changed to portrait orientation
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Inventory Usage Report', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, 'Period: ' . $startDate . ' to ' . $endDate, 0, 1, 'C');
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Adjusted column widths for portrait mode
            $colWidth = [10, 15, 45, 15, 40, 25, 40];
            
            // Table header
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[1], 8, 'Task ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
            $pdf->Cell($colWidth[3], 8, 'Qty', 1, 0, 'C', true);
            $pdf->Cell($colWidth[4], 8, 'Used By', 1, 0, 'C', true);
            $pdf->Cell($colWidth[5], 8, 'Date', 1, 0, 'C', true);
            $pdf->Cell($colWidth[6], 8, 'Notes', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('Arial', '', 7);
            
            if (count($data) > 0) {
                $totalQuantity = 0;
                
                foreach ($data as $row) {
                    $usedDate = date('Y-m-d H:i', strtotime($row['used_at']));
                    $totalQuantity += isset($row['quantity']) ? (int)$row['quantity'] : 0;
                    
                    // Safely get values using same approach as fetch_inventory_usage.php
                    $id = $row['id'];
                    $taskId = isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A';
                    $itemName = isset($row['item_name']) && !empty($row['item_name']) 
                              ? $row['item_name'] 
                              : 'Unknown Item';
                    $quantity = isset($row['quantity']) ? $row['quantity'] : '0';
                    $usedBy = isset($row['used_by']) ? $row['used_by'] : 'Unknown';
                    $notes = isset($row['notes']) ? $row['notes'] : '';
                    
                    // Truncate notes and other long fields to fit portrait layout
                    if (strlen($notes) > 25) {
                        $notes = substr($notes, 0, 22) . '...';
                    }
                    if (strlen($itemName) > 28) {
                        $itemName = substr($itemName, 0, 25) . '...';
                    }
                    if (strlen($usedBy) > 25) {
                        $usedBy = substr($usedBy, 0, 22) . '...';
                    }
                    
                    // Print row
                    $pdf->Cell($colWidth[0], 7, $id, 1, 0, 'C');
                    $pdf->Cell($colWidth[1], 7, $taskId, 1, 0, 'C');
                    $pdf->Cell($colWidth[2], 7, $itemName, 1, 0, 'L');
                    $pdf->Cell($colWidth[3], 7, $quantity, 1, 0, 'C');
                    $pdf->Cell($colWidth[4], 7, $usedBy, 1, 0, 'L');
                    $pdf->Cell($colWidth[5], 7, $usedDate, 1, 0, 'C');
                    $pdf->Cell($colWidth[6], 7, $notes, 1, 1, 'L');
                    
                    // Check if we need a new page - adjusted for portrait mode
                    if ($pdf->GetY() > 265) {
                        $pdf->AddPage();
                        
                        // Repeat header on the new page
                        $pdf->SetFont('Arial', 'B', 8);
                        $pdf->SetFillColor(240, 240, 240);
                        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[1], 8, 'Task ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[3], 8, 'Qty', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[4], 8, 'Used By', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[5], 8, 'Date', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[6], 8, 'Notes', 1, 1, 'C', true);
                        $pdf->SetFont('Arial', '', 7);
                    }
                }
                
                // Summary section
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 8, 'Usage Summary', 0, 1, 'L');
                $pdf->SetFont('Arial', '', 10);
                
                $pdf->Cell(80, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Total Quantity Used:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalQuantity, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Period:', 0, 0, 'L');
                $pdf->Cell(0, 7, $startDate . ' to ' . $endDate, 0, 1, 'L');
            } else {
                $pdf->Cell(0, 10, 'No usage data found for the selected period', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output('D', $filename . '.pdf');
        } 
        // If FPDF fails, try TCPDF
        else if (class_exists('TCPDF')) {
            // Use TCPDF - changed to portrait orientation
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('Housekeeping System');
            $pdf->SetAuthor('Paradise Hotel');
            $pdf->SetTitle('Inventory Usage Report');
            // Disable header/footer for cleaner output
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Inventory Usage Report', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Period: ' . $startDate . ' to ' . $endDate, 0, 1, 'C');
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Adjusted column widths for portrait mode
            $colWidth = [10, 15, 45, 15, 40, 25, 40];
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[1], 8, 'Task ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
            $pdf->Cell($colWidth[3], 8, 'Qty', 1, 0, 'C', true);
            $pdf->Cell($colWidth[4], 8, 'Used By', 1, 0, 'C', true);
            $pdf->Cell($colWidth[5], 8, 'Date', 1, 0, 'C', true);
            $pdf->Cell($colWidth[6], 8, 'Notes', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('helvetica', '', 7);
            
            if (count($data) > 0) {
                $totalQuantity = 0;
                
                foreach ($data as $row) {
                    $usedDate = date('Y-m-d H:i', strtotime($row['used_at']));
                    $totalQuantity += isset($row['quantity']) ? (int)$row['quantity'] : 0;
                    
                    // Safely get values using same approach as fetch_inventory_usage.php
                    $id = $row['id'];
                    $taskId = isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A';
                    $itemName = isset($row['item_name']) && !empty($row['item_name']) 
                              ? $row['item_name'] 
                              : 'Unknown Item';
                    $quantity = isset($row['quantity']) ? $row['quantity'] : '0';
                    $usedBy = isset($row['used_by']) ? $row['used_by'] : 'Unknown';
                    $notes = isset($row['notes']) ? $row['notes'] : '';
                    
                    // Truncate notes and other long fields to fit portrait layout
                    if (strlen($notes) > 25) {
                        $notes = substr($notes, 0, 22) . '...';
                    }
                    if (strlen($itemName) > 28) {
                        $itemName = substr($itemName, 0, 25) . '...';
                    }
                    if (strlen($usedBy) > 25) {
                        $usedBy = substr($usedBy, 0, 22) . '...';
                    }
                    
                    // Print row
                    $pdf->Cell($colWidth[0], 7, $id, 1, 0, 'C');
                    $pdf->Cell($colWidth[1], 7, $taskId, 1, 0, 'C');
                    $pdf->Cell($colWidth[2], 7, $itemName, 1, 0, 'L');
                    $pdf->Cell($colWidth[3], 7, $quantity, 1, 0, 'C');
                    $pdf->Cell($colWidth[4], 7, $usedBy, 1, 0, 'L');
                    $pdf->Cell($colWidth[5], 7, $usedDate, 1, 0, 'C');
                    $pdf->Cell($colWidth[6], 7, $notes, 1, 1, 'L');
                    
                    // TCPDF handles page breaks automatically
                }
                
                // Summary section
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, 'Usage Summary', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                
                $pdf->Cell(80, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Total Quantity Used:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalQuantity, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Period:', 0, 0, 'L');
                $pdf->Cell(0, 7, $startDate . ' to ' . $endDate, 0, 1, 'L');
            } else {
                $pdf->Cell(0, 10, 'No usage data found for the selected period', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output($filename . '.pdf', 'D');
        } else {
            // No PDF library available
            echo "<h1>PDF Export Error</h1>";
            echo "<p>PDF libraries not found despite being installed via Composer.</p>";
            echo "<p>Please try exporting to Excel format instead.</p>";
            exit;
        }
    } catch (Exception $e) {
        // If PDF generation fails, show error and suggest Excel export
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='inventory.php'>Return to Inventory</a></p>";
        exit;
    }
}
?>
