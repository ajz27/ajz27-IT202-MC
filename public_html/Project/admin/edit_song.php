<?php

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}


$song = [];
$isValid = false;

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM ShazamSongs WHERE id = :id");
    try {
        $stmt->execute([":id" => $id]);
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($song) {
            $isValid = true;
        }
    } catch (PDOException $e) {
        error_log("Error fetching song: " . var_export($e, true));
        flash("Error loading song", "danger");
    }
}

if (isset($_POST["save"]) && $isValid) {
    $title = se($_POST, "title", "", false);
    $Artist = se($_POST, "Artist", "", false);
    $song_key = se($_POST, "song_key", "", false);
    $image_url = se($_POST, "image_url", "", false);

    $db = getDB();
    $stmt = $db->prepare("UPDATE ShazamSongs SET title = :title, Artist = :Artist, song_key = :song_key, image_url = :image_url WHERE id = :id");
    try {
        $stmt->execute([
            ":title" => $title,
            ":Artist" => $Artist,
            ":song_key" => $song_key,
            ":image_url" => $image_url,
            ":id" => $id
        ]);
        flash("Updated song details successfully", "success");
        header("Location: list_songs.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating song: " . var_export($e, true));
        flash("Error updating song", "danger");
    }
}

if (isset($_POST["delete"]) && $isValid) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM ShazamSongs WHERE id = :id");
    try {
        $stmt->execute([":id" => $id]);
        flash("Deleted song successfully", "success");
        header("Location: list_songs.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting song: " . var_export($e, true));
        flash("Error deleting song", "danger");
    }
}
?>

<div class="container-fluid">
    <h3>Edit Song</h3>
    <?php if ($isValid) : ?>
        <form method="POST">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="<?php se($song, 'title'); ?>" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="Artist">Artist</label>
                <input type="text" name="Artist" id="Artist" value="<?php se($song, 'Artist'); ?>" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="song_key">Song Key</label>
                <input type="text" name="song_key" id="song_key" value="<?php se($song, 'song_key'); ?>" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="image_url">Cover Art URL</label>
                <input type="text" name="image_url" id="image_url" value="<?php se($song, 'image_url'); ?>" class="form-control" required />
            </div>
            <div class="form-group">
                <button type="submit" name="save" class="btn btn-primary">Save Changes</button>
                <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this song?');">Delete Song</button>
            </div>
        </form>
    <?php else : ?>
        <p>Invalid song selected.</p>
    <?php endif; ?>
</div>
<!-- ajz27 7/29 -->

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
