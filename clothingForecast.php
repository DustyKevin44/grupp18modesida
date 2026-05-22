<?php
require_once "include/views/_header.php";
require_once "include/models/weatherInfo.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Clothing Forecast</h2>
    <form>
    <p class="subtitle">Pick a day and place — we'll show you what people wore.</p>

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

    <div id="weather-banner" style="display:none;">
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

    <div id="search-container" class="hidden" style="position:relative; margin-top:12px;">
      <label for="locationSearch">Location:</label>
      <input type="text" id="locationSearch"
             placeholder="e.g. Drottninggatan, Stockholm"
             style="width:300px;" autocomplete="off">
      <span id="locationCheckmark" style="color:green; display:none;"> ✓ Validated</span>
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

    <!--
      locationData hidden input is required by autocomplete.js —
      it writes the selected location JSON here.
    -->
    <input type="hidden" id="locationData">

    <!--
      postForm dummy form is required by autocomplete.js —
      it calls form.addEventListener("submit", ...) and form.submit().
      We intercept submit below so nothing actually navigates.
    -->
    <form id="postForm" onsubmit="return false;"></form>

    <button id="find-btn">Find outfits</button>

    <div id="cf-results"></div>

  </div>
</div>

<!--
  Load currentLocation.js first — it reads #use-location, #location-status,
  #search-container, #locationSearch and stores coords in userLatitude /
  userLongitude on the script's own scope. We reach those via the getGPSCoords()
  bridge defined below BEFORE the script loads.
-->
<script>
  // Bridge: currentLocation.js writes to these module-scoped vars.
  // We expose a getter so the rest of our code can read them.
  // Must be defined BEFORE currentLocation.js runs.
  window._cf_getGPSCoords = function () {
    return (typeof userLatitude !== "undefined" && userLatitude !== null)
      ? { lat: userLatitude, lon: userLongitude }
      : null;
  };
</script>

<script src="include/models/currentLocation.js"></script>

<!--
  autocomplete.js writes the chosen location as JSON into #locationData.
  It also binds to #postForm submit — our dummy form + onsubmit=false
  prevents any navigation.
-->
<script src="include/models/autocomplete.js"></script>

<script>
(function () {
  /* ── State ──────────────────────────────────────────────────────────── */
  let forecast     = null;
  let useGPS       = true;

  /* ── DOM ────────────────────────────────────────────────────────────── */
  const slider     = document.getElementById("date-slider");
  const datePrev   = document.getElementById("date-preview");
  const banner     = document.getElementById("weather-banner");
  const wbIcon     = document.getElementById("wb-icon");
  const wbLabel    = document.getElementById("wb-label");
  const wbTemp     = document.getElementById("wb-temp");
  const useLocCb   = document.getElementById("use-location");
  const searchCont = document.getElementById("search-container");
  const findBtn    = document.getElementById("find-btn");
  const resultsDiv = document.getElementById("cf-results");

  const EMOJI = {
    "Clear sky":                    "☀️",
    "Mainly clear / Partly cloudy": "⛅",
    "Overcast":                     "☁️",
    "Foggy":                        "🌫️",
    "Rainy":                        "🌧️",
    "Snowy":                        "❄️",
    "Thunderstorm":                 "⛈️",
  };

  /* ── Helpers ────────────────────────────────────────────────────────── */
  function dayLabel(n) {
    if (n === 0) return "Today";
    if (n === 1) return "Tomorrow";
    const d = new Date();
    d.setDate(d.getDate() + n);
    return d.toLocaleDateString(undefined,
      { weekday: "long", month: "short", day: "numeric" });
  }

  // Read whichever coords are currently active.
  function activeCoords() {
    if (useGPS) {
      // currentLocation.js stores these as module-level vars; read via bridge.
      const gps = window._cf_getGPSCoords();
      if (!gps) return null;
      // City-level: round to 1 decimal (~11 km) — no street sent to search.php
      return { lat: +gps.lat.toFixed(1), lon: +gps.lon.toFixed(1), label: null };
    } else {
      // autocomplete.js writes JSON into #locationData on selection.
      const raw = document.getElementById("locationData").value;
      if (!raw) return null;
      try {
        const loc = JSON.parse(raw);
        if (loc.lat && loc.lon) {
          // Street-level: full precision + label for location filter.
          return { lat: loc.lat, lon: loc.lon, label: loc.label ?? null };
        }
      } catch (e) {}
      return null;
    }
  }

  /* ── Forecast ───────────────────────────────────────────────────────── */
  async function loadForecast() {
    const c = activeCoords();
    if (!c) return;

    banner.style.display = "none";
    forecast = null;

    try {
      const res = await fetch("include/models/weatherInfo.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ lat: c.lat, lon: c.lon, days: 7 }),
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
    wbTemp.textContent  = `${day.tempMin}°–${day.tempMax}°C`;
    banner.style.display = "flex";
  }

  /* ── Posts (reuses search.php POST endpoint) ────────────────────────── */
  async function fetchPosts(day, category, locationLabel) {
    const payload = {
      weather: day.weather,
      tempMin: day.tempMin,
      tempMax: day.tempMax,
    };
    if (category)      payload.type     = category;
    if (locationLabel) payload.location = JSON.stringify({ label: locationLabel });

    const res = await fetch("search.php", {
      method:  "POST",
      headers: { "Content-Type": "application/json" },
      body:    JSON.stringify(payload),
    });
    if (!res.ok) throw new Error("search.php " + res.status);
    return res.json();
  }

  function renderPosts(posts) {
    resultsDiv.innerHTML = "";
    if (!posts.length) {
      resultsDiv.innerHTML =
        "<p style='color:orange;'>No posts found for this weather. Try another date or category.</p>";
      return;
    }
    resultsDiv.innerHTML = `<p style='color:green;'>Found ${posts.length} post(s)</p>`;
    posts.forEach(post => {
      const el = document.createElement("div");
      el.className = "post";
      const imgs = post.ImagePaths
        ? post.ImagePaths.split(",")
            .map(p => `<img src="${p}" style="max-width:200px;margin:5px;">`)
            .join("")
        : "";
      el.innerHTML = `
        <h3>${post.Type ?? "Unknown"}</h3>
        <p>${post.Description ?? ""}</p>
        <p><b>Weather:</b> ${post.Weather ?? "-"}</p>
        <p><b>Temperature:</b> ${post.Temperature ?? "-"}°C</p>
        <p><b>Location:</b> ${post.Adress ?? "-"}</p>
        <div>${imgs}</div><hr>`;
      resultsDiv.appendChild(el);
    });
  }

  /* ── Events ─────────────────────────────────────────────────────────── */
  slider.addEventListener("input", () => {
    datePrev.textContent = dayLabel(parseInt(slider.value, 10));
    renderBanner(); // forecast already loaded, just re-render for new day
  });

  // currentLocation.js already handles the checkbox toggle (show/hide
  // search-container, start geolocation). We only need to track the state
  // change here so activeCoords() knows which branch to use.
  useLocCb.addEventListener("change", () => {
    useGPS = useLocCb.checked;
    forecast = null;
    banner.style.display = "none";
    // If switching back to GPS and coords are ready, reload forecast.
    if (useGPS && window._cf_getGPSCoords()) loadForecast();
  });

  // #locationData is updated synchronously by autocomplete.js when a result
  // is clicked. We watch it with a MutationObserver so we can auto-load the
  // forecast the moment a manual location is selected.
  const locationDataEl = document.getElementById("locationData");
  new MutationObserver(() => {
    if (!useGPS && locationDataEl.value) loadForecast();
  }).observe(locationDataEl, { attributes: true, attributeFilter: ["value"] });

  // autocomplete.js sets locationData.value via JS assignment, not setAttribute,
  // so MutationObserver won't fire. Patch the value setter instead.
  const nativeDescriptor = Object.getOwnPropertyDescriptor(
    HTMLInputElement.prototype, "value"
  );
  Object.defineProperty(locationDataEl, "value", {
    get() { return nativeDescriptor.get.call(this); },
    set(v) {
      nativeDescriptor.set.call(this, v);
      if (!useGPS && v) loadForecast();
    },
  });

  findBtn.addEventListener("click", async () => {
    const coords = activeCoords();

    // If GPS is selected but coords aren't ready yet, load forecast first.
    if (!forecast && coords) {
      await loadForecast();
    }

    if (!forecast) {
      resultsDiv.innerHTML = useGPS
        ? "<p style='color:orange;'>Still waiting for location. Please allow location access and try again.</p>"
        : "<p style='color:orange;'>Please select a location first.</p>";
      return;
    }

    const day      = forecast[parseInt(slider.value, 10)];
    const category = document.getElementById("category-select").value;

    resultsDiv.innerHTML = "<p>Searching…</p>";
    try {
      renderPosts(await fetchPosts(day, category, coords?.label ?? null));
    } catch (e) {
      console.error(e);
      resultsDiv.innerHTML = "<p style='color:red;'>Search failed. Please try again.</p>";
    }
  });

  /* ── Poll for GPS coords on load ────────────────────────────────────── */
  // currentLocation.js fires geolocation asynchronously. We poll every 500 ms
  // until coords appear (max 10 s), then load the forecast automatically.
  let pollCount = 0;
  const pollGPS = setInterval(() => {
    pollCount++;
    if (pollCount > 20) { clearInterval(pollGPS); return; } // 10 s timeout

    if (useGPS && window._cf_getGPSCoords()) {
      clearInterval(pollGPS);
      loadForecast();
    }
  }, 500);

})();
</script>

<?php require_once "include/views/_footer.php"; ?>