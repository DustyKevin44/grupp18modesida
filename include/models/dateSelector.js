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

  const targetDate = new Date();
  targetDate.setDate(targetDate.getDate() + value);

  const dayName = daysOfWeek[targetDate.getDay()];
  const month = targetDate.toLocaleString("default", { month: "short" });
  const dateNum = targetDate.getDate();

  if (value === 1) {
    preview.innerText = `Tomorrow, (${dayName}, ${month} ${dateNum})`;
  } else {
    preview.innerText = `${dayName}, ${month} ${dateNum}`;
  }
}

slider.addEventListener("input", updateDateDisplay);

updateDateDisplay();
