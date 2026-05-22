<?php
require_once "include/models/db.php";
require_once "include/models/weatherInfo.php";

/**
 * =========================
 * AJAX SEARCH HANDLER
 * =========================
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  header("Content-Type: application/json");

  $data = json_decode(file_get_contents("php://input"), true) ?? [];

  $where  = ["Private = 0"];
  $params = [];

  if (!empty($data["query"])) {
    $where[]  = "Description LIKE ?";
    $params[] = "%" . $data["query"] . "%";
  }

  if (!empty($data["weather"])) {
    $where[]  = "Weather = ?";
    $params[] = $data["weather"];
  }

  if (!empty($data["type"])) {
    $where[]  = "Type = ?";
    $params[] = $data["type"];
  }

  if (isset($data["tempMin"]) && $data["tempMin"] !== "" && $data["tempMin"] !== null) {
    $where[]  = "Temperature >= ?";
    $params[] = (int) $data["tempMin"];
  }

  if (isset($data["tempMax"]) && $data["tempMax"] !== "" && $data["tempMax"] !== null) {
    $where[]  = "Temperature <= ?";
    $params[] = (int) $data["tempMax"];
  }

  if (!empty($data["location"])) {
    $loc = json_decode($data["location"], true);
    if (is_array($loc) && !empty($loc["label"])) {
      $where[]  = "Adress LIKE ?";
      $params[] = "%" . $loc["label"] . "%";
    }
  }

  $sql = "SELECT Post.*, GROUP_CONCAT(Image.FilePath) as ImagePaths
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

require_once "include/views/_header.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Search Posts</h2>
    <form id="searchForm">
    <label>Keyword:</label>
    <input type="text" id="query">

    <label>Weather:</label>
    <select id="weather">
      <option value="">Any</option>
      <!-- Values must match wmoToLabel() / interpretWeatherCode() exactly -->
      <option value="Clear sky">Clear sky</option>
      <option value="Mainly clear / Partly cloudy">Mainly clear / Partly cloudy</option>
      <option value="Overcast">Overcast</option>
      <option value="Foggy">Foggy</option>
      <option value="Rainy">Rainy</option>
      <option value="Snowy">Snowy</option>
      <option value="Thunderstorm">Thunderstorm</option>
    </select>

    <label>Min Temperature (°C):</label>
    <input type="number" id="tempMin">

    <label>Max Temperature (°C):</label>
    <input type="number" id="tempMax">

    <label>Type:</label>
    <select id="type">
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

    <label>Location:</label>
    <input type="text" id="locationSearch" autocomplete="off">
    <div id="searchResults"></div>
    <input type="hidden" id="locationData">
    <span id="locationCheckmark" class="location-checkmark"> ✓ Validated</span>

    <button type="button" id="searchBtn" class="submit-btn">Search</button>
    </form>

  </div>

  <div id="results" class="posts-section"></div>
</div>

<script src="include/models/search.js"></script>

<script src="include/models/autocomplete.js"></script>

<?php require_once "include/views/_footer.php"; ?>