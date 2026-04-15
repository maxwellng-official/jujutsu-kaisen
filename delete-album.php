<?php
// delete-album.php — Allows the owner to permanently delete an album
// Note: photos inside the album are NOT deleted — they are unlinked from the album

include 'db.php';
requireLogin();

$myId    = (int)$_SESSION['creator_id'];
$idalbum = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Fetch album and verify ownership ─────────────────────────
$stmt = $mysqli->prepare(
    "SELECT * FROM album WHERE idalbum = ? AND idcreator = ?"
);
$stmt->bind_param("ii", $idalbum, $myId);
$stmt->execute();
$album = $stmt->get_result()->fetch_assoc();

if (!$album) {
    header('Location: index.php');
    exit();
}

$error = "";

// ── Handle confirmed deletion ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $deleteStmt = $mysqli->prepare(
        "DELETE FROM album WHERE idalbum = ? AND idcreator = ?"
    );
    $deleteStmt->bind_param("ii", $idalbum, $myId);

    if ($deleteStmt->execute()) {
        header('Location: index.php');
        exit();
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Album – JK Social</title>
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
        <h1>Delete Album</h1>

        <?php if ($error): ?>
            <div class="message" style="color: var(--danger);"><?= e($error) ?></div>
        <?php endif; ?>

        <p>
            Are you sure you want to delete the album
            <strong><?= e($album['title']) ?></strong>?<br>
            <span style="font-size: 14px; color: var(--muted);">
                The photos inside will <em>not</em> be deleted — they will simply
                be removed from the album.
            </span>
        </p>

        <form action="delete-album.php?id=<?= $idalbum ?>" method="post">
            <div class="btn-row-center">
                <input type="submit" value="Yes, Delete Album" class="btn btn-danger">
                <a href="view-album.php?id=<?= $idalbum ?>"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

</body>
</html>
