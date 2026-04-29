<?php
require_once __DIR__ . '/../config/config.php';

$target = url('pages/permissions.php');
header('Location: ' . $target, true, 302);
exit;
