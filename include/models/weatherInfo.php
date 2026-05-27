<?php
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

function weatherIncludes(): void { ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/bundle.min.js"></script>
<?php }
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

function placeTypeToCategory(string $placeType): string {
    $normalized = strtolower(trim($placeType));

    return match (true) {
        str_contains($normalized, 'school'),
        str_contains($normalized, 'university'),
        str_contains($normalized, 'college'),
        str_contains($normalized, 'kindergarten'),
        str_contains($normalized, 'academy'),
        str_contains($normalized, 'library') => 'School',

        str_contains($normalized, 'restaurant'),
        str_contains($normalized, 'cafe'),
        str_contains($normalized, 'coffee'),
        str_contains($normalized, 'fast_food'),
        str_contains($normalized, 'food'),
        str_contains($normalized, 'bistro') => 'Restaurant',

        str_contains($normalized, 'bar'),
        str_contains($normalized, 'pub'),
        str_contains($normalized, 'nightclub'),
        str_contains($normalized, 'brewery') => 'Bar',

        str_contains($normalized, 'beach'),
        str_contains($normalized, 'seaside'),
        str_contains($normalized, 'shore'),
        str_contains($normalized, 'coast') => 'Beach',

        str_contains($normalized, 'gym'),
        str_contains($normalized, 'fitness'),
        str_contains($normalized, 'sports'),
        str_contains($normalized, 'health'),
        str_contains($normalized, 'studio') => 'Gym',

        str_contains($normalized, 'museum'),
        str_contains($normalized, 'theatre'),
        str_contains($normalized, 'cinema'),
        str_contains($normalized, 'gallery'),
        str_contains($normalized, 'monument'),
        str_contains($normalized, 'park'),
        str_contains($normalized, 'garden'),
        str_contains($normalized, 'zoo'),
        str_contains($normalized, 'tourism'),
        str_contains($normalized, 'leisure') => 'Culture',

        str_contains($normalized, 'hotel'),
        str_contains($normalized, 'hostel'),
        str_contains($normalized, 'motel'),
        str_contains($normalized, 'lodging') => 'Other',

        $normalized === 'gps',
        $normalized === 'search',
        $normalized === 'unknown' => 'Other',

        default => 'other',
    };
}
