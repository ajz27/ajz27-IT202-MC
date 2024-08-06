<?php
require_once(__DIR__ . "/../../partials/nav.php");
//is_logged_in(true); // Commented out to allow public profiles
$user_id = -1;
try {
    $user_id = (int)se($_GET, "id", -1, false);
} catch (Exception $e) {
    // Handle data format issue
}
if ($user_id < 1) {
    $user_id = get_user_id(); // Get our ID if we're logged in
}
$is_me = $user_id == get_user_id();
$is_edit = isset($_GET["edit"]);
?>
<?php
if ($is_me && $is_edit && isset($_POST["save"])) {
    $email = se($_POST, "email", null, false);
    $username = se($_POST, "username", null, false);
    $hasError = false;
    // Sanitize
    $email = sanitize_email($email);
    // Validate
    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }
    if (!is_valid_username($username)) {
        flash("Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
        $hasError = true;
    }
    if (!$hasError) {
        $params = [":email" => $email, ":username" => $username, ":id" => get_user_id()];
        $db = getDB();
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
        try {
            $stmt->execute($params);
            flash("Profile saved", "success");
        } catch (PDOException $e) {
            users_check_duplicate($e->errorInfo);
        }
        // Select fresh data from table
        $stmt = $db->prepare("SELECT id, email, username from Users where id = :id LIMIT 1");
        try {
            $stmt->execute([":id" => get_user_id()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION["user"]["email"] = $user["email"];
                $_SESSION["user"]["username"] = $user["username"];
            } else {
                flash("User doesn't exist", "danger");
            }
        } catch (Exception $e) {
            flash("An unexpected error occurred, please try again", "danger");
        }
    }

    // Check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        $hasError = false;
        if (!is_valid_password($new_password)) {
            flash("Password too short", "danger");
            $hasError = true;
        }
        if (!$hasError) {
            if ($new_password === $confirm_password) {
                $stmt = $db->prepare("SELECT password from Users where id = :id");
                try {
                    $stmt->execute([":id" => get_user_id()]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (isset($result["password"])) {
                        if (password_verify($current_password, $result["password"])) {
                            $query = "UPDATE Users set password = :password where id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([
                                ":id" => get_user_id(),
                                ":password" => password_hash($new_password, PASSWORD_BCRYPT)
                            ]);
                            flash("Password reset", "success");
                        } else {
                            flash("Current password is invalid", "warning");
                        }
                    }
                } catch (PDOException $e) {
                    echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
                }
            } else {
                flash("New passwords don't match", "warning");
            }
        }
    }
}
?>

<?php
$user = [];
$songs = [];
if ($user_id > 0) {
    $db = getDB();
    // Fetch user profile
    $query = "SELECT email, username, created FROM Users where id = :user_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":user_id" => $user_id]);
        $r = $stmt->fetch();
        if ($r) {
            $user = $r;
        } else {
            flash("Couldn't find user profile", "warning");
        }
    } catch (PDOException $e) {
        error_log("Error fetching user: " . var_export($e, true));
        flash("Error fetching user", "danger");
    }
    
    // Fetch songs associated with the user
    $query = "SELECT id, title, artist, song_key, image_url, rating FROM ShazamSongs WHERE user_id = :user_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":user_id" => $user_id]);
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching songs: " . var_export($e, true));
        flash("Error fetching songs", "danger");
    }
}
?>
<div class="container-fluid">
    <?php if ($is_me && $is_edit) : ?>
        <a class="btn btn-secondary btn-sm" href="?">View</a>
        <form method="POST" onsubmit="return validate(this);">
            <?php render_input(["type" => "email", "id" => "email", "name" => "email", "label" => "Email", "value" => se($user, "email", "", false), "rules" => ["required" => true]]); ?>
            <?php render_input(["type" => "text", "id" => "username", "name" => "username", "label" => "Username", "value" => se($user, "username", "", false), "rules" => ["required" => true, "maxlength" => 30]]); ?>
            <!-- DO NOT PRELOAD PASSWORD -->
            <div class="lead">Password Reset</div>
            <?php render_input(["type" => "password", "id" => "cp", "name" => "currentPassword", "label" => "Current Password", "rules" => ["minlength" => 8]]); ?>
            <?php render_input(["type" => "password", "id" => "np", "name" => "newPassword", "label" => "New Password", "rules" => ["minlength" => 8]]); ?>
            <?php render_input(["type" => "password", "id" => "conp", "name" => "confirmPassword", "label" => "Confirm Password", "rules" => ["minlength" => 8]]); ?>
            <?php render_input(["type" => "hidden", "name" => "save"]); /* lazy value to check if form submitted, not ideal */ ?>
            <?php render_button(["text" => "Update Profile", "type" => "submit"]); ?>
        </form>
    <?php else : ?>
        <?php if ($is_me) : ?>
            <a class="btn btn-secondary btn-sm" href="?edit">Edit</a>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="h4">Username: <?php se($user, "username"); ?></div>
                <div class="text-body">Joined: <?php se($user, "created"); ?></div>
            </div>
        </div>
        <h3>Songs</h3>
        <div class="row">
            <?php if (!empty($songs)) : ?>
                <?php foreach ($songs as $song) : ?>
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
                        
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No songs found.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function validate(form) {
        let pw = form.newPassword.value;
        let con = form.confirmPassword.value;
        let isValid = true;

        // Example of using flash via JavaScript
        // Find the flash container, create a new element, appendChild
        if (pw !== con) {
            flash("Password and Confirm password must match", "warning");
            isValid = false;
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
