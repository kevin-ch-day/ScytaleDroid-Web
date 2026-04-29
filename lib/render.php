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

/** Static/web source state -> badge */
function source_state_chip(?string $state): string
{
    $normalized = strtolower(trim((string)$state));
    $tone = 'muted';
    $label = match ($normalized) {
        'catalog', 'catalog_only' => 'Catalog only',
        'static' => 'Static findings',
        'static_findings' => 'Static findings',
        'static+permission_audit' => 'Static + risk',
        'static_findings+risk' => 'Static + risk',
        'static_findings+risk+permission_audit' => 'Static + risk + audit',
        'permission_audit' => 'Permission audit',
        'permission_audit_only' => 'Permission audit',
        'risk_score_only' => 'Risk score only',
        default => $normalized !== '' ? str_replace('_', ' ', $normalized) : 'unknown',
    };

    if (in_array($normalized, ['static+permission_audit', 'static_findings+risk', 'static_findings+risk+permission_audit'], true)) {
        $tone = 'info';
    } elseif (in_array($normalized, ['static', 'static_findings'], true)) {
        $tone = 'low';
    } elseif (in_array($normalized, ['permission_audit', 'permission_audit_only', 'risk_score_only'], true)) {
        $tone = 'medium';
    }

    return chip($label, $tone);
}

function app_directory_grade_badge(?string $grade, ?string $sourceState): string
{
    $state = strtolower(trim((string)$sourceState));
    if (in_array($state, ['catalog', 'catalog_only'], true)) {
        return chip('Not analyzed', 'muted');
    }
    return grade_badge($grade);
}

function app_directory_score_text($scoreCapped, ?string $sourceState): string
{
    $state = strtolower(trim((string)$sourceState));
    if (in_array($state, ['catalog', 'catalog_only'], true)) {
        return '—';
    }
    $score = trim((string)($scoreCapped ?? ''));
    return $score !== '' ? $score : 'Risk score missing';
}

function app_directory_hmli_text(array $row): string
{
    $state = strtolower(trim((string)($row['source_state'] ?? '')));
    if (in_array($state, ['catalog', 'catalog_only'], true)) {
        return 'Not analyzed';
    }
    return fmt_hml($row['high'] ?? 0, $row['med'] ?? 0, $row['low'] ?? 0, isset($row['info']) ? (int)$row['info'] : null);
}

/** Session usability/state -> badge */
function session_usability_chip(?string $state): string
{
    $normalized = strtolower(trim((string)$state));
    $label = match ($normalized) {
        'usable_complete' => 'Usable',
        'in_progress_no_rows' => 'In Progress',
        'partial_rows' => 'Partial',
        'failed' => 'Failed',
        default => $normalized !== '' ? ucfirst(str_replace('_', ' ', $normalized)) : 'Unknown',
    };

    $tone = match ($normalized) {
        'usable_complete' => 'info',
        'in_progress_no_rows' => 'medium',
        'partial_rows' => 'low',
        'failed' => 'high',
        default => 'muted',
    };

    return chip($label, $tone);
}

/** Human-friendly finding evidence summary. */
function finding_evidence_excerpt(?string $evidence, int $maxLen = 220): string
{
    $evidence = trim((string)($evidence ?? ''));
    if ($evidence === '') {
        return '';
    }

    $decoded = null;
    if ($evidence[0] === '{' || $evidence[0] === '[') {
        $decoded = json_decode($evidence, true);
    }

    if (is_array($decoded)) {
        foreach (['detail', 'evidence', 'message', 'summary', 'path', 'value'] as $key) {
            if (!empty($decoded[$key]) && is_scalar($decoded[$key])) {
                $evidence = (string)$decoded[$key];
                break;
            }
        }
    }

    $evidence = preg_replace('/\s+/u', ' ', $evidence) ?? $evidence;
    return mb_strimwidth($evidence, 0, $maxLen, '…');
}

/** Permission weight badge for internal numeric severity values. */
function permission_weight_chip($weight): string
{
    $n = (int)$weight;
    $tone = 'info';
    if ($n >= 150) {
        $tone = 'high';
    } elseif ($n >= 80) {
        $tone = 'medium';
    } elseif ($n >= 30) {
        $tone = 'low';
    }
    return chip((string)$n, $tone);
}
