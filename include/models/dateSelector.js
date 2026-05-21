const slider = document.getElementById("date-slider");
const preview = document.getElementById("date-preview");

const daysOfWeek = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

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
  const month = targetDate.toLocaleString("default", { month: "short" });
  const dateNum = targetDate.getDate();

  if (value === 1) {
    preview.innerText = `Tomorrow, (${dayName}, ${month} ${dateNum})`;
  } else {
    preview.innerText = `${dayName}, ${month} ${dateNum}`;
  }
}

// Listen for user dragging the slider
slider.addEventListener("input", updateDateDisplay);

// Run once on load to establish initial "Today" state
updateDateDisplay();
