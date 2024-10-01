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

    fetch('extract-entities.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => displayEntities(data.url_entities_map))
    .catch(error => console.error('Error:', error));
}

function fetchSearchResults(keyword, numResults) {
    const formData = new FormData();
    formData.append('keyword', keyword);
    formData.append('num_results', numResults);

    fetch('extract-entities.php', {
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

fetch('extract-entities.php', {
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