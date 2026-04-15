<?php
// delete-photo.php — Allows the owner of a photo to delete it

include 'db.php';
requireLogin();

$myId = (int)$_SESSION['creator_id'];
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Fetch the photo and verify ownership ─────────────────────
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

// Only the creator who uploaded this photo may delete it
if ((int)$photo['idcreator'] !== $myId) {
    header('Location: index.php');
    exit();
}

// ── Handle the confirmed deletion ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $mysqli->prepare(
        "DELETE FROM photo WHERE idphoto = ? AND idcreator = ?"
    );
    $stmt->bind_param("ii", $id, $myId);

    if ($stmt->execute()) {
        // Remove the image file from the server (local uploads only)
        $imageFile = $photo['imageurl'];
        if (!empty($imageFile) &&
            strpos($imageFile, 'http') !== 0 &&
            file_exists($imageFile)) {
            unlink($imageFile);
        }

        header("Location: index.php");
        exit();
    } else {
        echo "<p>Something went wrong. Please contact your system administrator.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Photo – JK Social</title>
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

    <div class="delete-container">

        <div class="warning-icon">⚠️</div>
        <h1>Delete Photo</h1>

        <?php if (imageExists($photo['imageurl'])): ?>
            <img src="<?= e($photo['imageurl']) ?>"
                 alt="<?= e($photo['title']) ?>"
                 class="preview-image">
        <?php endif; ?>

        <p>
            Are you sure you want to delete
            <strong><?= e($photo['title']) ?></strong>?
            This cannot be undone.
        </p>

        <form action="delete-photo.php?id=<?= $id ?>" method="post">
            <div class="btn-row-center">
                <input type="submit" value="Yes, Delete" class="btn btn-danger">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

</body>
</html>
