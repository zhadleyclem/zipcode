<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Read the zip code data from CSV file
    $zipData = [];
    $filename = 'zipcode_data.csv';
    
    if (!file_exists($filename)) {
        throw new Exception('Zip code data file not found');
    }
    
    $handle = fopen($filename, 'r');
    if (!$handle) {
        throw new Exception('Could not open zip code data file');
    }
    
    // Skip header row
    $header = fgetcsv($handle);
    
    $count = 0;
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 5) {
            $zip = str_pad($row[0], 5, '0', STR_PAD_LEFT); // Ensure 5 digits with leading zeros
            $zipData[$zip] = [
                'zip' => $zip,
                'city' => $row[1],
                'state' => $row[2],
                'lat' => floatval($row[3]),
                'lng' => floatval($row[4])
            ];
            $count++;
        }
    }
    
    fclose($handle);
    
    if ($count == 0) {
        throw new Exception('No valid zip code data found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $zipData,
        'count' => $count,
        'message' => "Loaded $count zip codes successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
?>
