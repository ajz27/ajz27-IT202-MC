<?php
require(__DIR__ . "/../../../partials/nav.php");

// Check if the user has admin privileges
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

$db = getDB();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    flash("Invalid user ID", "danger");
    die(header("Location: " . get_url("admin/admin_list_users.php")));
}

// Check if the delete request for a song was made
if (isset($_POST['delete_song'])) {
    $song_id = isset($_POST['song_id']) ? intval($_POST['song_id']) : 0;

    if ($song_id > 0) {
        try {
            // Delete the song
            $delete_song_query = "DELETE FROM ShazamSongs WHERE id = :song_id AND user_id = :user_id";
            $delete_song_stmt = $db->prepare($delete_song_query);
            $delete_song_stmt->bindParam(':song_id', $song_id, PDO::PARAM_INT);
            $delete_song_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $delete_song_stmt->execute();

            flash("Song deleted successfully", "success");
            // Refresh the page
            header("Location: " . get_url("admin/view_user.php?id=" . $user_id));
            exit;
        } catch (PDOException $e) {
            error_log("Error deleting song: " . var_export($e, true));
            flash("Unhandled error occurred while deleting the song", "danger");
        }
    }
}

// Check if the add song request was made
if (isset($_POST['add_song'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $artist = isset($_POST['artist']) ? trim($_POST['artist']) : '';
    $song_key = isset($_POST['song_key']) ? trim($_POST['song_key']) : '';
    $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (!empty($title) && !empty($artist) && !empty($song_key)) {
        try {
            // Add the song
            $add_song_query = "INSERT INTO ShazamSongs (title, artist, song_key, image_url, rating, user_id) VALUES (:title, :artist, :song_key, :image_url, :rating, :user_id)";
            $add_song_stmt = $db->prepare($add_song_query);
            $add_song_stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $add_song_stmt->bindParam(':artist', $artist, PDO::PARAM_STR);
            $add_song_stmt->bindParam(':song_key', $song_key, PDO::PARAM_STR);
            $add_song_stmt->bindParam(':image_url', $image_url, PDO::PARAM_STR);
            $add_song_stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $add_song_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $add_song_stmt->execute();

            flash("Song added successfully", "success");
            // Refresh the page
            header("Location: " . get_url("admin/view_user.php?id=" . $user_id));
            exit;
        } catch (PDOException $e) {
            error_log("Error adding song: " . var_export($e, true));
            flash("Unhandled error occurred while adding the song", "danger");
        }
    } else {
        flash("Please fill in all required fields", "warning");
    }
}

// Fetch user details
$user_query = "SELECT id, username FROM Users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

$user = [];
try {
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        flash("User not found", "danger");
        die(header("Location: " . get_url("admin/admin_list_users.php")));
    }
} catch (PDOException $e) {
    error_log("Error fetching user details: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

// Query to fetch songs associated with the selected user
$songs_query = "SELECT id, title, artist, song_key, image_url, rating FROM ShazamSongs WHERE user_id = :user_id";
$songs_stmt = $db->prepare($songs_query);
$songs_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

$songs = [];
try {
    $songs_stmt->execute();
    $songs = $songs_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user's songs: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>
<div class="container-fluid">
    <h3>User Details</h3>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <h4>Songs Associated with This User</h4>
    <?php if ($songs): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Key</th>
                    <th>Image</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($song['title']); ?></td>
                        <td><?php echo htmlspecialchars($song['artist']); ?></td>
                        <td><?php echo htmlspecialchars($song['song_key']); ?></td>
                        <td>
                            <img src="<?php echo htmlspecialchars($song['image_url']); ?>" alt="Cover Art" style="max-height: 100px;">
                        </td>
                        <td><?php echo htmlspecialchars($song['rating']); ?></td>
                        <td>
                            <!-- Delete button for each song -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this song?');">
                                <input type="hidden" name="song_id" value="<?php echo htmlspecialchars($song['id']); ?>" />
                                <input type="submit" name="delete_song" value="Delete" class="btn btn-danger btn-sm" />
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No songs found for this user.</p>
    <?php endif; ?>

    <!-- Form to add a new song -->
    <h4>Add New Song</h4>
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="artist" class="form-label">Artist</label>
            <input type="text" name="artist" id="artist" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="song_key" class="form-label">Song Key</label>
            <input type="text" name="song_key" id="song_key" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="image_url" class="form-label">Image URL</label>
            <input type="text" name="image_url" id="image_url" class="form-control">
        </div>
        <div class="mb-3">
            <label for="rating" class="form-label">Rating</label>
            <input type="number" name="rating" id="rating" class="form-control" min="0" max="5" step="1" value="0">
        </div>
        <input type="submit" name="add_song" value="Add Song" class="btn btn-primary">
    </form>
    
    <a href="<?php echo get_url('admin/admin_list_users.php'); ?>" class="btn btn-primary mt-3">Back to User List</a>
</div>
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
