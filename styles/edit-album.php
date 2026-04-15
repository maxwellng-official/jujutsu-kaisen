<?php
// edit-album.php — Allows the owner to edit album details and manage its photos

include 'db.php';
requireLogin();

$myId    = (int)$_SESSION['creator_id'];
$idalbum = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg     = "";
$msgCol  = "green";

// ── Fetch album and verify ownership ─────────────────────────
$stmt = $mysqli->prepare(
    "SELECT * FROM album WHERE idalbum = ? AND idcreator = ?"
);
$stmt->bind_param("ii", $idalbum, $myId);
$stmt->execute();
$album = $stmt->get_result()->fetch_assoc();

// Redirect if album not found or not owned by this creator
if (!$album) {
    header('Location: index.php');
    exit();
}

// ── Handle removing a photo from this album ──────────────────
if (isset($_GET['remove_photo'])) {
    $removeId = (int)$_GET['remove_photo'];

    $removeStmt = $mysqli->prepare(
        "UPDATE photo SET idalbum = NULL WHERE idphoto = ? AND idcreator = ?"
    );
    $removeStmt->bind_param("ii", $removeId, $myId);
    $removeStmt->execute();

    header("Location: edit-album.php?id=$idalbum");
    exit();
}

// ── Handle adding a photo to this album ─────────────────────
if (isset($_GET['add_photo'])) {
    $addId = (int)$_GET['add_photo'];

    $addStmt = $mysqli->prepare(
        "UPDATE photo SET idalbum = ? WHERE idphoto = ? AND idcreator = ?"
    );
    $addStmt->bind_param("iii", $idalbum, $addId, $myId);
    $addStmt->execute();

    header("Location: edit-album.php?id=$idalbum");
    exit();
}

// ── Handle album title / cover update ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title    = trim($_POST['title'] ?? '');
    $imageurl = $album['imageurl'];
    $uploadOk = true;

    if (empty($title)) {
        $msg    = "Album title is required.";
        $msgCol = "red";
        $uploadOk = false;
    }

    // Handle optional new cover image
    if ($uploadOk && !empty($_FILES['imageurl']['name'])) {
        $target_dir  = "uploads/";
        $target_file = $target_dir . basename($_FILES['imageurl']['name']);
        $check       = getimagesize($_FILES['imageurl']['tmp_name']);

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($check === false) {
        $msg      = "Cover image must be a valid image file.";
        $msgCol   = "red";
        $uploadOk = false;
    } elseif ($_FILES['imageurl']['size'] > 5242880) {
        $msg      = "Cover image must be under 5 MB.";
        $msgCol   = "red";
        $uploadOk = false;
    } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $msg      = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        $msgCol   = "red";
        $uploadOk = false;
    } elseif (move_uploaded_file($_FILES['imageurl']['tmp_name'], $target_file)) {
        $imageurl = $target_file;
    } else {
        $msg      = "Error uploading new cover image.";
        $msgCol   = "red";
        $uploadOk = false;
    }
    }

    if ($uploadOk) {
        $updateStmt = $mysqli->prepare(
            "UPDATE album SET title = ?, imageurl = ?
             WHERE idalbum = ? AND idcreator = ?"
        );
        $updateStmt->bind_param("ssii", $title, $imageurl, $idalbum, $myId);

        if ($updateStmt->execute()) {
            $album['title']    = $title;
            $album['imageurl'] = $imageurl;
            $msg = "Album updated successfully!";
        } else {
            $msg    = "Database error: " . $mysqli->error;
            $msgCol = "red";
        }
    }
}

// ── Photos currently in this album ───────────────────────────
$inAlbum = $mysqli->prepare(
    "SELECT * FROM photo WHERE idalbum = ? ORDER BY idphoto DESC"
);
$inAlbum->bind_param("i", $idalbum);
$inAlbum->execute();
$albumPhotos = $inAlbum->get_result();

// ── Creator's photos NOT yet in this album ───────────────────
$notInAlbum = $mysqli->prepare(
    "SELECT * FROM photo
     WHERE idcreator = ? AND (idalbum IS NULL OR idalbum != ?)
     ORDER BY idphoto DESC"
);
$notInAlbum->bind_param("ii", $myId, $idalbum);
$notInAlbum->execute();
$availablePhotos = $notInAlbum->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Album – JK Social</title>
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

    <div class="container-wide">

        <h1>Edit Album</h1>

        <?php if ($msg): ?>
            <div class="message" style="color: <?= e($msgCol) ?>;"><?= e($msg) ?></div>
        <?php endif; ?>

        <!-- ── Album details form ── -->
        <div class="edit-album-form-wrap">
            <form action="edit-album.php?id=<?= $idalbum ?>"
                  method="post" enctype="multipart/form-data">

                <label for="title">Album Title <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= e($album['title']) ?>" required>

                <label for="imageurl">Cover Image <span class="optional">(optional)</span></label>
                <input type="file" id="imageurl" name="imageurl" accept="image/*">
                <p class="hint">Leave blank to keep the current cover.</p>

                <div class="btn-row">
                    <input type="submit" value="Update Album" class="btn btn-primary">
                    <a href="view-album.php?id=<?= $idalbum ?>"
                       class="btn btn-secondary">Back to Album</a>
                </div>
            </form>
        </div>

        <!-- ── Photos currently in this album ── -->
        <div class="album-section">
            <h2>Photos in this album</h2>

            <?php if ($albumPhotos->num_rows === 0): ?>
                <p class="muted-text">No photos in this album yet.</p>
            <?php else: ?>
                <div class="photo-grid">
                    <?php while ($p = $albumPhotos->fetch_assoc()): ?>
                        <div class="photo-card">
                            <?php if (imageExists($p['imageurl'])): ?>
                                <div class="img-wrap">
                                    <img src="<?= e($p['imageurl']) ?>"
                                         alt="<?= e($p['title']) ?>">
                                </div>
                            <?php else: ?>
                                <div class="no-image">No image</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h2><?= e($p['title']) ?></h2>
                                <div class="card-actions">
                                    <a href="edit-album.php?id=<?= $idalbum ?>&remove_photo=<?= $p['idphoto'] ?>"
                                       class="btn btn-danger btn-sm">Remove</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Available photos to add ── -->
        <div class="album-section">
            <h2>Add photos to this album</h2>

            <?php if ($availablePhotos->num_rows === 0): ?>
                <p class="muted-text">
                    No other photos to add.
                    <a href="add-photo.php">Post a new photo</a>.
                </p>
            <?php else: ?>
                <div class="photo-grid">
                    <?php while ($p = $availablePhotos->fetch_assoc()): ?>
                        <div class="photo-card">
                            <?php if (imageExists($p['imageurl'])): ?>
                                <div class="img-wrap">
                                    <img src="<?= e($p['imageurl']) ?>"
                                         alt="<?= e($p['title']) ?>">
                                </div>
                            <?php else: ?>
                                <div class="no-image">No image</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h2><?= e($p['title']) ?></h2>
                                <div class="card-actions">
                                    <a href="edit-album.php?id=<?= $idalbum ?>&add_photo=<?= $p['idphoto'] ?>"
                                       class="btn btn-edit btn-sm">Add</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
