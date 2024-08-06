<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

$db = getDB();
$result = [];
$songCount = 0;

if (isset($_GET["term"])) {
    $term = $_GET["term"];
    $locale = isset($_GET["locale"]) ? $_GET["locale"] : "en-US";
    $offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;
    $limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 5;

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
        $songCount = count($result['tracks']['hits']);
    } else {
        $result = [];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["song_key"]) && isset($_POST["users"])) {
        $song_key = $_POST["song_key"];
        $title = $_POST["title"];
        $artist = $_POST["artist"];
        $image_url = $_POST["image_url"];
        $rating = isset($_POST["rating"]) ? intval($_POST["rating"]) : NULL;
        $user_ids = $_POST["users"];

        $query = "INSERT INTO ShazamSongs (title, artist, song_key, image_url, user_id, rating) VALUES (:title, :artist, :song_key, :image_url, :user_id, :rating)";
        $stmt = $db->prepare($query);

        foreach ($user_ids as $user_id) {
            $params = [
                ":title" => $title,
                ":artist" => $artist,
                ":song_key" => $song_key,
                ":image_url" => $image_url,
                ":user_id" => $user_id,
                ":rating" => $rating
            ];
            try {
                $stmt->execute($params);
                flash("Track added successfully to user ID: $user_id", "success");
            } catch (PDOException $e) {
                flash("Error adding track to user ID: $user_id - " . $e->getMessage(), "danger");
            }
        }
    }
}

function get_users_by_username($db, $username) {
    $query = "SELECT id, username FROM Users WHERE username LIKE :username";
    $stmt = $db->prepare($query);
    $stmt->execute([":username" => "%$username%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_users_with_song($db, $song_key) {
    $query = "SELECT u.id, u.username FROM Users u JOIN ShazamSongs s ON u.id = s.user_id WHERE s.song_key = :song_key";
    $stmt = $db->prepare($query);
    $stmt->execute([":song_key" => $song_key]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container-fluid">
    <h1>Admin Song Management</h1>
    <p>Search for a song using the Shazam API and manage song assignments to users.</p>

    <!-- Song Search Form -->
    <form method="GET" class="mb-3">
        <div class="form-group">
            <label for="term">Search Term</label>
            <input type="text" name="term" id="term" class="form-control" value="<?php echo htmlspecialchars($term ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-group">
            <label for="locale">Locale</label>
            <input type="text" name="locale" id="locale" class="form-control" value="en-US">
        </div>
        <div class="form-group">
            <label for="offset">Offset</label>
            <input type="number" name="offset" id="offset" class="form-control" value="0">
        </div>
        <div class="form-group">
            <label for="limit">Limit</label>
            <input type="number" name="limit" id="limit" class="form-control" value="5">
        </div>
        <button type="submit" class="btn btn-primary">Fetch Tracks</button>
    </form>

    <!-- Search Results -->
    <?php if (isset($result) && isset($result['tracks']['hits'])) : ?>
        <p>Total Songs Found: <?php echo $songCount; ?></p>
        <div class="row">
            <?php foreach ($result['tracks']['hits'] as $track) : ?>
                <div class="col-md-4 mb-3">
                    <div class="card bg-dark text-light">
                        <img src="<?php echo htmlspecialchars($track['track']['images']['coverart'] ?? ''); ?>" alt="Cover Art" class="card-img-top img-fluid" style="max-height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($track['track']['title'] ?? ''); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($track['track']['subtitle'] ?? ''); ?></h6>
                            <p class="card-text">Key: <?php echo htmlspecialchars($track['track']['key'] ?? 'N/A'); ?></p>
                            
                            <!-- Display users who have saved this song -->
                            <?php
                            $users_with_song = get_users_with_song($db, $track['track']['key'] ?? '');
                            ?>
                            <h6>Users with this song:</h6>
                            <?php if (!empty($users_with_song)) : ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($users_with_song as $user) : ?>
                                        <li><?php echo htmlspecialchars($user['username']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p>None</p>
                            <?php endif; ?>

                            <!-- Username Search and Assignment Form -->
                            <form method="POST">
                                <input type="hidden" name="song_key" value="<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($track['track']['title'] ?? ''); ?>">
                                <input type="hidden" name="artist" value="<?php echo htmlspecialchars($track['track']['subtitle'] ?? ''); ?>">
                                <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($track['track']['images']['coverart'] ?? ''); ?>">

                                <div class="form-group">
                                    <label for="username_search_<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>">Search Username</label>
                                    <input type="text" name="username_search" id="username_search_<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>" class="form-control">
                                    <button type="submit" class="btn btn-primary mt-2">Search Users</button>
                                </div>
                            </form>

                            <?php
                            if (isset($_POST["username_search"])) {
                                $username = se($_POST, "username_search", "", false);
                                if (!empty($username)) {
                                    $users = get_users_by_username($db, $username);
                                } else {
                                    flash("Username must not be empty", "warning");
                                    $users = [];
                                }
                            }
                            ?>

                            <?php if (!empty($users)) : ?>
                                <form method="POST">
                                    <input type="hidden" name="song_key" value="<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>">
                                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($track['track']['title'] ?? ''); ?>">
                                    <input type="hidden" name="artist" value="<?php echo htmlspecialchars($track['track']['subtitle'] ?? ''); ?>">
                                    <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($track['track']['images']['coverart'] ?? ''); ?>">

                                    <div class="form-group">
                                        <label>Select Users</label>
                                        <?php foreach ($users as $user) : ?>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="users[]" value="<?php echo $user['id']; ?>">
                                                <label class="form-check-label"><?php echo htmlspecialchars($user['username']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="rating_<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>">Rating</label>
                                        <input type="number" name="rating" id="rating_<?php echo htmlspecialchars($track['track']['key'] ?? ''); ?>" min="0" max="5" step="1" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-light mt-2">Add to Selected Users</button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
