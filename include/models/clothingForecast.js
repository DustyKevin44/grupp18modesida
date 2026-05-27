(function () {
  let forecast = null;
  let useGPS = true;

  const slider = document.getElementById("date-slider");
  const datePrev = document.getElementById("date-preview");
  const banner = document.getElementById("weather-banner");
  const wbIcon = document.getElementById("wb-icon");
  const wbLabel = document.getElementById("wb-label");
  const wbTemp = document.getElementById("wb-temp");
  const useLocCb = document.getElementById("use-location");
  const findBtn = document.getElementById("find-btn");
  const resultsDiv = document.getElementById("cf-results");

  const EMOJI = {
    "Clear sky": "☀️",
    "Mainly clear / Partly cloudy": "⛅",
    Overcast: "☁️",
    Foggy: "🌫️",
    Rainy: "🌧️",
    Snowy: "❄️",
    Thunderstorm: "⛈️",
  };

  function dayLabel(n) {
    if (n === 0) return "Today";
    if (n === 1) return "Tomorrow";
    const d = new Date();
    d.setDate(d.getDate() + n);
    return d.toLocaleDateString(undefined, {
      weekday: "long",
      month: "short",
      day: "numeric",
    });
  }

  function activeCoords() {
    if (useGPS) {
      const gps = window._cf_getGPSCoords();
      if (!gps) return null;
      return {
        lat: +gps.lat.toFixed(1),
        lon: +gps.lon.toFixed(1),
        label: null,
      };
    }
    const raw = document.getElementById("locationData").value;
    if (!raw) return null;
    try {
      const loc = JSON.parse(raw);
      if (loc.lat && loc.lon)
        return { lat: loc.lat, lon: loc.lon, label: loc.label ?? null };
    } catch (e) {
      return null;
    }
    return null;
  }

  async function loadForecast() {
    const c = activeCoords();
    if (!c) return;
    banner.style.display = "none";
    banner.classList.add("hidden");
    forecast = null;
    try {
      const res = await fetch("include/models/weatherInfo.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ lat: c.lat, lon: c.lon, days: 7 }),
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
    wbIcon.textContent = EMOJI[day.weather] ?? "🌡️";
    wbLabel.textContent = day.weather;
    wbTemp.textContent = `${day.tempMin}°–${day.tempMax}°C`;
    banner.classList.remove("hidden");
    banner.style.display = "flex";
  }

  async function fetchPosts(day, category, locationLabel) {
    const payload = {
      weather: day.weather,
      tempMin: day.tempMin,
      tempMax: day.tempMax,
    };
    if (category) payload.type = category;
    if (locationLabel)
      payload.location = JSON.stringify({ label: locationLabel });

    const res = await fetch("clothingForecast.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error("clothingForecast.php " + res.status);
    return res.json();
  }

  function renderPosts(posts) {
    resultsDiv.innerHTML = "";
    if (!posts.length) {
      resultsDiv.innerHTML =
        "<p class='message-warning'>No posts found for this weather. Try another date or category.</p>";
      return;
    }
    posts.forEach((post) => {
      const el = document.createElement("div");
      el.className = "post-card";
      const imgs = post.ImagePaths
        ? post.ImagePaths.split(",")
            .map((p) => `<img src="${p}" alt="Post image">`)
            .join("")
        : "";
      el.innerHTML = `
        <div class="post-header">
          <strong>${post.Type ?? "Unknown"}</strong>
          <span>${post.Adress ?? "Unknown"}</span>
          <span>${post.Username ?? "Unknown"}</span>
        </div>
        <div class="post-main">
          <div class="post-images">${imgs}</div>
          <div class="post-meta">
            <span>🌤 ${post.Weather ?? "-"}</span>
            <span>🌡 ${post.Temperature ?? "-"}°C</span>
            ${post.Private ? '<span class="private-tag">Private</span>' : ""}
          </div>
          <p class="post-desc">${post.Description ?? ""}</p>
        </div>`;
      resultsDiv.appendChild(el);
    });
  }

  slider.addEventListener("input", () => {
    datePrev.textContent = dayLabel(parseInt(slider.value, 10));
    renderBanner();
  });

  useLocCb.addEventListener("change", () => {
    useGPS = useLocCb.checked;
    forecast = null;
    banner.style.display = "none";
    if (useGPS && window._cf_getGPSCoords()) loadForecast();
  });

  const locationDataEl = document.getElementById("locationData");
  new MutationObserver(() => {
    if (!useGPS && locationDataEl.value) loadForecast();
  }).observe(locationDataEl, { attributes: true, attributeFilter: ["value"] });

  const nativeDescriptor = Object.getOwnPropertyDescriptor(
    HTMLInputElement.prototype,
    "value",
  );
  Object.defineProperty(locationDataEl, "value", {
    get() {
      return nativeDescriptor.get.call(this);
    },
    set(v) {
      nativeDescriptor.set.call(this, v);
      if (!useGPS && v) loadForecast();
    },
  });

  findBtn.addEventListener("click", async () => {
    const coords = activeCoords();
    if (!forecast && coords) await loadForecast();
    if (!forecast) {
      resultsDiv.innerHTML = useGPS
        ? "<p class='message-warning'>Still waiting for location. Please allow location access and try again.</p>"
        : "<p class='message-warning'>Please select a location first.</p>";
      return;
    }
    const day = forecast[parseInt(slider.value, 10)];
    const category = document.getElementById("category-select").value;
    resultsDiv.innerHTML = "<p class='message-info'>Searching…</p>";
    try {
      renderPosts(await fetchPosts(day, category, coords?.label ?? null));
    } catch (e) {
      console.error(e);
      resultsDiv.innerHTML =
        "<p class='message-error'>Search failed. Please try again.</p>";
    }
  });

  let pollCount = 0;
  const pollGPS = setInterval(() => {
    pollCount++;
    if (pollCount > 20) {
      clearInterval(pollGPS);
      return;
    }
    if (useGPS && window._cf_getGPSCoords()) {
      clearInterval(pollGPS);
      loadForecast();
    }
  }, 500);
})();
