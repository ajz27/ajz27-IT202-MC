<?php
require(__DIR__ . "/../../partials/nav.php");

$result = [];
if (isset($_GET["term"])) {
    // Extract parameters from the GET request
    $term = $_GET["term"];
    $locale = isset($_GET["locale"]) ? $_GET["locale"] : "en-US"; // Default locale to "en-US"
    $offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0; // Default offset to 0
    $limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 5; // Default limit to 5

    $data = [
        "term" => $term,
        "locale" => $locale,
        "offset" => $offset,
        "limit" => $limit
    ];
    $endpoint = "https://shazam.p.rapidapi.com/search";
    $isRapidAPI = true;
    $rapidAPIHost = "shazam.p.rapidapi.com";

    // Use a function similar to get() for API requests
    $result = get($endpoint, "STOCK_API_KEY", $data, $isRapidAPI, $rapidAPIHost);

    // Example of cached data to save the results, uncomment if testing with cached data
    /*
    $result = [
        "status" => 200,
        "response" => '{
            "tracks": {
                "hits": [
                    {
                        "track": {
                            "title": "Track Title 1",
                            "subtitle": "Artist Name",
                            "images": {
                                "coverart": "https://example.com/image1.jpg"
                            },
                            "key": "Track Key 1"
                        }
                    },
                    {
                        "track": {
                            "title": "Track Title 2",
                            "subtitle": "Artist Name",
                            "images": {
                                "coverart": "https://example.com/image2.jpg"
                            },
                            "key": "Track Key 2"
                        }
                    }
                ]
            }
        }'
    ];
    */
    
    error_log("Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}
?>
<div class="container-fluid">
    <h1>Top Tracks</h1>
    <p>Fetch the top tracks from Shazam based on your search criteria.</p>
    <form>
        <div>
            <label>Search Term</label>
            <input name="term" />
            <label>Locale</label>
            <input name="locale" value="en-US" />
            <label>Offset</label>
            <input name="offset" type="number" value="0" />
            <label>Limit</label>
            <input name="limit" type="number" value="5" />
            <input type="submit" value="Fetch Tracks" />
        </div>
    </form>
    <div class="row">
        <?php if (isset($result) && isset($result['tracks']['hits'])) : ?>
            <?php foreach ($result['tracks']['hits'] as $track) : ?>
                <div class="track">
                    <h2><?php echo htmlspecialchars($track['track']['title']); ?></h2>
                    <p><?php echo htmlspecialchars($track['track']['subtitle']); ?></p>
                    <p>Key: <?php echo htmlspecialchars($track['track']['key'] ?? 'N/A'); ?></p>
                    <img src="<?php echo htmlspecialchars($track['track']['images']['coverart']); ?>" alt="Cover Art" />
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>
