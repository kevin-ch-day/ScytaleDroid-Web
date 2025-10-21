<?php
// lib/guards.php
require_once __DIR__ . '/../config/config.php';

/** Trimmed string or null if empty */
function guard_str(?string $s): ?string
{
    if ($s === null) return null;
    $s = trim($s);
    return $s === '' ? null : $s;
}

/** Whitelist page size using global defaults */
function guard_size($size): int
{
    $sizes = defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100];
    $d     = defined('DEFAULT_PAGE_SIZE') ? DEFAULT_PAGE_SIZE : 25;
    $n = (int)$size;
    return in_array($n, $sizes, true) ? $n : $d;
}

/** Positive page number (>=1) */
function guard_page($page): int
{
    $n = (int)$page;
    return $n >= 1 ? $n : 1;
}

/** Optional: safe search (limit length) */
function guard_search(?string $q): ?string
{
    $q = guard_str($q);
    if ($q === null) return null;
    // prevent wild injection via massive strings
    return mb_substr($q, 0, 128);
}

/** Optional: category – allow words, spaces, dashes, slashes */
function guard_category(?string $c): ?string
{
    $c = guard_str($c);
    if ($c === null) return null;
    if (!preg_match('/^[\w\s\-\/.]{1,64}$/u', $c)) return null;
    return $c;
}
