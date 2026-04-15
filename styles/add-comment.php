<?php
// add-comment.php — Handles adding a comment to a post (POST only)

include 'db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idphoto = isset($_POST['idphoto']) ? (int)$_POST['idphoto'] : 0;
    $body    = trim($_POST['body'] ?? '');
    $page    = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $myId    = (int)$_SESSION['creator_id'];

    // Only insert if we have valid data
    if (!empty($body) && $idphoto > 0) {
        $stmt = $mysqli->prepare(
            "INSERT INTO comment (body, idcreator, idphoto) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sii", $body, $myId, $idphoto);
        $stmt->execute();
    }

    // Redirect back to the same page of the feed
    header("Location: index.php?page=" . $page . "#post-" . $idphoto);
    exit();
}

// If accessed directly (not via POST), redirect home
header('Location: index.php');
exit();
?>
