// 1. Initialize the Search Provider with Nominatim-compliant headers

const provider = new window.GeoSearch.OpenStreetMapProvider({
  params: {
    "Accept-Language": "sv",
    email: "dustykevin44@gmail.com",
  },
});

const searchInput = document.getElementById("locationSearch");
const locationDataInput = document.getElementById("locationData");
const checkmark = document.getElementById("locationCheckmark");
const resultsBox = document.getElementById("searchResults");
const form = document.getElementById("postForm");

let debounceTimer;

function debounce(func, delay) {
  return function (...args) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => func.apply(this, args), delay);
  };
}

// 2. Autocomplete API trigger wrapped in a 1-second debounce handler
searchInput.addEventListener(
  "input",
  debounce(async (e) => {
    const query = e.target.value.trim();

    if (query.length < 3) {
      resetLocationSelection();
      return;
    }

    try {
      const results = await provider.search({ query: query });
      renderResults(results);
    } catch (error) {
      console.error("Geocoding error:", error);
    }
  }, 1000),
);

// Render the results into the dropdown box
function renderResults(results) {
  resultsBox.innerHTML = "";

  if (results.length === 0) {
    resultsBox.style.display = "none";
    return;
  }

  resultsBox.style.display = "block";

  results.forEach((result) => {
    const item = document.createElement("div");
    item.className = "search-item";
    item.textContent = result.label;

    item.addEventListener("click", () => {
      searchInput.value = result.label;

      const placeType = result.raw.type || result.raw.addresstype || "unknown";

      const structuredData = {
        type: "search",
        label: result.label,
        lat: result.y,
        lon: result.x,
        place_type: placeType,
      };

      locationDataInput.value = JSON.stringify(structuredData);
      checkmark.style.display = "inline";
      resultsBox.style.display = "none";
    });

    resultsBox.appendChild(item);
  });
}

document.addEventListener("click", function (e) {
  if (e.target !== searchInput && e.target !== resultsBox) {
    resultsBox.style.display = "none";
  }
});

function resetLocationSelection() {
  checkmark.style.display = "none";
  locationDataInput.value = "";
  resultsBox.innerHTML = "";
  resultsBox.style.display = "none";
}

// 3. Form Submission Handling
form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const query = searchInput.value.trim();

  // Case A: User typed a location
  if (query !== "") {
    if (locationDataInput.value !== "") {
      form.submit();
      return;
    }

    const results = await provider.search({ query: query });
    if (results.length > 0) {
      const topResult = results[0];
      const placeType =
        topResult.raw?.type || topResult.raw?.addresstype || "unknown";

      const structuredData = {
        type: "search",
        label: topResult.label,
        lat: topResult.y,
        lon: topResult.x,
        place_type: placeType,
      };

      locationDataInput.value = JSON.stringify(structuredData);
      form.submit();
    } else {
      alert("Please select or type a valid, recognizable location.");
    }
  }
  // Case B: User left it blank. Fetch browser GPS location.
  else {
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const structuredData = {
            type: "gps",
            label: "Current Location",
            lat: position.coords.latitude,
            lon: position.coords.longitude,
            place_type: "gps",
          };
          locationDataInput.value = JSON.stringify(structuredData);
          form.submit();
        },
        (error) => {
          alert(
            "Location access denied or unavailable. Please type a location manually.",
          );
        },
        { enableHighAccuracy: true, timeout: 5000 },
      );
    } else {
      alert(
        "Your browser does not support geolocation. Please type a location.",
      );
    }
  }
});
