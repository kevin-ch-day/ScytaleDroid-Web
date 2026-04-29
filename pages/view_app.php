<?php
require_once __DIR__ . '/../config/config.php';

$target = url('pages/app_report.php');
$params = [];
if (isset($_GET['pkg'])) {
    $params['pkg'] = (string)$_GET['pkg'];
}
if (isset($_GET['session'])) {
    $params['session'] = (string)$_GET['session'];
}
$location = $target . ($params ? ('?' . http_build_query($params)) : '');
header('Location: ' . $location, true, 302);
exit;
