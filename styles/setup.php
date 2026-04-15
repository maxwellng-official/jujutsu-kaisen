<?php
// ============================================================
// SETUP.PHP — Run this ONCE in your browser to seed demo data
// Visit: http://localhost/Jujutsu-kaisen/setup.php
// ⚠️  DELETE THIS FILE after running it for security!
// ============================================================

// Manually start session and connect (don't use db.php so we can show friendly errors)
session_start();

echo "<style>body{font-family:sans-serif;max-width:640px;margin:40px auto;line-height:1.9;padding:0 20px;}
code{background:#f0f0f0;padding:2px 6px;border-radius:4px;}</style>";
echo "<h2>🚀 JK Social — Demo Setup</h2>";

$mysqli = new mysqli("localhost", "root", "", "5114asst1");

if ($mysqli->connect_errno) {
    echo "<p style='color:red;'><strong>❌ Cannot connect to the database.</strong></p>";
    echo "<p>Make sure you have:</p><ol>
    <li>Opened <strong>XAMPP</strong> and started both <strong>Apache</strong> and <strong>MySQL</strong></li>
    <li>Opened <strong>phpMyAdmin</strong> and imported <code>database/5114asst1.sql</code></li>
    </ol>";
    echo "<p>Then refresh this page.</p>";
    exit();
}

// Check that the photo table exists (i.e. the SQL was imported)
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'photo'");
if ($tableCheck->num_rows === 0) {
    echo "<p style='color:red;'><strong>❌ Database tables not found.</strong></p>";
    echo "<p>Please import <code>database/5114asst1.sql</code> in phpMyAdmin first, then refresh this page.</p>";
    exit();
}

// ── 0. Run database upgrades (safe to run multiple times) ────
echo "<h3>Setting up database…</h3>";

// Add email column if it doesn't exist
$emailCheck = $mysqli->query("SHOW COLUMNS FROM creator LIKE 'email'");
if ($emailCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE creator ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT '' AFTER name");
    echo "✅ Added email column to creator table<br>";
} else {
    echo "⚠️  email column already exists — skipping<br>";
}

// Add password column if it doesn't exist
$passCheck = $mysqli->query("SHOW COLUMNS FROM creator LIKE 'password'");
if ($passCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE creator ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
    echo "✅ Added password column to creator table<br>";
} else {
    echo "⚠️  password column already exists — skipping<br>";
}

// Create comment table if it doesn't exist
$mysqli->query("
    CREATE TABLE IF NOT EXISTS comment (
        idcomment  int(11)      NOT NULL AUTO_INCREMENT,
        body       varchar(500) NOT NULL,
        idcreator  int(11)      NOT NULL,
        idphoto    int(11)      NOT NULL,
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (idcomment),
        KEY comment_creator_idx (idcreator),
        KEY comment_photo_idx   (idphoto),
        CONSTRAINT comment_creator FOREIGN KEY (idcreator)
            REFERENCES creator (idcreator) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT comment_photo FOREIGN KEY (idphoto)
            REFERENCES photo (idphoto) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "✅ comment table ready<br>";

// ── 1. Demo accounts ────────────────────────────────────────
$accounts = [
    ['Yuji Itadori',     'yuji@jk.com',     'Demo1234', 'https://picsum.photos/seed/yuji/100/100'],
    ['Megumi Fushiguro', 'megumi@jk.com',    'Demo1234', 'https://picsum.photos/seed/megumi/100/100'],
    ['Nobara Kugisaki',  'nobara@jk.com',    'Demo1234', 'https://picsum.photos/seed/nobara/100/100'],
];

$creatorIds = [];

echo "<h3>Creating accounts…</h3>";

foreach ($accounts as [$name, $email, $password, $avatar]) {

    // Check if already exists
    $check = $mysqli->prepare("SELECT idcreator FROM creator WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $mysqli->prepare(
            "INSERT INTO creator (name, email, password, imageurl) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $email, $hashed, $avatar);
        $stmt->execute();
        $creatorIds[$name] = $mysqli->insert_id;
        echo "✅ Created: <strong>$name</strong> — login: <code>$email</code> / <code>$password</code><br>";
    } else {
        $res               = $mysqli->query("SELECT idcreator FROM creator WHERE email = '$email'");
        $creatorIds[$name] = $res->fetch_assoc()['idcreator'];
        echo "⚠️  Already exists: <strong>$name</strong> ($email)<br>";
    }
}

// ── 2. Demo posts (5 posts, using picsum placeholder images) ─
$posts = [
    [
        'City at Night',
        'https://picsum.photos/seed/city/600/600',
        'Loved the view from the rooftop tonight 🌆',
        'Yuji Itadori',
    ],
    [
        'Mountain Trail',
        'https://picsum.photos/seed/mountain/600/600',
        'Nothing beats a weekend hike 🏔️',
        'Megumi Fushiguro',
    ],
    [
        'Coffee & Code',
        'https://picsum.photos/seed/coffee/600/600',
        'My kind of morning ☕💻',
        'Nobara Kugisaki',
    ],
    [
        'Sunset Vibes',
        'https://picsum.photos/seed/sunset/600/600',
        'Golden hour hits different 🌅',
        'Yuji Itadori',
    ],
    [
        'Street Art Find',
        'https://picsum.photos/seed/streetart/600/600',
        'Found this gem in the old city 🎨',
        'Megumi Fushiguro',
    ],
];

echo "<h3>Creating posts…</h3>";

$photoIds = [];

foreach ($posts as [$title, $imageurl, $caption, $creatorName]) {
    $creatorId = $creatorIds[$creatorName];

    $check = $mysqli->prepare("SELECT idphoto FROM photo WHERE title = ? AND idcreator = ?");
    $check->bind_param("si", $title, $creatorId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $stmt = $mysqli->prepare(
            "INSERT INTO photo (title, imageurl, comment, idcreator) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $title, $imageurl, $caption, $creatorId);
        $stmt->execute();
        $photoIds[] = $mysqli->insert_id;
        echo "✅ Created post: <strong>$title</strong> by $creatorName<br>";
    } else {
        echo "⚠️  Post already exists: <strong>$title</strong><br>";
    }
}

// ── 3. Seed some demo comments ───────────────────────────────
if (!empty($photoIds)) {
    echo "<h3>Adding demo comments…</h3>";

    $sampleComments = [
        [$creatorIds['Nobara Kugisaki'], 'This looks amazing! 😍'],
        [$creatorIds['Yuji Itadori'],    'So cool, love it! 🔥'],
        [$creatorIds['Megumi Fushiguro'],'Great shot! 📸'],
    ];

    foreach (array_slice($photoIds, 0, 3) as $i => $photoId) {
        $comment = $sampleComments[$i % count($sampleComments)];
        [$commenterId, $body] = $comment;

        $stmt = $mysqli->prepare(
            "INSERT INTO comment (body, idcreator, idphoto) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sii", $body, $commenterId, $photoId);
        $stmt->execute();
        echo "✅ Added comment to post #$photoId<br>";
    }
}

echo "<br><hr>";
echo "<h3>✅ Setup complete!</h3>";
echo "<p><strong>Demo login credentials (all accounts use the same password):</strong></p>";
echo "<ul>";
foreach ($accounts as [$name, $email, $password]) {
    echo "<li><strong>$name</strong> — <code>$email</code> / <code>$password</code></li>";
}
echo "</ul>";
echo "<p><a href='index.php'>→ Go to the feed</a></p>";
echo "<p style='color:red;'><strong>⚠️ Please delete this file (setup.php) now for security!</strong></p>";
?>
