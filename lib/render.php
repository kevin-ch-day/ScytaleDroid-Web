<?php
// lib/render.php

/** HTML escape */
function e($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format timestamp (YYYY-MM-DD HH:MM) if present */
function fmt_date(?string $ts): string
{
    if (!$ts) return '';
    // assume MySQL TIMESTAMP/TEXT; print as-is if parsing fails
    $t = strtotime($ts);
    return $t ? date('Y-m-d H:i', $t) : $ts;
}

/** H/M/L (and optional I) compact string */
function fmt_hml(?int $h, ?int $m, ?int $l, ?int $i = null): string
{
    $h = (int)($h ?? 0);
    $m = (int)($m ?? 0);
    $l = (int)($l ?? 0);
    $s = "{$h}/{$m}/{$l}";
    if ($i !== null) $s .= '/' . (int)$i;
    return $s;
}

/** Grade -> badge class */
function grade_badge(?string $g): string
{
    $g = strtoupper((string)$g);
    $tone = 'muted';
    if ($g === 'A') $tone = 'info';
    elseif ($g === 'B') $tone = 'low';
    elseif ($g === 'C') $tone = 'medium';
    elseif ($g === 'D' || $g === 'F') $tone = 'high';
    return chip($g ?: '-', $tone);
}

/** Generic chip helper */
function chip(string $label, string $tone = 'muted'): string
{
    $allowed = ['critical', 'high', 'medium', 'low', 'info', 'muted'];
    $tone = in_array($tone, $allowed, true) ? $tone : 'muted';
    return '<span class="chip chip-' . e($tone) . '">' . e($label) . '</span>';
}

/** Compact status badge for run state */
function status_chip(?string $status): string
{
    $normalized = strtoupper(trim((string)$status));
    $tone = 'muted';
    if ($normalized === 'COMPLETED' || $normalized === 'SUCCESS') {
        $tone = 'info';
    } elseif ($normalized === 'FAILED') {
        $tone = 'high';
    } elseif ($normalized === 'RUNNING' || $normalized === 'STARTED') {
        $tone = 'medium';
    }
    return chip($normalized !== '' ? $normalized : 'UNKNOWN', $tone);
}
