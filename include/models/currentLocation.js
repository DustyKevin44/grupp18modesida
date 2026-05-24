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
    searchContainer.classList.remove("hidden");
    return;
  }

  locationStatus.textContent = "Locating...";
  locationStatus.style.color = "#aaa";

  navigator.geolocation.getCurrentPosition(
    (position) => {
      userLatitude = position.coords.latitude;
      userLongitude = position.coords.longitude;
      locationStatus.textContent = `Location locked (Lat: ${userLatitude.toFixed(2)}, Lon: ${userLongitude.toFixed(2)})`;
      locationStatus.style.color = "#fff";
    },
    (error) => {
      userLatitude = null;
      userLongitude = null;
      locationCheckbox.checked = false;
      searchContainer.classList.remove("hidden");

      if (error.code === error.PERMISSION_DENIED) {
        locationStatus.textContent =
          "Permission denied. Please enter manually.";
      } else {
        locationStatus.textContent = "Unable to retrieve your location.";
      }
      locationStatus.style.color = "#ffffff";
    },
  );
}

locationCheckbox.addEventListener("change", function () {
  if (this.checked) {
    searchContainer.classList.add("hidden");
    locationSearchInput.value = "";
    triggerGeolocation();
  } else {
    searchContainer.classList.remove("hidden");
    userLatitude = null;
    userLongitude = null;
    locationStatus.textContent = "";
  }
});

if (locationCheckbox.checked) {
  searchContainer.classList.add("hidden");
  triggerGeolocation();
} else {
  searchContainer.classList.remove("hidden");
}
