<?php
// config/config.php

// ---- App identity ----
if (!defined('APP_NAME')) define('APP_NAME', 'ScytaleDroid');

// ---- Base URL (subdirectory where this app is served) ----
// Set this to your known subdir (e.g., '/ScytaleDroid-Web') or set to null to auto-detect.
$__BASE_URL = '/ScytaleDroid-Web'; // change to null to auto-detect

if (!defined('BASE_URL')) {
    if ($__BASE_URL === null) {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        define('BASE_URL', ($base === '/' || $base === '') ? '' : $base);
    } else {
        define('BASE_URL', rtrim($__BASE_URL, '/'));
    }
}

// ---- Convenience URLs ----
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . '/assets');
if (!defined('PAGES_URL'))  define('PAGES_URL',  BASE_URL . '/pages');

// ---- Paging defaults ----
if (!defined('PAGE_SIZES'))        define('PAGE_SIZES', [25, 50, 100]);
if (!defined('DEFAULT_PAGE_SIZE')) define('DEFAULT_PAGE_SIZE', 25);

// ---- Optional: timezone (helps date formatting be consistent) ----
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// ---- Tiny URL helper ----
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}
