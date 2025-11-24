<?php
/**
 * Geocoding proxy endpoint for the Red Cross Mobile App
 * Handles reverse geocoding requests to avoid CORS issues
 * 
 * This endpoint acts as a server-side proxy to the Nominatim API
 * since browsers block direct requests due to CORS policy.
 */

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$zoom = isset($_GET['zoom']) ? intval($_GET['zoom']) : 18;

// Validate parameters
if ($lat === null || $lon === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: lat and lon']);
    exit;
}

// Validate latitude and longitude ranges
if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// Build Nominatim API URL
$nominatimUrl = sprintf(
    'https://nominatim.openstreetmap.org/reverse?format=json&lat=%.8f&lon=%.8f&zoom=%d&addressdetails=1',
    $lat,
    $lon,
    $zoom
);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => $nominatimUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'RedCrossMobileApp/1.0 (PHP Proxy)',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9'
    ]
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// Handle cURL errors
if ($response === false || !empty($curlError)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Geocoding service unavailable',
        'message' => $curlError ?: 'Failed to connect to geocoding service'
    ]);
    exit;
}

// Handle HTTP errors
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'Geocoding service error',
        'http_code' => $httpCode
    ]);
    exit;
}

// Return the response from Nominatim
echo $response;


