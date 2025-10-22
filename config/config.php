<?php
// config/config.php

// ── App identity ───────────────────────────────────────────────────────────────
if (!defined('APP_NAME')) define('APP_NAME', 'ScytaleDroid');
// Optional: app version for cache-busting (edit when you ship UI changes)
if (!defined('APP_VERSION')) define('APP_VERSION', '0.1.0');

// ── Base URL (subdirectory) ───────────────────────────────────────────────────
// Options (precedence):
// 1) Env var SD_BASE_URL (e.g., "/ScytaleDroid-Web")
// 2) Manual override below (set to '' for web root, or '/ScytaleDroid-Web')
// 3) Auto-detect from SCRIPT_NAME (default)
$ENV_BASE = getenv('SD_BASE_URL') ?: null;
// Set to null to auto-detect; set to '' for web root; set to '/ScytaleDroid-Web' for fixed subdir.
$MANUAL_BASE = '/ScytaleDroid-Web';

if (!defined('BASE_URL')) {
    $base = $ENV_BASE;
    if ($base === null) {
        $base = $MANUAL_BASE;
    }
    if ($base === null) {
        // Auto-detect from SCRIPT_NAME, but be CLI-safe
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
        $dir = $scriptName !== '' ? rtrim(str_replace('\\', '/', dirname($scriptName)), '/') : '';
        // Normalize ".", "/" -> '' (root)
        if ($dir === '.' || $dir === '/') $dir = '';
        $base = $dir;
    }
    // Ensure leading slash (unless root) and no trailing slash
    if ($base !== '' && $base[0] !== '/') $base = '/' . $base;
    $base = rtrim($base, '/');
    define('BASE_URL', $base);
}

// ── Origin (scheme + host) for absolute URLs ──────────────────────────────────
if (!defined('APP_ORIGIN')) {
    // Respect proxies if present
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'
    );
    $host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    // If HTTP_HOST is missing a port but SERVER_PORT is non-standard, append
    if (strpos($host, ':') === false && isset($_SERVER['SERVER_PORT'])) {
        $port = (string)$_SERVER['SERVER_PORT'];
        $isDefault = ($proto === 'http' && $port === '80') || ($proto === 'https' && $port === '443');
        if (!$isDefault) $host .= ':' . $port;
    }
    define('APP_ORIGIN', $proto . '://' . $host);
}

// ── Convenience URL bases ─────────────────────────────────────────────────────
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . '/assets');
if (!defined('PAGES_URL'))  define('PAGES_URL',  BASE_URL . '/pages');

// ── Paging defaults ───────────────────────────────────────────────────────────
if (!defined('PAGE_SIZES'))        define('PAGE_SIZES', [25, 50, 100]);
if (!defined('DEFAULT_PAGE_SIZE')) define('DEFAULT_PAGE_SIZE', 25);

// ── Timezone (keeps dates consistent if php.ini lacks one) ────────────────────
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// ── URL helpers (lightweight) ─────────────────────────────────────────────────
if (!function_exists('url')) {
    /** App-relative URL: url('pages/index.php') → /<base>/pages/index.php */
    function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}
if (!function_exists('abs_url')) {
    /** Absolute URL: abs_url('pages/index.php') → https://host/<base>/pages/index.php */
    function abs_url(string $path = ''): string
    {
        return APP_ORIGIN . url($path);
    }
}
if (!function_exists('asset_url')) {
    /**
     * Asset URL with optional cache-buster:
     * asset_url('css/main_style.css') → /<base>/assets/css/main_style.css?v=0.1.0
     */
    function asset_url(string $assetPath, bool $withVersion = true): string
    {
        $u = ASSETS_URL . '/' . ltrim($assetPath, '/');
        if ($withVersion && defined('APP_VERSION') && APP_VERSION !== '') {
            $sep = (strpos($u, '?') === false) ? '?' : '&';
            $u  .= $sep . 'v=' . rawurlencode(APP_VERSION);
        }
        return $u;
    }
}
