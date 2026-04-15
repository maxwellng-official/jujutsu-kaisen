<?php
// profile.php — Logged-in creator's profile page
// Allows updating name, email, website, and profile picture

include 'db.php';
requireLogin();

$myId   = (int)$_SESSION['creator_id'];
$msg    = "";
$msgCol = "green";

// ── Fetch current profile data ───────────────────────────────
$stmt    = $mysqli->prepare("SELECT * FROM creator WHERE idcreator = ?");
$stmt->bind_param("i", $myId);
$stmt->execute();
$creator = $stmt->get_result()->fetch_assoc();

// ── Handle profile update form ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $website = trim($_POST['website'] ?? '');

    if (empty($name) || empty($email)) {
        $msg    = "Name and email are required fields.";
        $msgCol = "red";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg    = "Please enter a valid email address.";
        $msgCol = "red";

    } else {
        // Keep existing image by default
        $imageurl = $creator['imageurl'];

        // Handle optional new profile picture upload
        if (!empty($_FILES['imageurl']['name'])) {
            $target_dir  = "uploads/";
            $target_file = $target_dir . basename($_FILES['imageurl']['name']);
            $check       = getimagesize($_FILES['imageurl']['tmp_name']);

            if (!empty($_FILES['imageurl']['name'])) {
                $target_dir      = "uploads/";
                $target_filename = basename($_FILES['imageurl']['name']);
                $target_file     = $target_dir . $target_filename;
                $check           = getimagesize($_FILES['imageurl']['tmp_name']);
                $imageFileType   = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                if ($check === false) {
                    $msg    = "Profile picture must be a valid image file.";
                    $msgCol = "red";

                } elseif ($_FILES['imageurl']['size'] > 5242880) {
                    $msg    = "Profile picture must be under 5 MB.";
                    $msgCol = "red";

                } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $msg    = "Only JPG, JPEG, PNG, and GIF files are allowed.";
                    $msgCol = "red";

                } elseif (file_exists($target_file)) {
                    // Avoid overwriting existing files
                    $target_file = $target_dir . time() . "_" . $target_filename;
                    if (move_uploaded_file($_FILES['imageurl']['tmp_name'], $target_file)) {
                        $imageurl = $target_file;
                    } else {
                        $msg    = "Error uploading profile picture. Please try again.";
                        $msgCol = "red";
                    }

                } elseif (move_uploaded_file($_FILES['imageurl']['tmp_name'], $target_file)) {
                    $imageurl = $target_file;

                } else {
                    $msg    = "Error uploading profile picture. Please try again.";
                    $msgCol = "red";
                }
            }
        }

        if ($msg === "") {
            $update = $mysqli->prepare(
                "UPDATE creator
                 SET name = ?, email = ?, website = ?, imageurl = ?
                 WHERE idcreator = ?"
            );
            $update->bind_param("ssssi", $name, $email, $website, $imageurl, $myId);

            if ($update->execute()) {
                // Keep session name in sync
                $_SESSION['creator_name'] = $name;

                // Reflect changes immediately in the displayed form
                $creator['name']     = $name;
                $creator['email']    = $email;
                $creator['website']  = $website;
                $creator['imageurl'] = $imageurl;

                $msg = "Profile updated successfully!";
            } else {
                $msg    = "Error: " . $mysqli->error;
                $msgCol = "red";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>
        <div class="nav-links">
            <a href="add-photo.php" class="nav-btn">+ Post</a>
            <a href="add-album.php">Albums</a>
            <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name']) ?></a>
            <a href="logout.php" class="nav-logout">Log out</a>
        </div>
    </nav>

    <div class="container">

        <h1>My Profile</h1>

        <?php if ($msg): ?>
            <div class="message" style="color: <?= e($msgCol) ?>;"><?= e($msg) ?></div>
        <?php endif; ?>

        <!-- Current profile picture preview -->
        <?php if (imageExists($creator['imageurl'])): ?>
            <div style="text-align: center; margin-bottom: 28px;">
                <img src="<?= e($creator['imageurl']) ?>"
                     alt="Profile picture"
                     class="profile-avatar-preview">
            </div>
        <?php endif; ?>

        <form action="profile.php" method="post" enctype="multipart/form-data">

            <label for="name">Full Name <span class="required">*</span></label>
            <input type="text" id="name" name="name"
                   value="<?= e($creator['name']) ?>" required>

            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email"
                   value="<?= e($creator['email']) ?>" required>

            <label for="website">Website <span class="optional">(optional)</span></label>
            <input type="text" id="website" name="website"
                   placeholder="https://yoursite.com"
                   value="<?= e($creator['website'] ?? '') ?>">

            <label for="imageurl">Profile Picture <span class="optional">(optional)</span></label>
            <input type="file" id="imageurl" name="imageurl" accept="image/*">
            <p class="hint">Leave blank to keep your current picture.</p>

            <div class="btn-row">
                <input type="submit" value="Save Changes" class="btn btn-primary">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

        <hr class="section-divider">

        <div style="text-align: center;">
            <p class="danger-zone-label">Danger zone</p>
            <a href="delete-account.php" class="btn btn-danger">Delete My Account</a>
        </div>

    </div>

</body>
</html>
