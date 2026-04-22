<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? ''; // expects lat,lng

if (empty($origin) || empty($destination)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Origin and destination are required.']);
    exit;
}

$apiKey = GOOGLE_MAPS_API_KEY;

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Google Maps API Key completely missing from configuration.']);
    exit;
}

$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) . "&destinations=" . urlencode($destination) . "&key=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if(curl_errno($ch)){
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if ($data['status'] === 'OK') {
    $element = $data['rows'][0]['elements'][0];
    if ($element['status'] === 'OK') {
        echo json_encode([
            'success'  => true,
            'distance' => $element['distance']['text'],
            'duration' => $element['duration']['text'],
            'address'  => $data['origin_addresses'][0]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not calculate distance. Please try a more specific address.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Google API Error: ' . $data['status']]);
}
?>
