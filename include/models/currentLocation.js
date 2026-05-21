const locationCheckbox = document.getElementById("use-location");
const locationStatus = document.getElementById("location-status");
const searchContainer = document.getElementById("search-container");
const locationSearchInput = document.getElementById("locationSearch");

let userLatitude = null;
let userLongitude = null;

function triggerGeolocation() {
  if (!navigator.geolocation) {
    locationStatus.textContent =
      "Geolocation is not supported by your browser.";
    locationStatus.style.color = "#ff4d4d";
    locationCheckbox.checked = false;
    searchContainer.classList.remove("hidden"); // Show search bar as fallback
    return;
  }

  locationStatus.textContent = "Locating...";
  locationStatus.style.color = "#aaa";

  navigator.geolocation.getCurrentPosition(
    (position) => {
      userLatitude = position.coords.latitude;
      userLongitude = position.coords.longitude;
      locationStatus.textContent = `Location locked (Lat: ${userLatitude.toFixed(2)}, Lon: ${userLongitude.toFixed(2)})`;
      locationStatus.style.color = "#4df";
    },
    (error) => {
      userLatitude = null;
      userLongitude = null;
      locationCheckbox.checked = false;
      searchContainer.classList.remove("hidden"); // Show search bar as fallback

      if (error.code === error.PERMISSION_DENIED) {
        locationStatus.textContent =
          "Permission denied. Please enter manually.";
      } else {
        locationStatus.textContent = "Unable to retrieve your location.";
      }
      locationStatus.style.color = "#ff4d4d";
    },
  );
}

// Watch for the checkbox toggles
locationCheckbox.addEventListener("change", function () {
  if (this.checked) {
    searchContainer.classList.add("hidden"); // Hide search bar smoothly
    locationSearchInput.value = ""; // Clear typed values
    triggerGeolocation();
  } else {
    searchContainer.classList.remove("hidden"); // Reveal left-aligned search bar
    userLatitude = null;
    userLongitude = null;
    locationStatus.textContent = "";
  }
});

// Run once on load to sync initial state
if (locationCheckbox.checked) {
  searchContainer.classList.add("hidden");
  triggerGeolocation();
} else {
  searchContainer.classList.remove("hidden");
}
