<?php

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT id, title, artist, song_key, image_url FROM ShazamSongs ORDER BY created DESC LIMIT 25";
$db = getDB();
$stmt = $db->prepare($query);
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
    "ignored_columns" => [],  // Do not ignore any columns
    "edit_url" => get_url("admin/edit_song.php"),
    "view_url" => get_url("admin/view_song.php")  // URL for the View button
];
?>
<div class="container-fluid">
    <h3>List Songs</h3>
    <?php render_table($table); ?>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
