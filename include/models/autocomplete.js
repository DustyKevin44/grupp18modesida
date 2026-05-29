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
      const category = placeTypeToCategory(placeType);

      const structuredData = {
        type: "search",
        label: formatAddress(result.raw),
        lat: result.y,
        lon: result.x,
        place_type: category,
      };

      locationDataInput.value = JSON.stringify(structuredData);
      checkmark.classList.add("visible");
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
  checkmark.classList.remove("visible");
  locationDataInput.value = "";
  resultsBox.innerHTML = "";
  resultsBox.style.display = "none";
}

function placeTypeToCategory(rawType) {
  const type = String(rawType).toLowerCase();

  if (
    type.includes("school") ||
    type.includes("university") ||
    type.includes("college") ||
    type.includes("kindergarten") ||
    type.includes("academy") ||
    type.includes("library")
  ) {
    return "school";
  }

  if (
    type.includes("restaurant") ||
    type.includes("cafe") ||
    type.includes("coffee") ||
    type.includes("fast_food") ||
    type.includes("food") ||
    type.includes("bistro")
  ) {
    return "restaurant";
  }

  if (
    type.includes("bar") ||
    type.includes("pub") ||
    type.includes("nightclub") ||
    type.includes("brewery")
  ) {
    return "bar";
  }

  if (
    type.includes("beach") ||
    type.includes("seaside") ||
    type.includes("shore") ||
    type.includes("coast")
  ) {
    return "beach";
  }

  if (
    type.includes("gym") ||
    type.includes("fitness") ||
    type.includes("sports") ||
    type.includes("health") ||
    type.includes("studio")
  ) {
    return "gym";
  }

  if (
    type.includes("museum") ||
    type.includes("theatre") ||
    type.includes("cinema") ||
    type.includes("gallery") ||
    type.includes("monument") ||
    type.includes("park") ||
    type.includes("garden") ||
    type.includes("zoo") ||
    type.includes("tourism") ||
    type.includes("leisure")
  ) {
    return "culture";
  }

  if (
    type.includes("hotel") ||
    type.includes("hostel") ||
    type.includes("motel") ||
    type.includes("lodging")
  ) {
    return "other";
  }

  if (type === "gps" || type === "search" || type === "unknown") {
    return "other";
  }

  return "other";
}
function formatAddress(raw) {
  const a = raw.address;

  const parts = raw.display_name ? raw.display_name.split(", ") : [];
  const street = parts[2] || null;
  const houseNumber = parts[1] || null;
  const city =
    parts.find((p) =>
      ["Uppsala", "Stockholm", "Göteborg", "Malmö"].includes(p),
    ) ||
    parts[parts.length - 4] ||
    null;
  const country = parts[parts.length - 1] || null;

  if (!a) {
    const name = raw.name || null;
    const streetWithNumber = [street, houseNumber].filter(Boolean).join(" ");
    return [name, streetWithNumber, city, country].filter(Boolean).join(", ");
  }

  const name =
    a.amenity || a.club || a.building || a.tourism || a.leisure || raw.name;
  const aStreet = a.road;
  const aHouseNumber = a.house_number;
  const aCity = a.city || a.town || a.village;
  const aCountry = a.country;

  const streetWithNumber = [aStreet, aHouseNumber].filter(Boolean).join(" ");
  if (name)
    return [name, streetWithNumber, aCity, aCountry].filter(Boolean).join(", ");
  return [streetWithNumber, aCity, aCountry].filter(Boolean).join(", ");
}

if (form) {
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const query = searchInput.value.trim();

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
        const category = placeTypeToCategory(placeType);

        const structuredData = {
          type: "search",
          label: formatAddress(topResult.raw),
          lat: topResult.y,
          lon: topResult.x,
          place_type: category,
        };

        locationDataInput.value = JSON.stringify(structuredData);
        form.submit();
      } else {
        alert("Please select or type a valid, recognizable location.");
      }

      return;
    }

    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const structuredData = {
            type: "gps",
            label: "Current Location",
            lat: position.coords.latitude,
            lon: position.coords.longitude,
            place_type: placeTypeToCategory("gps"),
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
