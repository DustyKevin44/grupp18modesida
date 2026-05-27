<?php
require_once "include/models/db.php";
require_once "include/models/weatherInfo.php";

$where  = ["Private = 0"];
$params = [];

$query    = $_GET["query"]    ?? "";
$weather  = $_GET["weather"]  ?? "";
$type     = $_GET["type"]     ?? "";
$tempMin  = $_GET["tempMin"]  ?? "";
$tempMax  = $_GET["tempMax"]  ?? "";
$location = $_GET["location"] ?? "";

$searched = isset($_GET["searched"]);

if (!empty($query)) {
    $where[]  = "Description LIKE ?";
    $params[] = "%" . $query . "%";
}

if (!empty($weather)) {
    $where[]  = "Weather = ?";
    $params[] = $weather;
}

if (!empty($type)) {
    $where[]  = "Type = ?";
    $params[] = $type;
}

if ($tempMin !== "") {
    $where[]  = "Temperature >= ?";
    $params[] = (int) $tempMin;
}

if ($tempMax !== "") {
    $where[]  = "Temperature <= ?";
    $params[] = (int) $tempMax;
}

if (!empty($location)) {
    $loc = json_decode($location, true);
    if (is_array($loc) && !empty($loc["label"])) {
        $where[]  = "Adress LIKE ?";
        $params[] = "%" . $loc["label"] . "%";
    }
}

$posts = [];
$error = null;

if ($searched) {
    $sql = "SELECT User.Username, Post.*, GROUP_CONCAT(Image.FilePath) AS ImagePaths
            FROM Post
            LEFT JOIN User ON User.ID = Post.UserID
            LEFT JOIN Image ON Post.ID = Image.PostID
            WHERE " . implode(" AND ", $where) . "
            GROUP BY Post.ID
            ORDER BY Post.ID DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

require_once "include/views/_header.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Search Posts</h2>
    <form method="GET" action="search.php" id="searchForm">
      <input type="hidden" name="searched" value="1">

      <label>Keyword:</label>
      <input type="text" id="query" name="query"
             value="<?= htmlspecialchars($query) ?>">

      <label>Weather:</label>
      <select id="weather" name="weather">
        <option value="">Any</option>
        <?php
        $weatherOptions = [
            "Clear sky",
            "Mainly clear / Partly cloudy",
            "Overcast",
            "Foggy",
            "Rainy",
            "Snowy",
            "Thunderstorm",
        ];
        foreach ($weatherOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt) ?>"
            <?= $weather === $opt ? "selected" : "" ?>>
            <?= htmlspecialchars($opt) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Min Temperature (°C):</label>
      <input type="number" id="tempMin" name="tempMin"
             value="<?= htmlspecialchars($tempMin) ?>">

      <label>Max Temperature (°C):</label>
      <input type="number" id="tempMax" name="tempMax"
             value="<?= htmlspecialchars($tempMax) ?>">

      <label>Type:</label>
      <select id="type" name="type">
        <option value="">Any</option>
        <?php
        $typeOptions = [
            "school", "restaurant", "bar", "beach",
            "culture", "gym", "other"
        ];
        foreach ($typeOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt) ?>"
            <?= $type === $opt ? "selected" : "" ?>>
            <?= ucfirst(htmlspecialchars($opt)) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Location:</label>
      <input type="text" id="locationSearch" autocomplete="off">
      <div id="searchResults"></div>
      <input type="hidden" id="locationData" name="location"
             value="<?= htmlspecialchars($location) ?>">
      <span id="locationCheckmark" class="location-checkmark"> ✓ Validated</span>

      <button type="submit" class="btn btn-submit">Search</button>
    </form>

  </div>

  <div id="results" class="posts-section">
    <?php if ($error): ?>
      <p class="message-error"><?= htmlspecialchars($error) ?></p>

    <?php elseif ($searched && empty($posts)): ?>
      <p class="message-warning">No posts found for your filters.</p>

    <?php elseif (!empty($posts)): ?>
      <?php foreach ($posts as $post):
        $images = [];
        if (!empty($post["ImagePaths"])) {
            foreach (explode(",", $post["ImagePaths"]) as $path) {
                $images[] = '<img src="' . htmlspecialchars($path) . '" alt="Post image">';
            }
        }
      ?>
        <div class="post-card">
          <div class="post-header">
            <strong><?= htmlspecialchars($post["Type"] ?? "Unknown") ?></strong>
            <span><?= htmlspecialchars($post["Adress"] ?? "Unknown") ?></span>
            <span><?= htmlspecialchars($post["Username"] ?? "Unknown") ?></span>
          </div>
          <div class="post-main">
            <?php if (!empty($images)): ?>
              <div class="post-images"><?= implode("", $images) ?></div>
            <?php endif; ?>
            <div class="post-meta">
              <span>🌤 <?= htmlspecialchars($post["Weather"] ?? "-") ?></span>
              <span>🌡 <?= htmlspecialchars($post["Temperature"] ?? "-") ?>°C</span>
              <?php if (!empty($post["Private"])): ?>
                <span class="private-tag">Private</span>
              <?php endif; ?>
            </div>
            <p class="post-desc"><?= htmlspecialchars($post["Description"] ?? "") ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script src="include/models/autocomplete.js"></script>

<?php require_once "include/views/_footer.php"; ?>