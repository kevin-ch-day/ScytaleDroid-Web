<?php
// pages/apps.php
require_once __DIR__ . '/../config/config.php';

header('Location: ' . url('pages/index.php'), true, 302);
exit;
