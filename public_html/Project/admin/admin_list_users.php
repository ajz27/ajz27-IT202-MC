<?php
require(__DIR__ . "/../../../partials/nav.php");

// Check if the user has admin privileges
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

$db = getDB();

$search_term = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25; // default limit to 25
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'ASC'; // default sort order to ASC

// Validate sort order
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Build query to fetch users
$query = "SELECT id, username FROM Users";
$params = [];

// Add search condition if search_term is provided
if (!empty($search_term)) {
    $query .= " WHERE id LIKE :search_term OR username LIKE :search_term";
    $params[':search_term'] = "%$search_term%";
}

// Add sorting and limit
$query .= " ORDER BY username $sort_order LIMIT :limit";
$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

// Bind search_term if it is used
if (isset($params[':search_term'])) {
    $stmt->bindParam(':search_term', $params[':search_term'], PDO::PARAM_STR);
}

$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching users: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

// Prepare the table configuration
$table = [
    "data" => $results,
    "title" => "User List",
    "ignored_columns" => [],  
    "view_url" => get_url("admin/view_user.php")  // URL to the user detail page
];
?>
<div class="container-fluid">
    <h3>Admin User List</h3>
    <form method="GET" class="mb-3">
        <div class="form-group" id="search_value">
            <label for="user_id">User ID or Username</label>
            <input type="text" name="user_id" id="user_id" value="<?php echo htmlspecialchars($search_term ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" />
        </div>
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
    <?php 
    // Render the table with view links
    if (!empty($results)) {
        echo '<table class="table table-bordered">';
        echo '<thead><tr><th>ID</th><th>Username</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $user) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($user['id']) . '</td>';
            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
            echo '<td><a href="' . get_url('admin/view_user.php?user_id=' . urlencode($user['id'])) . '">View</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No users found.</p>';
    }
    ?>
</div>
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
