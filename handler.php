<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Shipment.php';

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $shipment = new Shipment();

    switch($method) {
        case 'GET':
            handleGetRequest($shipment, $response);
            break;
            
        case 'POST':
            handlePostRequest($shipment, $input, $response);
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
    }
    
} catch(Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function handleGetRequest($shipment, &$response) {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_shipment':
            $trackingNumber = $_GET['tracking'] ?? '';
            if (empty($trackingNumber)) {
                $response['message'] = 'Tracking number required';
                return;
            }
            
            $data = $shipment->getShipment($trackingNumber);
            if ($data) {
                $response['success'] = true;
                $response['data'] = $data;
                $response['message'] = 'Shipment data retrieved';
            } else {
                $response['message'] = 'Shipment not found';
            }
            break;
            
        case 'get_coordinates':
            $location = $_GET['location'] ?? '';
            if (empty($location)) {
                $response['message'] = 'Location required';
                return;
            }
            
            $coords = $shipment->getLocationCoordinates($location);
            $response['success'] = true;
            $response['data'] = $coords;
            $response['message'] = 'Coordinates retrieved';
            break;
            
        case 'get_history':
            $trackingNumber = $_GET['tracking'] ?? '';
            if (empty($trackingNumber)) {
                $response['message'] = 'Tracking number required';
                return;
            }
            
            $history = $shipment->getShipmentHistory($trackingNumber);
            $response['success'] = true;
            $response['data'] = $history;
            $response['message'] = 'History retrieved';
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
}

function handlePostRequest($shipment, $input, &$response) {
    $action = $input['action'] ?? '';
    
    switch($action) {
        case 'update_location':
            $trackingNumber = $input['tracking_number'] ?? '';
            $locationType = $input['location_type'] ?? '';
            $location = $input['location'] ?? '';
            $description = $input['description'] ?? '';
            
            if (empty($trackingNumber) || empty($locationType) || empty($location)) {
                $response['message'] = 'Missing required fields';
                return;
            }
            
            $result = $shipment->updateLocation($trackingNumber, $locationType, $location, $description);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Location updated successfully';
                
                // Return updated shipment data
                $response['data'] = $shipment->getShipment($trackingNumber);
            } else {
                $response['message'] = 'Failed to update location';
            }
            break;
            
        case 'create_shipment':
            $trackingNumber = $input['tracking_number'] ?? '';
            $origin = $input['origin'] ?? '';
            $destination = $input['destination'] ?? '';
            $originDesc = $input['origin_description'] ?? '';
            $destDesc = $input['destination_description'] ?? '';
            
            if (empty($trackingNumber) || empty($origin) || empty($destination)) {
                $response['message'] = 'Missing required fields';
                return;
            }
            
            $result = $shipment->createShipment($trackingNumber, $origin, $destination, $originDesc, $destDesc);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Shipment created successfully';
                $response['data'] = $shipment->getShipment($trackingNumber);
            } else {
                $response['message'] = 'Failed to create shipment';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
}
?>