<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$loggedIn = isset($_SESSION['creator_id']);
$myId     = $loggedIn ? (int)$_SESSION['creator_id'] : null;

$postsPerPage = 5;
$page         = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset       = ($page - 1) * $postsPerPage;
$countRes   = $mysqli->query("SELECT COUNT(*) AS total FROM photo");
$totalPosts = $countRes ? (int)$countRes->fetch_assoc()['total'] : 0;
$totalPages = max(1, (int)ceil($totalPosts / $postsPerPage));

$stmt = $mysqli->prepare(
    "SELECT p.idphoto,
            p.title,
            p.imageurl,
            p.comment   AS caption,
            p.idcreator,
            COALESCE(c.name,     'Unknown') AS creator_name,
            COALESCE(c.imageurl, '')        AS creator_avatar
     FROM   photo   p
     LEFT JOIN creator c ON p.idcreator = c.idcreator
     ORDER  BY p.idphoto DESC
     LIMIT  ? OFFSET ?"
);

$posts   = [];
$postIds = [];

if ($stmt) {
    $stmt->bind_param("ii", $postsPerPage, $offset);
    $stmt->execute();
    $postsResult = $stmt->get_result();

    while ($row = $postsResult->fetch_assoc()) {
        $posts[]   = $row;
        $postIds[] = (int)$row['idphoto'];
    }
}

$commentsByPost = [];
if (!empty($postIds)) {
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $stmt = $mysqli->prepare(
        "SELECT cm.idcomment,
                cm.idphoto,
                cm.body,
                cm.idcreator,
                cm.created_at,
                cr.name AS commenter_name
         FROM   comment cm
         JOIN   creator cr ON cm.idcreator = cr.idcreator
         WHERE  cm.idphoto IN ($placeholders)
         ORDER  BY cm.created_at ASC"
    );
    if ($stmt) {
        $stmt->bind_param(str_repeat('i', count($postIds)), ...$postIds);
        $stmt->execute();
        $cResult = $stmt->get_result();
        while ($c = $cResult->fetch_assoc()) {
            $commentsByPost[(int)$c['idphoto']][] = $c;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JK Social – Feed</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <nav>
        <a href="index.php" class="nav-brand">JK Social</a>

        <div class="nav-links">
            <?php if ($loggedIn): ?>
                <a href="add-photo.php" class="nav-btn">+ Post</a>
                <a href="add-album.php">Albums</a>
                <a href="profile.php" class="nav-profile"><?= e($_SESSION['creator_name'] ?? 'User') ?></a>
                <a href="logout.php" class="nav-logout">Log out</a>
            <?php else: ?>
                <a href="login.php">Sign in</a>
                <a href="register.php" class="nav-btn">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="feed-wrap">

        <?php if (empty($posts)): ?>

            <div class="empty-msg">
                <p>No posts yet.</p>
                <?php if ($loggedIn): ?>
                    <a href="add-photo.php">Be the first to post!</a>
                <?php else: ?>
                    <a href="register.php">Join to start posting</a>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <?php foreach ($posts as $post): ?>

                <article class="post-card" id="post-<?= $post['idphoto'] ?>">

                    <div class="post-header">
                        <div class="post-avatar">
                            <?php if (imageExists($post['creator_avatar'])): ?>
                                <img src="<?= e($post['creator_avatar']) ?>"
                                     alt="<?= e($post['creator_name']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?= strtoupper(substr($post['creator_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <span class="post-creator"><?= e($post['creator_name']) ?></span>

                        <?php if ($loggedIn && $myId === (int)$post['idcreator']): ?>
                            <div class="post-owner-actions">
                                <a href="edit-photo.php?id=<?= $post['idphoto'] ?>"
                                   class="btn btn-edit btn-sm">Edit</a>
                                <a href="delete-photo.php?id=<?= $post['idphoto'] ?>"
                                   class="btn btn-danger btn-sm">Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (imageExists($post['imageurl'])): ?>
                        <div class="post-image-wrap">
                            <img src="<?= e($post['imageurl']) ?>"
                                 alt="<?= e($post['title']) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="post-body">

                        <p class="post-caption">
                            <strong><?= e($post['creator_name']) ?></strong>
                            <?= e($post['title']) ?>
                            <?php if (!empty($post['caption'])): ?>
                                &mdash; <span class="caption-text"><?= e($post['caption']) ?></span>
                            <?php endif; ?>
                        </p>


                        <div class="comments-section">

                            <?php $comments = $commentsByPost[$post['idphoto']] ?? []; ?>

                            <?php foreach ($comments as $c): ?>
                                <div class="comment">
                                    <span class="comment-author"><?= e($c['commenter_name']) ?></span>
                                    <span class="comment-text"><?= e($c['body']) ?></span>

                                    <?php if ($loggedIn && $myId === (int)$c['idcreator']): ?>
                                        <form action="delete-comment.php"
                                              method="post" class="inline-form">
                                            <input type="hidden" name="idcomment"
                                                   value="<?= $c['idcomment'] ?>">
                                            <input type="hidden" name="page"
                                                   value="<?= $page ?>">
                                            <button type="submit"
                                                    class="comment-delete"
                                                    title="Delete comment">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($loggedIn): ?>
                                <form action="add-comment.php"
                                      method="post" class="comment-form">
                                    <input type="hidden" name="idphoto"
                                           value="<?= $post['idphoto'] ?>">
                                    <input type="hidden" name="page"
                                           value="<?= $page ?>">
                                    <input type="text" name="body"
                                           placeholder="Add a comment…"
                                           maxlength="500" required>
                                    <button type="submit">Post</button>
                                </form>
                            <?php else: ?>
                                <p class="comment-login-prompt">
                                    <a href="login.php">Sign in</a> to leave a comment.
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>"
                           class="btn btn-secondary">← Newer</a>
                    <?php endif; ?>

                    <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>"
                           class="btn btn-secondary">Older →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</body>
</html>
