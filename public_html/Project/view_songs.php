<?php
require(__DIR__ . "/../../partials/nav.php");

is_logged_in(true); // Ensure the user is logged in

// Get the database connection
$db = getDB();

$user_id = get_user_id(); // Get the currently logged-in user's ID

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // default limit to 10
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'title'; // default sort by title
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC'; // default sort order to ASC

// Validate sort order
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Validate sort by field
$valid_sort_by = ['title', 'rating'];
$sort_by = in_array($sort_by, $valid_sort_by) ? $sort_by : 'title';

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['delete_song_id'])) {
        $delete_song_id = intval($_POST['delete_song_id']);
        $delete_query = "DELETE FROM ShazamSongs WHERE id = :id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id', $delete_song_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        try {
            $delete_stmt->execute();
            flash("Song deleted successfully", "success");
        } catch (PDOException $e) {
            error_log("Error deleting song: " . var_export($e, true));
            flash("Error deleting song", "danger");
        }
    } elseif (isset($_POST['delete_all'])) {
        $delete_all_query = "DELETE FROM ShazamSongs WHERE user_id = :user_id";
        $delete_all_stmt = $db->prepare($delete_all_query);
        $delete_all_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        try {
            $delete_all_stmt->execute();
            flash("All songs deleted successfully", "success");
        } catch (PDOException $e) {
            error_log("Error deleting all songs: " . var_export($e, true));
            flash("Error deleting all songs", "danger");
        }
    }
}

// Query to fetch the total number of songs associated with the currently logged-in user
$count_query = "SELECT COUNT(*) as total FROM ShazamSongs WHERE user_id = :user_id";
$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

$total_songs = 0;
try {
    $count_stmt->execute();
    $total_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    if ($total_result) {
        $total_songs = $total_result['total'];
    }
} catch (PDOException $e) {
    error_log("Error fetching total song count: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

// Query to fetch songs associated with the currently logged-in user
$query = "SELECT id, title, artist, song_key, image_url, rating FROM ShazamSongs WHERE user_id = :user_id ORDER BY $sort_by $sort_order LIMIT :limit";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching songs: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = [
    "data" => $results,
    "title" => "Your Songs",
    "ignored_columns" => [],
    "view_url" => get_url("view_song.php") // URL to the song detail page
];
?>
<div class="container-fluid">
    <h3>List of Your Songs</h3>
    <p>Total number of entries: <?php echo $total_songs; ?></p>
    <form method="GET" class="mb-3">
        <div class="form-group">
            <label for="limit">Number of entries to show</label>
            <input type="number" name="limit" id="limit" value="<?php echo htmlspecialchars($limit); ?>" class="form-control" min="1" />
        </div>
        <div class="form-group">
            <label for="sort_by">Sort By</label>
            <select name="sort_by" id="sort_by" class="form-control">
                <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Rating</option>
            </select>
        </div>
        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <select name="sort_order" id="sort_order" class="form-control">
                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <input type="submit" value="Apply" class="btn btn-primary" />
    </form>
    <form method="POST" class="mb-3">
        <button type="submit" name="delete_all" class="btn btn-danger">Delete All Songs</button>
    </form>
    <div class="row">
        <?php if (isset($results) && !empty($results)) : ?>
            <?php foreach ($results as $song) : ?>
                <div class="track mb-3">
                    <h2><?php echo htmlspecialchars($song['title']); ?></h2>
                    <p><?php echo htmlspecialchars($song['artist']); ?></p>
                    <p>Key: <?php echo htmlspecialchars($song['song_key'] ?? 'N/A'); ?></p>
                    <div>
                        <?php
                        $readonly = true; // Set to true to make stars readonly
                        require(__DIR__ . "/../../partials/star_rating.php");
                        ?>
                    </div>
                    <img src="<?php echo htmlspecialchars($song['image_url']); ?>" alt="Cover Art" style="max-width: 150px; height: auto;" />
                    <a href="view_song.php?id=<?php echo htmlspecialchars($song['id']); ?>" class="btn btn-info mt-2">View Details</a>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_song_id" value="<?php echo htmlspecialchars($song['id']); ?>">
                        <button type="submit" class="btn btn-danger mt-2">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No songs found.</p>
        <?php endif; ?>
    </div>
</div>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
