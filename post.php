<?php
require_once("include/models/db.php");

// Simulated active user ID. Replace this with your actual session authentication: $_SESSION['user_id']
$currentUserId = 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_data'])) {
    
    // 1. Decode the incoming Javascript JSON data
    $locationData = json_decode($_POST['location_data'], true);

    if ($locationData) {
        $label     = $locationData['label'] ?? 'Unknown Location';
        $lat       = $locationData['lat'];
        $lon       = $locationData['lon'];
        $placeType = $locationData['place_type'] ?? 'unknown'; // e.g., "school", "mall"

        // Handle private checkbox setting (0 or 1)
        $isPrivate = isset($_POST['private']) ? 1 : 0;
        $description = $_POST['description'] ?? '';

        // Default weather fallbacks
        $temperature = 0; // NOT NULL requirement in your schema
        $weatherCondition = "Unknown";

        // 2. Fetch live weather using Open-Meteo API
        $weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true";
        
        // Make the API call safely
        $weatherResponse = @file_get_contents($weatherUrl);
        
        if ($weatherResponse !== false) {
            $weatherData = json_decode($weatherResponse, true);
            
            if (isset($weatherData['current_weather'])) {
                // Rounding because your schema defines Temperature as INT
                $temperature = round($weatherData['current_weather']['temperature']); 
                $weatherCode = $weatherData['current_weather']['weathercode'];
                
                // Map the WMO weather code integer to a human-readable string
                $weatherCondition = interpretWeatherCode($weatherCode);
            }
        }

        // 3. DATABASE INSERTION (Matching your SQLite Post Table Schema)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Post (UserID, Private, Description, Weather, Temperature, Adress, Type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $currentUserId,
                $isPrivate,
                $description,
                $weatherCondition,
                $temperature,
                $label,       // Maps to 'Adress'
                $placeType    // Maps to 'Type'
            ]);

            echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e6ffe6;'>Post successfully created!</div>";

        } catch (PDOException $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffe6e6;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Helper function to map standard WMO Weather Codes to text descriptions
function interpretWeatherCode($code) {
    if ($code == 0) return "Clear sky";
    if (in_array($code, [1, 2, 3])) return "Mainly clear / Partly cloudy";
    if (in_array($code, [45, 48])) return "Foggy";
    if (in_array($code, [51, 53, 55, 61, 63, 65])) return "Rainy";
    if (in_array($code, [71, 73, 75, 77, 85, 86])) return "Snowy";
    if (in_array($code, [95, 96, 99])) return "Thunderstorm";
    return "Overcast";
}
?>

<?php require_once "include/views/_header.php"; ?>

<!-- Load Leaflet and Geocoding CSS/JS (Free OpenStreetMap search) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
<script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/bundle.min.js"></script>

<div class="center">
<div class="post-container">
<form id="postForm" action="post.php" method="POST">
    <!-- Hidden fields for automated/structured data -->
    <input type="hidden" name="location_data" id="locationData">
    
    <div>
        <label for="description">Description:</label><br>
        <textarea name="description" id="description" required></textarea>
    </div>

    <div>
        <label for="private">Private Post:</label>
        <input type="checkbox" name="private" value="1" id="private">
    </div>

    <div style="margin-top: 15px; position: relative;">
        <label for="locationSearch">Location:</label><br>
        <input type="text" id="locationSearch" placeholder="Search for a place (e.g., University of Michigan)..." style="width: 300px;" autocomplete="off">
        <span id="locationCheckmark" style="color: green; display: none;"> ✓ Validated</span>
        
        <!-- Results dropdown menu container -->
        <div id="searchResults" class="search-results-box"></div>
        
        <p style="font-size: 0.85em; color: #666;">Leave blank to use your current GPS location.</p>
    </div>

    <button type="submit" style="margin-top: 15px;">Create Post</button>
</form>
</div>
</div>

<script>
// 1. Initialize the Search Provider with Nominatim-compliant headers
const provider = new window.GeoSearch.OpenStreetMapProvider({
    params: {
        'Accept-Language': 'sv', 
        'email': 'dustykevin44@gmail.com' 
    }
});

const searchInput = document.getElementById('locationSearch');
const locationDataInput = document.getElementById('locationData');
const checkmark = document.getElementById('locationCheckmark');
const resultsBox = document.getElementById('searchResults');
const form = document.getElementById('postForm');

let debounceTimer;

function debounce(func, delay) {
    return function (...args) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(this, args), delay);
    };
}

// 2. Autocomplete API trigger wrapped in a 1-second debounce handler
searchInput.addEventListener('input', debounce(async (e) => {
    const query = e.target.value.trim();
    
    if (query.length < 3) {
        resetLocationSelection();
        return;
    }

    try {
        const results = await provider.search({ query: query });
        renderResults(results);
    } catch (error) {
        console.error("Geocoding error:", error);
    }
}, 1000));

// Render the results into the dropdown box
function renderResults(results) {
    resultsBox.innerHTML = '';
    
    if (results.length === 0) {
        resultsBox.style.display = 'none';
        return;
    }

    resultsBox.style.display = 'block';
    
    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'search-item';
        item.textContent = result.label;
        
        item.addEventListener('click', () => {
            searchInput.value = result.label;
            
            const placeType = result.raw.type || result.raw.addresstype || "unknown";
            
            const structuredData = {
                type: "search",
                label: result.label,
                lat: result.y,
                lon: result.x,
                place_type: placeType 
            };
            
            locationDataInput.value = JSON.stringify(structuredData);
            checkmark.style.display = 'inline';
            resultsBox.style.display = 'none';
        });
        
        resultsBox.appendChild(item);
    });
}

document.addEventListener('click', function (e) {
    if (e.target !== searchInput && e.target !== resultsBox) {
        resultsBox.style.display = 'none';
    }
});

function resetLocationSelection() {
    checkmark.style.display = 'none';
    locationDataInput.value = '';
    resultsBox.innerHTML = '';
    resultsBox.style.display = 'none';
}

// 3. Form Submission Handling
form.addEventListener('submit', async function(e) {
    e.preventDefault(); 

    const query = searchInput.value.trim();

    // Case A: User typed a location
    if (query !== "") {
        if (locationDataInput.value !== "") {
            form.submit();
            return;
        }

        const results = await provider.search({ query: query });
        if (results.length > 0) {
            const topResult = results[0];
            const placeType = topResult.raw?.type || topResult.raw?.addresstype || "unknown";
            
            const structuredData = {
                type: "search",
                label: topResult.label,
                lat: topResult.y,
                lon: topResult.x,
                place_type: placeType
            };
            
            locationDataInput.value = JSON.stringify(structuredData);
            form.submit();
        } else {
            alert("Please select or type a valid, recognizable location.");
        }
    } 
    // Case B: User left it blank. Fetch browser GPS location.
    else {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const structuredData = {
                        type: "gps",
                        label: "Current Location",
                        lat: position.coords.latitude,
                        lon: position.coords.longitude,
                        place_type: "gps"
                    };
                    locationDataInput.value = JSON.stringify(structuredData);
                    form.submit();
                },
                (error) => {
                    alert("Location access denied or unavailable. Please type a location manually.");
                },
                { enableHighAccuracy: true, timeout: 5000 }
            );
        } else {
            alert("Your browser does not support geolocation. Please type a location.");
        }
    }
});
</script>

<?php require_once "include/views/_footer.php"; ?>