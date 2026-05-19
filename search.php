<?php require_once "include/views/_header.php"; ?>

<body>

<div class="center">
  <div class="post-container">
    <h2>Schedule a Date</h2>
    
    <form id="schedule-form">
      <label for="date-slider">Select a delivery day:</label>
      
      <input 
        type="range" 
        id="date-slider" 
        name="selected-day" 
        min="0" 
        max="6" 
        value="0" 
        step="1" 
        list="tickmarks"
      >
      
      <datalist id="tickmarks">
        <option value="0" label="Today"></option>
        <option value="1"></option>
        <option value="2"></option>
        <option value="3"></option>
        <option value="4"></option>
        <option value="5"></option>
        <option value="6" label="1 Week"></option>
      </datalist>
      
      <div id="date-preview">Today</div>
      
      <button type="submit" class="submit-btn">Confirm</button>
    </form>
  </div>
</div>

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
  
  if (value === 6) {
    preview.innerText = `In 1 Week (${dayName}, ${month} ${dateNum})`;
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