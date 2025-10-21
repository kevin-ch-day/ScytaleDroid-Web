<?php
// lib/render.php

/** HTML escape */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    $cls = 'badge-muted';
    if ($g === 'A') $cls = 'badge-ok';
    elseif ($g === 'B') $cls = 'badge-info';
    elseif ($g === 'C') $cls = 'badge-warn';
    elseif ($g === 'D' || $g === 'F') $cls = 'badge-bad';
    return '<span class="badge ' . $cls . '">' . e($g ?: '-') . '</span>';
}
