<?php
// lib/render.php

/** HTML escape */
function e($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Log an internal failure without disclosing database or schema details to a
 * browser. Pages can render the returned message directly.
 */
function page_error_message(string $surface, Throwable $error): string
{
    $label = trim($surface) !== '' ? trim($surface) : 'page';
    error_log('[ScytaleDroid-Web] ' . $label . ' failed: ' . $error->getMessage());
    return 'Data could not be loaded. Retry shortly or check the server logs.';
}

/** Format timestamp (YYYY-MM-DD HH:MM) if present */
function fmt_date(?string $ts): string
{
    if (!$ts) return '';
    // assume MySQL TIMESTAMP/TEXT; print as-is if parsing fails
    $t = strtotime($ts);
    return $t ? date('Y-m-d H:i', $t) : $ts;
}

/** Format timestamp for operator-facing table scans (e.g. 6/3/2026 5:47 pm). */
function fmt_date_compact(?string $ts): string
{
    if (!$ts) return '';
    $t = strtotime($ts);
    return $t ? date('n/j/Y g:i a', $t) : $ts;
}

function runtime_format_number($value, int $decimals = 1): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format((float)$value, $decimals);
}

function runtime_format_percent($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format(((float)$value) * 100, 1) . '%';
}

function runtime_format_bool($value): string
{
    if ($value === null || $value === '') {
        return 'unknown';
    }
    return ((int)$value) === 1 ? 'yes' : 'no';
}

function runtime_format_csv($value): string
{
    $text = trim((string)($value ?? ''));
    return $text === '' ? '-' : $text;
}

function runtime_csv_has($value, string $needle): bool
{
    $parts = array_map('trim', explode(',', strtolower((string)($value ?? ''))));
    return in_array(strtolower($needle), $parts, true);
}

function runtime_baseline_class(array $run, bool $detailed = false): string
{
    $profile = strtolower(trim((string)($run['run_profile'] ?? '')));
    if (!str_starts_with($profile, 'baseline')) {
        return $detailed ? 'Not a baseline run' : 'not baseline';
    }
    if (((int)($run['baseline_not_idle'] ?? 0)) !== 1) {
        return 'Strict Idle';
    }
    return $detailed
        ? 'Quiescent FG (valid retained evidence outside Strict Idle quota)'
        : 'QFG retained';
}

function runtime_qfg_reasons(array $run): string
{
    $raw = trim((string)($run['baseline_not_idle_reasons_json'] ?? ''));
    if ($raw === '') {
        return '—';
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return '—';
    }
    $reasons = array_filter(array_map(static fn ($value): string => trim((string)$value), $decoded));
    return $reasons === [] ? '—' : implode(', ', $reasons);
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

/** Treat empty/generic labels as presentation-only placeholders. */
function is_placeholder_label(?string $value): bool
{
    $normalized = strtolower(trim((string)($value ?? '')));
    return in_array($normalized, ['', 'unclassified', 'uncategorized', 'unknown', 'n/a', 'none'], true);
}

/** Compact list-row subtitle: prefer profile when meaningful, otherwise category. */
function app_secondary_label(?string $profile, ?string $category): string
{
    $profile = trim((string)($profile ?? ''));
    $category = trim((string)($category ?? ''));

    if (!is_placeholder_label($profile)) {
        return $profile;
    }
    if (!is_placeholder_label($category)) {
        return $category;
    }
    return '';
}

/** Detail-page subtitle fragments without generic placeholder noise. */
function app_context_fragments(?string $category, ?string $profile): array
{
    $fragments = [];
    $category = trim((string)($category ?? ''));
    $profile = trim((string)($profile ?? ''));

    if (!is_placeholder_label($category)) {
        $fragments[] = $category;
    }
    if (!is_placeholder_label($profile) && strcasecmp($profile, $category) !== 0) {
        $fragments[] = $profile;
    }

    return $fragments;
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
    return score_display_meta(null, $scoreCapped, $sourceState)['normalized_score_text'];
}

function app_directory_severity_value(array $row, string $key): string
{
    $state = strtolower(trim((string)($row['source_state'] ?? '')));
    if (in_array($state, ['catalog', 'catalog_only'], true)) {
        return '—';
    }
    return (string)((int)($row[$key] ?? 0));
}

/** Shared analyst-facing score contract. Raw/internal scores stay diagnostics-only. */
function score_display_meta(?string $grade, $scoreCapped, ?string $sourceState = null): array
{
    $state = strtolower(trim((string)$sourceState));
    $catalogOnly = in_array($state, ['catalog', 'catalog_only'], true);
    $gradeText = strtoupper(trim((string)$grade));
    $scoreRaw = trim((string)($scoreCapped ?? ''));
    $scorePresent = $scoreRaw !== '' && is_numeric($scoreRaw);

    $normalizedScoreText = 'Risk score missing';
    $normalizedScoreShort = null;
    if ($catalogOnly) {
        $normalizedScoreText = '—';
    } elseif ($scorePresent) {
        $normalizedScoreText = number_format((float)$scoreRaw, 3, '.', '');
        $normalizedScoreShort = $normalizedScoreText;
    }

    $band = null;
    if ($scorePresent) {
        $scoreValue = (float)$scoreRaw;
        if ($scoreValue >= 8.0) {
            $band = 'Critical';
        } elseif ($scoreValue >= 6.0) {
            $band = 'High';
        } elseif ($scoreValue >= 4.0) {
            $band = 'Medium';
        } elseif ($scoreValue >= 2.0) {
            $band = 'Low';
        } else {
            $band = 'Minimal';
        }
    } elseif ($gradeText !== '') {
        $band = match ($gradeText) {
            'F' => 'Critical',
            'D' => 'High',
            'C' => 'Medium',
            'B' => 'Low',
            'A' => 'Minimal',
            default => null,
        };
    }

    $bandTone = match ($band) {
        'Critical' => 'critical',
        'High' => 'high',
        'Medium' => 'medium',
        'Low' => 'low',
        'Minimal' => 'info',
        default => 'muted',
    };

    return [
        'grade_text' => $catalogOnly ? 'Not analyzed' : ($gradeText !== '' ? $gradeText : '—'),
        'normalized_score_text' => $normalizedScoreText,
        'normalized_score_short' => $normalizedScoreShort,
        'risk_band' => $band,
        'risk_band_tone' => $bandTone,
        'has_score' => $scorePresent,
        'is_catalog_only' => $catalogOnly,
    ];
}

function permission_custom_family_label(?string $family): string
{
    return match (strtolower(trim((string)$family))) {
        'app_defined_internal' => 'App Defined Internal',
        'vendor_oem' => 'Vendor / OEM',
        'google_platform_adjacent' => 'Google / Platform Adjacent',
        'launcher_badge' => 'Launcher / Badge Ecosystem',
        'amazon_ecosystem' => 'Amazon Ecosystem',
        'android_platform_adjacent' => 'Android Platform Adjacent',
        'meta_ecosystem' => 'Meta Ecosystem',
        'publisher_ecosystem' => 'Publisher Ecosystem',
        'unknown_custom' => 'Unknown Custom',
        default => $family !== null && trim((string)$family) !== '' ? ucwords(str_replace('_', ' ', (string)$family)) : '—',
    };
}

function score_chip(?string $grade, $scoreCapped, ?string $sourceState = null): string
{
    $meta = score_display_meta($grade, $scoreCapped, $sourceState);
    if (!$meta['has_score'] || $meta['normalized_score_short'] === null) {
        return chip($meta['normalized_score_text'], 'muted');
    }
    return chip('Score ' . $meta['normalized_score_short'], 'medium');
}

function risk_band_chip(?string $grade, $scoreCapped, ?string $sourceState = null): string
{
    $meta = score_display_meta($grade, $scoreCapped, $sourceState);
    if (!$meta['risk_band']) {
        return chip('Risk band unavailable', 'muted');
    }
    return chip((string)$meta['risk_band'], (string)$meta['risk_band_tone']);
}

function runtime_feature_state_meta(?string $state): array
{
    $normalized = strtolower(trim((string)$state));
    return match ($normalized) {
        'features_available' => [
            'key' => 'features_available',
            'label' => 'Features ready',
            'tone' => 'info',
            'hint' => 'Derived dynamic feature rows are available for this run.',
        ],
        'missing_features' => [
            'key' => 'missing_features',
            'label' => 'Features missing',
            'tone' => 'medium',
            'hint' => 'The run exists, but derived dynamic feature rows are missing.',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'unknown_features',
            'label' => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Features unknown',
            'tone' => 'muted',
            'hint' => 'Feature availability could not be classified from the current runtime view.',
        ],
    };
}

function runtime_feature_state_chip(?string $state): string
{
    $meta = runtime_feature_state_meta($state);
    return chip((string)$meta['label'], (string)$meta['tone']);
}

function runtime_feature_state_hint(?string $state): string
{
    $meta = runtime_feature_state_meta($state);
    return (string)$meta['hint'];
}

function runtime_static_link_state_meta(?string $state): array
{
    $normalized = strtolower(trim((string)$state));
    return match ($normalized) {
        'static_linked' => [
            'key' => 'static_linked',
            'label' => 'Static linked',
            'tone' => 'info',
            'hint' => 'This dynamic run is linked to a canonical static run.',
        ],
        'missing_static_run_id' => [
            'key' => 'missing_static_run_id',
            'label' => 'Missing static link',
            'tone' => 'high',
            'hint' => 'No canonical static_run_id is attached, so static-to-dynamic joins are limited.',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'unknown_static_link',
            'label' => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Static link unknown',
            'tone' => 'muted',
            'hint' => 'Static linkage could not be classified from the current runtime view.',
        ],
    };
}

function runtime_static_link_state_chip(?string $state): string
{
    $meta = runtime_static_link_state_meta($state);
    return chip((string)$meta['label'], (string)$meta['tone']);
}

function runtime_static_link_state_hint(?string $state): string
{
    $meta = runtime_static_link_state_meta($state);
    return (string)$meta['hint'];
}

function runtime_technical_validity_meta(?string $state): array
{
    $normalized = strtoupper(trim((string)$state));
    return match ($normalized) {
        'TECH_VALID' => [
            'key' => 'TECH_VALID',
            'label' => 'Valid evidence',
            'short_label' => 'valid',
            'tone' => 'info',
            'hint' => 'This run satisfies the current technical dataset-validity checks.',
        ],
        'TECH_INVALID' => [
            'key' => 'TECH_INVALID',
            'label' => 'Invalid / skipped',
            'short_label' => 'invalid',
            'tone' => 'high',
            'hint' => 'This run is excluded from quota-valid and paper-facing evidence.',
        ],
        'TECH_LEGACY_UNKNOWN' => [
            'key' => 'TECH_LEGACY_UNKNOWN',
            'label' => 'Unevaluated historical',
            'short_label' => 'historical',
            'tone' => 'muted',
            'hint' => 'This retained historical run predates the current normalized validity-state model or has not been fully reclassified.',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'TECH_UNRESOLVED',
            'label' => $normalized !== '' ? ucwords(strtolower(str_replace('_', ' ', $normalized))) : 'Validity unknown',
            'short_label' => $normalized !== '' ? strtolower(str_replace('_', ' ', $normalized)) : 'unknown',
            'tone' => 'muted',
            'hint' => 'Technical validity could not be classified from the current runtime view.',
        ],
    };
}

function runtime_technical_validity_chip(?string $state): string
{
    $meta = runtime_technical_validity_meta($state);
    return chip((string)$meta['label'], (string)$meta['tone']);
}

function runtime_technical_validity_label(?string $state): string
{
    $meta = runtime_technical_validity_meta($state);
    return (string)$meta['label'];
}

function runtime_quota_state_meta(?string $state): array
{
    $normalized = strtoupper(trim((string)$state));
    return match ($normalized) {
        'QUOTA_VALID' => [
            'key' => 'QUOTA_VALID',
            'label' => 'Quota-valid',
            'short_label' => 'quota-valid',
            'tone' => 'info',
            'hint' => 'This run counts toward the current quota-valid dynamic evidence target.',
        ],
        'SUPPLEMENTAL_VALID' => [
            'key' => 'SUPPLEMENTAL_VALID',
            'label' => 'Supplemental',
            'short_label' => 'supplemental',
            'tone' => 'medium',
            'hint' => 'This run is technically valid, but retained outside the capped quota count.',
        ],
        'QUOTA_INELIGIBLE' => [
            'key' => 'QUOTA_INELIGIBLE',
            'label' => 'Invalid / skipped',
            'short_label' => 'invalid',
            'tone' => 'high',
            'hint' => 'This run is not eligible for quota-valid evidence.',
        ],
        'VALID_COUNTING_UNKNOWN' => [
            'key' => 'VALID_COUNTING_UNKNOWN',
            'label' => 'Valid, counting unknown',
            'short_label' => 'valid unknown',
            'tone' => 'medium',
            'hint' => 'The run appears technically valid, but quota counting has not been normalized yet.',
        ],
        'QUOTA_LEGACY_UNKNOWN' => [
            'key' => 'QUOTA_LEGACY_UNKNOWN',
            'label' => 'Unevaluated historical',
            'short_label' => 'historical',
            'tone' => 'muted',
            'hint' => 'This retained historical run predates the current quota-state model or has not been fully reclassified.',
        ],
        default => [
            'key' => $normalized !== '' ? $normalized : 'QUOTA_UNRESOLVED',
            'label' => $normalized !== '' ? ucwords(strtolower(str_replace('_', ' ', $normalized))) : 'Quota unknown',
            'short_label' => $normalized !== '' ? strtolower(str_replace('_', ' ', $normalized)) : 'unknown',
            'tone' => 'muted',
            'hint' => 'Quota state could not be classified from the current runtime view.',
        ],
    };
}

function runtime_quota_state_chip(?string $state): string
{
    $meta = runtime_quota_state_meta($state);
    return chip((string)$meta['label'], (string)$meta['tone']);
}

function runtime_quota_state_label(?string $state): string
{
    $meta = runtime_quota_state_meta($state);
    return (string)$meta['label'];
}

function dynamic_evidence_quality_meta(array $summary): array
{
    $dynamicRuns = (int)($summary['dynamic_runs'] ?? 0);
    $quotaValidRuns = (int)($summary['quota_valid_runs'] ?? 0);
    $supplementalRuns = (int)($summary['supplemental_valid_runs'] ?? 0);
    $invalidRuns = (int)($summary['invalid_or_skipped_runs'] ?? 0);
    $unevaluatedHistoricalRuns = (int)($summary['unevaluated_historical_runs'] ?? 0);
    $staticLinkedRuns = (int)($summary['static_linked_runs'] ?? 0);
    $featuresAvailableRuns = (int)($summary['features_available_runs'] ?? 0);

    if ($dynamicRuns <= 0) {
        return [
            'label' => 'Dynamic missing',
            'tone' => 'muted',
            'summary' => 'No dynamic runtime rows are available for this package.',
        ];
    }

    if ($quotaValidRuns > 0 && $staticLinkedRuns > 0 && $featuresAvailableRuns > 0) {
        return [
            'label' => 'Dynamic bridge ready',
            'tone' => 'info',
            'summary' => $quotaValidRuns . ' quota-valid run(s), '
                . $staticLinkedRuns . ' statically linked, '
                . $featuresAvailableRuns . ' with derived features.',
        ];
    }

    if (($quotaValidRuns + $supplementalRuns) > 0 && ($staticLinkedRuns > 0 || $featuresAvailableRuns > 0)) {
        $counts = [];
        if ($quotaValidRuns > 0) {
            $counts[] = $quotaValidRuns . ' quota-valid';
        }
        if ($supplementalRuns > 0) {
            $counts[] = $supplementalRuns . ' supplemental';
        }
        return [
            'label' => 'Dynamic partial',
            'tone' => 'medium',
            'summary' => implode(' and ', $counts) . ' run(s), but static linkage or derived features remain incomplete.',
        ];
    }

    if ($supplementalRuns > 0 && $quotaValidRuns <= 0) {
        return [
            'label' => 'Dynamic supplemental only',
            'tone' => 'medium',
            'summary' => $supplementalRuns . ' supplemental valid run(s) are available, but quota-valid coverage is still absent.',
        ];
    }

    if ($invalidRuns > 0 && ($quotaValidRuns + $supplementalRuns) <= 0) {
        return [
            'label' => 'Dynamic invalid only',
            'tone' => 'high',
            'summary' => $invalidRuns . ' runtime run(s) exist, but all current evidence is invalid or skipped.',
        ];
    }

    if ($unevaluatedHistoricalRuns > 0 && ($quotaValidRuns + $supplementalRuns + $invalidRuns) <= 0) {
        return [
            'label' => 'Dynamic historical only',
            'tone' => 'muted',
            'summary' => $unevaluatedHistoricalRuns . ' historical runtime row(s) exist, but they remain unevaluated under the current model.',
        ];
    }

    return [
        'label' => 'Dynamic weak',
        'tone' => 'high',
        'summary' => 'Dynamic rows exist, but quota-valid evidence or static bridge coverage is still weak.',
    ];
}

function dynamic_evidence_quality_chip(array $summary): string
{
    $meta = dynamic_evidence_quality_meta($summary);
    return chip((string)$meta['label'], (string)$meta['tone']);
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

/** Human-friendly finding evidence summary (string, JSON string, or decoded JSON array/object). */
function finding_evidence_excerpt($evidence, int $maxLen = 220): string
{
    if ($evidence === null) {
        return '';
    }
    if (is_object($evidence)) {
        return finding_evidence_excerpt((array) $evidence, $maxLen);
    }
    if (is_array($evidence)) {
        foreach (['detail', 'evidence', 'message', 'summary', 'path', 'value'] as $key) {
            if (!empty($evidence[$key]) && is_scalar($evidence[$key])) {
                return finding_evidence_excerpt((string) $evidence[$key], $maxLen);
            }
        }
        $json = json_encode($evidence, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return '';
        }
        $json = trim($json);

        return mb_strimwidth($json, 0, $maxLen, '…');
    }

    $evidence = trim((string) $evidence);
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
                $evidence = (string) $decoded[$key];
                break;
            }
        }
    }

    $evidence = preg_replace('/\s+/u', ' ', $evidence) ?? $evidence;

    return mb_strimwidth($evidence, 0, $maxLen, '…');
}

/** Full evidence block for pre/code display (handles JSON columns returned as arrays). */
function finding_evidence_display_text($evidence): string
{
    if ($evidence === null || $evidence === '') {
        return 'No evidence payload';
    }
    if (is_object($evidence)) {
        $evidence = (array) $evidence;
    }
    if (is_array($evidence)) {
        $json = json_encode(
            $evidence,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $json !== false ? $json : 'No evidence payload';
    }

    return trim((string) $evidence);
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
