<?php
require_once __DIR__ . '/../lib/guards.php';

$pkg = guard_package_name(isset($_GET['pkg']) ? (string) $_GET['pkg'] : null);
$session = guard_session(isset($_GET['session']) ? (string) $_GET['session'] : null);

$target = url('pages/app_report.php');
$params = [];
if ($pkg !== null) {
    $params['pkg'] = $pkg;
}
if ($session !== null) {
    $params['session'] = $session;
}
$location = $target . ($params !== [] ? ('?' . http_build_query($params)) : '');
header('Location: ' . $location, true, 302);
exit;
