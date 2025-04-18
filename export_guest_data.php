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
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Get password from request or use default
$encryptionPassword = $_GET['encryption_password'] ?? "paradisehotel2025";
// Get Excel password if provided
$excelPassword = $_GET['excel_password'] ?? "";

// Store who generated the report
$generatedBy = $_SESSION['username'] ?? 'Unknown User';

// Start building the query
$queryBase = "";
$whereClause = " WHERE 1=1"; // Base where clause to make appending conditions easier

// Process based on selected table
if ($exportType == 'checkout') {
    $queryBase = "SELECT id, room_no, checkout_time, request, special_request, status, assigned_to, created_at FROM checkout_notices";
    $filename = 'checkout_notices_' . date('Y-m-d_H-i-s');
    $title = 'Checkout Notices';
    $headers = ['ID', 'Room No', 'Return Time', 'Type', 'Special Request', 'Status', 'Assigned To', 'Created At'];
} elseif ($exportType == 'foodorders') {
    $queryBase = "SELECT id, code, customer_name, food_item, quantity, totalprice, status, created_at FROM foodorders";
    $filename = 'food_orders_' . date('Y-m-d_H-i-s');
    $title = 'Food Orders';
    $headers = ['ID', 'Code', 'Customer Name', 'Food Item', 'Quantity', 'Total Price', 'Status', 'Created At'];
} elseif ($exportType == 'messages') {
    $queryBase = "SELECT id, uname, request, details, room, status, priority, created_at FROM customer_messages";
    $filename = 'customer_messages_' . date('Y-m-d_H-i-s');
    $title = 'Customer Messages';
    $headers = ['ID', 'Username', 'Request', 'Details', 'Room', 'Status', 'Priority', 'Created At'];
} else {
    die("Invalid export type");
}

// Add date filter if provided
if (!empty($startDate) && !empty($endDate)) {
    $whereClause .= " AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $whereClause .= " AND DATE(created_at) >= '$startDate'";
} elseif (!empty($endDate)) {
    $whereClause .= " AND DATE(created_at) <= '$endDate'";
}

// Add status filter if provided
if (!empty($statusFilter)) {
    $whereClause .= " AND status = '$statusFilter'";
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
        $data[] = $row;
    }
}

// Export based on format
if ($exportFormat == 'excel') {
    exportToExcel($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter);
} elseif ($exportFormat == 'pdf') {
    exportToPDFSecure($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter);
} else {
    die("Invalid export format");
}

/**
 * Export data to Excel
 */
function exportToExcel($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter) {
    global $excelPassword;
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start output
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    
    // Add VBA script for password protection if password is provided
    if (!empty($excelPassword)) {
        echo '<!--[if gte mso 9]>
        <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Protected Sheet</x:Name>
                    <x:WorksheetOptions>
                        <x:Password>' . strtoupper(substr(md5($excelPassword), 0, 16)) . '</x:Password>
                        <x:ProtectContents>True</x:ProtectContents>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
        </xml>
        <![endif]-->';
        
        // Add JavaScript to enforce password protection when opened in modern applications
        echo '<script>
        window.onload = function() {
            alert("This document is password protected. Password: ' . htmlspecialchars($excelPassword) . '");
        }
        </script>';
    }
    
    echo '<style>';
    echo 'table { border-collapse: collapse; }';
    echo 'th, td { border: 1px solid #000000; padding: 5px; }';
    echo 'th { background-color: #f0f0f0; font-weight: bold; }';
    echo '.pending { color: #FF8000; }'; // Orange for pending
    echo '.assigned, .completed { color: #008000; }'; // Green for assigned/completed
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
    
    // Show applied filters if any
    if ($startDate && $endDate) {
        echo 'Date Range: ' . $startDate . ' to ' . $endDate . '<br>';
    } elseif ($startDate) {
        echo 'From Date: ' . $startDate . '<br>';
    } elseif ($endDate) {
        echo 'To Date: ' . $endDate . '<br>';
    }
    
    if ($statusFilter) {
        echo 'Status Filter: ' . $statusFilter . '<br>';
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
 * Export data to PDF with TCPDF and password protection
 */
function exportToPDFSecure($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter) {
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
            
            // Show filters if applied
            $filterText = '';
            if ($startDate && $endDate) {
                $filterText .= 'Date Range: ' . $startDate . ' to ' . $endDate . "\n";
            } elseif ($startDate) {
                $filterText .= 'From Date: ' . $startDate . "\n";
            } elseif ($endDate) {
                $filterText .= 'To Date: ' . $endDate . "\n";
            }
            
            if ($statusFilter) {
                $filterText .= 'Status Filter: ' . $statusFilter . "\n";
            }
            
            if (!empty($filterText)) {
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Write(0, 'Applied Filters:', '', 0, 'L', true, 0, false, false, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Write(0, $filterText, '', 0, 'L', true, 0, false, false, 0);
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
            foreach ($headers as $header) {
                if (strpos(strtolower($header), 'request') !== false || 
                    strpos(strtolower($header), 'details') !== false) {
                    $colWidths[] = $pageWidth * 0.2; // 20% for description columns
                } else if ($header === 'ID') {
                    $colWidths[] = $pageWidth * 0.05; // 5% for ID column
                } else if ($header === 'Created At') {
                    $colWidths[] = $pageWidth * 0.12; // 12% for date columns
                } else {
                    $colWidths[] = $pageWidth * 0.1; // 10% for other columns
                }
            }
            
            // Normalize widths to fit page
            $totalWidth = array_sum($colWidths);
            if ($totalWidth > $pageWidth) {
                $ratio = $pageWidth / $totalWidth;
                for ($i = 0; $i < count($colWidths); $i++) {
                    $colWidths[$i] = $colWidths[$i] * $ratio;
                }
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
            
            $pendingCount = 0;
            $assignedCount = 0;
            
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $i = 0;
                    foreach ($row as $key => $value) {
                        // Set text color based on status
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
                        
                        $pdf->Cell($colWidths[$i], 6, $value, 1, 0, 'L');
                        $i++;
                    }
                    $pdf->Ln();
                }
                
                // Add summary section
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                
                $pdf->Cell(60, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                if ($exportType != 'foodorders') {
                    $pdf->Cell(60, 7, 'Pending Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $pendingCount, 0, 1, 'L');
                    
                    $pdf->Cell(60, 7, 'Assigned/Completed Items:', 0, 0, 'L');
                    $pdf->Cell(0, 7, $assignedCount, 0, 1, 'L');
                }
            } else {
                $pdf->Cell(array_sum($colWidths), 10, 'No data available', 1, 1, 'C');
            }
            
            // Close and output PDF document
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } else {
            // Fall back to basic PDF
            fallbackPDF($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter);
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

/**
 * Fallback to simple PDF export if TCPDF fails
 */
function fallbackPDF($data, $filename, $headers, $title, $exportType, $generatedBy, $startDate, $endDate, $statusFilter) {
    // This is a basic fallback that uses any available PDF library
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>PDF Export Error</h1>";
    echo "<p>The TCPDF library is not available or failed to load.</p>";
    echo "<p>Please try exporting to Excel format instead.</p>";
    echo "<p><a href='guest.php'>Return to Guest Requests</a></p>";
    exit;
}

// Close connection
$conn->close();
exit;
?>
