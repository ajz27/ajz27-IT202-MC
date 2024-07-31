<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $artist = $_POST["artist"];
    $song_key = $_POST["song_key"];
    $image_url = $_POST["image_url"];
    
    $db = getDB();
    $query = "INSERT INTO ShazamSongs (title, artist, song_key, image_url) VALUES (:title, :artist, :song_key, :image_url)";
    $params = [
        ":title" => $title,
        ":artist" => $artist,
        ":song_key" => $song_key,
        ":image_url" => $image_url
    ];

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Song added successfully", "success");
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            flash("A song with the same artist and title already exists", "warning");
        } else {
            error_log("Error adding song: " . var_export($e, true));
            flash("An error occurred", "danger");
        }
    }
}
//ajz27 7/29
?>

<div class="container-fluid">
    <h3>Add New Song</h3>
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Song Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
            <label for="artist" class="form-label">Artist</label>
            <input type="text" class="form-control" id="artist" name="artist" required>
        </div>
        <div class="mb-3">
            <label for="song_key" class="form-label">Song Key</label>
            <input type="text" class="form-control" id="song_key" name="song_key" required>
        </div>
        <div class="mb-3">
            <label for="image_url" class="form-label">Image URL</label>
            <input type="text" class="form-control" id="image_url" name="image_url" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Song</button>
    </form>
</div>
<!-- ajz27 7/29 -->

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
