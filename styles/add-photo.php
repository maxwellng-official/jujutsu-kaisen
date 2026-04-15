<?php
// add-photo.php — Allows a logged-in creator to upload a new photo

include 'db.php';
requireLogin();

$myId      = (int)$_SESSION['creator_id'];
$message   = "";
$msgColour = "green";

// ── Fetch this creator's albums for the optional album selector ─
$albumStmt = $mysqli->prepare(
    "SELECT idalbum, title FROM album WHERE idcreator = ? ORDER BY title ASC"
);
$albumStmt->bind_param("i", $myId);
$albumStmt->execute();
$myAlbums = $albumStmt->get_result();

// ── Handle the upload form submission ────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title    = trim($_POST['title']   ?? '');
    $caption  = trim($_POST['comment'] ?? '');
    $idalbum  = !empty($_POST['idalbum']) ? (int)$_POST['idalbum'] : null;

    $target_dir      = "uploads/";
    $target_filename = basename($_FILES["fileToUpload"]["name"]);
    $target_file     = $target_dir . $target_filename;
    $uploadOk        = true;

    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate: must be a real image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        $message   = "Error: the selected file is not a valid image.";
        $msgColour = "red";
        $uploadOk  = false;
    }

    // Validate: size limit 5 MB
    if ($uploadOk && $_FILES["fileToUpload"]["size"] > 5242880) {
        $message   = "Error: file size must be under 5 MB.";
        $msgColour = "red";
        $uploadOk  = false;
    }

    // Validate: allowed extensions
    if ($uploadOk &&
        !in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $message   = "Error: only JPG, JPEG, PNG, and GIF files are allowed.";
        $msgColour = "red";
        $uploadOk  = false;
    }

    // Avoid overwriting an existing file by prepending a timestamp
    if ($uploadOk && file_exists($target_file)) {
        $target_file = $target_dir . time() . "_" . $target_filename;
    }

    if ($uploadOk) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

            // Insert the new photo, linked to the logged-in creator
            $stmt = $mysqli->prepare(
                "INSERT INTO photo (title, imageurl, comment, idcreator, idalbum)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssii", $title, $target_file, $caption, $myId, $idalbum);

            if ($stmt->execute()) {
                $message = "Photo shared successfully!";
            } else {
                $message   = "Database error: " . $mysqli->error;
                $msgColour = "red";
            }

        } else {
            $message   = "Error: there was a problem uploading your file. Please try again.";
            $msgColour = "red";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>
        <div class="nav-links">
            <a href="add-photo.php" class="nav-btn active">+ Post</a>
            <a href="add-album.php">Albums</a>
            <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name']) ?></a>
            <a href="logout.php" class="nav-logout">Log out</a>
        </div>
    </nav>

    <div class="container">

        <h1>New Post</h1>

        <?php if ($message != ""): ?>
            <div class="message" style="color: <?= e($msgColour) ?>;">
                <?= e($message) ?>
                <?php if ($msgColour === "green"): ?>
                    &nbsp;<a href="index.php">View in feed →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="add-photo.php" method="post" enctype="multipart/form-data">

            <label for="fileToUpload">Photo <span class="required">*</span></label>
            <input type="file" id="fileToUpload" name="fileToUpload"
                   accept="image/*" required>
            <p class="hint">Accepted formats: JPG, JPEG, PNG, GIF &nbsp;·&nbsp; Max size: 5 MB</p>

            <label for="title">Title <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   placeholder="Give your photo a title" required>

            <label for="comment">Caption <span class="optional">(optional)</span></label>
            <textarea id="comment" name="comment" maxlength="140"
                      placeholder="Write a caption… (max 140 characters)"></textarea>

            <!-- Album selector — only shown if the creator has at least one album -->
            <?php if ($myAlbums->num_rows > 0): ?>
                <label for="idalbum">Add to Album <span class="optional">(optional)</span></label>
                <select id="idalbum" name="idalbum">
                    <option value="">— No album —</option>
                    <?php while ($al = $myAlbums->fetch_assoc()): ?>
                        <option value="<?= $al['idalbum'] ?>">
                            <?= e($al['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>

            <div class="btn-row">
                <input type="submit" value="Share Photo" class="btn btn-primary">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

    </div>

</body>
</html>
