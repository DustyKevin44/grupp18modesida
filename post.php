<?php require_once "include/views/_header.php"; ?>

<!-- Load Leaflet and Geocoding CSS/JS (Free OpenStreetMap search) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
<script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/bundle.min.js"></script>

<style>
    /* Styling for the autocomplete dropdown container */
    .search-results-box {
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        width: 300px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
        border-radius: 4px;
        margin-top: 2px;
    }

    /* Styling for each individual item inside the dropdown */
    .search-item {
        padding: 10px;
        cursor: pointer;
        font-family: sans-serif;
        font-size: 14px;
        border-bottom: 1px solid #eee;
        color: #333;
        text-align: left;
    }

    .search-item:hover {
        background-color: #f0f0f0;
    }
</style>

<body>
    <main>
        <div class="center" id="container">
            <div class="post-container">
                <form name="add" id="postForm" action="post.php" method="post">
                    <h2>Create Post</h2>
                    
                    <!-- Hidden payload field for location data JSON string -->
                    <input type="hidden" name="location_data" id="locationData">

                    <label for="description">Description:</label>
                    <textarea
                        rows="5" cols="40"
                        name="description"
                        id="description"
                        placeholder="Tell us about yourself!"></textarea>
                    
                    <div style="margin-top: 15px; position: relative;">
                        <label for="locationSearch">Location:</label><br>
                        <input type="text" id="locationSearch" autocomplete="off" placeholder="Search for a place (e.g., University of Michigan)..." style="width: 300px;">
                        <span id="locationCheckmark" style="color: green; display: none;"> ✓ Validated</span>
                        
                        <!-- Dropdown container for search results -->
                        <div id="searchResults" class="search-results-box" style="display: none;"></div>
                        
                        <p style="font-size: 0.85em; color: #666; margin-top: 5px;">Leave blank to use your current GPS location.</p>
                    </div>

                    <input type="submit" value="Send" style="margin-top: 15px;" />
                </form>
            </div>
            <div class="post-container">
                <p>test</p>
            </div>
        </div>
    </main>
<style>
    /* Basic styling for the autocomplete dropdown */
    .search-results-box {
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        width: 300px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 px 4px rgba(0,0,0,0.1);
    }
    .search-item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 14px;
        border-bottom: 1px solid #eee;
    }
    .search-item:hover {
        background-color: #f0f0f0;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("locationSearch");
    const resultsBox = document.getElementById("searchResults");
    const checkmark = document.getElementById("locationCheckmark");
    const hiddenPayload = document.getElementById("locationData");
    const postForm = document.getElementById("postForm");

    let debounceTimeout;
    let selectedLocationData = null;

    // 1. Listen for typing in the location input
    searchInput.addEventListener("input", () => {
        const query = searchInput.value.trim();
        
        // Reset state if user clears input
        if (query.length < 3) {
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";
            checkmark.style.display = "none";
            selectedLocationData = null;
            return;
        }

        // Debounce API calls to save bandwidth and prevent rate-limiting
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            fetchLocations(query);
        }, 400); 
    });

    // 2. Fetch data from OpenStreetMap Nominatim (CORS-safe)
    async function fetchLocations(query) {
        // Nominatim requires a descriptive User-Agent or Email to prevent blocking
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`;
        
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            displayResults(data);
        } catch (error) {
            console.error("Error fetching location data:", error);
        }
    }

    // 3. Render results in the dropdown box
    function displayResults(results) {
        resultsBox.innerHTML = "";
        
        if (results.length === 0) {
            resultsBox.style.display = "none";
            return;
        }

        results.forEach(item => {
            const div = document.createElement("div");
            div.className = "search-item";
            div.textContent = item.display_name;
            
            // Handle clicking an item
            div.addEventListener("click", () => {
                searchInput.value = item.display_name;
                resultsBox.style.display = "none";
                checkmark.style.display = "inline"; // Visual validation
                
                // Store the relevant data to pass along later
                selectedLocationData = {
                    display_name: item.display_name,
                    latitude: item.lat,
                    longitude: item.lon
                };
            });
            
            resultsBox.appendChild(div);
        });

        resultsBox.style.display = "block";
    }

    // Close dropdown if user clicks anywhere else on the screen
    document.addEventListener("click", (e) => {
        if (e.target !== searchInput && e.target !== resultsBox) {
            resultsBox.style.display = "none";
        }
    });

    // 4. Handle Form Submission
    postForm.addEventListener("submit", async (e) => {
        // If the user typed something but didn't select from dropdown, clear data or force a check
        if (searchInput.value.trim() !== "" && !selectedLocationData) {
            alert("Please select a valid address from the dropdown list.");
            e.preventDefault();
            return;
        }

        // Fallback to Browser GPS if input is left blank
        if (searchInput.value.trim() === "") {
            e.preventDefault(); // Pause submission to get coordinates
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const fallbackData = {
                            display_name: "Current GPS Location",
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        };
                        hiddenPayload.value = JSON.stringify(fallbackData);
                        postForm.submit(); // Resume submission
                    },
                    (error) => {
                        alert("Could not retrieve GPS location. Submitting without coordinates.");
                        hiddenPayload.value = JSON.stringify({ error: "GPS denied or unavailable" });
                        postForm.submit();
                    }
                );
            } else {
                hiddenPayload.value = JSON.stringify({ error: "Geolocation not supported" });
                postForm.submit();
            }
        } else {
            // Address was chosen from dropdown. Package the data string into the hidden field.
            hiddenPayload.value = JSON.stringify(selectedLocationData);
        }
    });
});
</script>

<?php require_once "include/views/_footer.php"; ?>