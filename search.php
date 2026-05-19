<?php require_once "include/views/_header.php"; 
require_once "include/models/weatherInfo.php"?>

<body>

<div class="center">
  <div class="post-container">
    <h2>Search outfit</h2>
    
    <form id="schedule-form">
      <label for="date-slider">Select a date:</label>
      
      <input 
        type="range" 
        id="date-slider" 
        name="selected-day" 
        min="0" 
        max="7" 
        value="0" 
        step="1" 
        list="tickmarks"
      >
      
      <datalist id="tickmarks">
        <option value="0" label="Today"></option>
        <option value="1" label="Tomorrow"></option>
        <option value="2"></option>
        <option value="3"></option>
        <option value="4"></option>
        <option value="5"></option>
        <option value="6"></option>
        <option value="7"></option>
      </datalist>
      
      <div id="date-preview">Today</div>

      <label for="category-select">Select a category:</label>
      <select id="category-select" name="category" required>
        <option value="" disabled selected>-- Choose an option --</option>
        <option value="Education">School</option>
        <option value="Sustenance">Bar/Resturant</option>
        <option value="Entertainment, Arts & Culture">Culture</option>
        <option value="Other">Other</option>
      </select>

      <div class="location-group">
        <label class="checkbox-container">
          <input type="checkbox" id="use-location" name="use-location" checked>
          Use my current location
        </label>
        <div id="location-status"></div>
      </div>

      <div id="search-container" class="hidden" style="margin-top: 15px; position: relative;">
        <label for="locationSearch">Location:</label>
        <input type="text" id="locationSearch" placeholder="Search for a place (e.g., University of Michigan)..." style="width: 300px;" autocomplete="off">
        <span id="locationCheckmark" style="color: green; display: none;"> ✓ Validated</span>
        
        <div id="searchResults" class="search-results-box"></div>
        
        <p style="font-size: 0.85em; color: #666;">Leave blank to use your current GPS location.</p>
      </div>
      
      <button type="submit" class="submit-btn">Confirm</button>
    </form>
  </div>
</div>

<script src="include/models/currentLocation.js"></script>
<script src="include/models/autocomplete.js"></script>

<script>
const slider = document.getElementById('date-slider');
const preview = document.getElementById('date-preview');

const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function updateDateDisplay() {
  const value = parseInt(slider.value);
  
  if (value === 0) {
    preview.innerText = "Today";
    return;
  }
  
  // Calculate future date based on slider value
  const targetDate = new Date();
  targetDate.setDate(targetDate.getDate() + value);
  
  // Format day name and date (e.g., "Thursday, May 21")
  const dayName = daysOfWeek[targetDate.getDay()];
  const month = targetDate.toLocaleString('default', { month: 'short' });
  const dateNum = targetDate.getDate();
  
  if (value === 1) {
    preview.innerText = `Tomorrow, (${dayName}, ${month} ${dateNum})`;
  } else {
    preview.innerText = `${dayName}, ${month} ${dateNum}`;
  }
}

// Listen for user dragging the slider
slider.addEventListener('input', updateDateDisplay);

// Run once on load to establish initial "Today" state
updateDateDisplay();
</script>

<?php require_once "include/views/_footer.php"; ?>