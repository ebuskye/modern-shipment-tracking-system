<?php
require_once 'classes/Shipment.php';

// Get tracking number from URL parameter or default
$trackingNumber = $_GET['tracking'] ?? 'SH001234567890';
$shipment = new Shipment();
$shipmentData = $shipment->getShipment($trackingNumber);

// If no shipment found, use default data
if (!$shipmentData) {
    $shipmentData = [
        'tracking_number' => $trackingNumber,
        'origin_location' => 'Dubai, UAE',
        'origin_description' => 'Package dispatched from Dubai distribution center',
        'current_location' => 'Toronto, Canada',
        'current_description' => 'Package in transit through Canadian customs',
        'destination_location' => 'Los Angeles, California, USA',
        'destination_description' => 'Destination delivery address',
        'status' => 'in_transit'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Tracking Map - <?php echo htmlspecialchars($shipmentData['tracking_number']); ?></title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/all.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1a5a40 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .tracking-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .tracking-info h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .map-container {
            position: relative;
            height: 600px;
            margin: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        .route-info {
            display: flex;
            justify-content: space-around;
            padding: 30px;
            background: #f8fafc;
            margin: 0 30px 30px 30px;
            border-radius: 12px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .location-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            flex: 1;
            min-width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .location-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }
        
        .origin .icon {
            background: linear-gradient(135deg, #1a5a40, #2d5a3d);
            box-shadow: 0 0 0 10px rgba(26, 90, 64, 0.1);
        }
        
        .current .icon {
            background: linear-gradient(135deg, #fcb529, #f59e0b);
            box-shadow: 0 0 0 10px rgba(252, 181, 41, 0.1);
        }
        
        .destination .icon {
            background: linear-gradient(135deg, #059669, #10b981);
            box-shadow: 0 0 0 10px rgba(5, 150, 105, 0.1);
        }
        
        .location-card h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .location-card p {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .location-card small {
            color: #9ca3af;
            font-style: italic;
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f4f6;
            border-top: 3px solid #059669;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .custom-popup .leaflet-popup-content {
            margin: 15px;
            font-weight: 500;
        }
        
        .control-panel {
            margin: 0 30px 30px 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .control-section {
            padding: 30px;
        }
        
        .control-section h3 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .control-section > p {
            color: #6b7280;
            margin-bottom: 25px;
        }
        
        .control-options {
            display: grid;
            gap: 20px;
        }
        
        .control-option {
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #059669;
        }
        
        .control-option h4 {
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .control-option p {
            color: #6b7280;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #fcb529, #f59e0b);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(252, 181, 41, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .location-select,
        .location-input {
            width: 100%;
            max-width: 300px;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }
        
        .location-select:focus,
        .location-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .click-mode-active {
            cursor: crosshair !important;
        }
        
        .click-mode-active * {
            cursor: crosshair !important;
        }
        
        .status-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #059669;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }
        
        .status-message.error {
            background: #dc2626;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .admin-link {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #059669;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .admin-link:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .map-container {
                height: 400px;
                margin: 20px;
            }
            
            .route-info {
                flex-direction: column;
                margin: 0 20px 20px 20px;
                padding: 20px;
            }
            
            .location-card {
                min-width: auto;
            }
            
            .control-panel {
                margin: 0 20px 20px 20px;
            }
            
            .control-section {
                padding: 20px;
            }
            
            .control-options {
                grid-template-columns: 1fr;
            }
            
            .location-select,
            .location-input {
                width: 100%;
                max-width: none;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <a href="admin.php" class="admin-link">
        <i class="fas fa-cog"></i> Admin Panel
    </a>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shipping-fast"></i> Global Shipment Tracking</h1>
            <p>Real-time tracking for package delivery</p>
            <div class="tracking-info">
                <h3>Tracking: <?php echo htmlspecialchars($shipmentData['tracking_number']); ?></h3>
                <p>Status: <?php echo ucfirst(str_replace('_', ' ', $shipmentData['status'])); ?></p>
            </div>
        </div>
        
        <div class="map-container">
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <span>Loading shipment route...</span>
            </div>
            <div id="map"></div>
        </div>
        
        <div class="route-info">
            <div class="location-card origin">
                <div class="icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <h3>Origin</h3>
                <p id="origin-location-name"><?php echo htmlspecialchars($shipmentData['origin_location']); ?></p>
                <small><?php echo htmlspecialchars($shipmentData['origin_description']); ?></small>
            </div>
            
            <div class="location-card current">
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Current Location</h3>
                <p id="current-location-name"><?php echo htmlspecialchars($shipmentData['current_location']); ?></p>
                <small><?php echo htmlspecialchars($shipmentData['current_description']); ?></small>
            </div>
            
            <div class="location-card destination">
                <div class="icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <h3>Destination</h3>
                <p id="destination-location-name"><?php echo htmlspecialchars($shipmentData['destination_location']); ?></p>
                <small><?php echo htmlspecialchars($shipmentData['destination_description']); ?></small>
            </div>
        </div>
        
        <!-- Location Control Panel -->
        <div class="control-panel">
            <div class="control-section">
                <h3><i class="fas fa-cog"></i> Update Current Location</h3>
                <p>Choose how to update the current shipment location:</p>
                
                <div class="control-options">
                    <div class="control-option">
                        <h4><i class="fas fa-mouse-pointer"></i> Click on Map</h4>
                        <p>Click anywhere on the map to set the current location</p>
                        <button id="enable-click-mode" class="btn btn-primary">
                            <i class="fas fa-mouse-pointer"></i> Enable Click Mode
                        </button>
                    </div>
                    
                    <div class="control-option">
                        <h4><i class="fas fa-list"></i> Select from List</h4>
                        <select id="location-select" class="location-select">
                            <option value="">Choose a location...</option>
                            <option value="London, UK">London, UK</option>
                            <option value="Paris, France">Paris, France</option>
                            <option value="Frankfurt, Germany">Frankfurt, Germany</option>
                            <option value="Tokyo, Japan">Tokyo, Japan</option>
                            <option value="Singapore">Singapore</option>
                            <option value="Mumbai, India">Mumbai, India</option>
                            <option value="New York, USA">New York, USA</option>
                            <option value="Chicago, USA">Chicago, USA</option>
                            <option value="Vancouver, Canada">Vancouver, Canada</option>
                            <option value="Toronto, Canada">Toronto, Canada</option>
                            <option value="Sydney, Australia">Sydney, Australia</option>
                            <option value="São Paulo, Brazil">São Paulo, Brazil</option>
                        </select>
                        <button id="update-location" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Update Location
                        </button>
                    </div>
                    
                    <div class="control-option">
                        <h4><i class="fas fa-search"></i> Custom Location</h4>
                        <input type="text" id="custom-location" class="location-input" placeholder="Enter city name (e.g., Berlin, Germany)">
                        <button id="set-custom-location" class="btn btn-secondary">
                            <i class="fas fa-map-marker-alt"></i> Set Location
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <script>
        // PHP data for JavaScript
        const shipmentData = <?php echo json_encode($shipmentData); ?>;
        const trackingNumber = "<?php echo $shipmentData['tracking_number']; ?>";
        
        document.addEventListener("DOMContentLoaded", async () => {
            // Global variables
            let currentLocationMarker = null;
            let routeLine = null;
            let clickModeEnabled = false;
            let originCoords = null;
            let destinationCoords = null;
            let currentCoords = null;
            
            // Initialize the map
            const map = L.map("map", {
                zoomControl: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: true,
                keyboard: true,
            }).setView([30, 0], 2);

            // Add tile layer
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "&copy; <a href='https://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors",
                maxZoom: 19,
            }).addTo(map);

            // Custom icons with beautiful styling
            const createCustomIcon = (color, iconClass, shadowColor) => {
                return L.divIcon({
                    className: 'custom-div-icon',
                    html: `
                        <div style="
                            background: linear-gradient(135deg, ${color}, ${color}dd); 
                            color: white; 
                            width: 40px; 
                            height: 40px; 
                            border-radius: 50%; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            box-shadow: 0 0 0 8px ${shadowColor}, 0 4px 15px rgba(0,0,0,0.2);
                            border: 3px solid white;
                            animation: pulse 2s infinite;
                        ">
                            <i class="${iconClass}" style="font-size: 18px;"></i>
                        </div>
                        <style>
                            @keyframes pulse {
                                0% { box-shadow: 0 0 0 8px ${shadowColor}, 0 4px 15px rgba(0,0,0,0.2); }
                                50% { box-shadow: 0 0 0 12px ${shadowColor}88, 0 4px 15px rgba(0,0,0,0.2); }
                                100% { box-shadow: 0 0 0 8px ${shadowColor}, 0 4px 15px rgba(0,0,0,0.2); }
                            }
                        </style>
                    `,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });
            };

            const originIcon = createCustomIcon('#1a5a40', 'fas fa-warehouse', 'rgba(26, 90, 64, 0.3)');
            const currentLocationIcon = createCustomIcon('#fcb529', 'fas fa-truck', 'rgba(252, 181, 41, 0.3)');
            const destinationIcon = createCustomIcon('#059669', 'fas fa-flag-checkered', 'rgba(5, 150, 105, 0.3)');

            // Utility functions
            const showStatusMessage = (message, isError = false) => {
                const statusDiv = document.createElement('div');
                statusDiv.className = `status-message ${isError ? 'error' : ''}`;
                statusDiv.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${message}`;
                document.body.appendChild(statusDiv);
                
                setTimeout(() => {
                    if (statusDiv.parentNode) {
                        statusDiv.parentNode.removeChild(statusDiv);
                    }
                }, 3000);
            };
            
            const getLocationCoordinates = async (locationName) => {
                try {
                    const response = await fetch(`api/handler.php?action=get_coordinates&location=${encodeURIComponent(locationName)}`);
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        return [data.data.lat, data.data.lng];
                    }
                    return null;
                } catch (error) {
                    console.error("Failed to get coordinates:", error);
                    return null;
                }
            };
            
            const reverseGeocode = async (lat, lng) => {
                try {
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`
                    );
                    const data = await response.json();
                    
                    if (data && data.display_name) {
                        const parts = data.display_name.split(',');
                        if (parts.length >= 2) {
                            return `${parts[0].trim()}, ${parts[parts.length - 1].trim()}`;
                        }
                        return data.display_name;
                    }
                    return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                } catch (error) {
                    console.error("Reverse geocoding failed:", error);
                    return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
            };
            
            const updateRoute = () => {
                if (routeLine) {
                    map.removeLayer(routeLine);
                }
                
                if (originCoords && currentCoords && destinationCoords) {
                    const routePoints = [originCoords, currentCoords, destinationCoords];
                    routeLine = L.polyline(routePoints, {
                        color: "#fcb529",
                        weight: 4,
                        opacity: 0.8,
                        smoothFactor: 1,
                        dashArray: "10, 5"
                    }).addTo(map);
                }
            };
            
            const updateCurrentLocationInDatabase = async (coords, locationName, description = '') => {
                try {
                    const response = await fetch('api/handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_location',
                            tracking_number: trackingNumber,
                            location_type: 'current',
                            location: locationName,
                            description: description || `Package in transit - ${locationName}`
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Update the UI with the new data
                        if (data.data) {
                            document.getElementById('current-location-name').textContent = data.data.current_location;
                        }
                        return true;
                    } else {
                        console.error('Failed to update database:', data.message);
                        return false;
                    }
                } catch (error) {
                    console.error('Database update error:', error);
                    return false;
                }
            };
            
            const updateCurrentLocation = async (coords, locationName = null) => {
                if (currentLocationMarker) {
                    map.removeLayer(currentLocationMarker);
                }
                
                if (!locationName) {
                    locationName = await reverseGeocode(coords[0], coords[1]);
                }
                
                currentCoords = coords;
                
                const popupContent = `
                    <div style="text-align: center; min-width: 200px;">
                        <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 16px;">
                            Current Location
                        </h3>
                        <p style="margin: 0 0 8px 0; color: #4b5563; font-weight: 500;">
                            ${locationName}
                        </p>
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                            Package in transit
                        </p>
                    </div>
                `;
                
                currentLocationMarker = L.marker(coords, { icon: currentLocationIcon })
                    .addTo(map)
                    .bindPopup(popupContent, {
                        className: 'custom-popup',
                        maxWidth: 250,
                        closeButton: true
                    });
                
                // Update database
                const dbUpdate = await updateCurrentLocationInDatabase(coords, locationName);
                
                updateRoute();
                
                if (dbUpdate) {
                    showStatusMessage(`Current location updated to: ${locationName}`);
                } else {
                    showStatusMessage(`Location updated on map only (database update failed)`, true);
                }
            };

            // Initialize locations from database
            const locations = [
                { 
                    name: shipmentData.origin_location, 
                    label: "Origin Warehouse", 
                    type: "origin", 
                    icon: originIcon,
                    description: shipmentData.origin_description
                },
                { 
                    name: shipmentData.current_location, 
                    label: "Current Location", 
                    type: "current", 
                    icon: currentLocationIcon,
                    description: shipmentData.current_description
                },
                { 
                    name: shipmentData.destination_location, 
                    label: "Final Destination", 
                    type: "destination", 
                    icon: destinationIcon,
                    description: shipmentData.destination_description
                },
            ];

            const bounds = [];
            let loadedLocations = 0;

            // Geocode and add markers
            for (const loc of locations) {
                try {
                    const coords = await getLocationCoordinates(loc.name);

                    if (coords) {
                        bounds.push(coords);
                        
                        if (loc.type === 'origin') {
                            originCoords = coords;
                        } else if (loc.type === 'destination') {
                            destinationCoords = coords;
                        }

                        const popupContent = `
                            <div style="text-align: center; min-width: 200px;">
                                <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 16px;">
                                    ${loc.label}
                                </h3>
                                <p style="margin: 0 0 8px 0; color: #4b5563; font-weight: 500;">
                                    ${loc.name}
                                </p>
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                    ${loc.description}
                                </p>
                            </div>
                        `;

                        if (loc.type !== 'current') {
                            L.marker(coords, { icon: loc.icon })
                                .addTo(map)
                                .bindPopup(popupContent, {
                                    className: 'custom-popup',
                                    maxWidth: 250,
                                    closeButton: true
                                });
                        } else {
                            currentCoords = coords;
                            currentLocationMarker = L.marker(coords, { icon: loc.icon })
                                .addTo(map)
                                .bindPopup(popupContent, {
                                    className: 'custom-popup',
                                    maxWidth: 250,
                                    closeButton: true
                                });
                        }

                        loadedLocations++;
                        
                        document.getElementById('loading').innerHTML = `
                            <div class="spinner"></div>
                            <span>Loading location ${loadedLocations} of ${locations.length}...</span>
                        `;

                    } else {
                        console.warn("Could not geocode:", loc.name);
                    }
                } catch (error) {
                    console.error("Geocoding failed for:", loc.name, error);
                }
            }

            updateRoute();

            if (bounds.length) {
                map.fitBounds(bounds, { 
                    padding: [50, 50],
                    maxZoom: 10
                });
            } else {
                map.setView([30, 0], 2);
            }

            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
            }, 2000);

            setTimeout(() => {
                map.invalidateSize();
            }, 300);

            L.control.scale({
                position: 'bottomleft',
                metric: true,
                imperial: true
            }).addTo(map);

            map.attributionControl.addAttribution('Shipment Tracking System');
            
            // Event handlers for location controls
            document.getElementById('enable-click-mode').addEventListener('click', () => {
                clickModeEnabled = !clickModeEnabled;
                const button = document.getElementById('enable-click-mode');
                
                if (clickModeEnabled) {
                    button.innerHTML = '<i class="fas fa-times"></i> Disable Click Mode';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                    document.body.classList.add('click-mode-active');
                    showStatusMessage('Click anywhere on the map to set the current location');
                } else {
                    button.innerHTML = '<i class="fas fa-mouse-pointer"></i> Enable Click Mode';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                    document.body.classList.remove('click-mode-active');
                    showStatusMessage('Click mode disabled');
                }
            });
            
            map.on('click', async (e) => {
                if (clickModeEnabled) {
                    const { lat, lng } = e.latlng;
                    await updateCurrentLocation([lat, lng]);
                    
                    clickModeEnabled = false;
                    const button = document.getElementById('enable-click-mode');
                    button.innerHTML = '<i class="fas fa-mouse-pointer"></i> Enable Click Mode';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                    document.body.classList.remove('click-mode-active');
                }
            });
            
            document.getElementById('update-location').addEventListener('click', async () => {
                const select = document.getElementById('location-select');
                const locationName = select.value;
                
                if (!locationName) {
                    showStatusMessage('Please select a location first', true);
                    return;
                }
                
                const button = document.getElementById('update-location');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                
                const coords = await getLocationCoordinates(locationName);
                
                if (coords) {
                    await updateCurrentLocation(coords, locationName);
                    select.value = '';
                } else {
                    showStatusMessage('Could not find the selected location', true);
                }
                
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-sync"></i> Update Location';
            });
            
            document.getElementById('set-custom-location').addEventListener('click', async () => {
                const input = document.getElementById('custom-location');
                const locationName = input.value.trim();
                
                if (!locationName) {
                    showStatusMessage('Please enter a location name', true);
                    return;
                }
                
                const button = document.getElementById('set-custom-location');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting...';
                
                const coords = await getLocationCoordinates(locationName);
                
                if (coords) {
                    await updateCurrentLocation(coords, locationName);
                    input.value = '';
                } else {
                    showStatusMessage('Could not find the specified location', true);
                }
                
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-map-marker-alt"></i> Set Location';
            });
            
            document.getElementById('custom-location').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    document.getElementById('set-custom-location').click();
                }
            });
        });
    </script>
</body>
</html>