<?php
require_once 'classes/Shipment.php';

$shipment = new Shipment();
$allShipments = $shipment->getAllShipments();
$message = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update_location':
            $trackingNumber = $_POST['tracking_number'] ?? '';
            $locationType = $_POST['location_type'] ?? '';
            $location = $_POST['location'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($shipment->updateLocation($trackingNumber, $locationType, $location, $description)) {
                $message = "Location updated successfully!";
            } else {
                $message = "Failed to update location.";
            }
            break;
            
        case 'create_shipment':
            $trackingNumber = $_POST['tracking_number'] ?? '';
            $origin = $_POST['origin'] ?? '';
            $destination = $_POST['destination'] ?? '';
            $originDesc = $_POST['origin_description'] ?? '';
            $destDesc = $_POST['destination_description'] ?? '';
            
            if ($shipment->createShipment($trackingNumber, $origin, $destination, $originDesc, $destDesc)) {
                $message = "Shipment created successfully!";
            } else {
                $message = "Failed to create shipment.";
            }
            break;
    }
    
    // Refresh shipments list
    $allShipments = $shipment->getAllShipments();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Admin Panel</title>
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
            max-width: 1400px;
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
        
        .admin-content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 40px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 30px;
        }
        
        .section h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
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
        
        .shipments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .shipments-table th,
        .shipments-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .shipments-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .shipments-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-dispatched {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-in_transit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-delayed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .message {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .view-link {
            color: #059669;
            text-decoration: none;
            font-weight: 600;
        }
        
        .view-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .shipments-table {
                font-size: 14px;
            }
            
            .shipments-table th,
            .shipments-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Shipment Admin Panel</h1>
            <p>Manage shipments, locations, and tracking information</p>
        </div>
        
        <div class="admin-content">
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Create New Shipment Section -->
            <div class="section">
                <h2><i class="fas fa-plus-circle"></i> Create New Shipment</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_shipment">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tracking_number">Tracking Number</label>
                            <input type="text" id="tracking_number" name="tracking_number" required 
                                   placeholder="e.g., SH001234567890">
                        </div>
                        <div class="form-group">
                            <label for="origin">Origin Location</label>
                            <input type="text" id="origin" name="origin" required 
                                   placeholder="e.g., Dubai, UAE">
                        </div>
                        <div class="form-group">
                            <label for="destination">Destination Location</label>
                            <input type="text" id="destination" name="destination" required 
                                   placeholder="e.g., Los Angeles, California, USA">
                        </div>
                        <div class="form-group">
                            <label for="origin_description">Origin Description</label>
                            <textarea id="origin_description" name="origin_description" rows="2"
                                      placeholder="Package dispatched from warehouse"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="destination_description">Destination Description</label>
                            <textarea id="destination_description" name="destination_description" rows="2"
                                      placeholder="Final delivery address"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Shipment
                    </button>
                </form>
            </div>
            
            <!-- Update Location Section -->
            <div class="section">
                <h2><i class="fas fa-edit"></i> Update Shipment Location</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_location">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="update_tracking">Tracking Number</label>
                            <select id="update_tracking" name="tracking_number" required>
                                <option value="">Select a shipment...</option>
                                <?php foreach ($allShipments as $ship): ?>
                                    <option value="<?php echo htmlspecialchars($ship['tracking_number']); ?>">
                                        <?php echo htmlspecialchars($ship['tracking_number']); ?> - 
                                        <?php echo htmlspecialchars($ship['current_location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location_type">Location Type</label>
                            <select id="location_type" name="location_type" required>
                                <option value="">Select location type...</option>
                                <option value="origin">Origin</option>
                                <option value="current">Current</option>
                                <option value="destination">Destination</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_location">New Location</label>
                            <input type="text" id="update_location" name="location" required 
                                   placeholder="e.g., Toronto, Canada">
                        </div>
                        <div class="form-group">
                            <label for="update_description">Description</label>
                            <textarea id="update_description" name="description" rows="2"
                                      placeholder="Package status update"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Update Location
                    </button>
                </form>
            </div>
            
            <!-- All Shipments Section -->
            <div class="section">
                <h2><i class="fas fa-list"></i> All Shipments</h2>
                <?php if (empty($allShipments)): ?>
                    <p>No shipments found. Create a new shipment to get started.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="shipments-table">
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Origin</th>
                                    <th>Current Location</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allShipments as $ship): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ship['tracking_number']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($ship['origin_location']); ?></td>
                                        <td><?php echo htmlspecialchars($ship['current_location']); ?></td>
                                        <td><?php echo htmlspecialchars($ship['destination_location']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $ship['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ship['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($ship['updated_at'])); ?></td>
                                        <td>
                                            <a href="index.php?tracking=<?php echo urlencode($ship['tracking_number']); ?>" 
                                               class="view-link" target="_blank">
                                                <i class="fas fa-eye"></i> View Map
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>