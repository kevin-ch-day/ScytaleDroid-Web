<?php
// Compute the base URL of this project (works in subdirs like /ScytaleDroid-Web)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$target = $base . '/pages/index.php';

// Do a 302 redirect to the pages landing
header('Location: ' . $target, true, 302);
exit;
