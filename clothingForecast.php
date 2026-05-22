<?php
require_once "include/views/_header.php";
require_once "include/models/weatherInfo.php";
weatherIncludes();
?>

<div class="center">
  <div class="post-container">

    <h2>Clothing Forecast</h2>
    <form>
    <p class="subtitle">Pick a day and place — we'll show you what to wear.</p>

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
      <span id="locationCheckmark" class="location-checkmark"> ✓ Validated</span>
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

    <button id="find-btn" class="submit-btn">Find outfits</button>
  </div>

  <div id="cf-results" class="posts-section"></div>
</div>

<!--
  Load currentLocation.js first — it reads #use-location, #location-status,
  #search-container, #locationSearch and stores coords in userLatitude /
  userLongitude on the script's own scope. We reach those via the getGPSCoords()
  bridge defined below BEFORE the script loads.
-->
<script src="include/models/clothingForecastBridge.js"></script>

<script src="include/models/currentLocation.js"></script>

<!--
  autocomplete.js writes the chosen location as JSON into #locationData.
  It also binds to #postForm submit — our dummy form + onsubmit=false
  prevents any navigation.
-->
<script src="include/models/autocomplete.js"></script>

<script src="include/models/clothingForecast.js"></script>

<?php require_once "include/views/_footer.php"; ?>