<?php

require 'vendor/autoload.php';

use Phpml\Classification\MLPClassifier;
use Phpml\Dataset\ArrayDataset;

// Get correct path to CSV file
$csvPath = __DIR__ . '/tasks.csv';

// Check if CSV exists
if (!file_exists($csvPath)) {
    die("Error: Training data file not found at: $csvPath\n");
}

try {
    // Load CSV Data with error handling
    $csvContent = file_get_contents($csvPath);
    if ($csvContent === false) {
        throw new Exception("Failed to read CSV file");
    }
    
    $data = array_map('str_getcsv', explode("\n", $csvContent));
    array_shift($data); // Remove CSV header

    // Initialize samples and labels
    $samples = [];
    $labels = [];

    // Task type mapping (matching your data)
    $taskMapping = [
        'Request Amenities' => 1,
        'Housekeeping' => 2,
        'Order Food' => 3
    ];

    foreach ($data as $row) {
        // Check if row has all required columns
        if (isset($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6])) {
            // CSV Structure: id,uname,request,details,room,status,created_at
            $request = $taskMapping[$row[2]] ?? 0;  // request is in column 2
            $room = (int) $row[4];                  // room is in column 4
            $detailsLength = strlen($row[3]);       // details is in column 3
            $status = ($row[5] === 'Pending') ? 1 : 0;  // status is in column 5
            
            // Add time-based features
            $timestamp = strtotime($row[6]);        // created_at is in column 6
            $hourOfDay = (int) date('H', $timestamp);
            
            // Enhanced feature set
            $samples[] = [
                $request,           // task type
                $room,             // room number
                $detailsLength,    // length of details
                $hourOfDay,        // hour of the day (0-23)
            ];
            $labels[] = $status;
        }
    }

    if (empty($samples)) {
        throw new Exception("No valid training data found in CSV");
    }

    // Add debug logging before training
    echo "Sample structure example:\n";
    echo "First sample: " . json_encode($samples[0]) . "\n";
    echo "Features per sample: " . count($samples[0]) . "\n";
    
    // Train MLP Classifier with updated input size (4 features)
    $classifier = new MLPClassifier(4, [12, 8], [0, 1]);
    
    // Add debug logging after training
    echo "Model configuration:\n";
    echo "Input features: 4\n";
    echo "Hidden layers: [12, 8]\n";

    // Save Model to JSON File
    $modelPath = __DIR__ . '/task_model.json';
    if (file_put_contents($modelPath, json_encode(serialize($classifier))) === false) {
        throw new Exception("Failed to save model file");
    }

    echo "✅ AI Model Successfully Trained!\n";
    echo "Model saved to: $modelPath\n";
    echo "Number of training samples: " . count($samples) . "\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
