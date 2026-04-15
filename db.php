<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mysqli = new mysqli("localhost", "root", "", "5114asst1");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit();
}

function requireLogin() {
    if (!isset($_SESSION['creator_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 *
 * @param  string $str
 * @return string
 */

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * 
 * @param  string|null $url
 * @return bool
 */
function imageExists($url) {
    if (empty($url)) return false;
    if (strpos($url, 'http') === 0) return true;
    return file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($url, '/'));
}
?>
