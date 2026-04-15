<?php
// add-album.php — Allows a logged-in creator to create a new album

include 'db.php';
requireLogin();

$myId     = (int)$_SESSION['creator_id'];
$message  = "";
$msgColor = "green";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title    = trim($_POST['title'] ?? '');
    $uploadOk = true;
    $imageurl = "";

    if (empty($title)) {
        $message  = "Album title is required.";
        $msgColor = "red";
        $uploadOk = false;
    }

    // Handle optional cover image upload
    if ($uploadOk && !empty($_FILES['imageurl']['name'])) {
        $target_dir      = "uploads/";
        $target_filename = basename($_FILES['imageurl']['name']);
        $target_file     = $target_dir . $target_filename;
        $check           = getimagesize($_FILES['imageurl']['tmp_name']);
        $imageFileType   = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($check === false) {
            $message  = "Cover image must be a valid image file.";
            $msgColor = "red";
            $uploadOk = false;

        } elseif ($_FILES['imageurl']['size'] > 5242880) {
            $message  = "Cover image must be under 5 MB.";
            $msgColor = "red";
            $uploadOk = false;

        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $message  = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            $msgColor = "red";
            $uploadOk = false;

        } elseif (file_exists($target_file)) {
            // Avoid overwriting existing files
            $target_file = $target_dir . time() . "_" . $target_filename;
            if (move_uploaded_file($_FILES['imageurl']['tmp_name'], $target_file)) {
                $imageurl = $target_file;
            } else {
                $message  = "Error uploading cover image. Please try again.";
                $msgColor = "red";
                $uploadOk = false;
            }

        } elseif (move_uploaded_file($_FILES['imageurl']['tmp_name'], $target_file)) {
            $imageurl = $target_file;

        } else {
            $message  = "Error uploading cover image. Please try again.";
            $msgColor = "red";
            $uploadOk = false;
        }
    }

    if ($uploadOk) {
        $stmt = $mysqli->prepare(
            "INSERT INTO album (title, imageurl, idcreator) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssi", $title, $imageurl, $myId);

        if ($stmt->execute()) {
            // Redirect straight to the newly created album
            $newId = $mysqli->insert_id;
            header("Location: view-album.php?id=$newId");
            exit();
        } else {
            $message  = "Database error: " . $mysqli->error;
            $msgColor = "red";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Album – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>
        <div class="nav-links">
            <a href="add-photo.php" class="nav-btn">+ Post</a>
            <a href="add-album.php" class="active">Albums</a>
            <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name']) ?></a>
            <a href="logout.php" class="nav-logout">Log out</a>
        </div>
    </nav>

    <div class="container">

        <h1>Create New Album</h1>

        <?php if ($message): ?>
            <div class="message" style="color: <?= e($msgColor) ?>;"><?= e($message) ?></div>
        <?php endif; ?>

        <form action="add-album.php" method="post" enctype="multipart/form-data">

            <label for="title">Album Title <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   placeholder="e.g. Summer 2025" required
                   value="<?= isset($_POST['title']) ? e($_POST['title']) : '' ?>">

            <label for="imageurl">Cover Image <span class="optional">(optional)</span></label>
            <input type="file" id="imageurl" name="imageurl" accept="image/*">
            <p class="hint">You can add a cover image later if you prefer.</p>

            <div class="btn-row">
                <input type="submit" value="Create Album" class="btn btn-primary">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

    </div>

</body>
</html>
