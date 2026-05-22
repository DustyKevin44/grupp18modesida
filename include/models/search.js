const searchForm = document.getElementById("searchForm");
if (searchForm) {
  searchForm.addEventListener("submit", (event) => event.preventDefault());
}

const searchBtn = document.getElementById("searchBtn");
if (searchBtn) {
  searchBtn.addEventListener("click", async (e) => {
    e.preventDefault();
    const resultsDiv = document.getElementById("results");
    resultsDiv.innerHTML = "<p class='message-info'>Searching...</p>";

    const tempMin = document.getElementById("tempMin").value;
    const tempMax = document.getElementById("tempMax").value;

    const payload = {
      query: document.getElementById("query").value.trim(),
      weather: document.getElementById("weather").value,
      type: document.getElementById("type").value,
      location: document.getElementById("locationData").value,
    };

    if (tempMin !== "") payload.tempMin = parseInt(tempMin);
    if (tempMax !== "") payload.tempMax = parseInt(tempMax);

    try {
      const res = await fetch("search.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        console.error("Server error:", await res.text());
        resultsDiv.innerHTML =
          '<p class="message-error">Server error occurred.</p>';
        return;
      }

      let data;
      try {
        data = JSON.parse(await res.text());
      } catch (e) {
        resultsDiv.innerHTML =
          '<p class="message-error">Invalid server response.</p>';
        return;
      }

      resultsDiv.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        resultsDiv.innerHTML =
          '<p class="message-warning">No posts found for your filters.</p>';
        return;
      }

      data.forEach((post) => {
        const el = document.createElement("div");
        el.className = "post-card";

        const images = post.ImagePaths
          ? post.ImagePaths.split(",")
              .map((path) => `<img src="${path}" alt="Post image">`)
              .join("")
          : "";

        el.innerHTML = `
          <div class="post-header">
            <strong>${post.Type ?? "Unknown"}</strong>
            <span>${post.Adress ?? "Unknown"}</span>
          </div>
          <div class="post-main">
            <div class="post-images">${images}</div>
            <div class="post-meta">
              <span>🌤 ${post.Weather ?? "-"}</span>
              <span>🌡 ${post.Temperature ?? "-"}°C</span>
              ${post.Private ? '<span class="private-tag">Private</span>' : ""}
            </div>
            <p class="post-desc">${post.Description ?? ""}</p>
          </div>`;

        resultsDiv.appendChild(el);
      });
    } catch (err) {
      console.error("Fetch failed:", err);
      resultsDiv.innerHTML =
        '<p class="message-error">Network error. Please try again.</p>';
    }
  });
}
