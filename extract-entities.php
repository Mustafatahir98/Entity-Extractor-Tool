<?php
/*
Template Name: Entity Extractor
*/
// get_header(); 

// Check if user is logged in


// API keys
$textrazor_api_key = '';  // Your Textrazor API Key
$google_api_key = '';  // Your Google API Key
$search_engine_id = '';  // Your Search Engine ID

// Regular expression patterns for filtering
$date_pattern = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3}\+\d{2}:\d{2}/';
$irrelevant_patterns = '/\b(all rights reserved|pay television|email|the guardian|united states|\d+|faq|cost|contract|scam|manufacturing|luxury car|[\d]+)\b/i';

// Handle URL-based entity extraction
function extract_entities_from_url($url) {
    global $textrazor_api_key, $date_pattern, $irrelevant_patterns;

    // Prepare cURL request to TextRazor API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.textrazor.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['extractors' => 'entities,topics', 'url' => $url]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-textrazor-key: ' . $textrazor_api_key,
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Get API response
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    // Filter entities based on regular expression criteria
    $entities = [];
    if (!empty($data['response']['entities'])) {
        foreach ($data['response']['entities'] as $entity) {
            if (!preg_match($date_pattern, $entity['entityId']) && !preg_match($irrelevant_patterns, $entity['entityId'])) {
                $entities[] = $entity['entityId'];
            }
        }
    }
    
    return array_count_values($entities);  // Return entities with count
}

// Fetch top search results using Google Custom Search API
function get_search_results($keyword, $num_results = 10) {
    global $google_api_key, $search_engine_id;

    // Prepare Google Custom Search API request
    $search_url = "https://www.googleapis.com/customsearch/v1?key=$google_api_key&cx=$search_engine_id&q=" . urlencode($keyword) . "&num=$num_results";
    
    // cURL setup
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['items'])) {
        return array_column($data['items'], 'link');
    } else {
        return [];
    }
}

// Fetch URLs from a sitemap
function fetch_urls_from_sitemap($sitemap_url) {
    $xml = simplexml_load_file($sitemap_url);
    $urls = [];
    if ($xml !== false) {
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }
    }
    return $urls;
}

// Process POST requests from the frontend (URL, keyword, or sitemap)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = [];

    // Extract entities based on URL
    if (isset($_POST['url'])) {
        $url = $_POST['url'];
        $result = ['url_entities_map' => [ $url => extract_entities_from_url($url) ]];

    // Search results based on a keyword
    } elseif (isset($_POST['keyword'])) {
        $keyword = $_POST['keyword'];
        $num_results = isset($_POST['num_results']) ? $_POST['num_results'] : 10;
        $result = ['urls' => get_search_results($keyword, $num_results)];
    
    // Sitemap extraction
    } elseif (isset($_POST['sitemap_url'])) {
        $sitemap_url = $_POST['sitemap_url'];
        $urls = fetch_urls_from_sitemap($sitemap_url);
        
        foreach ($urls as $url) {
            $result['url_entities_map'][$url] = extract_entities_from_url($url);
        }
    }

    echo json_encode($result);
    exit;
}

get_header(); 
// HTML Section Below:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entity Extractor Tool</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
   <style>

:root {
    --primary-color: #047cba; /* Blue */
    --secondary-color: #007bff; /* Light blue */
    --accent-color: #ffcc00; /* Yellow */
    --bg-color: #ffffff; /* White background */
    --text-color: #333333; /* Dark gray text */
    --border-color: #e0e0e0; /* Light gray border */
}

body {
    /* font-family: 'Roboto', sans-serif; */
    background-color: var(--bg-color);
    color: var(--text-color);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    /* display: flex; */
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin-top: 2rem;
    margin-bottom: 2rem;
    border: 2px solid var(--border-color);
}

h1 {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
}

.tool-section {
    background: var(--bg-color); /* White */
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color); /* Light gray border */
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.tool-section:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.tool-section h2 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 5px;
    color: var(--text-color);
    padding: 0.75rem;
    width: 100%;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}

.btn {
    background-color: var(--primary-color); /* Blue */
    border: none;
    border-radius: 5px;
    color: var(--bg-color); /* White text */
    cursor: pointer;
    font-weight: bold;
    padding: 0.75rem 1.5rem;
    text-transform: uppercase;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 1rem;
}

.btn:hover {
    background-color: var(--secondary-color); /* Light blue */
    transform: scale(1.05);
}

#loading-spinner {
    display: none;
    width: 50px;
    height: 50px;
    border: 4px solid var(--border-color); /* Lighter border */
    border-top: 4px solid var(--primary-color); /* Primary color for top border */
    border-radius: 50%;
    animation: spin 1s linear infinite; /* This makes the spinner rotate */
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.visible-spinner {
    display: block !important;
}


.results-section {
    display: none;
    background: var(--bg-color); /* White */
    border-radius: 10px;
    padding: 2rem;
    margin-top: 2rem;
    border: 1px solid var(--border-color); /* Light gray border */
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

.results-section h2 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 1rem;
}

#results-list {
    list-style-type: none;
    padding: 0;
}

#results-list li {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 5px;
    margin-bottom: 0.5rem;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

#results-list li:hover {
    background: rgba(0, 0, 0, 0.1);
}

#results-list a {
    color: var(--primary-color);
    text-decoration: none;
}

#entities-table {
    width: 100%;
    /* border-collapse: separate; */
    border-spacing: 0 0.5rem;
}

#entities-table th,
#entities-table td {
    background: rgba(0, 0, 0, 0.05);
    padding: 1rem;
    text-align: left;
}

#entities-table th {
    background: rgba(0, 0, 0, 0.1);
    color: var(--primary-color);
}

#entities-table tr {
    transition: all 0.3s ease;
}

#entities-table tr:hover td {
    background: rgba(0, 0, 0, 0.1);
}

#export-csv-btn {
    margin-top: 1rem;
    background-color: #27ae60;
    padding: 0.75rem 1.25rem;
    border-radius: 5px;
    border: none;
    color: #fff; /* Black text */
    cursor: pointer;
    transition: all 0.3s ease;
}

#export-csv-btn:hover {
    background-color: var(--primary-color); /* Blue */
    color: var(--bg-color); /* White */
    opacity: 0.9;
}

.newsletter-popup h2{
    font-weight: 600;
}

h3 {
    font-weight: bold;
    text-transform: capitalize;
    font-size: 25px;
}
   </style>
</head>
<body>
    <div class="container">
        <h1>Entity Extractor Tool</h1>

        <!-- URL Form -->
        <form id="entity-form">
            <div class="form-group">
                <label for="url">Enter a URL:</label>
                <input type="url" class="form-control" id="url" placeholder="https://example.com">
                <button type="submit" class="btn btn-primary mt-3">Check Entities</button>
            </div>
        </form>

        <!-- Keyword Form -->
        <form id="keyword-form" class="mt-3">
            <div class="form-group">
                <label for="keyword">Enter a Keyword:</label>
                <input type="text" class="form-control" id="keyword" placeholder="Enter a keyword">
                <button type="button" class="btn btn-primary mt-3" id="check-top10-btn">Check Top 10 Search Results</button>
            </div>
        </form>

        <!-- Sitemap Form -->
        <form id="sitemap-form" class="mt-3">
            <div class="form-group">
                <label for="sitemap-url">Enter a Sitemap URL:</label>
                <input type="url" class="form-control" id="sitemap-url" placeholder="https://example.com/sitemap.xml">
                <button type="submit" class="btn btn-primary mt-3">Check Entities for Sitemap</button>
            </div>
            <div class="spinner-border text-primary" role="status" id="loading-spinner">
                <span class="sr-only">Loading...</span>
            </div>
        </form>

        <!-- Button for Exporting CSV -->
        <button id="export-csv-btn" class="btn btn-success mt-3">Export to CSV</button>

        <!-- Display Results -->
        <div class="mt-5">
            <h3>Results</h3>
            <ul id="results-list" class="list-group"></ul>
            <table id="entities-table" class="table mt-3">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Entity</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <footer>
        <!-- <p>Entity Extractor Tool &copy; 2024 | Designed for Efficiency</p> -->
    </footer>

   
    <script>
        let entityData = [];  // To store data for CSV export

// Handle URL entity form submission
document.getElementById('entity-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const url = document.getElementById('url').value;
    if (url) {
        fetchEntities(url);
    }
});

// Handle keyword search form submission
document.getElementById('check-top10-btn').addEventListener('click', function() {
    const keyword = document.getElementById('keyword').value;
    if (keyword) {
        fetchSearchResults(keyword, 10);
    }
});

// Handle sitemap form submission (show spinner while loading)
document.getElementById('sitemap-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const sitemapUrl = document.getElementById('sitemap-url').value;
    if (sitemapUrl) {
        document.getElementById('loading-spinner').classList.add('visible-spinner');
        fetchSitemapEntities(sitemapUrl);
    }
});

function fetchEntities(url) {
    const formData = new FormData();
    formData.append('url', url);

    fetch('https://www.personaldrivers.com/wp-content/themes/generatepress_child/EET/extract-entities.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => displayEntities(data.url_entities_map));
    // .catch(error => console.error('Error:', error));
}

function fetchSearchResults(keyword, numResults) {
    const formData = new FormData();
    formData.append('keyword', keyword);
    formData.append('num_results', numResults);

    fetch('https://www.personaldrivers.com/wp-content/themes/generatepress_child/EET/extract-entities.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => displaySearchResults(data.urls))
    .catch(error => console.error('Error:', error));
}

// Handle sitemap form submission (show spinner while loading)
document.getElementById('sitemap-form').addEventListener('submit', function(event) {
event.preventDefault();
const sitemapUrl = document.getElementById('sitemap-url').value;
const spinner = document.getElementById('loading-spinner');
if (sitemapUrl) {
spinner.classList.add('visible-spinner'); // Show spinner
fetchSitemapEntities(sitemapUrl);
}
});

function fetchSitemapEntities(sitemapUrl) {
const formData = new FormData();
formData.append('sitemap_url', sitemapUrl);

fetch('', {
method: 'POST',
body: formData,
})
.then(response => response.json())
.then(data => {
displayEntities(data.url_entities_map);
document.getElementById('loading-spinner').classList.remove('visible-spinner'); // Hide spinner
})
.catch(error => {
console.error('Error:', error);
document.getElementById('loading-spinner').classList.remove('visible-spinner'); // Hide spinner on error
});
}


function displayEntities(data) {
    const tableBody = document.querySelector('#entities-table tbody');
    tableBody.innerHTML = '';
    entityData = [];  // Reset entity data

    Object.keys(data).forEach(url => {
        const entities = data[url];
        Object.entries(entities).forEach(([entity, count]) => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${url}</td><td>${entity}</td><td>${count}</td>`;
            tableBody.appendChild(row);

            // Collect data for CSV export
            entityData.push({ url, entity, count });
        });
    });
}

function displaySearchResults(urls) {
    const resultsList = document.getElementById('results-list');
    resultsList.innerHTML = '';  // Clear previous results

    urls.forEach(url => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = '#';
        a.textContent = url;
        li.appendChild(a);

        // Prevent default link behavior and fetch entities instead
        a.addEventListener('click', function(event) {
            event.preventDefault();
            fetchEntities(url);
        });

        resultsList.appendChild(li);
    });
}

// Export the entityData to CSV
document.getElementById('export-csv-btn').addEventListener('click', function() {
    if (entityData.length === 0) {
        alert("No data to export!");
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,URL,Entity,Count\n";

    entityData.forEach(dataItem => {
        csvContent += `${dataItem.url},${dataItem.entity},${dataItem.count}\n`;
    });

    // Create a download link and trigger it
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "entities.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);  // Clean up
});
    </script>
    


    
</body>
</html>


<?php
if (!is_user_logged_in()) {
    auth_redirect();
    exit;
}
get_footer();
?>