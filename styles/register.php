<?php
// register.php — New creator registration page

include 'db.php';

// Already logged in? Go to the feed
if (isset($_SESSION['creator_id'])) {
    header('Location: index.php');
    exit();
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']             ?? '');
    $email   = trim($_POST['email']            ?? '');
    $pass    =       $_POST['password']         ?? '';
    $confirm =       $_POST['confirm_password'] ?? '';
    $website = trim($_POST['website']           ?? '');

    // ── Validation ───────────────────────────────────────────
    if (empty($name) || empty($email) || empty($pass)) {
        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters long.";

    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match. Please try again.";

    } else {

        // Check whether the email is already registered
        $check = $mysqli->prepare("SELECT idcreator FROM creator WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with that email address already exists.";
        } else {

            // Hash the password and insert the new creator
            $hashed = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare(
                "INSERT INTO creator (name, email, password, website) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $name, $email, $hashed, $website);

            if ($stmt->execute()) {
                $success = "Account created successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up – JK Social</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">JK Social</div>
        <h1>Create account</h1>
        <p class="auth-sub">Join JK Social and start sharing</p>

        <?php if ($error): ?>
            <div class="message" style="color: var(--danger);"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message" style="color: green;">
                <?= e($success) ?> &nbsp;<a href="login.php">Sign in →</a>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post" novalidate>

            <label for="name">Full Name <span class="required">*</span></label>
            <input type="text" id="name" name="name"
                   placeholder="Your display name" required
                   value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>">

            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com" required
                   value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">

            <label for="password">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password"
                   placeholder="At least 6 characters" required>

            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Repeat your password" required>

            <label for="website">
                Website <span class="optional">(optional)</span>
            </label>
            <input type="text" id="website" name="website"
                   placeholder="https://yoursite.com"
                   value="<?= isset($_POST['website']) ? e($_POST['website']) : '' ?>">

            <input type="submit" value="Create Account" class="btn btn-primary btn-full">

        </form>

        <p class="auth-switch">
            Already have an account? <a href="login.php">Sign in</a>
        </p>

    </div>

</body>
</html>
