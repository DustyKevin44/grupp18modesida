<?php require_once "include/views/_header.php"; ?>

<!-- Load Leaflet and Geocoding CSS/JS (Free OpenStreetMap search) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
<script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/bundle.min.js"></script>

<div class="center">
<div class="post-container">
<form id="postForm" action="submit_post.php" method="POST">
    <!-- Hidden fields for automated/structured data -->
    <input type="hidden" name="location_data" id="locationData">
    
    <div>
        <label for="description">Description:</label><br>
        <textarea name="description" id="description" required></textarea>
    </div>

    <div>
        <label for="private">Private Post:</label>
        <input type="checkbox" name="private" value="1" id="private">
    </div>

    <div style="margin-top: 15px; position: relative;">
        <label for="locationSearch">Location:</label><br>
        <input type="text" id="locationSearch" placeholder="Search for a place (e.g., University of Michigan)..." style="width: 300px;" autocomplete="off">
        <span id="locationCheckmark" style="color: green; display: none;"> ✓ Validated</span>
        
        <!-- Results dropdown menu container -->
        <div id="searchResults" class="search-results-box"></div>
        
        <p style="font-size: 0.85em; color: #666;">Leave blank to use your current GPS location.</p>
    </div>

    <button type="submit" style="margin-top: 15px;">Create Post</button>
</form>
</div>
</div>

<script>
// 1. Initialize the Search Provider with Nominatim-compliant headers
const provider = new window.GeoSearch.OpenStreetMapProvider({
    params: {
        // REQUIRED BY POLICY: Identify your app. Replace with your actual email or unique app identifier.
        'Accept-Language': 'sv', 
        'email': 'dustykevin44@gmail.com' 
    }
});

const searchInput = document.getElementById('locationSearch');
const locationDataInput = document.getElementById('locationData');
const checkmark = document.getElementById('locationCheckmark');
const resultsBox = document.getElementById('searchResults');
const form = document.getElementById('postForm');

let debounceTimer;

// Debounce helper: Delays executing the inner function until 1000ms after the last keystroke
function debounce(func, delay) {
    return function (...args) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(this, args), delay);
    };
}

// 2. Autocomplete API trigger wrapped in a 1-second debounce handler
searchInput.addEventListener('input', debounce(async (e) => {
    const query = e.target.value.trim();
    
    if (query.length < 3) {
        resetLocationSelection();
        return;
    }

    try {
        // Query the free geocoding API safely complying with 1 req/sec limit
        const results = await provider.search({ query: query });
        renderResults(results);
    } catch (error) {
        console.error("Geocoding error:", error);
    }
}, 1000)); // 1000ms = 1 second of absolutely no input

// Render the results into the dropdown box
function renderResults(results) {
    resultsBox.innerHTML = '';
    
    if (results.length === 0) {
        resultsBox.style.display = 'none';
        return;
    }

    resultsBox.style.display = 'block';
    
    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'search-item';
        item.textContent = result.label;
        
        // Handle when a user selects a location from the dropdown
        item.addEventListener('click', () => {
            searchInput.value = result.label;
            
            const structuredData = {
                type: "search",
                label: result.label,
                lat: result.y,
                lon: result.x
            };
            
            locationDataInput.value = JSON.stringify(structuredData);
            checkmark.style.display = 'inline';
            resultsBox.style.display = 'none';
        });
        
        resultsBox.appendChild(item);
    });
}

// Close the dropdown list if clicking anywhere outside the search container
document.addEventListener('click', function (e) {
    if (e.target !== searchInput && e.target !== resultsBox) {
        resultsBox.style.display = 'none';
    }
});

function resetLocationSelection() {
    checkmark.style.display = 'none';
    locationDataInput.value = '';
    resultsBox.innerHTML = '';
    resultsBox.style.display = 'none';
}

// 3. Form Submission Handling
form.addEventListener('submit', async function(e) {
    e.preventDefault(); 

    const query = searchInput.value.trim();

    // Case A: User typed a location
    if (query !== "") {
        // If they chose from dropdown, locationDataInput will already be set
        if (locationDataInput.value !== "") {
            form.submit();
            return;
        }

        // If they just typed something and hit Enter without choosing, do one fallback verification lookup
        const results = await provider.search({ query: query });
        if (results.length > 0) {
            const topResult = results[0];
            const structuredData = {
                type: "search",
                label: topResult.label,
                lat: topResult.y,
                lon: topResult.x
            };
            
            locationDataInput.value = JSON.stringify(structuredData);
            form.submit();
        } else {
            alert("Please select or type a valid, recognizable location.");
        }
    } 
    // Case B: User left it blank. Fetch browser GPS location.
    else {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const structuredData = {
                        type: "gps",
                        label: "Current Location",
                        lat: position.coords.latitude,
                        lon: position.coords.longitude
                    };
                    locationDataInput.value = JSON.stringify(structuredData);
                    form.submit();
                },
                (error) => {
                    alert("Location access denied or unavailable. Please type a location manually.");
                },
                { enableHighAccuracy: true, timeout: 5000 }
            );
        } else {
            alert("Your browser does not support geolocation. Please type a location.");
        }
    }
});
</script>

<?php require_once "include/views/_footer.php"; ?>