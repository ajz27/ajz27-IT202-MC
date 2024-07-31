<?php

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}


$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25; // default limit to 25
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'ASC'; // default sort order to ASC

// validate sort order
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

$query = "SELECT id, title, artist, song_key, image_url FROM ShazamSongs ORDER BY title $sort_order LIMIT :limit";
$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
//ajz27 7/29
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
    "title" => "Latest Songs",
    "ignored_columns" => [],  
    "edit_url" => get_url("admin/edit_song.php"),
    "view_url" => get_url("admin/view_song.php")  
];
?>
<div class="container-fluid">
    <h3>List Songs</h3>
    <form method="GET" class="mb-3">
        <div class="form-group">
            <label for="limit">Number of Entries</label>
            <input type="number" name="limit" id="limit" value="<?php echo htmlspecialchars($limit); ?>" class="form-control" min="1" />
        </div>
        <div class="form-group">
            <label for="sort">Sort Order</label>
            <select name="sort" id="sort" class="form-control">
                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <input type="submit" value="Apply" class="btn btn-primary" />
    </form>
    <?php render_table($table); ?>
</div>
<!-- ajz27 7/29 -->
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
