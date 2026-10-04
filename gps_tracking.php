<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Get all vehicles with GPS devices
$vehicles_query = "SELECT v.*, g.id as gps_id, g.device_id, g.device_name, g.status as gps_status,
                   g.last_location_lat, g.last_location_lng, g.last_update, g.battery_level
                   FROM vehicles v 
                   LEFT JOIN gps_devices g ON v.id = g.vehicle_id 
                   ORDER BY v.brand, v.model";
$vehicles = mysqli_query($conn, $vehicles_query);

// Get active alerts
$alerts_query = "SELECT a.*, v.brand, v.model 
                 FROM gps_alerts a 
                 JOIN vehicles v ON a.vehicle_id = v.id 
                 WHERE a.is_resolved = 0 
                 ORDER BY a.created_at DESC 
                 LIMIT 20";
$alerts = mysqli_query($conn, $alerts_query);

// Get tracking history for selected vehicle
$selected_vehicle = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$tracking_history = [];
if($selected_vehicle) {
    $history_query = "SELECT * FROM gps_tracking_history 
                      WHERE vehicle_id = $selected_vehicle 
                      ORDER BY recorded_at DESC 
                      LIMIT 100";
    $tracking_history = mysqli_query($conn, $history_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Vehicle Tracking - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: #1a1a2e;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar h2 { color: #FFD700; margin-bottom: 30px; font-size: 22px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        #map {
            height: 500px;
            border-radius: 10px;
            width: 100%;
        }
        
        .vehicles-list {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .vehicle-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .vehicle-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .vehicle-card.selected { border-color: #28a745; background: #d4edda; }
        .vehicle-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-online { background: #28a745; box-shadow: 0 0 5px #28a745; animation: pulse 1.5s infinite; }
        .status-offline { background: #dc3545; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        
        .alerts-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .alert-item {
            padding: 12px;
            border-left: 3px solid #ffc107;
            background: #fff3cd;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .history-table {
            background: white;
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; }
        
        .btn-add {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-refresh {
            background: #17a2b8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .vehicle-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php $current_page = "gps_tracking"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <div><h1><i class="fas fa-map-marker-alt"></i> GPS Vehicle Tracking</h1><p>Real-time vehicle location monitoring</p></div>
            <div>
                <button class="btn-refresh" onclick="refreshMap()"><i class="fas fa-sync-alt"></i> Refresh</button>
                <a href="add_gps_device.php" class="btn-add"><i class="fas fa-plus"></i> Add GPS Device</a>
            </div>
        </div>

        <div class="map-container">
            <div id="map"></div>
        </div>

        <div class="vehicles-list">
            <h3><i class="fas fa-car"></i> Tracked Vehicles</h3>
            <div class="vehicle-grid" id="vehicleGrid">
                <?php while($vehicle = mysqli_fetch_assoc($vehicles)): ?>
                <div class="vehicle-card" onclick="selectVehicle(<?php echo $vehicle['id']; ?>, <?php echo $vehicle['last_location_lat'] ?? 'null'; ?>, <?php echo $vehicle['last_location_lng'] ?? 'null'; ?>, '<?php echo $vehicle['brand'] . ' ' . $vehicle['model']; ?>')" data-vehicle-id="<?php echo $vehicle['id']; ?>">
                    <div class="vehicle-status <?php echo $vehicle['last_update'] && strtotime($vehicle['last_update']) > strtotime('-5 minutes') ? 'status-online' : 'status-offline'; ?>"></div>
                    <strong><?php echo $vehicle['brand'] . ' ' . $vehicle['model']; ?></strong>
                    <div style="font-size: 12px; color: #666;"><?php echo $vehicle['registration_number']; ?></div>
                    <?php if($vehicle['device_id']): ?>
                        <div style="font-size: 11px; color: #28a745;">Device: <?php echo $vehicle['device_id']; ?></div>
                        <div style="font-size: 11px;">Last update: <?php echo $vehicle['last_update'] ? date('H:i:s', strtotime($vehicle['last_update'])) : 'Never'; ?></div>
                        <div style="font-size: 11px;">Battery: <?php echo $vehicle['battery_level'] ?? 'N/A'; ?>%</div>
                    <?php else: ?>
                        <div style="font-size: 11px; color: #dc3545;">No GPS device assigned</div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="alerts-section">
            <h3><i class="fas fa-bell"></i> Active Alerts</h3>
            <?php if(mysqli_num_rows($alerts) > 0): ?>
                <?php while($alert = mysqli_fetch_assoc($alerts)): ?>
                <div class="alert-item">
                    <strong><i class="fas fa-exclamation-triangle"></i> <?php echo $alert['brand'] . ' ' . $alert['model']; ?></strong><br>
                    <?php echo $alert['alert_message']; ?><br>
                    <small><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></small>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No active alerts</p>
            <?php endif; ?>
        </div>

        <?php if($selected_vehicle): ?>
        <div class="history-table">
            <h3><i class="fas fa-history"></i> Tracking History</h3>
            <table>
                <thead>
                    <tr><th>Time</th><th>Location</th><th>Speed</th><th>Heading</th><th>Address</th></tr>
                </thead>
                <tbody>
                    <?php while($track = mysqli_fetch_assoc($tracking_history)): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i:s', strtotime($track['recorded_at'])); ?></td>
                        <td><?php echo $track['latitude'] . ', ' . $track['longitude']; ?></td>
                        <td><?php echo $track['speed']; ?> km/h</td>
                        <td><?php echo $track['heading']; ?>°</td>
                        <td><?php echo $track['location_address'] ?: 'N/A'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        let map;
        let markers = [];
        
        function initMap() {
            map = L.map('map').setView([-1.286389, 36.817223], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
            }).addTo(map);
        }
        
        function addVehicleMarkers() {
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            <?php 
            mysqli_data_seek($vehicles, 0);
            while($v = mysqli_fetch_assoc($vehicles)): 
                if($v['last_location_lat'] && $v['last_location_lng']):
            ?>
            var marker = L.marker([<?php echo $v['last_location_lat']; ?>, <?php echo $v['last_location_lng']; ?>])
                .addTo(map)
                .bindPopup(`<strong><?php echo $v['brand'] . ' ' . $v['model']; ?></strong><br>Reg: <?php echo $v['registration_number']; ?><br>Last seen: <?php echo $v['last_update'] ? date('H:i:s', strtotime($v['last_update'])) : 'Never'; ?><br><button onclick="selectVehicle(<?php echo $v['id']; ?>, <?php echo $v['last_location_lat']; ?>, <?php echo $v['last_location_lng']; ?>, '<?php echo $v['brand'] . ' ' . $v['model']; ?>')">View Details</button>`);
            markers.push(marker);
            <?php 
                endif;
            endwhile; 
            ?>
        }
        
        function selectVehicle(vehicleId, lat, lng, vehicleName) {
            if(lat && lng) {
                map.setView([lat, lng], 15);
                document.querySelectorAll('.vehicle-card').forEach(card => {
                    card.classList.remove('selected');
                    if(card.dataset.vehicleId == vehicleId) {
                        card.classList.add('selected');
                    }
                });
                window.history.pushState({}, '', `?vehicle_id=${vehicleId}`);
            }
        }
        
        function refreshMap() { location.reload(); }
        
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            setTimeout(addVehicleMarkers, 500);
        });
    </script>
</body>
</html>