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

    <div id="results" class="posts-section"></div>

  </div>
</div>

<script>
  const searchForm = document.getElementById("searchForm");
  if (searchForm) {
    searchForm.addEventListener("submit", (event) => event.preventDefault());
  }

  document.getElementById("searchBtn").addEventListener("click", async (e) => {
    e.preventDefault();
    const resultsDiv = document.getElementById("results");
    resultsDiv.innerHTML = "<p class='message-info'>Searching...</p>";

    const tempMin = document.getElementById("tempMin").value;
    const tempMax = document.getElementById("tempMax").value;

    const payload = {
      query:    document.getElementById("query").value.trim(),
      weather:  document.getElementById("weather").value,
      type:     document.getElementById("type").value,
      location: document.getElementById("locationData").value,
    };

    if (tempMin !== "") payload.tempMin = parseInt(tempMin);
    if (tempMax !== "") payload.tempMax = parseInt(tempMax);

    try {
      const res = await fetch("search.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload),
      });

      if (!res.ok) {
        console.error("Server error:", await res.text());
        resultsDiv.innerHTML = "<p class='message-error'>Server error occurred.</p>";
        return;
      }

      let data;
      try {
        data = JSON.parse(await res.text());
      } catch (e) {
        resultsDiv.innerHTML = "<p class='message-error'>Invalid server response.</p>";
        return;
      }

      resultsDiv.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        resultsDiv.innerHTML = "<p class='message-warning'>No posts found for your filters.</p>";
        return;
      }

      resultsDiv.innerHTML = `<p class='message-success'>Found ${data.length} post(s)</p>`;

      data.forEach(post => {
        const el = document.createElement("div");
        el.className = "post-card";

        const images = post.ImagePaths
          ? post.ImagePaths.split(",")
              .map(path => `<img src="${path}" alt="Post image">`)
              .join("")
          : "";

        el.innerHTML = `
          <div class="post-header">
            <strong>${post.Type ?? "Unknown"}</strong>
            <span>${post.Adress ?? "Unknown"}</span>
          </div>
          <p class="post-desc">${post.Description ?? ""}</p>
          <div class="post-meta">
            <span>🌤 ${post.Weather ?? "-"}</span>
            <span>🌡 ${post.Temperature ?? "-"}°C</span>
            ${post.Private ? '<span class="private-tag">Private</span>' : ""}
          </div>
          <div class="post-images">${images}</div>`;

        resultsDiv.appendChild(el);
      });

    } catch (err) {
      console.error("Fetch failed:", err);
      resultsDiv.innerHTML = "<p class='message-error'>Network error. Please try again.</p>";
    }
  });
</script>

<script src="include/models/autocomplete.js"></script>

<?php require_once "include/views/_footer.php"; ?>