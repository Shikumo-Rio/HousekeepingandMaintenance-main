<?php
// Turn off warnings and notices to prevent any output before PDF generation
error_reporting(E_ERROR | E_PARSE);

// Check if session already started to avoid warning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");
require 'vendor/autoload.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Get the export type and format from the URL
$exportType = $_GET['type'] ?? '';
$exportFormat = $_GET['format'] ?? '';

// Process based on selected table
if ($exportType == 'checkout') {
    $query = "SELECT id, room_no, checkout_time, request, special_request, status, created_at FROM checkout_notices ORDER BY id DESC";
    $filename = 'checkout_notices_' . date('Y-m-d_H-i-s');
    $title = 'Checkout Notices';
    $headers = ['ID', 'Room No', 'Return Time', 'Type', 'Special Request', 'Status', 'Created At'];
} elseif ($exportType == 'foodorders') {
    $query = "SELECT id, code, customer_name, food_item, quantity, totalprice, status, created_at FROM foodorders ORDER BY id DESC";
    $filename = 'food_orders';
    $title = 'Food Orders';
    $headers = ['ID', 'Code', 'Customer Name', 'Food Item', 'Quantity', 'Total Price', 'Status', 'Created At'];
} elseif ($exportType == 'messages') {
    $query = "SELECT id, uname, request, details, room, status, priority, created_at FROM customer_messages ORDER BY id DESC";
    $filename = 'customer_messages';
    $title = 'Customer Messages';
    $headers = ['ID', 'Username', 'Request', 'Details', 'Room', 'Status', 'Priority', 'Created At'];
} else {
    die("Invalid export type");
}

// Execute the query
$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Data array
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Export based on format
if ($exportFormat == 'excel') {
    exportToExcel($data, $filename, $headers, $title, $exportType);
} elseif ($exportFormat == 'pdf') {
    exportToPDF($data, $filename, $headers, $title, $exportType);
} else {
    die("Invalid export format");
}

/**
 * Export data to Excel
 */
function exportToExcel($data, $filename, $headers, $title, $exportType) {
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
    echo '.pending { color: #FF8000; }'; // Orange for pending
    echo '.assigned, .completed { color: #008000; }'; // Green for assigned/completed
    echo '.summary-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report title
    echo '<h1>' . $title . '</h1>';
    echo '<h3>Generated on: ' . date('Y-m-d H:i:s') . '</h3>';
    
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
            foreach ($row as $key => $value) {
                $class = '';
                
                // Apply status-based styling
                if ($key === 'status') {
                    if (strtolower($value) === 'pending') {
                        $class = 'class="pending"';
                    } elseif (strtolower($value) === 'assigned' || strtolower($value) === 'completed') {
                        $class = 'class="assigned"';
                    }
                }
                
                echo '<td ' . $class . '>' . $value . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . count($headers) . '">No data available</td></tr>';
    }
    
    // Summary section
    if (count($data) > 0) {
        $totalRecords = count($data);
        $pendingCount = 0;
        $assignedCount = 0;
        
        foreach ($data as $row) {
            if (isset($row['status'])) {
                if (strtolower($row['status']) === 'pending') {
                    $pendingCount++;
                } elseif (strtolower($row['status']) === 'assigned' || strtolower($row['status']) === 'completed') {
                    $assignedCount++;
                }
            }
        }
        
        echo '<tr><td colspan="' . count($headers) . '"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Records:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $totalRecords . '</td>';
        echo '</tr>';
        
        if ($exportType != 'foodorders') { // Food orders might not have assigned status
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Pending Items:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $pendingCount . '</td>';
            echo '</tr>';
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">Assigned/Completed Items:</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $assignedCount . '</td>';
        }
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export data to PDF
 */
function exportToPDF($data, $filename, $headers, $title, $exportType) {
    try {
        // Clear all previous output and buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Try to use FPDF
        if (class_exists('FPDF')) {
            // Use FPDF
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(10);
            
            // Define column widths - adjust based on headers
            $colWidths = [];
            $totalHeaders = count($headers);
            $pageWidth = 190; // Available width in mm for A4 portrait
            
            // Dynamically calculate width based on number of columns
            $baseWidth = floor($pageWidth / $totalHeaders);
            foreach ($headers as $header) {
                // Allocate more width for text-heavy columns
                if (strpos(strtolower($header), 'request') !== false || 
                    strpos(strtolower($header), 'details') !== false) {
                    $colWidths[] = $baseWidth * 1.5;
                } else if ($header === 'ID') {
                    $colWidths[] = $baseWidth * 0.5;
                } else {
                    $colWidths[] = $baseWidth;
                }
            }
            
            // Adjust widths proportionally to fit page
            $totalWidth = array_sum($colWidths);
            if ($totalWidth > $pageWidth) {
                $ratio = $pageWidth / $totalWidth;
                for ($i = 0; $i < count($colWidths); $i++) {
                    $colWidths[$i] = floor($colWidths[$i] * $ratio);
                }
            }
            
            // Table header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            
            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Table data
            $pdf->SetFont('Arial', '', 9);
            
            if (count($data) > 0) {
                $pendingCount = 0;
                $assignedCount = 0;
                
                foreach ($data as $row) {
                    $i = 0;
                    foreach ($row as $key => $value) {
                        // Set special colors for status
                        if ($key === 'status') {
                            if (strtolower($value) === 'pending') {
                                $pdf->SetTextColor(255, 128, 0); // Orange
                                $pendingCount++;
                            } elseif (strtolower($value) === 'assigned' || strtolower($value) === 'completed') {
                                $pdf->SetTextColor(0, 128, 0); // Green
                                $assignedCount++;
                            } else {
                                $pdf->SetTextColor(0, 0, 0); // Black
                            }
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        // Truncate long text
                        if (strlen($value) > 30) {
                            $value = substr($value, 0, 27) . '...';
                        }
                        
                        // Output the cell
                        $pdf->Cell($colWidths[$i], 7, $value, 1, 0, 'L');
                        $i++;
                    }
                    $pdf->Ln();
                    
                    // Check if we need a new page
                    if ($pdf->GetY() > 260) {
                        $pdf->AddPage();
                        
                        // Repeat header on new page
                        $pdf->SetFont('Arial', 'B', 10);
                        $pdf->SetFillColor(240, 240, 240);
                        $pdf->SetTextColor(0, 0, 0);
                        
                        foreach ($headers as $i => $header) {
                            $pdf->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
                        }
                        $pdf->Ln();
                        $pdf->SetFont('Arial', '', 9);
                    }
                }
                
                // Summary section
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
                $pdf->SetFont('Arial', '', 10);
                
                $pdf->Cell(80, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                if ($exportType != 'foodorders') {
                    $pdf->Cell(80, 7, 'Pending Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $pendingCount, 0, 1, 'L');
                    
                    $pdf->Cell(80, 7, 'Assigned/Completed Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $assignedCount, 0, 1, 'L');
                }
            } else {
                $pdf->Cell(array_sum($colWidths), 10, 'No data available', 1, 1, 'C');
            }
            
            // Output PDF directly as download
            $pdf->Output('D', $filename . '.pdf');
            exit;
        } 
        // Fallback to TCPDF if available
        else if (class_exists('TCPDF')) {
            // Similar TCPDF implementation would go here
            // ...existing code...
        } 
        else {
            // No PDF library available
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>PDF Export Error</h1>";
            echo "<p>PDF libraries not found despite being installed via Composer.</p>";
            echo "<p>Please try exporting to Excel format instead.</p>";
            echo "<p><a href='guest.php'>Return to Guest Requests</a></p>";
            exit;
        }
    } catch (Exception $e) {
        // If PDF generation fails, show error and suggest Excel export
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='guest.php'>Return to Guest Requests</a></p>";
        exit;
    }
}

// Close connection
$conn->close();
exit;
?>
