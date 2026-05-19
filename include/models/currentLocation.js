const locationCheckbox = document.getElementById("use-location");
const locationStatus = document.getElementById("location-status");

// Hidden variables to store coordinates for your form submission
let userLatitude = null;
let userLongitude = null;

locationCheckbox.addEventListener("change", function () {
  if (this.checked) {
    // Check if the browser supports Geolocation API
    if (!navigator.geolocation) {
      locationStatus.textContent =
        "Geolocation is not supported by your browser.";
      locationStatus.style.color = "#ff4d4d"; // Red error
      this.checked = false;
      return;
    }

    locationStatus.textContent = "Locating...";
    locationStatus.style.color = "#aaa";

    navigator.geolocation.getCurrentPosition(
      // Success Callback
      (position) => {
        userLatitude = position.coords.latitude;
        userLongitude = position.coords.longitude;
        locationStatus.textContent = `Location locked (Lat: ${userLatitude.toFixed(2)}, Lon: ${userLongitude.toFixed(2)})`;
        locationStatus.style.color = "#4df"; // Matches your blue date-preview text accent
      },
      // Error Callback
      (error) => {
        userLatitude = null;
        userLongitude = null;
        this.checked = false;

        if (error.code === error.PERMISSION_DENIED) {
          locationStatus.textContent =
            "Permission denied. Please enable location settings.";
        } else {
          locationStatus.textContent = "Unable to retrieve your location.";
        }
        locationStatus.style.color = "#ff4d4d";
      },
    );
  } else {
    // Clear data if unchecked
    userLatitude = null;
    userLongitude = null;
    locationStatus.textContent = "";
  }
});
