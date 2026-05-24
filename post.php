<?php
require_once("include/models/db.php");
require_once("include/models/weatherInfo.php");
session_start();

$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    die("You must be logged in to post.");
}

// Check if user's account is locked
$checkLocked = $pdo->prepare("SELECT Locked FROM User WHERE ID = ?");
$checkLocked->execute([$currentUserId]);
$userStatus = $checkLocked->fetch(PDO::FETCH_ASSOC);
if ($userStatus && (int)$userStatus['Locked'] === 1) {
    die("Your account is locked. You cannot create posts.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_data'])) {

    $locationData = json_decode($_POST['location_data'], true);

    if ($locationData) {
        $label     = $locationData['label']      ?? 'Unknown Location';
        $lat       = $locationData['lat'];
        $lon       = $locationData['lon'];
        $placeType = $locationData['place_type'] ?? 'unknown';

        $isPrivate   = isset($_POST['private']) ? 1 : 0;
        $description = $_POST['description'] ?? '';

        // Default fallbacks
        $temperature      = 0;
        $weatherCondition = "Unknown";

        // Fetch today's forecast via shared fetchForecast() from weatherInfo.php
        $todayForecast = fetchForecast((float)$lat, (float)$lon, 0);

        if (!empty($todayForecast[0])) {
            $temperature      = $todayForecast[0]["temperature"];
            $weatherCondition = $todayForecast[0]["weather"];
        }

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
                $label,
                $placeType
            ]);

            $postId = $pdo->lastInsertId();

            /* ── Image upload ── */
            if (!empty($_FILES['images']['name'][0])) {

                $uploadDir = "uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {

                    if ($_FILES['images']['error'][$index] === 0) {

                        $originalName = basename($_FILES['images']['name'][$index]);
                        $ext          = pathinfo($originalName, PATHINFO_EXTENSION);
                        $newName      = uniqid("img_", true) . "." . $ext;
                        $destination  = $uploadDir . $newName;

                        move_uploaded_file($tmpName, $destination);

                        $stmtImg = $pdo->prepare("
                            INSERT INTO Image (UserID, PostID, FilePath)
                            VALUES (?, ?, ?)
                        ");

                        $stmtImg->execute([
                            $currentUserId,
                            $postId,
                            $destination
                        ]);
                    }
                }
            }

            echo "<div class='message-success'>Post successfully created!</div>";

        } catch (PDOException $e) {
            echo "<div class='message-error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<?php
require_once "include/views/_header.php";
weatherIncludes(); // Loads Leaflet + GeoSearch JS required by autocomplete.js
?>

<div class="center">
    <div class="post-container">
        <h2>Create Post</h2>
        <form id="postForm" action="post.php" method="POST" enctype="multipart/form-data">

            <div>
                <label for="image">Images:</label><br>
                <input type="file" name="images[]" id="images" multiple accept="image/*">
            </div>

            <input type="hidden" name="location_data" id="locationData">

            <div>
                <label for="description">Description:</label><br>
                <textarea name="description" id="description" required></textarea>
            </div>

            <div>
                <label class="checkbox-container">
                    <input type="checkbox" name="private" value="1" id="private">
                    Private Post
                </label>
            </div>

            <div id="search-container" class="search-container-spacing">
                <label for="locationSearch">Location:</label>
                <input type="text" id="locationSearch"
                        placeholder="Search for a place (e.g., University of Michigan)..."
                        class="location-input-narrow" autocomplete="off">
                <span id="locationCheckmark" class="location-checkmark"> ✓ Validated</span>
                <div id="searchResults" class="search-results-box"></div>
            </div>

            <button type="submit" class="btn btn-submit">Create Post</button>
        </form>
    </div>
</div>

<script src="include/models/currentLocation.js"></script>
<script src="include/models/autocomplete.js"></script>

<?php require_once "include/views/_footer.php"; ?>