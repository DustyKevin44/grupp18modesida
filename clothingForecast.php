<?php
require_once "include/views/_header.php";
require_once "include/models/weatherInfo.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Clothing Forecast</h2>
    <p class="subtitle">Pick a day and place — we'll show you what people wore.</p>

    <!-- ── Date slider ───────────────────────────────────────────────── -->
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

    <!-- ── Weather banner (shown once forecast loads) ────────────────── -->
    <div id="weather-banner" style="display:none;">
      <span id="wb-icon"></span>
      <span id="wb-label"></span>
      <span id="wb-temp"></span>
    </div>

    <!-- ── Location toggle ───────────────────────────────────────────── -->
    <div class="location-group">
      <label class="checkbox-container">
        <input type="checkbox" id="use-location" checked>
        Use my current location
      </label>
      <div id="location-status"></div>
    </div>

    <!-- ── Manual location search (hidden when GPS active) ───────────── -->
    <div id="search-container" class="hidden" style="position:relative; margin-top:12px;">
      <label for="locationSearch">Location:</label>
      <input type="text" id="locationSearch"
             placeholder="e.g. Drottninggatan, Stockholm"
             style="width:300px;" autocomplete="off">
      <span id="locationCheckmark" style="color:green; display:none;"> ✓ Validated</span>
      <div id="searchResults" class="search-results-box"></div>
    </div>

    <!-- ── Category filter (optional) ───────────────────────────────── -->
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

    <button id="find-btn" disabled>Loading location…</button>

    <hr>
    <div id="cf-results"></div>

  </div>
</div>

<script src="include/models/currentLocation.js"></script>
<script src="include/models/autocomplete.js"></script>
<script src="include/models/dateSelector.js"></script>

<script>
(function () {
  /* ── State ──────────────────────────────────────────────────────────── */
  let forecast      = null;   // array[0..7] from weatherInfo.php
  let gpsCoords     = null;   // {lat, lon} raw GPS
  let manualCoords  = null;   // {lat, lon, label} from autocomplete
  let useGPS        = true;

  /* ── DOM refs ───────────────────────────────────────────────────────── */
  const slider      = document.getElementById("date-slider");
  const datePrev    = document.getElementById("date-preview");
  const banner      = document.getElementById("weather-banner");
  const wbIcon      = document.getElementById("wb-icon");
  const wbLabel     = document.getElementById("wb-label");
  const wbTemp      = document.getElementById("wb-temp");
  const useLocCb    = document.getElementById("use-location");
  const searchCont  = document.getElementById("search-container");
  const locStatus   = document.getElementById("location-status");
  const findBtn     = document.getElementById("find-btn");
  const resultsDiv  = document.getElementById("cf-results");

  /* ── Emoji map (matches wmoToLabel() in weatherInfo.php) ────────────── */
  const EMOJI = {
    "Clear sky":                      "☀️",
    "Mainly clear / Partly cloudy":   "⛅",
    "Overcast":                       "☁️",
    "Foggy":                          "🌫️",
    "Rainy":                          "🌧️",
    "Snowy":                          "❄️",
    "Thunderstorm":                   "⛈️",
  };

  /* ── Helpers ────────────────────────────────────────────────────────── */
  function dayLabel(n) {
    if (n === 0) return "Today";
    if (n === 1) return "Tomorrow";
    const d = new Date();
    d.setDate(d.getDate() + n);
    return d.toLocaleDateString(undefined, { weekday:"long", month:"short", day:"numeric" });
  }

  function readyCoords() {
    // City-level for GPS (1 decimal ≈ 11 km); street-level for manual (full precision)
    if (useGPS && gpsCoords)    return { lat: +gpsCoords.lat.toFixed(1),
                                         lon: +gpsCoords.lon.toFixed(1), label: null };
    if (!useGPS && manualCoords) return manualCoords;
    return null;
  }

  function enableButton() {
    const ok = readyCoords() !== null;
    findBtn.disabled = !ok;
    findBtn.textContent = ok ? "Find outfits" : "Waiting for location…";
  }

  /* ── Fetch forecast from weatherInfo.php ────────────────────────────── */
  async function loadForecast() {
    const coords = readyCoords();
    if (!coords) return;

    banner.style.display = "none";
    forecast = null;

    try {
      const res = await fetch("include/models/weatherInfo.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ lat: coords.lat, lon: coords.lon, days: 7 }),
      });
      if (!res.ok) return;
      forecast = await res.json();
      renderBanner();
    } catch (e) {
      console.warn("Forecast error", e);
    }
  }

  function renderBanner() {
    if (!forecast) return;
    const day = forecast[parseInt(slider.value, 10)];
    if (!day) return;
    wbIcon.textContent  = EMOJI[day.weather] ?? "🌡️";
    wbLabel.textContent = day.weather;
    wbTemp.textContent  = `${day.tempMin}° – ${day.tempMax}°C`;
    banner.style.display = "flex";
  }

  /* ── Fetch posts from search.php (reuses its POST endpoint) ─────────── */
  async function fetchPosts(day, category, locationLabel) {
    const payload = {
      weather:  day.weather,
      tempMin:  day.tempMin,
      tempMax:  day.tempMax,
    };
    if (category)      payload.type     = category;
    if (locationLabel) payload.location = JSON.stringify({ label: locationLabel });

    const res = await fetch("search.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error("search.php error " + res.status);
    return res.json();
  }

  /* ── Render posts ───────────────────────────────────────────────────── */
  function renderPosts(posts) {
    resultsDiv.innerHTML = "";

    if (!posts.length) {
      resultsDiv.innerHTML =
        "<p style='color:orange;'>No posts found for this day's weather. Try a different date or category.</p>";
      return;
    }

    resultsDiv.innerHTML = `<p style="color:green;">Found ${posts.length} post(s)</p>`;

    posts.forEach(post => {
      const el  = document.createElement("div");
      el.className = "post";

      const imgs = post.ImagePaths
        ? post.ImagePaths.split(",")
            .map(p => `<img src="${p}" style="max-width:200px; margin:5px;">`)
            .join("")
        : "";

      el.innerHTML = `
        <h3>${post.Type ?? "Unknown"}</h3>
        <p>${post.Description ?? ""}</p>
        <p><b>Weather:</b> ${post.Weather ?? "-"}</p>
        <p><b>Temperature:</b> ${post.Temperature ?? "-"}°C</p>
        <p><b>Location:</b> ${post.Adress ?? "-"}</p>
        <div>${imgs}</div>
        <hr>`;

      resultsDiv.appendChild(el);
    });
  }

  /* ── Event: slider ──────────────────────────────────────────────────── */
  slider.addEventListener("input", () => {
    datePrev.textContent = dayLabel(parseInt(slider.value, 10));
    renderBanner();   // forecast already loaded; just re-render banner for new day
  });

  /* ── Event: GPS toggle ──────────────────────────────────────────────── */
  useLocCb.addEventListener("change", () => {
    useGPS = useLocCb.checked;
    searchCont.classList.toggle("hidden", useGPS);
    forecast = null;
    banner.style.display = "none";
    enableButton();
    if (useGPS && gpsCoords) loadForecast();
  });

  /* ── Event: find button ─────────────────────────────────────────────── */
  findBtn.addEventListener("click", async () => {
    if (!forecast) { resultsDiv.innerHTML = "<p>Forecast not loaded yet.</p>"; return; }

    const offset   = parseInt(slider.value, 10);
    const day      = forecast[offset];
    const category = document.getElementById("category-select").value;
    const coords   = readyCoords();

    resultsDiv.innerHTML = "<p>Searching…</p>";

    try {
      const posts = await fetchPosts(day, category, coords?.label ?? null);
      renderPosts(posts);
    } catch (e) {
      console.error(e);
      resultsDiv.innerHTML = "<p style='color:red;'>Search failed. Please try again.</p>";
    }
  });

  /* ── GPS resolution (called by currentLocation.js OR inline below) ─── */
  window.onGPSResolved = function ({ lat, lon, city }) {
    gpsCoords = { lat, lon };
    locStatus.textContent = `📍 ${city ?? "Location detected"}`;
    enableButton();
    if (useGPS) loadForecast();
  };

  /* ── Autocomplete selection (called by autocomplete.js) ─────────────── */
  window.onLocationSelected = function ({ lat, lon, label }) {
    manualCoords = { lat, lon, label };
    // Also keep locationData in sync in case autocomplete.js reads it
    const ld = document.getElementById("locationData");
    if (ld) ld.value = JSON.stringify({ label, lat, lon });
    enableButton();
    if (!useGPS) loadForecast();
  };

  /* ── Bootstrap GPS immediately ──────────────────────────────────────── */
  if (navigator.geolocation) {
    locStatus.textContent = "Detecting location…";
    navigator.geolocation.getCurrentPosition(
      pos => {
        const { latitude: lat, longitude: lon } = pos.coords;
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`)
          .then(r => r.json())
          .then(d => {
            const city = d.address?.city || d.address?.town
                       || d.address?.village || "Your location";
            window.onGPSResolved({ lat, lon, city });
          })
          .catch(() => window.onGPSResolved({ lat, lon, city: "Your location" }));
      },
      () => {
        locStatus.textContent = "⚠️ Could not get location — please search manually.";
        useLocCb.checked = false;
        useGPS = false;
        searchCont.classList.remove("hidden");
        enableButton();
      },
      { timeout: 8000 }
    );
  } else {
    locStatus.textContent = "GPS not supported — please search manually.";
    useLocCb.checked = false;
    useGPS = false;
    searchCont.classList.remove("hidden");
  }
})();
</script>

<?php require_once "include/views/_footer.php"; ?>