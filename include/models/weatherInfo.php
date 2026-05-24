<?php
/**
 * weatherInfo.php
 *
 * 1. Provides Leaflet / geocoding CSS+JS includes (echo via weatherIncludes()).
 * 2. Exposes fetchForecast($lat, $lon, $days) for use by other PHP files.
 * 3. Handles POST requests from JS: { lat, lon, days } → JSON forecast array.
 *
 * Each forecast item:
 *   { date, weather, temperature, tempMin, tempMax }
 *
 * "weather" values match the labels used in search.php exactly.
 */

/* ── AJAX endpoint ─────────────────────────────────────────────────────── */
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']) && $_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $lat  = isset($data["lat"])  ? (float)$data["lat"]  : null;
    $lon  = isset($data["lon"])  ? (float)$data["lon"]  : null;
    $days = isset($data["days"]) ? (int)$data["days"]   : 7;

    if ($lat === null || $lon === null) {
        http_response_code(400);
        echo json_encode(["error" => "lat and lon required"]);
        exit;
    }

    echo json_encode(fetchForecast($lat, $lon, $days));
    exit;
}

/* ── HTML includes (call once in <head> or before </body>) ─────────────── */
function weatherIncludes(): void { ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/bundle.min.js"></script>
<?php }

/* ── Core forecast function ────────────────────────────────────────────── */
/**
 * Fetches an 8-day daily forecast from Open-Meteo 
 *
 * @param float $lat
 * @param float $lon
 * @param int   $days  Number of days to return (max 7, i.e. today + 7).
 * @return array       Array of forecast objects, index 0 = today.
 */
function fetchForecast(float $lat, float $lon, int $days = 7): array {
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        "latitude"      => $lat,
        "longitude"     => $lon,
        "daily"         => "weathercode,temperature_2m_max,temperature_2m_min",
        "timezone"      => "auto",
        "forecast_days" => min($days + 1, 8),
    ]);

    $json = @file_get_contents($url);
    if ($json === false) return [];

    $resp = json_decode($json, true);
    if (empty($resp["daily"])) return [];

    $daily   = $resp["daily"];
    $results = [];

    foreach ($daily["time"] as $i => $date) {
        $code    = (int)($daily["weathercode"][$i] ?? 0);
        $tempMax = (int)round($daily["temperature_2m_max"][$i] ?? 0);
        $tempMin = (int)round($daily["temperature_2m_min"][$i] ?? 0);

        $results[] = [
            "date"        => $date,
            "weather"     => wmoToLabel($code),
            "temperature" => (int)round(($tempMax + $tempMin) / 2),
            "tempMin"     => $tempMin,
            "tempMax"     => $tempMax,
        ];
    }

    return $results;
}

/* ── WMO weather-code → search.php label ──────────────────────────────── */
function wmoToLabel(int $code): string {
    return match(true) {
        $code === 0              => "Clear sky",
        in_array($code, [1, 2]) => "Mainly clear / Partly cloudy",
        $code === 3             => "Overcast",
        in_array($code, [45, 48])               => "Foggy",
        in_array($code, [51,53,55,61,63,65,
                          66,67,80,81,82])      => "Rainy",
        in_array($code, [71,73,75,77,85,86])    => "Snowy",
        in_array($code, [95,96,99])             => "Thunderstorm",
        default                                 => "Overcast",
    };
}