<!-- Floating Find Near Me Button -->
<style>
    .floating-locator {
        position: fixed;
        bottom: 100px;
        right: 20px;
        z-index: 999;
        cursor: pointer;
    }

    .locator-btn {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        position: relative;
        animation: pulse 2s infinite;
    }

    .locator-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 10px 25px rgba(255,215,0,0.4);
    }

    .locator-btn i {
        font-size: 28px;
        color: #1a1a2e;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.7);
        }
        70% {
            box-shadow: 0 0 0 15px rgba(255, 215, 0, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
        }
    }

    /* Tooltip */
    .locator-tooltip {
        position: absolute;
        right: 70px;
        top: 50%;
        transform: translateY(-50%);
        background: #1a1a2e;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }

    .locator-btn:hover .locator-tooltip {
        opacity: 1;
        visibility: visible;
        right: 75px;
    }

    /* Modal */
    .locator-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 10000;
        justify-content: center;
        align-items: center;
    }

    .locator-modal.active {
        display: flex;
    }

    .locator-modal-content {
        background: white;
        width: 90%;
        max-width: 1000px;
        height: 85vh;
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .locator-modal-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .locator-modal-header h3 {
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .close-locator {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .close-locator:hover {
        transform: rotate(90deg);
    }

    .locator-modal-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    .locator-controls {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .locate-me-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .locate-me-btn:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .radius-select {
        padding: 10px 15px;
        border: 2px solid #667eea;
        border-radius: 25px;
        font-family: inherit;
        cursor: pointer;
        background: white;
    }

    .locator-map {
        height: 350px;
        border-radius: 15px;
        margin-bottom: 20px;
        background: #f0f0f0;
    }

    .locator-vehicles {
        max-height: 300px;
        overflow-y: auto;
    }

    .locator-vehicle-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
        border: 1px solid #e9ecef;
    }

    .locator-vehicle-card:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }

    .locator-vehicle-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .locator-vehicle-icon i {
        font-size: 24px;
        color: white;
    }

    .locator-vehicle-info {
        flex: 1;
    }

    .locator-vehicle-title {
        font-weight: 600;
        font-size: 16px;
        color: #333;
    }

    .locator-vehicle-distance {
        font-size: 12px;
        color: #28a745;
        margin-top: 3px;
    }

    .locator-vehicle-price {
        font-weight: 700;
        color: #667eea;
        font-size: 14px;
        margin-top: 3px;
    }

    .locator-book-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
        font-weight: 500;
    }

    .locator-book-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }

    .locator-loading {
        text-align: center;
        padding: 40px;
    }

    .locator-spinner {
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

    .no-vehicles-message {
        text-align: center;
        padding: 40px;
    }

    .no-vehicles-message i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .floating-locator {
            bottom: 80px;
            right: 15px;
        }
        .locator-btn {
            width: 50px;
            height: 50px;
        }
        .locator-btn i {
            font-size: 22px;
        }
        .locator-modal-content {
            width: 95%;
            height: 90vh;
        }
        .locator-vehicle-card {
            flex-wrap: wrap;
        }
        .locator-book-btn {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>

<div class="floating-locator">
    <div class="locator-btn" onclick="openLocatorModal()">
        <i class="fas fa-map-marker-alt"></i>
        <span class="locator-tooltip">Find cars near you</span>
    </div>
</div>

<div id="locatorModal" class="locator-modal">
    <div class="locator-modal-content">
        <div class="locator-modal-header">
            <h3><i class="fas fa-map-marker-alt"></i> Find Vehicles Near You</h3>
            <button class="close-locator" onclick="closeLocatorModal()">&times;</button>
        </div>
        <div class="locator-modal-body">
            <div class="locator-controls">
                <button class="locate-me-btn" onclick="getUserLocationForLocator()">
                    <i class="fas fa-location-dot"></i> Use My Current Location
                </button>
                <select id="locatorRadius" class="radius-select" onchange="filterVehiclesByRadius()">
                    <option value="2">Within 2 km</option>
                    <option value="5" selected>Within 5 km</option>
                    <option value="10">Within 10 km</option>
                    <option value="20">Within 20 km</option>
                    <option value="50">Within 50 km</option>
                </select>
            </div>
            <div id="locatorMap" class="locator-map"></div>
            <div id="locatorVehiclesList" class="locator-vehicles">
                <div class="locator-loading">
                    <div class="locator-spinner"></div>
                    <p>Loading vehicles near you...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
    let locatorMap = null;
    let locatorMarkers = [];
    let locatorUserMarker = null;
    let locatorUserLocation = null;
    let locatorVehicles = [];
    let locatorFilteredVehicles = [];

    // Vehicle data from PHP
    const locatorVehiclesData = <?php 
    global $conn;
    $vehicles_array = [];
    $vehicles_query = mysqli_query($conn, "SELECT id, brand, model, daily_rate, registration_number, latitude, longitude, image FROM vehicles WHERE status = 'available'");
    while($v = mysqli_fetch_assoc($vehicles_query)) {
        if($v['latitude'] && $v['longitude']) {
            $vehicles_array[] = [
                'id' => $v['id'],
                'brand' => $v['brand'],
                'model' => $v['model'],
                'price' => floatval($v['daily_rate']),
                'reg' => $v['registration_number'],
                'lat' => floatval($v['latitude']),
                'lng' => floatval($v['longitude'])
            ];
        }
    }
    echo json_encode($vehicles_array);
    ?>;

    function openLocatorModal() {
        document.getElementById('locatorModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        
        if (!locatorMap) {
            initLocatorMap();
        } else {
            setTimeout(() => {
                locatorMap.invalidateSize();
            }, 100);
        }
        
        // Try to get user location
        getUserLocationForLocator();
    }

    function closeLocatorModal() {
        document.getElementById('locatorModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function initLocatorMap() {
        locatorMap = L.map('locatorMap').setView([-1.286389, 36.817223], 12);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
        }).addTo(locatorMap);
        
        addLocatorMarkers(locatorVehiclesData);
    }

    function addLocatorMarkers(vehicles) {
        if (locatorMarkers.length) {
            locatorMarkers.forEach(marker => locatorMap.removeLayer(marker));
            locatorMarkers = [];
        }
        
        vehicles.forEach(vehicle => {
            if (vehicle.lat && vehicle.lng) {
                const popupContent = `
                    <div style="min-width: 180px; text-align: center;">
                        <strong style="font-size: 14px;">${vehicle.brand} ${vehicle.model}</strong><br>
                        <small style="color: #666;">${vehicle.reg}</small><br>
                        <strong style="color: #667eea; font-size: 16px;">KES ${vehicle.price.toLocaleString()}/day</strong><br>
                        <button onclick="locatorBookVehicle(${vehicle.id})" style="margin-top:8px;padding:5px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;width:100%;">Book Now</button>
                    </div>
                `;
                const marker = L.marker([vehicle.lat, vehicle.lng])
                    .bindPopup(popupContent)
                    .addTo(locatorMap);
                locatorMarkers.push(marker);
            }
        });
    }

    function getUserLocationForLocator() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    locatorUserLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    if (locatorUserMarker) {
                        locatorMap.removeLayer(locatorUserMarker);
                    }
                    
                    locatorUserMarker = L.marker([locatorUserLocation.lat, locatorUserLocation.lng], {
                        icon: L.divIcon({
                            html: '<div style="background: #28a745; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>',
                            iconSize: [20, 20],
                            className: 'user-marker'
                        })
                    }).addTo(locatorMap)
                    .bindPopup('<strong>📍 You are here</strong>').openPopup();
                    
                    locatorMap.setView([locatorUserLocation.lat, locatorUserLocation.lng], 13);
                    filterVehiclesByRadius();
                },
                function(error) {
                    console.log("Location error:", error);
                    displayLocatorVehicles(locatorVehiclesData);
                    showLocatorNotification("Unable to get your location. Showing all available vehicles.", "warning");
                }
            );
        } else {
            displayLocatorVehicles(locatorVehiclesData);
            showLocatorNotification("Geolocation is not supported by your browser.", "warning");
        }
    }

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function filterVehiclesByRadius() {
        if (!locatorUserLocation) {
            displayLocatorVehicles(locatorVehiclesData);
            return;
        }
        
        const radius = parseInt(document.getElementById('locatorRadius').value);
        locatorFilteredVehicles = locatorVehiclesData.filter(vehicle => {
            if (!vehicle.lat || !vehicle.lng) return false;
            const distance = calculateDistance(locatorUserLocation.lat, locatorUserLocation.lng, vehicle.lat, vehicle.lng);
            vehicle.distance = distance;
            return distance <= radius;
        });
        
        locatorFilteredVehicles.sort((a, b) => a.distance - b.distance);
        
        addLocatorMarkers(locatorFilteredVehicles);
        displayLocatorVehicles(locatorFilteredVehicles);
    }

    function displayLocatorVehicles(vehicles) {
        const container = document.getElementById('locatorVehiclesList');
        
        if (!vehicles || vehicles.length === 0) {
            container.innerHTML = `
                <div class="no-vehicles-message">
                    <i class="fas fa-car-side"></i>
                    <h3>No vehicles found nearby</h3>
                    <p>Try increasing the search radius or check back later.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = vehicles.map(vehicle => {
            const distance = vehicle.distance ? vehicle.distance.toFixed(1) : '?';
            return `
                <div class="locator-vehicle-card">
                    <div class="locator-vehicle-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="locator-vehicle-info">
                        <div class="locator-vehicle-title">${vehicle.brand} ${vehicle.model}</div>
                        <div class="locator-vehicle-distance"><i class="fas fa-location-dot"></i> ${distance} km away</div>
                        <div class="locator-vehicle-price">KES ${vehicle.price.toLocaleString()} / day</div>
                    </div>
                    <button class="locator-book-btn" onclick="locatorBookVehicle(${vehicle.id})">Book Now →</button>
                </div>
            `;
        }).join('');
    }

    function locatorBookVehicle(vehicleId) {
        closeLocatorModal();
        <?php if(isset($_SESSION['user_id'])): ?>
            window.location.href = 'book.php?id=' + vehicleId;
        <?php else: ?>
            if(confirm('Please login to book a vehicle. Go to login page?')) {
                window.location.href = 'index.php';
            }
        <?php endif; ?>
    }

    function showLocatorNotification(message, type) {
        // Simple alert for now, can be replaced with toast notification
        console.log(message);
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('locatorModal');
            if (modal.classList.contains('active')) {
                closeLocatorModal();
            }
        }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('locatorModal');
        if (event.target === modal) {
            closeLocatorModal();
        }
    }
</script>