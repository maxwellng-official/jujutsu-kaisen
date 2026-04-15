<?php
// login.php — Creator sign-in page

include 'db.php';

// Already logged in? Go to the feed
if (isset($_SESSION['creator_id'])) {
    header('Location: index.php');
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        // Look up the creator by email
        $stmt = $mysqli->prepare(
            "SELECT idcreator, name, password FROM creator WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result  = $stmt->get_result();
        $creator = $result->fetch_assoc();

        // Verify the password against the stored hash
        if ($creator && password_verify($password, $creator['password'])) {
            $_SESSION['creator_id']   = $creator['idcreator'];
            $_SESSION['creator_name'] = $creator['name'];
            header('Location: index.php');
            exit();
        } else {
            $error = "Incorrect email or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">JK Social</div>
        <h1>Welcome back</h1>
        <p class="auth-sub">Sign in to your account to continue</p>

        <?php if ($error): ?>
            <div class="message" style="color: var(--danger);"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="post" novalidate>

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com" required
                   value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <input type="submit" value="Sign In" class="btn btn-primary btn-full">

        </form>

        <p class="auth-switch">
            Don't have an account? <a href="register.php">Sign up free</a>
        </p>

    </div>

</body>
</html>
