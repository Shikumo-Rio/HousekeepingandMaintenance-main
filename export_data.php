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
$table = $_GET['table'] ?? '';
$exportFormat = $_GET['format'] ?? 'excel';
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Get password from request or use default
$encryptionPassword = $_GET['encryption_password'] ?? "paradisehotel2025";

// Store who generated the report
$generatedBy = $_SESSION['username'] ?? 'Unknown User';

// Start building the query
$queryBase = "";
$whereClause = " WHERE 1=1"; // Base where clause to make appending conditions easier
$exportType = ""; // Used internally for data processing

// Process based on selected table
if ($table == 'lost_and_found') {
    $queryBase = "SELECT id, found_by, type, room, date, item, description, status, picture FROM lost_and_found";
    $filename = 'lost_and_found_items_' . date('Y-m-d_H-i-s');
    $title = 'Lost and Found Items';
    $headers = ['ID', 'Reported By', 'Type', 'Room/Area', 'Date', 'Item', 'Description', 'Status', 'Picture'];
    $exportType = "all";
} elseif ($table == 'claims') {
    $queryBase = "SELECT id, lost_item_id, guest_name, room_no, contact_info, area_lost, date_lost, date_claimed, 
                  description, validated_by, proof_id, claim_status FROM claims";
    $filename = 'claims_history_' . date('Y-m-d_H-i-s');
    $title = 'Claims History';
    $headers = ['ID', 'Lost Item ID', 'Guest Name', 'Room No', 'Contact Info', 'Area Lost', 'Date Lost', 'Date Claimed', 
                'Description', 'Validated By', 'Proof ID', 'Status'];
    $exportType = "claims";
} else {
    die("Invalid export table type");
}

// Add date filter if provided
if (!empty($startDate) && !empty($endDate)) {
    if ($exportType == "claims") {
        $whereClause .= " AND DATE(date_claimed) BETWEEN '$startDate' AND '$endDate'";
    } else {
        $whereClause .= " AND DATE(date) BETWEEN '$startDate' AND '$endDate'";
    }
} elseif (!empty($startDate)) {
    if ($exportType == "claims") {
        $whereClause .= " AND DATE(date_claimed) >= '$startDate'";
    } else {
        $whereClause .= " AND DATE(date) >= '$startDate'";
    }
} elseif (!empty($endDate)) {
    if ($exportType == "claims") {
        $whereClause .= " AND DATE(date_claimed) <= '$endDate'";
    } else {
        $whereClause .= " AND DATE(date) <= '$endDate'";
    }
}

// Add status filter if provided
if (!empty($statusFilter)) {
    if ($exportType == "claims") {
        $whereClause .= " AND claim_status = '$statusFilter'";
    } else {
        $whereClause .= " AND status = '$statusFilter'";
    }
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
    echo '.claimed, .found, .approved { color: #008000; }'; // Green for claimed/found/approved
    echo '.lost, .expired, .rejected { color: #FF0000; }'; // Red for lost/expired/rejected
    echo '.returned { color: #0000FF; }'; // Blue for returned
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
                // Skip showing the picture path
                if ($key === 'picture') {
                    $value = !empty($value) ? 'Yes' : 'No';
                }
                
                $class = '';
                
                // Apply status-based styling
                if ($key === 'status' || $key === 'claim_status') {
                    $status = strtolower($value);
                    
                    if ($status === 'pending') {
                        $class = 'class="pending"';
                    } elseif (in_array($status, ['claimed', 'found', 'approved'])) {
                        $class = 'class="claimed"';
                    } elseif (in_array($status, ['returned'])) {
                        $class = 'class="returned"';
                    } elseif (in_array($status, ['lost', 'expired', 'rejected'])) {
                        $class = 'class="lost"';
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
        $statusCounts = [];
        $statusField = ($exportType === 'claims') ? 'claim_status' : 'status';
        
        foreach ($data as $row) {
            if (isset($row[$statusField])) {
                $status = strtolower($row[$statusField]);
                if (!isset($statusCounts[$status])) {
                    $statusCounts[$status] = 0;
                }
                $statusCounts[$status]++;
            }
            
            // For lost_and_found, count types as well
            if ($exportType === 'all' && isset($row['type'])) {
                $type = strtolower($row['type']);
                $typeKey = "type_" . $type;
                if (!isset($statusCounts[$typeKey])) {
                    $statusCounts[$typeKey] = 0;
                }
                $statusCounts[$typeKey]++;
            }
        }
        
        echo '<tr><td colspan="' . count($headers) . '"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Records:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $totalRecords . '</td>';
        echo '</tr>';
        
        // Show status summaries
        foreach ($statusCounts as $key => $count) {
            if (strpos($key, 'type_') === 0) {
                $label = ucfirst(str_replace('type_', '', $key)) . ' Items';
            } else {
                $label = ucfirst($key) . ' Items';
            }
            
            echo '<tr class="summary-row">';
            echo '<td colspan="2">' . $label . ':</td>';
            echo '<td colspan="' . (count($headers) - 2) . '">' . $count . '</td>';
            echo '</tr>';
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
                if (strpos(strtolower($header), 'description') !== false) {
                    $colWidths[] = $pageWidth * 0.2; // 20% for description columns
                } else if ($header === 'ID' || $header === 'Picture' || $header === 'Proof ID') {
                    $colWidths[] = $pageWidth * 0.05; // 5% for ID columns
                } else if (strpos(strtolower($header), 'date') !== false) {
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
            
            $statusCounts = [];
            $statusField = ($exportType === 'claims') ? 'claim_status' : 'status';
            
            if (count($data) > 0) {
                foreach ($data as $row) {
                    // Count status occurrences for summary
                    if (isset($row[$statusField])) {
                        $status = strtolower($row[$statusField]);
                        if (!isset($statusCounts[$status])) {
                            $statusCounts[$status] = 0;
                        }
                        $statusCounts[$status]++;
                    }
                    
                    // For lost_and_found, count types as well
                    if ($exportType === 'all' && isset($row['type'])) {
                        $type = strtolower($row['type']);
                        $typeKey = "type_" . $type;
                        if (!isset($statusCounts[$typeKey])) {
                            $statusCounts[$typeKey] = 0;
                        }
                        $statusCounts[$typeKey]++;
                    }
                    
                    $i = 0;
                    foreach ($row as $key => $value) {
                        // Handle picture column
                        if ($key === 'picture') {
                            $value = !empty($value) ? 'Yes' : 'No';
                        }
                        
                        // Set text color based on status
                        if ($key === 'status' || $key === 'claim_status') {
                            $status = strtolower($value);
                            
                            if ($status === 'pending') {
                                $pdf->SetTextColor(255, 128, 0); // Orange
                            } elseif (in_array($status, ['claimed', 'found', 'approved'])) {
                                $pdf->SetTextColor(0, 128, 0); // Green
                            } elseif (in_array($status, ['returned'])) {
                                $pdf->SetTextColor(0, 0, 255); // Blue
                            } elseif (in_array($status, ['lost', 'expired', 'rejected'])) {
                                $pdf->SetTextColor(255, 0, 0); // Red
                            } else {
                                $pdf->SetTextColor(0, 0, 0); // Black
                            }
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                        
                        // Truncate long text
                        if (strlen($value) > 30 && strpos($key, 'description') !== false) {
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
                $pdf->SetTextColor(0, 0, 0); // Reset to black
                
                $pdf->Cell(60, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                // Show status summaries with appropriate colors
                foreach ($statusCounts as $key => $count) {
                    if (strpos($key, 'type_') === 0) {
                        $label = ucfirst(str_replace('type_', '', $key)) . ' Items';
                        $pdf->SetTextColor(0, 0, 0); // Black for types
                    } else {
                        $label = ucfirst($key) . ' Items';
                        
                        if ($key === 'pending') {
                            $pdf->SetTextColor(255, 128, 0); // Orange
                        } elseif (in_array($key, ['claimed', 'found', 'approved'])) {
                            $pdf->SetTextColor(0, 128, 0); // Green
                        } elseif (in_array($key, ['returned'])) {
                            $pdf->SetTextColor(0, 0, 255); // Blue
                        } elseif (in_array($key, ['lost', 'expired', 'rejected'])) {
                            $pdf->SetTextColor(255, 0, 0); // Red
                        } else {
                            $pdf->SetTextColor(0, 0, 0); // Black
                        }
                    }
                    
                    $pdf->Cell(60, 7, $label . ':', 0, 0, 'L');
                    $pdf->Cell(0, 7, $count, 0, 1, 'L');
                    $pdf->SetTextColor(0, 0, 0); // Reset to black
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
        echo "<p><a href='lostfoundItems.php'>Return to Lost and Found Items</a></p>";
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
    echo "<p><a href='lostfoundItems.php'>Return to Lost and Found Items</a></p>";
    exit;
}

// Close connection
$conn->close();
exit;
?>
