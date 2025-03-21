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

// Get export format
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';

// Fetch inventory data
$sql = "SELECT * FROM inventory ORDER BY category, item_name";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "inventory_export_{$timestamp}";

// Export based on format
if ($format === 'excel') {
    exportToExcel($data, $filename);
} elseif ($format === 'pdf') {
    exportToPDF($data, $filename);
} else {
    echo "Invalid export format";
    exit;
}

/**
 * Export inventory data to Excel
 */
function exportToExcel($data, $filename) {
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
    echo '<h1>Inventory Export</h1>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
    // Table start
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Category</th>';
    echo '<th>Item Name</th>';
    echo '<th>SKU</th>';
    echo '<th>Quantity</th>';
    echo '<th>Stock Status</th>';
    echo '</tr>';
    
    // Data rows
    if (count($data) > 0) {
        foreach ($data as $row) {
            $stockStatus = '';
            $rowClass = '';
            
            if ((int)$row['quantity'] === 0) {
                $stockStatus = 'Out of Stock';
                $rowClass = 'class="red-text"';
            } else if ((int)$row['quantity'] < 10) {
                $stockStatus = 'Low Stock';
                $rowClass = 'class="orange-text"';
            } else {
                $stockStatus = 'In Stock';
            }
            
            echo '<tr ' . $rowClass . '>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['category'] . '</td>';
            echo '<td>' . $row['item_name'] . '</td>';
            echo '<td>' . $row['sku'] . '</td>';
            echo '<td>' . $row['quantity'] . '</td>';
            echo '<td>' . $stockStatus . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">No inventory data available</td></tr>';
    }
    
    // Summary section
    if (count($data) > 0) {
        $categories = array_unique(array_column($data, 'category'));
        $totalStock = array_sum(array_column($data, 'quantity'));
        $lowStock = count(array_filter($data, function($item) {
            return (int)$item['quantity'] > 0 && (int)$item['quantity'] < 10;
        }));
        $outOfStock = count(array_filter($data, function($item) {
            return (int)$item['quantity'] === 0;
        }));
        
        echo '<tr><td colspan="6"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Categories:</td>';
        echo '<td colspan="4">' . count($categories) . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Stock:</td>';
        echo '<td colspan="4">' . $totalStock . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Low Stock Items:</td>';
        echo '<td colspan="4">' . $lowStock . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Out of Stock Items:</td>';
        echo '<td colspan="4">' . $outOfStock . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export inventory data to PDF using Composer's FPDF or TCPDF
 */
function exportToPDF($data, $filename) {
    try {
        // Try to use FPDF first (setasign/fpdf)
        if (class_exists('FPDF')) {
            // Use FPDF
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(10);
            
            // Define column widths for portrait mode
            $colWidth = [10, 40, 70, 25, 20, 25];
            
            // Table header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[1], 8, 'Category', 1, 0, 'C', true);
            $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
            $pdf->Cell($colWidth[3], 8, 'SKU', 1, 0, 'C', true);
            $pdf->Cell($colWidth[4], 8, 'Quantity', 1, 0, 'C', true);
            $pdf->Cell($colWidth[5], 8, 'Status', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('Arial', '', 9);
            
            if (count($data) > 0) {
                $totalStock = 0;
                $lowStock = 0;
                $outOfStock = 0;
                $categories = [];
                
                foreach ($data as $row) {
                    // Determine stock status and color
                    if ((int)$row['quantity'] === 0) {
                        $stockStatus = 'Out of Stock';
                        $pdf->SetTextColor(255, 0, 0); // Red
                        $outOfStock++;
                    } else if ((int)$row['quantity'] < 10) {
                        $stockStatus = 'Low Stock';
                        $pdf->SetTextColor(255, 128, 0); // Orange
                        $lowStock++;
                    } else {
                        $stockStatus = 'In Stock';
                        $pdf->SetTextColor(0, 0, 0); // Black
                    }
                    
                    // Add to counts
                    $totalStock += (int)$row['quantity'];
                    if (!in_array($row['category'], $categories)) {
                        $categories[] = $row['category'];
                    }
                    
                    // Print row
                    $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                    $pdf->Cell($colWidth[1], 7, $row['category'], 1, 0, 'L');
                    $pdf->Cell($colWidth[2], 7, $row['item_name'], 1, 0, 'L');
                    $pdf->Cell($colWidth[3], 7, $row['sku'], 1, 0, 'L');
                    $pdf->Cell($colWidth[4], 7, $row['quantity'], 1, 0, 'C');
                    $pdf->Cell($colWidth[5], 7, $stockStatus, 1, 1, 'C');
                    
                    // Check if we need a new page
                    if ($pdf->GetY() > 260) {
                        $pdf->AddPage();
                        
                        // Repeat header on the new page
                        $pdf->SetFont('Arial', 'B', 10);
                        $pdf->SetFillColor(240, 240, 240);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[1], 8, 'Category', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[3], 8, 'SKU', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[4], 8, 'Quantity', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[5], 8, 'Status', 1, 1, 'C', true);
                        $pdf->SetFont('Arial', '', 9);
                    }
                }
                
                // Summary section
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 8, 'Inventory Summary', 0, 1, 'L');
                $pdf->SetFont('Arial', '', 10);
                
                $pdf->Cell(80, 7, 'Total Categories:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($categories), 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Total Stock:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalStock, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Low Stock Items:', 0, 0, 'L');
                $pdf->Cell(0, 7, $lowStock, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Out of Stock Items:', 0, 0, 'L');
                $pdf->Cell(0, 7, $outOfStock, 0, 1, 'L');
            } else {
                $pdf->Cell(0, 10, 'No inventory data available', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output('D', $filename . '.pdf');
            exit;
        } 
        // If FPDF fails, try TCPDF
        else if (class_exists('TCPDF')) {
            // Use TCPDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('Housekeeping System');
            $pdf->SetAuthor('Paradise Hotel');
            $pdf->SetTitle('Inventory Report');
            // Disable header/footer for cleaner output
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(10);
            
            // Define column widths for portrait mode
            $colWidth = [10, 40, 70, 25, 20, 25];
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
            $pdf->Cell($colWidth[1], 8, 'Category', 1, 0, 'C', true);
            $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
            $pdf->Cell($colWidth[3], 8, 'SKU', 1, 0, 'C', true);
            $pdf->Cell($colWidth[4], 8, 'Quantity', 1, 0, 'C', true);
            $pdf->Cell($colWidth[5], 8, 'Status', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('helvetica', '', 9);
            
            if (count($data) > 0) {
                $totalStock = 0;
                $lowStock = 0;
                $outOfStock = 0;
                $categories = [];
                
                foreach ($data as $row) {
                    // Determine stock status and color
                    if ((int)$row['quantity'] === 0) {
                        $stockStatus = 'Out of Stock';
                        $pdf->SetTextColor(255, 0, 0); // Red
                        $outOfStock++;
                    } else if ((int)$row['quantity'] < 10) {
                        $stockStatus = 'Low Stock';
                        $pdf->SetTextColor(255, 128, 0); // Orange
                        $lowStock++;
                    } else {
                        $stockStatus = 'In Stock';
                        $pdf->SetTextColor(0, 0, 0); // Black
                    }
                    
                    // Add to counts
                    $totalStock += (int)$row['quantity'];
                    if (!in_array($row['category'], $categories)) {
                        $categories[] = $row['category'];
                    }
                    
                    // Print row
                    $pdf->Cell($colWidth[0], 7, $row['id'], 1, 0, 'C');
                    $pdf->Cell($colWidth[1], 7, $row['category'], 1, 0, 'L');
                    $pdf->Cell($colWidth[2], 7, $row['item_name'], 1, 0, 'L');
                    $pdf->Cell($colWidth[3], 7, $row['sku'], 1, 0, 'L');
                    $pdf->Cell($colWidth[4], 7, $row['quantity'], 1, 0, 'C');
                    $pdf->Cell($colWidth[5], 7, $stockStatus, 1, 1, 'C');
                    
                    // Check if we need a new page
                    if ($pdf->GetY() > 260) {
                        $pdf->AddPage();
                        
                        // Repeat header on the new page
                        $pdf->SetFont('helvetica', 'B', 10);
                        $pdf->SetFillColor(240, 240, 240);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->Cell($colWidth[0], 8, 'ID', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[1], 8, 'Category', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[2], 8, 'Item Name', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[3], 8, 'SKU', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[4], 8, 'Quantity', 1, 0, 'C', true);
                        $pdf->Cell($colWidth[5], 8, 'Status', 1, 1, 'C', true);
                        $pdf->SetFont('helvetica', '', 9);
                    }
                }
                
                // Summary section
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, 'Inventory Summary', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                
                $pdf->Cell(80, 7, 'Total Categories:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($categories), 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Total Stock:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalStock, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Low Stock Items:', 0, 0, 'L');
                $pdf->Cell(0, 7, $lowStock, 0, 1, 'L');
                
                $pdf->Cell(80, 7, 'Out of Stock Items:', 0, 0, 'L');
                $pdf->Cell(0, 7, $outOfStock, 0, 1, 'L');
            } else {
                $pdf->Cell(0, 10, 'No inventory data available', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output('D', $filename . '.pdf');
            exit;
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
