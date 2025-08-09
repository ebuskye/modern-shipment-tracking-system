<?php
require_once __DIR__ . '/../config/database.php';

class Shipment {
    private $conn;

    public function __construct() {
        $this->conn = getDbConnection();
    }

    /**
     * Get shipment data by tracking number
     */
    public function getShipment($trackingNumber) {
        try {
            $query = "SELECT * FROM shipments WHERE tracking_number = :tracking_number";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tracking_number', $trackingNumber);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all shipments
     */
    public function getAllShipments() {
        try {
            $query = "SELECT * FROM shipments ORDER BY updated_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update shipment location
     */
    public function updateLocation($trackingNumber, $locationType, $location, $description = '') {
        try {
            // Determine which field to update based on location type
            $allowedTypes = ['origin', 'current', 'destination'];
            if (!in_array($locationType, $allowedTypes)) {
                throw new Exception("Invalid location type");
            }

            $locationField = $locationType . '_location';
            $descriptionField = $locationType . '_description';

            $query = "UPDATE shipments SET 
                        {$locationField} = :location, 
                        {$descriptionField} = :description,
                        updated_at = CURRENT_TIMESTAMP 
                      WHERE tracking_number = :tracking_number";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':tracking_number', $trackingNumber);
            
            $result = $stmt->execute();

            if ($result) {
                // Add to history
                $this->addToHistory($trackingNumber, $location, $description, $locationType);
                return true;
            }
            return false;
        } catch(Exception $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add shipment update to history
     */
    private function addToHistory($trackingNumber, $location, $description, $status) {
        try {
            // Get shipment ID
            $shipment = $this->getShipment($trackingNumber);
            if (!$shipment) return false;

            // Get coordinates for the location
            $coords = $this->getLocationCoordinates($location);

            $query = "INSERT INTO shipment_history 
                      (shipment_id, location, description, status, latitude, longitude) 
                      VALUES (:shipment_id, :location, :description, :status, :latitude, :longitude)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':shipment_id', $shipment['id']);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':latitude', $coords['lat']);
            $stmt->bindParam(':longitude', $coords['lng']);
            
            return $stmt->execute();
        } catch(Exception $e) {
            error_log("History Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get coordinates for a location (with caching)
     */
    public function getLocationCoordinates($location) {
        try {
            // Check cache first
            $query = "SELECT latitude, longitude FROM location_cache WHERE location_name = :location";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $location);
            $stmt->execute();
            
            $cached = $stmt->fetch();
            if ($cached) {
                return [
                    'lat' => floatval($cached['latitude']),
                    'lng' => floatval($cached['longitude'])
                ];
            }

            // If not cached, geocode it
            $coords = $this->geocodeLocation($location);
            if ($coords) {
                // Cache the result
                $this->cacheLocationCoordinates($location, $coords['lat'], $coords['lng']);
                return $coords;
            }

            // Default coordinates if geocoding fails
            return ['lat' => 0, 'lng' => 0];
        } catch(Exception $e) {
            error_log("Coordinates Error: " . $e->getMessage());
            return ['lat' => 0, 'lng' => 0];
        }
    }

    /**
     * Geocode location using OpenStreetMap Nominatim
     */
    private function geocodeLocation($location) {
        try {
            $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($location) . "&format=json&limit=1";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Shipment Tracking System'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => floatval($data[0]['lat']),
                    'lng' => floatval($data[0]['lon'])
                ];
            }
            return null;
        } catch(Exception $e) {
            error_log("Geocoding Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cache location coordinates
     */
    private function cacheLocationCoordinates($location, $lat, $lng) {
        try {
            $query = "INSERT INTO location_cache (location_name, latitude, longitude) 
                      VALUES (:location, :lat, :lng) 
                      ON DUPLICATE KEY UPDATE 
                      latitude = :lat2, longitude = :lng2";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->bindParam(':lat2', $lat);
            $stmt->bindParam(':lng2', $lng);
            
            return $stmt->execute();
        } catch(Exception $e) {
            error_log("Cache Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get shipment history
     */
    public function getShipmentHistory($trackingNumber) {
        try {
            $query = "SELECT sh.* FROM shipment_history sh 
                      JOIN shipments s ON sh.shipment_id = s.id 
                      WHERE s.tracking_number = :tracking_number 
                      ORDER BY sh.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tracking_number', $trackingNumber);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(Exception $e) {
            error_log("History Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new shipment
     */
    public function createShipment($trackingNumber, $origin, $destination, $originDesc = '', $destDesc = '') {
        try {
            $query = "INSERT INTO shipments 
                      (tracking_number, origin_location, origin_description, current_location, 
                       current_description, destination_location, destination_description, status) 
                      VALUES (:tracking, :origin, :origin_desc, :origin, :origin_desc, :destination, :dest_desc, 'dispatched')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tracking', $trackingNumber);
            $stmt->bindParam(':origin', $origin);
            $stmt->bindParam(':origin_desc', $originDesc);
            $stmt->bindParam(':destination', $destination);
            $stmt->bindParam(':dest_desc', $destDesc);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Add initial history entry
                $this->addToHistory($trackingNumber, $origin, $originDesc, 'dispatched');
                return true;
            }
            return false;
        } catch(Exception $e) {
            error_log("Create Error: " . $e->getMessage());
            return false;
        }
    }
}
?>