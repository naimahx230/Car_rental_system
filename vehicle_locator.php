<?php
session_start();
require_once 'config/database.php';

// Get available vehicles with locations
$vehicles_query = "SELECT v.*,
                   (6371 * acos(
                       cos(radians(-1.286389))
                       * cos(radians(IFNULL(v.latitude, -1.286389)))
                       * cos(radians(IFNULL(v.longitude, 36.817223)) - radians(36.817223))
                       + sin(radians(-1.286389))
                       * sin(radians(IFNULL(v.latitude, -1.286389)))
                   )) AS distance
                   FROM vehicles v
                   WHERE v.status = 'available'
                   AND v.latitude IS NOT NULL
                   ORDER BY distance ASC";
$vehicles = mysqli_query($conn, $vehicles_query);

// Get all vehicles for map
$all_vehicles = mysqli_query($conn, "SELECT id, brand, model, latitude, longitude, daily_rate, registration_number, image FROM vehicles WHERE status = 'available' AND latitude IS NOT NULL");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Vehicles Near You - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: #1a1a2e;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #FFD700;
        }

        .login-btn {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a2e !important;
            padding: 8px 25px;
            border-radius: 25px;
        }

        .hero {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 60px 5%;
            text-align: center;
        }

        .hero h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Map Container */
        .map-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        #map {
            height: 500px;
            border-radius: 15px;
            width: 100%;
        }

        /* Location Controls */
        .location-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-location {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-location:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .radius-select {
            padding: 12px 20px;
            border: 2px solid #667eea;
            border-radius: 30px;
            font-family: inherit;
            cursor: pointer;
        }

        /* Vehicle List */
        .vehicles-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .vehicles-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .vehicle-count {
            color: #666;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 15px;
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .toggle-btn.active {
            background: #667eea;
            color: white;
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .vehicle-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: 1px solid #eee;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .vehicle-image {
            height: 180px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .vehicle-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .distance-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .vehicle-info {
            padding: 15px;
        }

        .vehicle-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .vehicle-location {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vehicle-specs {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            font-size: 12px;
            color: #666;
        }

        .price {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
            margin: 10px 0;
        }

        .price span {
            font-size: 12px;
            color: #666;
        }

        .btn-book {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-book:hover {
            transform: translateY(-2px);
        }

        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 28px;
            }
            .vehicles-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="vehicle_locator.php" style="color: #FFD700;">Find Near Me</a>
        <a href="services.php">Services</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="my_bookings.php">My Bookings</a>
            <span style="color: white;">Hi, <?php echo $_SESSION['user_name']; ?></span>
            <a href="logout.php" style="background: #dc3545; padding: 8px 20px; border-radius: 25px;">Logout</a>
        <?php else: ?>
            <a href="index.php" class="login-btn">Sign Up / Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="hero">
    <h1>Find Vehicles Near You</h1>
    <p>Locate available rental cars in your area</p>
</div>

<div class="container">
    <!-- Map Container -->
    <div class="map-container">
        <div class="location-controls">
            <button class="btn-location" onclick="getUserLocation()">
                <i class="fas fa-location-dot"></i> Use My Location
            </button>
            <select id="radiusSelect" class="radius-select" onchange="filterByRadius()">
                <option value="2">Within 2 km</option>
                <option value="5" selected>Within 5 km</option>
                <option value="10">Within 10 km</option>
                <option value="20">Within 20 km</option>
                <option value="50">Within 50 km</option>
            </select>
        </div>
        <div id="map"></div>
    </div>

    <!-- Vehicles Section -->
    <div class="vehicles-section">
        <div class="vehicles-header">
            <div>
                <h2><i class="fas fa-car"></i> Available Vehicles Near You</h2>
                <p class="vehicle-count" id="vehicleCount">Loading vehicles...</p>
            </div>
            <div class="view-toggle">
                <button class="toggle-btn active" onclick="toggleView('grid')"><i class="fas fa-th-large"></i> Grid</button>
                <button class="toggle-btn" onclick="toggleView('list')"><i class="fas fa-list"></i> List</button>
            </div>
        </div>
        <div id="vehiclesContainer" class="vehicles-grid">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading vehicles near you...</p>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

<script>
    let map;
    let markers = [];
    let userMarker = null;
    let userLocation = null;
    let allVehicles = [];
    let currentView = 'grid';

    // Vehicle data from PHP
    const vehiclesData = <?php 
        $vehicles_array = [];
        while($v = mysqli_fetch_assoc($all_vehicles)) {
            $vehicles_array[] = [
                'id' => $v['id'],
                'brand' => $v['brand'],
                'model' => $v['model'],
                'lat' => floatval($v['latitude']),
                'lng' => floatval($v['longitude']),
                'price' => floatval($v['daily_rate']),
                'reg' => $v['registration_number'],
                'image' => $v['image']
            ];
        }
        echo json_encode($vehicles_array);
    ?>;

    // Initialize map
    function initMap() {
        // Default center (Nairobi)
        map = L.map('map').setView([-1.286389, 36.817223], 12);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
        }).addTo(map);
        
        // Add vehicle markers
        addVehicleMarkers();
        
        // Try to get user location
        getUserLocation();
    }
    
    // Get user's current location
    function getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Add user marker
                    if (userMarker) {
                        map.removeLayer(userMarker);
                    }
                    
                    userMarker = L.marker([userLocation.lat, userLocation.lng], {
                        icon: L.divIcon({
                            html: '<div style="background: #28a745; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>',
                            iconSize: [20, 20],
                            className: 'user-marker'
                        })
                    }).addTo(map)
                    .bindPopup('<strong>You are here</strong>').openPopup();
                    
                    // Center map on user location
                    map.setView([userLocation.lat, userLocation.lng], 13);
                    
                    // Filter vehicles by radius
                    filterByRadius();
                },
                function(error) {
                    console.log("Error getting location:", error);
                    showNotification("Unable to get your location. Showing all available vehicles.", "warning");
                    displayVehicles(vehiclesData);
                }
            );
        } else {
            showNotification("Geolocation is not supported by this browser.", "warning");
            displayVehicles(vehiclesData);
        }
    }
    
    // Add vehicle markers to map
    function addVehicleMarkers() {
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];
        
        vehiclesData.forEach(vehicle => {
            if (vehicle.lat && vehicle.lng) {
                const popupContent = `
                    <div style="min-width: 200px;">
                        <strong>${vehicle.brand} ${vehicle.model}</strong><br>
                        <small>${vehicle.reg}</small><br>
                        <strong style="color: #667eea;">KES ${vehicle.price.toLocaleString()}/day</strong><br>
                        <a href="book.php?id=${vehicle.id}" style="display: inline-block; margin-top: 8px; padding: 5px 15px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Book Now</a>
                    </div>
                `;
                
                const marker = L.marker([vehicle.lat, vehicle.lng])
                    .bindPopup(popupContent)
                    .addTo(map);
                
                markers.push(marker);
            }
        });
    }
    
    // Calculate distance between two points (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Filter vehicles by radius
    function filterByRadius() {
        if (!userLocation) {
            displayVehicles(vehiclesData);
            return;
        }
        
        const radius = parseInt(document.getElementById('radiusSelect').value);
        const filteredVehicles = vehiclesData.filter(vehicle => {
            if (!vehicle.lat || !vehicle.lng) return false;
            const distance = calculateDistance(userLocation.lat, userLocation.lng, vehicle.lat, vehicle.lng);
            vehicle.distance = distance;
            return distance <= radius;
        });
        
        // Sort by distance
        filteredVehicles.sort((a, b) => a.distance - b.distance);
        
        displayVehicles(filteredVehicles);
        updateVehicleCount(filteredVehicles.length);
    }
    
    // Display vehicles in the container
    function displayVehicles(vehicles) {
        const container = document.getElementById('vehiclesContainer');
        
        if (vehicles.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px; grid-column: 1/-1;">
                    <i class="fas fa-car-side" style="font-size: 60px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No vehicles found nearby</h3>
                    <p>Try increasing the search radius or check back later.</p>
                </div>
            `;
            return;
        }
        
        if (currentView === 'grid') {
            container.className = 'vehicles-grid';
        } else {
            container.className = 'vehicles-list';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '15px';
        }
        
        container.innerHTML = vehicles.map(vehicle => {
            const distance = vehicle.distance ? vehicle.distance.toFixed(1) : '?';
            const imageUrl = vehicle.image && vehicle.image !== '' ? vehicle.image : 'assets/images/default-car.jpg';
            
            if (currentView === 'grid') {
                return `
                    <div class="vehicle-card">
                        <div class="vehicle-image">
                            <img src="${imageUrl}" alt="${vehicle.brand} ${vehicle.model}" onerror="this.src='assets/images/default-car.jpg'">
                            <div class="distance-badge"><i class="fas fa-location-dot"></i> ${distance} km away</div>
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">${vehicle.brand} ${vehicle.model}</h3>
                            <div class="vehicle-location">
                                <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> Available near you
                            </div>
                            <div class="vehicle-specs">
                                <span><i class="fas fa-id-card"></i> ${vehicle.reg}</span>
                            </div>
                            <div class="price">
                                KES ${vehicle.price.toLocaleString()} <span>/ day</span>
                            </div>
                            <button class="btn-book" onclick="bookVehicle(${vehicle.id})">Book Now →</button>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="vehicle-card" style="display: flex; align-items: center;">
                        <div class="vehicle-image" style="width: 120px; height: 120px; margin: 15px;">
                            <img src="${imageUrl}" alt="${vehicle.brand} ${vehicle.model}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='assets/images/default-car.jpg'">
                        </div>
                        <div style="flex: 1; padding: 15px;">
                            <h3 class="vehicle-title">${vehicle.brand} ${vehicle.model}</h3>
                            <div class="vehicle-specs">
                                <span><i class="fas fa-id-card"></i> ${vehicle.reg}</span>
                                <span><i class="fas fa-location-dot"></i> ${distance} km away</span>
                            </div>
                            <div class="price">
                                KES ${vehicle.price.toLocaleString()} <span>/ day</span>
                            </div>
                        </div>
                        <div style="padding: 15px;">
                            <button class="btn-book" onclick="bookVehicle(${vehicle.id})" style="width: auto; padding: 10px 25px;">Book Now →</button>
                        </div>
                    </div>
                `;
            }
        }).join('');
        
        if (currentView === 'list') {
            container.style.display = 'flex';
        } else {
            container.style.display = 'grid';
        }
    }
    
    function updateVehicleCount(count) {
        const countEl = document.getElementById('vehicleCount');
        if (countEl) {
            countEl.innerHTML = `${count} vehicle${count !== 1 ? 's' : ''} found near you`;
        }
    }
    
    function toggleView(view) {
        currentView = view;
        const btns = document.querySelectorAll('.toggle-btn');
        btns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Re-display vehicles with new view
        if (typeof filteredVehicles !== 'undefined') {
            displayVehicles(filteredVehicles);
        } else {
            filterByRadius();
        }
    }
    
    function bookVehicle(vehicleId) {
        <?php if(isset($_SESSION['user_id'])): ?>
            window.location.href = 'book.php?id=' + vehicleId;
        <?php else: ?>
            if(confirm('Please login to book a vehicle. Go to login page?')) {
                window.location.href = 'index.php';
            }
        <?php endif; ?>
    }
    
    function showNotification(message, type) {
        // Simple alert for now
        console.log(message);
    }
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        // Initial display of vehicles
        setTimeout(() => {
            if (userLocation) {
                filterByRadius();
            } else {
                displayVehicles(vehiclesData);
                updateVehicleCount(vehiclesData.length);
            }
        }, 1000);
    });
</script>
</body>
</html>
