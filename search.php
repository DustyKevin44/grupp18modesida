<?php
require_once "include/views/_header.php";
require_once "include/models/weatherInfo.php";
require_once "include/models/db.php";

/**
 * =========================
 * AJAX SEARCH HANDLER
 * =========================
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true) ?? [];

    $where = [];
    $params = [];

    // OPTIONAL: hide private posts (recommended)
    $where[] = "Private = 0";

    // keyword search
    if (!empty($data["query"])) {
        $where[] = "Description LIKE ?";
        $params[] = "%" . $data["query"] . "%";
    }

    // weather filter
    if (!empty($data["weather"])) {
        $where[] = "Weather = ?";
        $params[] = $data["weather"];
    }

    // type filter
    if (!empty($data["type"])) {
        $where[] = "Type = ?";
        $params[] = $data["type"];
    }

    // temperature min
    if (isset($data["tempMin"]) && $data["tempMin"] !== "") {
        $where[] = "Temperature >= ?";
        $params[] = (int)$data["tempMin"];
    }

    // temperature max
    if (isset($data["tempMax"]) && $data["tempMax"] !== "") {
        $where[] = "Temperature <= ?";
        $params[] = (int)$data["tempMax"];
    }

    // location filter
    if (!empty($data["location"])) {
        $loc = json_decode($data["location"], true);

        if (is_array($loc) && !empty($loc["label"])) {
            $where[] = "Adress LIKE ?";
            $params[] = "%" . $loc["label"] . "%";
        }
    }

    $sql = "SELECT * FROM Post";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Database error",
            "message" => $e->getMessage()
        ]);
    }

    exit;
}
?>

<body>

<div class="center">
  <div class="post-container">

    <h2>Search Posts</h2>

    <!-- FILTERS -->
    <label>Keyword:</label>
    <input type="text" id="query">

    <label>Weather:</label>
    <select id="weather">
      <option value="">Any</option>
      <option value="sunny">Sunny</option>
      <option value="cloudy">Cloudy</option>
      <option value="rainy">Rainy</option>
      <option value="snowy">Snowy</option>
      <option value="windy">Windy</option>
      <option value="foggy">Foggy</option>
    </select>

    <label>Min Temperature:</label>
    <input type="number" id="tempMin">

    <label>Max Temperature:</label>
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

    <!-- LOCATION -->
    <label>Location:</label>
    <input type="text" id="locationSearch" autocomplete="off">

    <div id="searchResults"></div>

    <input type="hidden" id="locationData">

    <button id="searchBtn">Search</button>

    <hr>

    <!-- RESULTS -->
    <div id="results"></div>

  </div>
</div>

<script>
const provider = new window.GeoSearch.OpenStreetMapProvider({
  params: { "accept-language": "sv" }
});

const searchInput = document.getElementById("locationSearch");
const locationData = document.getElementById("locationData");
const resultsBox = document.getElementById("searchResults");

let timer;

function debounce(fn, delay = 600) {
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

function renderGeo(results) {
  resultsBox.innerHTML = "";

  if (!results.length) {
    resultsBox.style.display = "none";
    return;
  }

  resultsBox.style.display = "block";

  results.forEach((r) => {
    const div = document.createElement("div");
    div.className = "search-item";
    div.textContent = r.label;

    div.onclick = () => {
      searchInput.value = r.label;

      locationData.value = JSON.stringify({
        label: r.label,
        lat: r.y,
        lon: r.x,
        type: r.raw?.type || "unknown"
      });

      resultsBox.style.display = "none";
    };

    resultsBox.appendChild(div);
  });
}

searchInput.addEventListener("input", debounce(async (e) => {
  const q = e.target.value.trim();

  if (q.length < 3) {
    resultsBox.style.display = "none";
    return;
  }

  const res = await provider.search({ query: q });
  renderGeo(res);
}));

document.addEventListener("click", (e) => {
  if (!resultsBox.contains(e.target) && e.target !== searchInput) {
    resultsBox.style.display = "none";
  }
});
document.getElementById("searchBtn").addEventListener("click", async () => {

  const resultsDiv = document.getElementById("results");

  // 🔄 SHOW LOADING STATE
  resultsDiv.innerHTML = "<p>Searching...</p>";

  const payload = {
    query: document.getElementById("query").value,
    weather: document.getElementById("weather").value,
    tempMin: document.getElementById("tempMin").value,
    tempMax: document.getElementById("tempMax").value,
    type: document.getElementById("type").value,
    location: locationData.value
  };

  try {
    const res = await fetch("search.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    // ❗ HANDLE HTTP ERRORS
    if (!res.ok) {
      const text = await res.text();
      console.error("Server error response:", text);
      resultsDiv.innerHTML = "<p>Server error occurred.</p>";
      return;
    }

    let data;

    try {
      data = await res.json();
    } catch (e) {
      console.error("Invalid JSON from server");
      resultsDiv.innerHTML = "<p>Invalid server response.</p>";
      return;
    }

    console.log("Search results:", data);

    resultsDiv.innerHTML = "";

    // ✅ NO RESULTS CASE
    if (!Array.isArray(data) || data.length === 0) {
      resultsDiv.innerHTML = `
        <p style="color: orange;">
          No posts found for your filters.
        </p>
      `;
      return;
    }

    // ✅ SUCCESS CASE (SHOW POSTS)
    resultsDiv.innerHTML = `
      <p style="color: green;">
        Found ${data.length} post(s)
      </p>
    `;

    data.forEach(post => {
      const el = document.createElement("div");
      el.className = "post";

      el.innerHTML = `
        <h3>${post.Type ?? "Unknown"}</h3>
        <p>${post.Description ?? ""}</p>
        <p><b>Weather:</b> ${post.Weather ?? "-"}</p>
        <p><b>Temperature:</b> ${post.Temperature ?? "-"}°C</p>
        <p><b>Location:</b> ${post.Adress ?? "-"}</p>
        <hr>
      `;

      resultsDiv.appendChild(el);
    });

  } catch (err) {
    console.error("Fetch failed:", err);
    resultsDiv.innerHTML = "<p style='color:red;'>Network error. Please try again.</p>";
  }
});
/**
 * =========================
 * SEARCH BUTTON
 * =========================
 */
document.getElementById("searchBtn").addEventListener("click", async () => {

  const payload = {
    query: document.getElementById("query").value,
    weather: document.getElementById("weather").value,
    tempMin: document.getElementById("tempMin").value,
    tempMax: document.getElementById("tempMax").value,
    type: document.getElementById("type").value,
    location: locationData.value
  };

  const res = await fetch("search.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });

  if (!res.ok) {
    console.error("Server error");
    return;
  }

  const data = await res.json();

  const resultsDiv = document.getElementById("results");
  resultsDiv.innerHTML = "";

  if (!data.length) {
    resultsDiv.innerHTML = "<p>No posts found.</p>";
    return;
  }

  data.forEach(post => {
    const el = document.createElement("div");
    el.className = "post";

    el.innerHTML = `
      <h3>${post.Type}</h3>
      <p>${post.Description}</p>
      <p><b>Weather:</b> ${post.Weather}</p>
      <p><b>Temperature:</b> ${post.Temperature}°C</p>
      <p><b>Location:</b> ${post.Adress}</p>
      <hr>
    `;

    resultsDiv.appendChild(el);
  });
});
</script>

<?php require_once "include/views/_footer.php"; ?>