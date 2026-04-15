<?php
// delete-comment.php — Handles deleting a comment (POST only, owner only)

include 'db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idcomment = isset($_POST['idcomment']) ? (int)$_POST['idcomment'] : 0;
    $page      = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $myId      = (int)$_SESSION['creator_id'];

    // Only delete if the comment belongs to the logged-in creator
    $stmt = $mysqli->prepare(
        "DELETE FROM comment WHERE idcomment = ? AND idcreator = ?"
    );
    $stmt->bind_param("ii", $idcomment, $myId);
    $stmt->execute();

    header("Location: index.php?page=" . $page);
    exit();
}

// If accessed directly, redirect home
header('Location: index.php');
exit();
?>
