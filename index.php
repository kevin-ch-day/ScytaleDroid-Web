<?php
// /var/www/html/ScytaleDroid-Web/index.php
require_once __DIR__ . '/config/config.php';

header('Location: ' . abs_url('pages/index.php'), true, 302);
exit;
