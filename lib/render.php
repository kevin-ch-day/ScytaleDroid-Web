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
    $meta = source_state_meta($state);
    return chip($meta['label'], $meta['tone']);
}

function source_state_meta(?string $state): array
{
    $normalized = strtolower(trim((string)$state));
    return match ($normalized) {
        'catalog', 'catalog_only' => [
            'key' => 'catalog_only',
            'label' => 'Catalog only',
            'tone' => 'muted',
            'hint' => 'Inventory/catalog record only. No finalized static-analysis result is being shown.',
        ],
        'static', 'static_findings' => [
            'key' => 'static_findings',
            'label' => 'Static findings',
            'tone' => 'low',
            'hint' => 'Static findings are available, but no current risk score or audit row is attached here.',
        ],
        'static+permission_audit', 'static_findings+risk' => [
            'key' => 'static_findings+risk',
            'label' => 'Static findings + risk',
            'tone' => 'info',
            'hint' => 'Static findings and a latest risk score are available for this package/session.',
        ],
        'static_findings+risk+permission_audit' => [
            'key' => 'static_findings+risk+permission_audit',
            'label' => 'Static + risk + audit',
            'tone' => 'info',
            'hint' => 'Static findings, risk score, and permission-audit coverage are all available.',
        ],
        'permission_audit', 'permission_audit_only' => [
            'key' => 'permission_audit_only',
            'label' => 'Permission audit',
            'tone' => 'medium',
            'hint' => 'Permission-audit data exists even if full static findings are not attached here.',
        ],
        'risk_score_only' => [
            'key' => 'risk_score_only',
            'label' => 'Risk score only',
            'tone' => 'medium',
            'hint' => 'A derived risk score exists, but full findings coverage is not available on this row.',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'unknown',
            'label' => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Unknown',
            'tone' => 'muted',
            'hint' => 'This row has an unclassified data state and may need read-model cleanup.',
        ],
    };
}

function source_state_hint(?string $state): string
{
    $meta = source_state_meta($state);
    return (string)$meta['hint'];
}

function source_state_summary_text(?string $state): string
{
    $normalized = strtolower(trim((string)$state));
    return match ($normalized) {
        'catalog', 'catalog_only' => 'Not analyzed',
        'static', 'static_findings' => 'Findings available',
        'static+permission_audit', 'static_findings+risk' => 'Findings and risk available',
        'static_findings+risk+permission_audit' => 'Findings, risk, and audit available',
        'permission_audit', 'permission_audit_only' => 'Permission audit available',
        'risk_score_only' => 'Risk score available',
        default => 'Data state unknown',
    };
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

/** Session stamp/profile -> session type metadata */
function session_type_meta(?string $sessionStamp, ?string $profile = null): array
{
    $stamp = strtolower(trim((string)$sessionStamp));
    $profile = strtolower(trim((string)$profile));

    $meta = [
        'key' => 'unknown',
        'label' => 'Session',
        'tone' => 'muted',
        'hint' => 'Session type is not classified yet.',
        'hidden_by_default' => false,
    ];

    if ($stamp === '' && $profile === '') {
        return $meta;
    }

    $contains = static fn(string $needle): bool => $needle !== '' && (str_contains($stamp, $needle) || str_contains($profile, $needle));

    if ($contains('qa') || $contains('headless') || $contains('stability') || $contains('debug') || $contains('static-batch')) {
        return [
            'key' => 'qa',
            'label' => 'QA / Debug',
            'tone' => 'muted',
            'hint' => 'This looks like a QA, debug, headless, or stability session and is hidden by default in analyst-facing selectors.',
            'hidden_by_default' => true,
        ];
    }
    if ($contains('smoke')) {
        return [
            'key' => 'smoke',
            'label' => 'Smoke',
            'tone' => 'low',
            'hint' => 'This is a smoke or quick validation run and is hidden by default in analyst-facing selectors.',
            'hidden_by_default' => true,
        ];
    }
    if ($contains('rerun')) {
        return [
            'key' => 'rerun',
            'label' => 'Rerun',
            'tone' => 'medium',
            'hint' => 'This is a rerun for the same package or session family.',
            'hidden_by_default' => false,
        ];
    }
    if ($contains('fast')) {
        return [
            'key' => 'fast',
            'label' => 'Fast',
            'tone' => 'low',
            'hint' => 'This is a fast/static review run, usually narrower than a full session.',
            'hidden_by_default' => false,
        ];
    }
    if ($contains('single') || $contains('one-app')) {
        return [
            'key' => 'single_app',
            'label' => 'Single App',
            'tone' => 'medium',
            'hint' => 'This session appears to target a single app rather than a full harvested set.',
            'hidden_by_default' => false,
        ];
    }
    if ($contains('all-full') || $contains('rda-full') || $contains('full')) {
        return [
            'key' => 'full',
            'label' => 'Full',
            'tone' => 'info',
            'hint' => 'This is a full static-analysis session over a broader harvested scope.',
            'hidden_by_default' => false,
        ];
    }

    return $meta;
}

function session_type_chip(?string $sessionStamp, ?string $profile = null): string
{
    $meta = session_type_meta($sessionStamp, $profile);
    return chip($meta['label'], $meta['tone']);
}

function session_type_label(?string $sessionStamp, ?string $profile = null): string
{
    $meta = session_type_meta($sessionStamp, $profile);
    return (string)$meta['label'];
}

function session_type_hint(?string $sessionStamp, ?string $profile = null): string
{
    $meta = session_type_meta($sessionStamp, $profile);
    return (string)$meta['hint'];
}

function session_type_hidden_by_default(?string $sessionStamp, ?string $profile = null): bool
{
    $meta = session_type_meta($sessionStamp, $profile);
    return (bool)($meta['hidden_by_default'] ?? false);
}

/** Session usability/state -> badge */
function session_usability_chip(?string $state): string
{
    $meta = session_usability_meta($state);
    return chip($meta['label'], $meta['tone']);
}

function session_usability_meta(?string $state): array
{
    $normalized = strtolower(trim((string)$state));
    return match ($normalized) {
        'usable_complete' => [
            'key' => 'usable_complete',
            'label' => 'Usable',
            'tone' => 'info',
            'hint' => 'Findings, permissions, strings, and other report-facing rows are finalized for this session.',
            'summary' => 'Completed and ready for report use',
        ],
        'in_progress_no_rows' => [
            'key' => 'in_progress_no_rows',
            'label' => 'In Progress',
            'tone' => 'medium',
            'hint' => 'This session started, but report-facing rows are not finalized yet.',
            'summary' => 'Session started but not finalized',
        ],
        'partial_rows' => [
            'key' => 'partial_rows',
            'label' => 'Partial',
            'tone' => 'low',
            'hint' => 'Some report-facing rows exist, but the session is not fully complete.',
            'summary' => 'Partially usable with missing surfaces',
        ],
        'failed' => [
            'key' => 'failed',
            'label' => 'Failed',
            'tone' => 'high',
            'hint' => 'The session failed or aborted before it produced a complete report set.',
            'summary' => 'Failed before finalization',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'unknown',
            'label' => $normalized !== '' ? ucfirst(str_replace('_', ' ', $normalized)) : 'Unknown',
            'tone' => 'muted',
            'hint' => 'Session usability is unknown and may need run-health review.',
            'summary' => 'Session state unknown',
        ],
    };
}

function session_usability_hint(?string $state): string
{
    $meta = session_usability_meta($state);
    return (string)$meta['hint'];
}

function session_usability_summary_text(?string $state): string
{
    $meta = session_usability_meta($state);
    return (string)$meta['summary'];
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
