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

// Get parameters
$table = isset($_GET['table']) ? $_GET['table'] : '';
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate = isset($_GET['end']) ? $_GET['end'] : '';
$title = isset($_GET['title']) ? $_GET['title'] : ucfirst($table) . ' Export';

// Define allowed tables for security
$allowedTables = [
    'inventory' => [
        'query' => "SELECT * FROM inventory ORDER BY category, item_name",
        'date_field' => '',
        'columns' => ['id', 'category', 'item_name', 'sku', 'quantity'],
        'headers' => ['ID', 'Category', 'Item Name', 'SKU', 'Quantity']
    ],
    'lost_and_found' => [
        'query' => "SELECT id, found_by, type, room, date, item, description, status FROM lost_and_found",
        'date_field' => 'date',
        'columns' => ['id', 'found_by', 'type', 'room', 'date', 'item', 'description', 'status'],
        'headers' => ['ID', 'Found By', 'Type', 'Room/Area', 'Date', 'Item', 'Description', 'Status']
    ],
    'claims' => [
        'query' => "SELECT id, lost_item_id, guest_name, room_no, contact_info, area_lost, description, validated_by, claim_status, date_lost, date_claimed FROM claims",
        'date_field' => 'date_claimed',
        'columns' => ['id', 'lost_item_id', 'guest_name', 'room_no', 'contact_info', 'area_lost', 'description', 'validated_by', 'claim_status', 'date_lost', 'date_claimed'],
        'headers' => ['ID', 'Lost Item ID', 'Guest Name', 'Room No', 'Contact Info', 'Area Lost', 'Description', 'Validated By', 'Status', 'Date Lost', 'Date Claimed']
    ],
    'inventory_usage' => [
        'query' => "SELECT iu.id, iu.task_id, i.item_name, iu.quantity, iu.used_by, iu.used_at, iu.notes 
                   FROM inventory_usage iu 
                   JOIN inventory i ON iu.item_id = i.id",
        'date_field' => 'used_at',
        'columns' => ['id', 'task_id', 'item_name', 'quantity', 'used_by', 'used_at', 'notes'],
        'headers' => ['ID', 'Task ID', 'Item', 'Quantity', 'Used By', 'Date Used', 'Notes']
    ]
];

// Security check - only allow export of predefined tables
if (!array_key_exists($table, $allowedTables)) {
    echo "Invalid table specified";
    exit;
}

// Get table configuration
$tableConfig = $allowedTables[$table];

// Build query with date range if applicable
$query = $tableConfig['query'];
if (!empty($tableConfig['date_field']) && !empty($startDate) && !empty($endDate)) {
    if (strpos($query, 'WHERE') !== false) {
        $query .= " AND {$tableConfig['date_field']} BETWEEN '$startDate' AND '$endDate'";
    } else {
        $query .= " WHERE {$tableConfig['date_field']} BETWEEN '$startDate' AND '$endDate'";
    }
}

// Add ordering if not already in the query
if (strpos(strtoupper($query), 'ORDER BY') === false) {
    $query .= " ORDER BY {$tableConfig['columns'][0]} DESC";
}

// Execute query
$result = $conn->query($query);

// Fetch data
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "{$table}_export_{$timestamp}";

// Export based on format
if ($format === 'excel') {
    exportToExcel($data, $filename, $tableConfig, $title);
} elseif ($format === 'pdf') {
    exportToPDF($data, $filename, $tableConfig, $title);
} else {
    echo "Invalid export format";
    exit;
}

/**
 * Export data to Excel
 */
function exportToExcel($data, $filename, $tableConfig, $title) {
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
    echo '.red-text { color: #FF0000; }';
    echo '.orange-text { color: #FF8000; }';
    echo '.summary-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report title
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
    // Table start
    echo '<table border="1">';
    
    // Header row with column names
    echo '<tr>';
    foreach ($tableConfig['headers'] as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Data rows
    if (count($data) > 0) {
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($tableConfig['columns'] as $column) {
                if (isset($row[$column])) {
                    // Format date fields
                    if (strpos($column, 'date') !== false || strpos($column, 'used_at') !== false) {
                        echo '<td>' . date('Y-m-d', strtotime($row[$column])) . '</td>';
                    } 
                    // Format status fields with colors
                    else if ($column === 'status' || $column === 'claim_status') {
                        $status = $row[$column];
                        $class = '';
                        
                        if ($status === 'pending') {
                            $class = 'class="orange-text"';
                        } else if ($status === 'claimed') {
                            $class = 'class="green-text"';
                        }
                        
                        echo '<td ' . $class . '>' . htmlspecialchars($status) . '</td>';
                    }
                    // Format quantity fields
                    else if ($column === 'quantity') {
                        $qty = (int)$row[$column];
                        $class = '';
                        
                        if ($qty === 0) {
                            $class = 'class="red-text"';
                        } else if ($qty < 10) {
                            $class = 'class="orange-text"';
                        }
                        
                        echo '<td ' . $class . '>' . $qty . '</td>';
                    }
                    // Default formatting
                    else {
                        echo '<td>' . htmlspecialchars($row[$column]) . '</td>';
                    }
                } else {
                    echo '<td></td>';
                }
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . count($tableConfig['headers']) . '">No data available</td></tr>';
    }
    
    echo '</table>';
    
    // Add summary section with counts
    if (count($data) > 0) {
        echo '<br><table border="1">';
        echo '<tr class="summary-row"><td>Total Records:</td><td>' . count($data) . '</td></tr>';
        
        // Add table-specific summaries if applicable
        if (isset($tableConfig['columns']) && in_array('quantity', $tableConfig['columns'])) {
            $totalQuantity = array_sum(array_column($data, 'quantity'));
            echo '<tr class="summary-row"><td>Total Quantity:</td><td>' . $totalQuantity . '</td></tr>';
        }
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}

/**
 * Export data to PDF
 */
function exportToPDF($data, $filename, $tableConfig, $title) {
    try {
        // Try to use FPDF first (setasign/fpdf)
        if (class_exists('FPDF')) {
            // Use FPDF
            $pdf = new FPDF('L', 'mm', 'A4'); // Landscape mode for more columns
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Calculate column widths based on number of columns
            $columnCount = count($tableConfig['headers']);
            $pageWidth = 270; // For landscape A4
            $colWidth = $pageWidth / $columnCount;
            
            // Table header
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            foreach ($tableConfig['headers'] as $header) {
                $pdf->Cell($colWidth, 8, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Table data
            $pdf->SetFont('Arial', '', 8);
            
            if (count($data) > 0) {
                foreach ($data as $row) {
                    foreach ($tableConfig['columns'] as $column) {
                        if (isset($row[$column])) {
                            // Format date fields
                            if (strpos($column, 'date') !== false || strpos($column, 'used_at') !== false) {
                                $value = date('Y-m-d', strtotime($row[$column]));
                            }
                            // Format status fields
                            else if ($column === 'status' || $column === 'claim_status') {
                                $status = $row[$column];
                                $value = $status;
                                
                                if ($status === 'pending') {
                                    $pdf->SetTextColor(255, 128, 0); // Orange
                                } else if ($status === 'claimed') {
                                    $pdf->SetTextColor(0, 128, 0); // Green
                                } else {
                                    $pdf->SetTextColor(0, 0, 0); // Black
                                }
                            }
                            // Format quantity fields
                            else if ($column === 'quantity') {
                                $qty = (int)$row[$column];
                                $value = $qty;
                                
                                if ($qty === 0) {
                                    $pdf->SetTextColor(255, 0, 0); // Red
                                } else if ($qty < 10) {
                                    $pdf->SetTextColor(255, 128, 0); // Orange
                                } else {
                                    $pdf->SetTextColor(0, 0, 0); // Black
                                }
                            }
                            // Default formatting
                            else {
                                $value = $row[$column];
                            }
                            
                            $pdf->Cell($colWidth, 7, $value, 1, 0, 'L');
                            $pdf->SetTextColor(0, 0, 0); // Reset text color
                        } else {
                            $pdf->Cell($colWidth, 7, '', 1, 0, 'L');
                        }
                    }
                    $pdf->Ln();
                    
                    // Check if we need a new page
                    if ($pdf->GetY() > 180) {
                        $pdf->AddPage();
                        
                        // Repeat header on the new page
                        $pdf->SetFont('Arial', 'B', 9);
                        $pdf->SetFillColor(240, 240, 240);
                        foreach ($tableConfig['headers'] as $header) {
                            $pdf->Cell($colWidth, 8, $header, 1, 0, 'C', true);
                        }
                        $pdf->Ln();
                        $pdf->SetFont('Arial', '', 8);
                    }
                }
            } else {
                $pdf->Cell($pageWidth, 10, 'No data available', 1, 1, 'C');
            }
            
            // Summary section
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);
            
            $pdf->Cell(60, 7, 'Total Records:', 0, 0, 'L');
            $pdf->Cell(0, 7, count($data), 0, 1, 'L');
            
            // Add table-specific summaries if applicable
            if (isset($tableConfig['columns']) && in_array('quantity', $tableConfig['columns'])) {
                $totalQuantity = array_sum(array_column($data, 'quantity'));
                $pdf->Cell(60, 7, 'Total Quantity:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalQuantity, 0, 1, 'L');
            }
            
            // Output PDF
            $pdf->Output('D', $filename . '.pdf');
            exit;
        } 
        else if (class_exists('TCPDF')) {
            // Use TCPDF as fallback
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
            // Basic TCPDF configuration (similar to the FPDF version)
            // ...implementation similar to FPDF but with TCPDF syntax
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } 
        else {
            throw new Exception("No PDF generation library available");
        }
    } catch (Exception $e) {
        // If PDF generation fails, suggest Excel format
        echo "<h1>PDF Export Error</h1>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        exit;
    }
}
?>
