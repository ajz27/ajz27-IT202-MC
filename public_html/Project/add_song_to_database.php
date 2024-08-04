<?php
require(__DIR__ . "/../../partials/nav.php");

is_logged_in(true);

// retrieve user ID
$user_id = get_user_id();

$result = [];
if (isset($_GET["term"])) {
    
    $term = $_GET["term"];
    $locale = isset($_GET["locale"]) ? $_GET["locale"] : "en-US"; // default locale to "en-US"
    $offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0; // default offset to 0
    $limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 5; // default limit to 5

    $data = [
        "term" => $term,
        "locale" => $locale,
        "offset" => $offset,
        "limit" => $limit
    ];
    $endpoint = "https://shazam.p.rapidapi.com/search";
    $isRapidAPI = true;
    $rapidAPIHost = "shazam.p.rapidapi.com";
    
    $result = get($endpoint, "STOCK_API_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = getDB();
    $title = isset($_POST["title"]) ? $_POST["title"] : '';
    $artist = isset($_POST["artist"]) ? $_POST["artist"] : '';
    $song_key = isset($_POST["song_key"]) ? $_POST["song_key"] : '';
    $image_url = isset($_POST["image_url"]) ? $_POST["image_url"] : '';
    $rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : NULL; // Get rating from form

    $query = "INSERT INTO ShazamSongs (title, artist, song_key, image_url, user_id, rating) VALUES (:title, :artist, :song_key, :image_url, :user_id, :rating)";
    $params = [
        ":title" => $title,
        ":artist" => $artist,
        ":song_key" => $song_key,
        ":image_url" => $image_url,
        ":user_id" => $user_id,
        ":rating" => $rating
    ];

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Track added successfully", "success");
    } catch (PDOException $e) {
        flash("Error adding track: " . $e->getMessage(), "danger");
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
                <div class="col-md-4 mb-3">
                    <div class="card bg-dark text-light">
                        <img src="<?php echo htmlspecialchars($track['track']['images']['coverart'] ?? ''); ?>" alt="Cover Art" class="card-img-top img-fluid" style="max-height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($track['track']['title'] ?? ''); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($track['track']['subtitle'] ?? ''); ?></h6>
                            <p class="card-text">Key: <?php echo htmlspecialchars($track['track']['key'] ?? 'N/A'); ?></p>
                            <form method="POST">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($track['track']['title'] ?? ''); ?>">
                                <input type="hidden" name="artist" value="<?php echo htmlspecialchars($track['track']['subtitle'] ?? ''); ?>">
                                <input type="hidden" name="song_key" value="<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>">
                                <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($track['track']['images']['coverart'] ?? ''); ?>">
                                <label for="rating-<?php echo htmlspecialchars($track['track']['id'] ?? ''); ?>">Rating</label>
                                <?php
                                $song = [
                                    'rating' => 0, // Default rating, you can adjust based on your needs
                                    'id' => $track['track']['id'] ?? 0
                                ];
                                $readonly = false; // Make sure stars are clickable
                                include(__DIR__ . "/../../partials/star_rating.php");
                                ?>
                                <button type="submit" class="btn btn-light mt-2">Add to Database</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>
