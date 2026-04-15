<?php
// delete-account.php — Allows a logged-in creator to permanently delete their account

include 'db.php';
requireLogin();

$myId = (int)$_SESSION['creator_id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mysqli->prepare("DELETE FROM comment WHERE idcreator = ?")->bind_param("i", $myId)->execute();
    $mysqli->prepare("UPDATE photo SET idalbum = NULL WHERE idcreator = ?")->bind_param("i", $myId)->execute();
    $mysqli->prepare("DELETE FROM photo WHERE idcreator = ?")->bind_param("i", $myId)->execute();
    $mysqli->prepare("DELETE FROM album WHERE idcreator = ?")->bind_param("i", $myId)->execute();

    $stmt = $mysqli->prepare("DELETE FROM creator WHERE idcreator = ?");
    $stmt->bind_param("i", $myId);

if ($stmt->execute()) {
        session_unset();
        session_destroy();
        header('Location: register.php');
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
    <title>Delete Account – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>
        <div class="nav-links">
            <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name']) ?></a>
            <a href="logout.php" class="nav-logout">Log out</a>
        </div>
    </nav>

    <div class="delete-container">

        <div class="warning-icon">⚠️</div>
        <h1>Delete Account</h1>

        <?php if ($error): ?>
            <div class="message" style="color: var(--danger);"><?= e($error) ?></div>
        <?php endif; ?>

        <p>
            Are you sure you want to permanently delete your account,
            <strong><?= e($_SESSION['creator_name']) ?></strong>?
            All your photos, albums, and comments will be removed.
            <strong>This cannot be undone.</strong>
        </p>

        <form action="delete-account.php" method="post">
            <div class="btn-row-center">
                <input type="submit" value="Yes, Delete My Account" class="btn btn-danger">
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

</body>
</html>
