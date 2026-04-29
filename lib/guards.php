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

/** Package names are dot/underscore/hyphen heavy and should stay bounded. */
function guard_package_name(?string $pkg): ?string
{
    $pkg = guard_str($pkg);
    if ($pkg === null) return null;
    if (!preg_match('/^[A-Za-z0-9._:-]{1,255}$/', $pkg)) return null;
    return $pkg;
}

/** Session stamps include timestamps, suffixes, and hyphens. */
function guard_session(?string $session): ?string
{
    $session = guard_str($session);
    if ($session === null) return null;
    if (!preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $session)) return null;
    return $session;
}

/** Dynamic run ids are UUIDs in current schemas, but keep the guard tolerant. */
function guard_dynamic_run_id(?string $runId): ?string
{
    $runId = guard_str($runId);
    if ($runId === null) return null;
    if (!preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $runId)) return null;
    return $runId;
}

/** Enum-like choice guard with case-insensitive matching. */
function guard_choice(?string $value, array $allowed): ?string
{
    $value = guard_str($value);
    if ($value === null) return null;
    foreach ($allowed as $item) {
        $candidate = (string)$item;
        if (strcasecmp($value, $candidate) === 0) {
            return $candidate;
        }
    }
    return null;
}

/** Checkbox/bool-ish query guard with explicit default. */
function guard_bool($value, bool $default = false): bool
{
    if ($value === null) {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    $normalized = strtolower(trim((string)$value));
    if ($normalized === '') {
        return $default;
    }
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}
