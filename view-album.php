<?php
// view-album.php — Displays a single album and the photos inside it

include 'db.php';

$idalbum = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Fetch album with creator info ─────────────────────────────
$stmt = $mysqli->prepare(
    "SELECT a.*, c.name AS creator_name
     FROM   album   a
     JOIN   creator c ON a.idcreator = c.idcreator
     WHERE  a.idalbum = ?"
);
$stmt->bind_param("i", $idalbum);
$stmt->execute();
$album = $stmt->get_result()->fetch_assoc();

if (!$album) {
    echo "<p>Album not found.</p>";
    exit();
}

// ── Fetch photos that belong to this album ────────────────────
$photosStmt = $mysqli->prepare(
    "SELECT * FROM photo WHERE idalbum = ? ORDER BY idphoto DESC"
);
$photosStmt->bind_param("i", $idalbum);
$photosStmt->execute();
$photos = $photosStmt->get_result();

$loggedIn = isset($_SESSION['creator_id']);
$isOwner  = $loggedIn && (int)$_SESSION['creator_id'] === (int)$album['idcreator'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($album['title']) ?> – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>
        <div class="nav-links">
            <?php if ($loggedIn): ?>
                <a href="add-photo.php" class="nav-btn">+ Post</a>
                <a href="add-album.php">Albums</a>
                <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name']) ?></a>
                <a href="logout.php" class="nav-logout">Log out</a>
            <?php else: ?>
                <a href="login.php">Sign in</a>
                <a href="register.php" class="nav-btn">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container-wide">

        <!-- ── Album header ── -->
        <div class="album-header">
            <?php if (imageExists($album['imageurl'])): ?>
                <img src="<?= e($album['imageurl']) ?>"
                     alt="Album cover"
                     class="album-cover">
            <?php endif; ?>

            <div class="album-info">
                <h1><?= e($album['title']) ?></h1>
                <p class="album-creator">A collection by <strong><?= e($album['creator_name']) ?></strong></p>
                <p class="album-count">
                    <?= $photos->num_rows ?>
                    <?= $photos->num_rows === 1 ? 'photo' : 'photos' ?>
                </p>
            </div>

            <?php if ($isOwner): ?>
                <div class="album-owner-actions">
                    <a href="edit-album.php?id=<?= $idalbum ?>" class="btn btn-edit">Edit Album</a>
                    <a href="delete-album.php?id=<?= $idalbum ?>" class="btn btn-danger">Delete</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Photos grid ── -->
        <?php if ($photos->num_rows === 0): ?>
            <div class="empty-msg">
                <p>No photos in this album yet.</p>
                <?php if ($isOwner): ?>
                    <a href="add-photo.php">Post a photo</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="photo-grid">
                <?php while ($p = $photos->fetch_assoc()): ?>
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
                            <?php if ($p['comment']): ?>
                                <p><?= e($p['comment']) ?></p>
                            <?php endif; ?>
                            <?php if ($isOwner): ?>
                                <div class="card-actions">
                                    <a href="edit-photo.php?id=<?= $p['idphoto'] ?>"
                                       class="btn btn-edit">Edit</a>
                                    <a href="delete-photo.php?id=<?= $p['idphoto'] ?>"
                                       class="btn btn-danger">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
