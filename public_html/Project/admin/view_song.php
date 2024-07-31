<?php
// Include necessary navigation and utility functions
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

// Initialize variables
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
?>

<div class="container-fluid">
    <h3>View Song</h3>
    <?php if ($isValid) : ?>
        <div class="song-details">
            <h4>Title: <?php echo htmlspecialchars($song['title']); ?></h4>
            <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
            <p>Song Key: <?php echo htmlspecialchars($song['song_key']); ?></p>
            <p>Cover Art URL: <?php echo htmlspecialchars($song['image_url']); ?></p>
            <img src="<?php echo htmlspecialchars($song['image_url']); ?>" alt="Cover Art" style="max-width: 200px;"/>
        </div>
    <?php else : ?>
        <p>Invalid song selected.</p>
    <?php endif; ?>
    <a href="list_songs.php" class="btn btn-secondary">Back to List</a>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
