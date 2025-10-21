<?php
// /var/www/html/ScytaleDroid-Web/index.php
require_once __DIR__ . '/config/config.php';

$targetPath = rtrim(BASE_URL, '/') . '/pages/index.php';

// build absolute Location (prevents any relative resolution weirdness)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

header('Location: ' . $scheme . '://' . $host . $targetPath, true, 302);
exit;
