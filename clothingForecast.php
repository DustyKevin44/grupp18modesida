<?php
require_once "include/models/db.php";
require_once "include/models/weatherInfo.php";

// ================================
// AJAX POST handler
// ================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true) ?? [];

    $where  = ["Private = 0"];
    $params = [];

    if (!empty($data["weather"])) {
        $where[]  = "Weather = ?";
        $params[] = $data["weather"];
    }

    if (isset($data["tempMin"]) && $data["tempMin"] !== "") {
        $where[]  = "Temperature >= ?";
        $params[] = (int) $data["tempMin"];
    }

    if (isset($data["tempMax"]) && $data["tempMax"] !== "") {
        $where[]  = "Temperature <= ?";
        $params[] = (int) $data["tempMax"];
    }

    if (!empty($data["type"])) {
        $where[]  = "Type = ?";
        $params[] = $data["type"];
    }

    if (!empty($data["location"])) {
        $loc = json_decode($data["location"], true);
        if (is_array($loc) && !empty($loc["label"])) {
            $where[]  = "Adress LIKE ?";
            $params[] = "%" . $loc["label"] . "%";
        }
    }

    $sql = "SELECT Post.*, GROUP_CONCAT(Image.FilePath) AS ImagePaths
            FROM Post
            LEFT JOIN Image ON Post.ID = Image.PostID
            WHERE " . implode(" AND ", $where) . "
            GROUP BY Post.ID
            ORDER BY Post.ID DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error", "message" => $e->getMessage()]);
    }

    exit;
}

// ================================
// GET — page render
// ================================
require_once "include/views/_header.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Clothing Forecast</h2>
    <form>
    <label>Pick a day and place — we'll show you what to wear.</label>

    <label for="date-slider">Select a date:</label>
    <input type="range" id="date-slider" min="0" max="7" value="0" step="1" list="cf-ticks">
    <datalist id="cf-ticks">
      <option value="0" label="Today"></option>
      <option value="1" label="Tomorrow"></option>
      <option value="2"></option><option value="3"></option>
      <option value="4"></option><option value="5"></option>
      <option value="6"></option><option value="7"></option>
    </datalist>
    <div id="date-preview">Today</div>

    <div id="weather-banner" class="hidden">
      <span id="wb-icon"></span>
      <span id="wb-label"></span>
      <span id="wb-temp"></span>
    </div>

    <div class="location-group">
      <label class="checkbox-container">
        <input type="checkbox" id="use-location" checked>
        Use my current location
      </label>
      <div id="location-status"></div>
    </div>

    <div id="search-container" class="hidden search-container-spacing">
      <label for="locationSearch">Location:</label>
      <input type="text" id="locationSearch"
             placeholder="e.g. Drottninggatan, Stockholm"
             class="location-input-narrow" autocomplete="off">
      <span id="locationCheckmark" class="location-checkmark"> ✓ Validated</span>
      <div id="searchResults" class="search-results-box"></div>
    </div>

    <label for="category-select">Category (optional):</label>
    <select id="category-select">
      <option value="">Any</option>
      <option value="outfit">Outfit</option>
      <option value="school">School</option>
      <option value="restaurant">Restaurant</option>
      <option value="bar">Bar</option>
      <option value="culture">Culture</option>
      <option value="gym">Gym</option>
      <option value="outdoor">Outdoor</option>
      <option value="other">Other</option>
    </select>

    <input type="hidden" id="locationData">
    <form id="postForm" onsubmit="return false;"></form>

    <button id="find-btn" class="btn btn-submit">Find outfits</button>
    </form>
  </div>

  <div id="cf-results" class="posts-section"></div>
</div>

<script src="include/models/clothingForecastBridge.js"></script>
<script src="include/models/currentLocation.js"></script>
<script src="include/models/autocomplete.js"></script>

<script src="include/models/clothingForecast.js"></script>

<?php require_once "include/views/_footer.php"; ?>