<?php
// Turn off warnings and notices to prevent any output before PDF generation
error_reporting(E_ERROR | E_PARSE);

// Check if session already started to avoid warning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");
require 'vendor/autoload.php';

// Verify the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Get the export type and format from the URL
$exportType = $_GET['type'] ?? '';
$exportFormat = $_GET['format'] ?? '';
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';

// Get password from request or use default
$encryptionPassword = $_GET['encryption_password'] ?? "paradisehotel2025";

// Store who generated the report
$generatedBy = $_SESSION['username'] ?? 'Unknown User';

// Start building the query
$queryBase = "";
$whereClause = " WHERE 1=1"; // Base where clause to make appending conditions easier

// Process based on selected export type
if ($exportType == 'inventory') {
    $queryBase = "SELECT id, category, item_name, sku, quantity FROM inventory";
    $filename = 'inventory_export_' . date('Y-m-d_H-i-s');
    $title = 'Inventory Report';
    $headers = ['ID', 'Category', 'Item Name', 'SKU', 'Quantity', 'Status'];
} elseif ($exportType == 'usage') {
    $queryBase = "SELECT u.id, u.task_id, u.item_id, i.item_name, u.quantity, u.used_by, a.emp_name, u.used_at, u.notes
                  FROM inventory_usage u
                  LEFT JOIN inventory i ON u.item_id = i.id
                  LEFT JOIN assigntasks a ON u.task_id = a.task_id";
    $filename = 'inventory_usage_' . date('Y-m-d_H-i-s');
    $title = 'Inventory Usage Report';
    $headers = ['ID', 'Task ID', 'Item Name', 'Quantity', 'Used By', 'Date', 'Notes'];
    
    // Add date filter if provided
    if (!empty($startDate) && !empty($endDate)) {
        $endDateForQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $whereClause .= " AND u.used_at BETWEEN '$startDate' AND '$endDateForQuery'";
    } elseif (!empty($startDate)) {
        $whereClause .= " AND u.used_at >= '$startDate'";
    } elseif (!empty($endDate)) {
        $endDateForQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $whereClause .= " AND u.used_at <= '$endDateForQuery'";
    }
} else {
    die("Invalid export type");
}

// Complete the query
$query = $queryBase . $whereClause . " ORDER BY id DESC";

// Execute the query
$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Data array
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // For usage data, handle the employee name logic
        if ($exportType == 'usage') {
            // Use emp_name from assigntasks if available, otherwise use used_by field
            if (!empty($row['emp_name'])) {
                $row['used_by'] = $row['emp_name'];
            }
            
            // Remove extra fields to keep data clean
            unset($row['emp_name']);
        }
        // For inventory data, add a status field
        else if ($exportType == 'inventory') {
            if ((int)$row['quantity'] === 0) {
                $row['status'] = 'Out of Stock';
            } elseif ((int)$row['quantity'] < 10) {
                $row['status'] = 'Low Stock';
            } else {
                $row['status'] = 'In Stock';
            }
        }
        
        $data[] = $row;
    }
}

// Export based on format
if ($exportFormat == 'excel') {
    exportToExcel($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate);
} elseif ($exportFormat == 'pdf') {
    exportToPDFSecure($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate);
} else {
    die("Invalid export format");
}

/**
 * Export data to Excel
 */
function exportToExcel($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate) {
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
    echo '.red-text { color: #FF0000; }'; // Red for out of stock
    echo '.orange-text { color: #FF8000; }'; // Orange for low stock
    echo '.summary-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '.report-header { font-size: 14pt; font-weight: bold; margin-bottom: 10px; }';
    echo '.report-meta { font-size: 10pt; margin-bottom: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report title and metadata
    echo '<div class="report-header">' . $title . '</div>';
    echo '<div class="report-meta">';
    echo 'Generated on: ' . date('Y-m-d H:i:s') . '<br>';
    echo 'Generated by: ' . $generatedBy . '<br>';
    
    // Show date range for usage reports
    if ($exportType == 'usage' && $startDate && $endDate) {
        echo 'Date Range: ' . $startDate . ' to ' . $endDate . '<br>';
    }
    
    echo '</div>';
    
    // Table start
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    
    // Data rows
    if (count($data) > 0) {
        foreach ($data as $row) {
            echo '<tr>';
            
            if ($exportType == 'inventory') {
                // Apply status-based styling for inventory items
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['category'] . '</td>';
                echo '<td>' . $row['item_name'] . '</td>';
                echo '<td>' . $row['sku'] . '</td>';
                
                // Quantity with color coding
                $class = '';
                if ((int)$row['quantity'] === 0) {
                    $class = 'class="red-text"';
                } else if ((int)$row['quantity'] < 10) {
                    $class = 'class="orange-text"';
                }
                echo '<td ' . $class . '>' . $row['quantity'] . '</td>';
                echo '<td ' . $class . '>' . $row['status'] . '</td>';
            } 
            else if ($exportType == 'usage') {
                // Format date for usage data
                $usedDate = isset($row['used_at']) ? date('Y-m-d H:i', strtotime($row['used_at'])) : '';
                
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . (isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A') . '</td>';
                echo '<td>' . (isset($row['item_name']) ? $row['item_name'] : 'Unknown Item') . '</td>';
                echo '<td>' . (isset($row['quantity']) ? $row['quantity'] : '0') . '</td>';
                echo '<td>' . (isset($row['used_by']) ? $row['used_by'] : 'Unknown') . '</td>';
                echo '<td>' . $usedDate . '</td>';
                echo '<td>' . (isset($row['notes']) ? $row['notes'] : '') . '</td>';
            }
            
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . count($headers) . '">No data available</td></tr>';
    }
    
    // Summary section
    if (count($data) > 0) {
        echo '<tr><td colspan="' . count($headers) . '"></td></tr>';
        
        if ($exportType == 'inventory') {
            // Calculate inventory summary data
            $categories = array_unique(array_column($data, 'category'));
            $totalStock = array_sum(array_column($data, 'quantity'));
            $lowStock = count(array_filter($data, function($item) {
                return (int)$item['quantity'] > 0 && (int)$item['quantity'] < 10;
            }));
            $outOfStock = count(array_filter($data, function($item) {
                return (int)$item['quantity'] === 0;
            }));
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Total Categories:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . count($categories) . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Total Stock:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $totalStock . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Low Stock Items:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $lowStock . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Out of Stock Items:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $outOfStock . '</td>';
            echo '</tr>';
        }
        else if ($exportType == 'usage') {
            // Calculate usage summary data
            $totalQuantity = array_sum(array_column($data, 'quantity'));
            $uniqueUsers = count(array_unique(array_column($data, 'used_by')));
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Total Records:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . count($data) . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Total Quantity Used:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $totalQuantity . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Unique Users:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $uniqueUsers . '</td>';
            echo '</tr>';
            
            if ($startDate && $endDate) {
                echo '<tr class="summary-row">';
                echo '<td colspan="2">Period:</td>';
                echo '<td colspan="' . (count($headers) - 2) . '">' . $startDate . ' to ' . $endDate . '</td>';
                echo '</tr>';
            }
        }
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export data to PDF with TCPDF and password protection
 */
function exportToPDFSecure($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate) {
    global $encryptionPassword;
    
    try {
        // Clear all previous output and buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check if TCPDF class exists
        if (class_exists('TCPDF')) {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Paradise Hotel');
            $pdf->SetAuthor($generatedBy);
            $pdf->SetTitle($title);
            $pdf->SetSubject('Export Report');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, $title, 'Generated on: ' . date('Y-m-d H:i:s') . ' | Generated by: ' . $generatedBy);
            
            // Set header and footer fonts
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont('courier');
            
            // Set margins
            $pdf->SetMargins(15, 27, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 25);
            
            // Set PDF protection
            $pdf->SetProtection(array('print', 'copy'), $encryptionPassword, null, 0, null);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Show date range for usage reports
            if ($exportType == 'usage' && $startDate && $endDate) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Write(0, 'Date Range:', '', 0, 'L', true, 0, false, false, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Write(0, $startDate . ' to ' . $endDate, '', 0, 'L', true, 0, false, false, 0);
                $pdf->Ln(5);
            }
            
            // Create table header
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetTextColor(0);
            $pdf->SetFont('helvetica', 'B', 10);
            
            // Calculate column widths based on page width
            $pageWidth = $pdf->getPageWidth() - 30; // Account for margins
            $colWidths = array();
            
            // Adjust column widths based on content type
            if ($exportType == 'inventory') {
                $colWidths = array(
                    $pageWidth * 0.08, // ID
                    $pageWidth * 0.20, // Category
                    $pageWidth * 0.30, // Item Name
                    $pageWidth * 0.15, // SKU
                    $pageWidth * 0.12, // Quantity
                    $pageWidth * 0.15  // Status
                );
            } else { // usage
                $colWidths = array(
                    $pageWidth * 0.08, // ID
                    $pageWidth * 0.12, // Task ID
                    $pageWidth * 0.25, // Item Name
                    $pageWidth * 0.10, // Quantity
                    $pageWidth * 0.15, // Used By
                    $pageWidth * 0.15, // Date
                    $pageWidth * 0.15  // Notes
                );
            }
            
            // Print header row
            $pdf->SetFillColor(240, 240, 240);
            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 7, $header, 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Print data rows
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor(255, 255, 255);
            
            if (count($data) > 0) {
                if ($exportType == 'inventory') {
                    $categories = array();
                    $totalStock = 0;
                    $lowStock = 0;
                    $outOfStock = 0;
                    
                    foreach ($data as $row) {
                        // Determine text color based on stock level
                        if ((int)$row['quantity'] === 0) {
                            $pdf->SetTextColor(255, 0, 0); // Red
                            $outOfStock++;
                        } elseif ((int)$row['quantity'] < 10) {
                            $pdf->SetTextColor(255, 128, 0); // Orange
                            $lowStock++;
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        // Track categories and total stock
                        if (!in_array($row['category'], $categories)) {
                            $categories[] = $row['category'];
                        }
                        $totalStock += (int)$row['quantity'];
                        
                        // Print row
                        $pdf->Cell($colWidths[0], 6, $row['id'], 1, 0, 'C');
                        $pdf->Cell($colWidths[1], 6, $row['category'], 1, 0, 'L');
                        
                        // Truncate item name if too long
                        $itemName = $row['item_name'];
                        if (strlen($itemName) > 30) {
                            $itemName = substr($itemName, 0, 27) . '...';
                        }
                        $pdf->Cell($colWidths[2], 6, $itemName, 1, 0, 'L');
                        
                        $pdf->Cell($colWidths[3], 6, $row['sku'], 1, 0, 'L');
                        $pdf->Cell($colWidths[4], 6, $row['quantity'], 1, 0, 'C');
                        $pdf->Cell($colWidths[5], 6, $row['status'], 1, 1, 'C');
                    }
                    
                    // Add summary section
                    $pdf->Ln(10);
                    $pdf->SetTextColor(0, 0, 0); // Reset text color
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    
                    $pdf->Cell(60, 7, 'Total Categories:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($categories), 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Total Stock:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $totalStock, 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Low Stock Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $lowStock, 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Out of Stock Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $outOfStock, 0, 1, 'L');
                }
                else if ($exportType == 'usage') {
                    $totalQuantity = 0;
                    $uniqueUsers = array();
                    
                    foreach ($data as $row) {
                        $pdf->SetTextColor(0, 0, 0); // Reset text color
                        
                        // Format date
                        $usedDate = isset($row['used_at']) ? date('Y-m-d H:i', strtotime($row['used_at'])) : '';
                        
                        // Track quantities and users
                        $totalQuantity += isset($row['quantity']) ? (int)$row['quantity'] : 0;
                        if (isset($row['used_by']) && !in_array($row['used_by'], $uniqueUsers)) {
                            $uniqueUsers[] = $row['used_by'];
                        }
                        
                        // Print row with truncated fields if needed
                        $pdf->Cell($colWidths[0], 6, $row['id'], 1, 0, 'C');
                        
                        $taskId = isset($row['task_id']) && $row['task_id'] ? $row['task_id'] : 'N/A';
                        $pdf->Cell($colWidths[1], 6, $taskId, 1, 0, 'C');
                        
                        // Truncate item name if too long
                        $itemName = isset($row['item_name']) ? $row['item_name'] : 'Unknown Item';
                        if (strlen($itemName) > 25) {
                            $itemName = substr($itemName, 0, 22) . '...';
                        }
                        $pdf->Cell($colWidths[2], 6, $itemName, 1, 0, 'L');
                        
                        $quantity = isset($row['quantity']) ? $row['quantity'] : '0';
                        $pdf->Cell($colWidths[3], 6, $quantity, 1, 0, 'C');
                        
                        // Truncate user name if too long
                        $usedBy = isset($row['used_by']) ? $row['used_by'] : 'Unknown';
                        if (strlen($usedBy) > 15) {
                            $usedBy = substr($usedBy, 0, 12) . '...';
                        }
                        $pdf->Cell($colWidths[4], 6, $usedBy, 1, 0, 'L');
                        
                        $pdf->Cell($colWidths[5], 6, $usedDate, 1, 0, 'C');
                        
                        // Truncate notes if too long
                        $notes = isset($row['notes']) ? $row['notes'] : '';
                        if (strlen($notes) > 15) {
                            $notes = substr($notes, 0, 12) . '...';
                        }
                        $pdf->Cell($colWidths[6], 6, $notes, 1, 1, 'L');
                    }
                    
                    // Add summary section
                    $pdf->Ln(10);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    
                    $pdf->Cell(60, 7, 'Total Records:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Total Quantity Used:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $totalQuantity, 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Unique Users:', 0, 0, 'L');
                    $pdf->Cell(0, 7, count($uniqueUsers), 0, 1, 'L');
                    
                    if ($startDate && $endDate) {
                        $pdf->Cell(60, 7, 'Period:', 0, 0, 'L');
                        $pdf->Cell(0, 7, $startDate . ' to ' . $endDate, 0, 1, 'L');
                    }
                }
            } else {
                $pdf->Cell(array_sum($colWidths), 10, 'No data available', 1, 1, 'C');
            }
            
            // Close and output PDF document
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } else {
            // Fall back to basic PDF
            fallbackPDF($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate);
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

/**
 * Fallback to simple PDF export if TCPDF fails
 */
function fallbackPDF($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate) {
    // This is a basic fallback that uses any available PDF library
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>PDF Export Error</h1>";
    echo "<p>The TCPDF library is not available or failed to load.</p>";
    echo "<p>Please try exporting to Excel format instead.</p>";
    echo "<p><a href='inventory.php'>Return to Inventory</a></p>";
    exit;
}

// Close connection
$conn->close();
exit;
?>
