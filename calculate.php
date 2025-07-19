<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    // Convert degrees to radians
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);
    
    // Haversine formula
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    // Earth's radius in miles
    $earthRadius = 3959;
    
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

function loadZipData() {
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
    fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 5) {
            $zip = str_pad($row[0], 5, '0', STR_PAD_LEFT);
            $zipData[$zip] = [
                'zip' => $zip,
                'city' => $row[1],
                'state' => $row[2],
                'lat' => floatval($row[3]),
                'lng' => floatval($row[4])
            ];
        }
    }
    
    fclose($handle);
    return $zipData;
}

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    // Get the zip codes from POST data
    $zip1 = isset($_POST['zip1']) ? trim($_POST['zip1']) : '';
    $zip2 = isset($_POST['zip2']) ? trim($_POST['zip2']) : '';
    
    // Validate input
    if (empty($zip1) || empty($zip2)) {
        throw new Exception('Both zip codes are required');
    }
    
    if (!preg_match('/^\d{5}$/', $zip1) || !preg_match('/^\d{5}$/', $zip2)) {
        throw new Exception('Please enter valid 5-digit zip codes');
    }
    
    if ($zip1 === $zip2) {
        throw new Exception('Please enter two different zip codes');
    }
    
    // Load zip code data
    $zipData = loadZipData();
    
    // Check if both zip codes exist in our data
    if (!isset($zipData[$zip1])) {
        throw new Exception("Zip code $zip1 not found in database");
    }
    
    if (!isset($zipData[$zip2])) {
        throw new Exception("Zip code $zip2 not found in database");
    }
    
    // Get zip code information
    $zip1Info = $zipData[$zip1];
    $zip2Info = $zipData[$zip2];
    
    // Calculate distance using Haversine formula
    $distance = haversineDistance(
        $zip1Info['lat'], $zip1Info['lng'],
        $zip2Info['lat'], $zip2Info['lng']
    );
    
    // Return the results
    echo json_encode([
        'success' => true,
        'zip1_info' => $zip1Info,
        'zip2_info' => $zip2Info,
        'distance' => $distance,
        'formula' => 'Haversine',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
