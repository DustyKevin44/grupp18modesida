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

<?php require_once "include/views/_header.php"; 
require_once "include/models/weatherInfo.php"
?>


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

<script src="include/models/autocomplete.js"></script>

<?php require_once "include/views/_footer.php"; ?>