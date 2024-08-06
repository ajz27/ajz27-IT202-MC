<?php
require(__DIR__ . "/../../partials/nav.php");

// check if the user is logged in
is_logged_in(true);

$song_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($song_id <= 0) {
    flash("Invalid song ID", "danger");
    die(header("Location: " . get_url("view_songs.php")));
}

$db = getDB();
$song = [];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['rating'])) {
    $new_rating = intval($_POST['rating']);
    if ($new_rating < 1 || $new_rating > 5) {
        flash("Rating must be between 1 and 5", "warning");
    } else {
        $query = "UPDATE ShazamSongs SET rating = :rating WHERE id = :id AND user_id = :user_id";
        $params = [":rating" => $new_rating, ":id" => $song_id, ":user_id" => get_user_id()];

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            flash("Rating updated successfully", "success");
        } catch (PDOException $e) {
            error_log("Error updating rating: " . var_export($e, true));
            flash("Error updating rating", "danger");
        }
    }
}

// retrieve song details
$query = "SELECT title, artist, song_key, image_url, rating FROM ShazamSongs WHERE id = :id AND user_id = :user_id";
$params = [":id" => $song_id, ":user_id" => get_user_id()];

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$song) {
        flash("Song not found or you don't have access", "danger");
        die(header("Location: " . get_url("view_songs.php")));
    }
} catch (PDOException $e) {
    error_log("Error fetching song: " . var_export($e, true));
    flash("Error fetching song details", "danger");
}
?>

<div class="container mt-4">
    <div class="card bg-dark text-light">
        <img src="<?php echo htmlspecialchars($song['image_url']); ?>" class="card-img-top img-fluid" alt="Cover Art" style="max-height: 300px; width: auto;">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($song['title']); ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($song['artist']); ?></h6>
            <p class="card-text">Song Key: <?php echo htmlspecialchars($song['song_key']); ?></p>
            <p class="card-text">Current Rating: <?php echo htmlspecialchars($song['rating']) ?: 'No rating'; ?>/5</p>

            <form method="POST">
                <?php
                $readonly = false; // Ensure the stars are clickable
                include(__DIR__ . "/../../partials/star_rating.php");
                ?>
                <button type="submit" class="btn btn-light mt-3">Update Rating</button>
            </form>

            <a href="<?php echo get_url('view_songs.php'); ?>" class="btn btn-light mt-3">Back to Songs</a>
        </div>
    </div>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>
