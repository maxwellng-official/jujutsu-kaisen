<?php

include 'db.php';
requireLogin();

$myId      = (int)$_SESSION['creator_id'];
$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message   = "";
$msgColour = "green";

$fetchStmt = $mysqli->prepare(
    "SELECT * FROM photo WHERE idphoto = ?"
);
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$photoResult = $fetchStmt->get_result();

if ($photoResult->num_rows === 0) {
    echo "<p>Photo not found.</p>";
    exit();
}

$photo = $photoResult->fetch_assoc();

if ((int)$photo['idcreator'] !== $myId) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title   = trim($_POST['title']   ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if (!empty($_FILES["fileToUpload"]["name"])) {

        $target_dir      = "uploads/";
        $target_filename = basename($_FILES["fileToUpload"]["name"]);
        $target_file     = $target_dir . $target_filename;
        $uploadOk        = true;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if ($check === false) {
            $message   = "Error: the selected file is not a valid image.";
            $msgColour = "red";
            $uploadOk  = false;
        }

        if ($uploadOk && $_FILES["fileToUpload"]["size"] > 5242880) {
            $message   = "Error: file size must be under 5 MB.";
            $msgColour = "red";
            $uploadOk  = false;
        }

        if ($uploadOk && !in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $message   = "Error: only JPG, JPEG, PNG, and GIF files are allowed.";
            $msgColour = "red";
            $uploadOk  = false;
        }

        if ($uploadOk && file_exists($target_file)) {
            $target_file = $target_dir . time() . "_" . $target_filename;
        }

        if ($uploadOk) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

                $stmt = $mysqli->prepare(
                    "UPDATE photo
                     SET title = ?, imageurl = ?, comment = ?
                     WHERE idphoto = ? AND idcreator = ?"
                );
                $stmt->bind_param("sssii", $title, $target_file, $comment, $id, $myId);

                if ($stmt->execute()) {
                    $message          = "Photo updated successfully!";
                    $photo['title']   = $title;
                    $photo['imageurl']= $target_file;
                    $photo['comment'] = $comment;
                } else {
                    $message   = "Database error: " . $mysqli->error;
                    $msgColour = "red";
                }

            } else {
                $message   = "Error uploading the new image. Please try again.";
                $msgColour = "red";
            }
        }

    } else {

        $stmt = $mysqli->prepare(
            "UPDATE photo SET title = ?, comment = ?
             WHERE idphoto = ? AND idcreator = ?"
        );
        $stmt->bind_param("ssii", $title, $comment, $id, $myId);

        if ($stmt->execute()) {
            $message          = "Photo updated successfully!";
            $photo['title']   = $title;
            $photo['comment'] = $comment;
        } else {
            $message   = "Database error: " . $mysqli->error;
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
    <title>Edit Photo – JK Social</title>
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

        <h1>Edit Photo</h1>

        <?php if ($message != ""): ?>
            <div class="message" style="color: <?= e($msgColour) ?>;"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (imageExists($photo['imageurl'])): ?>
            <div class="current-image">
                <p>Current photo</p>
                <img src="<?= e($photo['imageurl']) ?>"
                     alt="<?= e($photo['title']) ?>">
            </div>
        <?php endif; ?>

        <form action="edit-photo.php?id=<?= $id ?>"
              method="post" enctype="multipart/form-data">

            <label for="title">Title</label>
            <input type="text" id="title" name="title"
                   value="<?= e($photo['title']) ?>" required>

            <label for="fileToUpload">Replace Photo <span class="optional">(optional)</span></label>
            <input type="file" id="fileToUpload" name="fileToUpload" accept="image/*">
            <p class="hint">Leave blank to keep the current photo.</p>

            <label for="comment">Caption</label>
            <textarea id="comment" name="comment"
                      maxlength="140"><?= e($photo['comment']) ?></textarea>

            <div class="btn-row">
                <input type="submit" value="Save Changes" class="btn btn-primary">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

    </div>

</body>
</html>
