<?php
// Include the Composer autoloader
require 'vendor/autoload.php';

// Set proper content type for HTML
header('Content-Type: text/html; charset=utf-8');

echo "<h1>PDF Library Status</h1>";

// Check if FPDF is available
if (class_exists('FPDF')) {
    echo "<p style='color:green'>✓ FPDF (setasign/fpdf) is installed and available.</p>";
    echo "<p>Version: " . FPDF_VERSION . "</p>";
} else {
    echo "<p style='color:red'>✗ FPDF (setasign/fpdf) is not available.</p>";
}

// Check if TCPDF is available
if (class_exists('TCPDF')) {
    echo "<p style='color:green'>✓ TCPDF (tecnickcom/tcpdf) is installed and available.</p>";
    echo "<p>Version: " . TCPDF_VERSION . "</p>";
} else {
    echo "<p style='color:red'>✗ TCPDF (tecnickcom/tcpdf) is not available.</p>";
}

// Show path information
echo "<h2>Path Information</h2>";
echo "<p>Current working directory: " . getcwd() . "</p>";
echo "<p>Autoloader path: " . realpath('vendor/autoload.php') . "</p>";

// Create a simple test PDF
echo "<h2>PDF Test</h2>";
echo "<p>Attempting to create a test PDF...</p>";

try {
    if (class_exists('FPDF')) {
        // Try FPDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'FPDF Test Successful');
        $pdf->Output('F', 'fpdf_test.pdf');
        
        echo "<p style='color:green'>✓ FPDF test PDF created successfully: <a href='fpdf_test.pdf' target='_blank'>View PDF</a></p>";
    } 
    else if (class_exists('TCPDF')) {
        // Try TCPDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(40, 10, 'TCPDF Test Successful');
        $pdf->Output('tcpdf_test.pdf', 'F');
        
        echo "<p style='color:green'>✓ TCPDF test PDF created successfully: <a href='tcpdf_test.pdf' target='_blank'>View PDF</a></p>";
    }
    else {
        echo "<p style='color:red'>✗ No PDF library is available to test.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error creating test PDF: " . $e->getMessage() . "</p>";
}

echo "<p><a href='inventory.php'>Back to Inventory</a></p>";
?>
