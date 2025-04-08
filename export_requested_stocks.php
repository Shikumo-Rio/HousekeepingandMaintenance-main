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

// Get the format and filters from the URL
$exportFormat = $_GET['format'] ?? 'excel';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Get password from request or use default
$encryptionPassword = $_GET['encryption_password'] ?? "paradisehotel2025";

// Store who generated the report
$generatedBy = $_SESSION['username'] ?? 'Unknown User';

// Format the report filename
$filename = 'requested_stocks_' . date('Y-m-d_H-i-s');
$title = 'Requested Stocks Report';
$headers = ['ID', 'Item Name', 'Quantity', 'Category', 'Status', 'Requested By', 'Created At'];

// Retrieve requested stocks from the API
$apiUrl = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/view_api.php";
$response = file_get_contents($apiUrl);

if ($response === false) {
    die("Failed to retrieve data from API");
}

$jsonData = json_decode($response, true);
if (!$jsonData) {
    die("Failed to parse API response");
}

// Filter requests for Housekeeping department
$filteredRequests = [];
if (isset($jsonData['requests']) && is_array($jsonData['requests'])) {
    foreach ($jsonData['requests'] as $request) {
        if (isset($request['pickup_location']) && 
            strtolower($request['pickup_location']) === 'housekeeping') {
            $filteredRequests[] = $request;
        }
    }
}

// Process request items
$data = [];
if (!empty($filteredRequests) && isset($jsonData['request_items']) && is_array($jsonData['request_items'])) {
    foreach ($filteredRequests as $request) {
        $requestItems = array_filter($jsonData['request_items'], function($item) use ($request) {
            return $item['request_id'] == $request['id'];
        });
        
        foreach ($requestItems as $item) {
            $status = $request['status'] ?? 'Pending';
            
            // Apply date filter if provided
            if (!empty($startDate) && !empty($endDate)) {
                $createdAt = strtotime($request['created_at']);
                $startTs = strtotime($startDate);
                $endTs = strtotime($endDate) + 86400; // Add one day to include the end date
                
                if ($createdAt < $startTs || $createdAt > $endTs) {
                    continue;
                }
            } else if (!empty($startDate)) {
                $createdAt = strtotime($request['created_at']);
                $startTs = strtotime($startDate);
                
                if ($createdAt < $startTs) {
                    continue;
                }
            } else if (!empty($endDate)) {
                $createdAt = strtotime($request['created_at']);
                $endTs = strtotime($endDate) + 86400; // Add one day to include the end date
                
                if ($createdAt > $endTs) {
                    continue;
                }
            }
            
            // Apply status filter if provided
            if (!empty($statusFilter) && strtolower($status) !== strtolower($statusFilter)) {
                continue;
            }
            
            // Add to data array
            $data[] = [
                'id' => $item['id'],
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'category' => $item['category'] ?? 'General',
                'status' => $status,
                'requested_by' => $request['requester_name'] ?? 'Unknown',
                'created_at' => $request['created_at']
            ];
        }
    }
}

// Sort by created date (newest first)
usort($data, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Export based on format
if ($exportFormat == 'excel') {
    exportToExcel($data, $filename, $headers, $title, $generatedBy, $startDate, $endDate, $statusFilter);
} elseif ($exportFormat == 'pdf') {
    exportToPDFSecure($data, $filename, $headers, $title, $generatedBy, $startDate, $endDate, $statusFilter, $encryptionPassword);
} else {
    die("Invalid export format");
}

/**
 * Export data to Excel
 */
function exportToExcel($data, $filename, $headers, $title, $generatedBy, $startDate, $endDate, $statusFilter) {
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
    echo '.pending { color: #0000FF; }'; // Blue for pending
    echo '.approved, .completed { color: #008000; }'; // Green for approved/completed
    echo '.cancelled { color: #FF0000; }'; // Red for cancelled
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
                    $statusLower = strtolower($value);
                    if ($statusLower === 'pending') {
                        $class = 'class="pending"';
                    } elseif ($statusLower === 'approved' || $statusLower === 'completed') {
                        $class = 'class="approved"';
                    } elseif ($statusLower === 'cancelled') {
                        $class = 'class="cancelled"';
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
        $totalQuantity = array_sum(array_column($data, 'quantity'));
        $pendingCount = count(array_filter($data, function($item) {
            return strtolower($item['status']) === 'pending';
        }));
        $approvedCount = count(array_filter($data, function($item) {
            return strtolower($item['status']) === 'approved' || strtolower($item['status']) === 'completed';
        }));
        $cancelledCount = count(array_filter($data, function($item) {
            return strtolower($item['status']) === 'cancelled';
        }));
        
        echo '<tr><td colspan="' . count($headers) . '"></td></tr>';
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Records:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . count($data) . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Total Items Requested:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $totalQuantity . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Pending Requests:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $pendingCount . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Approved/Completed Requests:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $approvedCount . '</td>';
        echo '</tr>';
        
        echo '<tr class="summary-row">';
        echo '<td colspan="2">Cancelled Requests:</td>';
        echo '<td colspan="' . (count($headers) - 2) . '">' . $cancelledCount . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export data to PDF with TCPDF and password protection
 */
function exportToPDFSecure($data, $filename, $headers, $title, $generatedBy, $startDate, $endDate, $statusFilter, $encryptionPassword) {
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
            $pdf->SetSubject('Requested Stocks Report');
            
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
            $colWidths = array(
                $pageWidth * 0.06, // ID
                $pageWidth * 0.25, // Item Name
                $pageWidth * 0.10, // Quantity
                $pageWidth * 0.13, // Category
                $pageWidth * 0.13, // Status
                $pageWidth * 0.17, // Requested By
                $pageWidth * 0.16  // Created At
            );
            
            // Print header row
            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 7, $header, 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Print data rows
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor(255, 255, 255);
            
            $totalQuantity = 0;
            $pendingCount = 0;
            $approvedCount = 0;
            $cancelledCount = 0;
            
            if (count($data) > 0) {
                foreach ($data as $row) {
                    // Update counters
                    $totalQuantity += (int)$row['quantity'];
                    $statusLower = strtolower($row['status']);
                    
                    if ($statusLower === 'pending') {
                        $pendingCount++;
                        $pdf->SetTextColor(0, 0, 255); // Blue
                    } elseif ($statusLower === 'approved' || $statusLower === 'completed') {
                        $approvedCount++;
                        $pdf->SetTextColor(0, 128, 0); // Green
                    } elseif ($statusLower === 'cancelled') {
                        $cancelledCount++;
                        $pdf->SetTextColor(255, 0, 0); // Red
                    } else {
                        $pdf->SetTextColor(0, 0, 0); // Black
                    }
                    
                    $i = 0;
                    foreach ($row as $key => $value) {
                        // Reset text color for non-status columns
                        if ($key !== 'status') {
                            $pdf->SetTextColor(0, 0, 0);
                        }
                        
                        // Truncate long text fields
                        if (strlen($value) > 30 && ($key === 'item_name' || $key === 'requested_by')) {
                            $value = substr($value, 0, 27) . '...';
                        }
                        
                        // Format created_at date
                        if ($key === 'created_at' && !empty($value)) {
                            $value = date('Y-m-d H:i', strtotime($value));
                        }
                        
                        $pdf->Cell($colWidths[$i], 6, $value, 1, 0, 'L');
                        $i++;
                    }
                    $pdf->Ln();
                }
                
                // Add summary section
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                
                $pdf->Cell(60, 7, 'Total Records:', 0, 0, 'L');
                $pdf->Cell(0, 7, count($data), 0, 1, 'L');
                
                $pdf->Cell(60, 7, 'Total Items Requested:', 0, 0, 'L');
                $pdf->Cell(0, 7, $totalQuantity, 0, 1, 'L');
                
                $pdf->Cell(60, 7, 'Pending Requests:', 0, 0, 'L');
                $pdf->Cell(0, 7, $pendingCount, 0, 1, 'L');
                
                $pdf->Cell(60, 7, 'Approved/Completed Requests:', 0, 0, 'L');
                $pdf->Cell(0, 7, $approvedCount, 0, 1, 'L');
                
                $pdf->Cell(60, 7, 'Cancelled Requests:', 0, 0, 'L');
                $pdf->Cell(0, 7, $cancelledCount, 0, 1, 'L');
            } else {
                $pdf->Cell(array_sum($colWidths), 10, 'No data available', 1, 1, 'C');
            }
            
            // Output PDF
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } else {
            // Fall back to basic PDF
            fallbackPDF($data, $filename, $headers, $title);
        }
    } catch (Exception $e) {
        // If PDF generation fails, show error and suggest Excel export
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>PDF Export Error</h1>";
        echo "<p>An error occurred while generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please try exporting to Excel format instead.</p>";
        echo "<p><a href='request_inventory.php'>Return to Request Inventory</a></p>";
        exit;
    }
}

/**
 * Fallback to simple PDF export if TCPDF fails
 */
function fallbackPDF($data, $filename, $headers, $title) {
    // This is a basic fallback that uses any available PDF library
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>PDF Export Error</h1>";
    echo "<p>The TCPDF library is not available or failed to load.</p>";
    echo "<p>Please try exporting to Excel format instead.</p>";
    echo "<p><a href='request_inventory.php'>Return to Request Inventory</a></p>";
    exit;
}

// Close connection
$conn->close();
exit;
?>
