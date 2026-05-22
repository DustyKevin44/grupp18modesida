// 1. Initialize the Search Provider with Nominatim-compliant headers

const provider = new window.GeoSearch.OpenStreetMapProvider({
  params: {
    "Accept-Language": "sv",
    email: "your-email@example.com",
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
    item.textContent = formatAddress(result.raw);

    item.addEventListener("click", () => {
      searchInput.value = formatAddress(result.raw);

      const placeType = result.raw.type || result.raw.addresstype || "unknown";

      const structuredData = {
        type: "search",
        label: formatAddress(result.raw), // ← fixed: was formatAddress(raw)
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
function formatAddress(raw) {
  const a = raw.address;

  // Extract city and street from display_name parts
  const parts = raw.display_name ? raw.display_name.split(", ") : [];
  // display_name: "ICA Folkes Livs, 8, Rackarbergsgatan, ..., Uppsala, ..."
  // Street is usually at index 2, city near the end before country
  const street = parts[2] || null;
  const houseNumber = parts[1] || null;
  const city = parts.find(p => ["Uppsala", "Stockholm", "Göteborg", "Malmö"].includes(p)) 
               || parts[parts.length - 4] 
               || null;
  const country = parts[parts.length - 1] || null;

  if (!a) {
    const name = raw.name || null;
    const streetWithNumber = [street, houseNumber].filter(Boolean).join(" ");
    return [name, streetWithNumber, city, country].filter(Boolean).join(", ");
  }

  const name = a.amenity || a.club || a.building || a.tourism || a.leisure || raw.name;
  const aStreet = a.road;
  const aHouseNumber = a.house_number;
  const aCity = a.city || a.town || a.village;
  const aCountry = a.country;

  const streetWithNumber = [aStreet, aHouseNumber].filter(Boolean).join(" ");
  if (name) return [name, streetWithNumber, aCity, aCountry].filter(Boolean).join(", ");
  return [streetWithNumber, aCity, aCountry].filter(Boolean).join(", ");
}

// 3. Form Submission Handling
if (form) {
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
          label: formatAddress(topResult.raw),
          lat: topResult.y,
          lon: topResult.x,
          place_type: placeType,
        };

        locationDataInput.value = JSON.stringify(structuredData);
        form.submit();
      } else {
        alert("Please select or type a valid, recognizable location.");
      }

      return;
    }

    // Case B: User left it blank. Fetch browser GPS location.
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
  });
}
