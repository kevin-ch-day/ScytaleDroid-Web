<?php
// lib/app_report_payload.php — Compose app report view variables (DB reads + derived metrics).

require_once __DIR__ . '/app_detail.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

/**
 * Build variables for pages/app_report.php body (after load_app_detail_context).
 * Caller MUST only invoke when package/session/app are usable for detail queries.
 *
 * @param array<string,mixed> $context
 * @return array<string,mixed>
 */
function build_app_report_payload(array $context): array
{
    $packageName = $context['package_name'];
    $app = $context['app'];
    $activeSession = $context['active_session'];
    /** @var array<string,mixed> $asr Selected session row (may be empty if context omitted it). */
    $asr = is_array($context['active_session_row']) ? $context['active_session_row'] : [];
    $activeSessionUsable = (bool) $context['active_session_usable'];
    $preferredSession = $context['preferred_session'];

    $findingSummary = null;
    $reportSummaryRow = null;
    $topFindings = [];
    $permissionRows = [];
    $permissionSummaryRow = null;
    $stringsSummary = null;
    $stringSamples = [];
    $fileProviders = [];
    $providerAcl = [];
    $componentSummaryRow = null;
    $dynamicSummary = [];
    $dynamicRuns = [];
    $dbErrorDuringPayload = null;

    try {
        $reportSummaryRow = app_report_summary($packageName, $activeSession);
        $findingSummary = app_findings_summary($packageName, $activeSession);
        $topFindings = app_findings_list($packageName, $activeSession, 8);
        $permissionSummaryRow = app_permission_summary($packageName, $activeSession);
        $permissionRows = app_permissions($packageName, $activeSession, 12);
        $stringsSummary = app_strings_summary($packageName, $activeSession);
        $stringSamples = app_string_samples($packageName, $activeSession, 6);
        $componentSummaryRow = app_component_summary($packageName, $activeSession);
        $fileProviders = app_fileproviders($packageName, $activeSession, 12);
        $providerAcl = app_provider_acl($packageName, $activeSession, 12);
        $dynamicSummary = app_dynamic_summary($packageName);
        $dynamicRuns = app_dynamic_runs($packageName, 5);
    } catch (Throwable $e) {
        $dbErrorDuringPayload = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app report payload failed: ' . $e);
    }

    $rs = is_array($reportSummaryRow) ? $reportSummaryRow : [];
    $fs = is_array($findingSummary) ? $findingSummary : [];
    $ssum = is_array($stringsSummary) ? $stringsSummary : [];
    $csr = is_array($componentSummaryRow) ? $componentSummaryRow : [];
    $psr = is_array($permissionSummaryRow) ? $permissionSummaryRow : [];

    $details = decode_assoc_json(is_array($app) ? ($app['details_json'] ?? null) : null);

    $selectedGrade = $activeSessionUsable
        ? ($rs['grade'] ?? $asr['grade'] ?? ($app['grade'] ?? null))
        : ($app['grade'] ?? null);
    $selectedScore = $activeSessionUsable
        ? ($rs['score_capped'] ?? $asr['score_capped'] ?? ($app['score_capped'] ?? null))
        : ($app['score_capped'] ?? null);
    $selectedHigh = (int) ($rs['high'] ?? $fs['high'] ?? ($asr['high'] ?? ($app['high'] ?? 0)));
    $selectedMed = (int) ($rs['med'] ?? $fs['med'] ?? ($asr['med'] ?? ($app['med'] ?? 0)));
    $selectedLow = (int) ($rs['low'] ?? $fs['low'] ?? ($asr['low'] ?? ($app['low'] ?? 0)));
    $selectedInfo = (int) ($rs['info'] ?? $fs['info'] ?? ($asr['info'] ?? ($app['info'] ?? 0)));
    $selectedDangerous = (int) ($rs['dangerous_count'] ?? ($asr['dangerous_count'] ?? ($app['dangerous_count'] ?? 0)));
    $selectedHighEntropy = (int) ($rs['high_entropy'] ?? ($ssum['high_entropy'] ?? ($asr['high_entropy'] ?? ($app['high_entropy'] ?? 0))));
    $sessionUsability = strtolower((string) ($asr['session_usability'] ?? 'unknown'));
    $sessionUsabilitySummary = session_usability_summary_text($sessionUsability);
    $sessionUsabilityHint = session_usability_hint($sessionUsability);
    $runHealthUrl = url('pages/run_health.php');
    $scoreMeta = score_display_meta(
        is_string($selectedGrade) ? $selectedGrade : null,
        $selectedScore,
        (string) ($app['source_state'] ?? '')
    );
    $sessionHealth = [
        'status' => (string) ($rs['run_status'] ?? ($asr['run_status'] ?? 'UNKNOWN')),
        'findings_total' => (int) ($rs['findings_total'] ?? ($asr['findings_total'] ?? 0)),
        'permission_rows' => (int) ($rs['permission_rows'] ?? ($asr['permission_rows'] ?? 0)),
        'string_rows' => (int) ($rs['string_rows'] ?? ($asr['string_rows'] ?? 0)),
        'audit_rows' => (int) ($rs['audit_rows'] ?? ($asr['audit_rows'] ?? 0)),
        'link_rows' => (int) ($rs['link_rows'] ?? ($asr['link_rows'] ?? 0)),
    ];
    $reportRow = $rs;
    $findingPersistenceRuntime = $reportRow['findings_runtime_total'] ?? null;
    $findingPersistenceCapped = isset($reportRow['findings_capped_total']) ? (int) $reportRow['findings_capped_total'] : null;
    $findingPersistenceCappedJson = $reportRow['findings_capped_by_detector_json'] ?? null;
    $findingPersistenceCappedText = '';
    if ($findingPersistenceCappedJson !== null && $findingPersistenceCappedJson !== '') {
        if (is_array($findingPersistenceCappedJson)) {
            $findingPersistenceCappedText = json_encode($findingPersistenceCappedJson, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($findingPersistenceCappedJson)) {
            $decodedCap = decode_assoc_json($findingPersistenceCappedJson);
            $findingPersistenceCappedText = $decodedCap !== []
                ? json_encode($decodedCap, JSON_UNESCAPED_UNICODE)
                : $findingPersistenceCappedJson;
        } else {
            $findingPersistenceCappedText = json_encode($findingPersistenceCappedJson, JSON_UNESCAPED_UNICODE);
        }
    }
    $showFindingReconcile = (
        ($findingPersistenceRuntime !== null && $findingPersistenceRuntime !== '')
        || ($findingPersistenceCapped !== null && $findingPersistenceCapped > 0)
        || $findingPersistenceCappedText !== ''
    );

    $componentSummary = [
        'providers' => (int) ($rs['providers'] ?? ($csr['providers'] ?? count($fileProviders))),
        'exported_providers' => (int) ($rs['exported_providers'] ?? ($csr['exported_providers'] ?? 0)),
        'weak_provider_guards' => (int) ($rs['weak_provider_guards'] ?? ($csr['weak_provider_guards'] ?? 0)),
        'acl_rows' => (int) ($rs['acl_rows'] ?? ($csr['acl_rows'] ?? count($providerAcl))),
    ];
    if (!is_array($componentSummaryRow)) {
        foreach ($fileProviders as $row) {
            $exported = (int) ($row['exported'] ?? 0) === 1;
            if ($exported) {
                $componentSummary['exported_providers']++;
            }
            $guard = strtolower((string) ($row['effective_guard'] ?? ''));
            if ($exported && ($guard === '' || in_array($guard, ['none', 'weak'], true))) {
                $componentSummary['weak_provider_guards']++;
            }
        }
    }

    $sessionType = (string) ($asr['session_type_label'] ?? session_type_label((string) $activeSession, (string) ($asr['profile'] ?? '')));

    $permissionSummary = [
        'dangerous' => (int) ($rs['dangerous_count'] ?? ($psr['dangerous_count'] ?? 0)),
        'signature_privileged' => (int) (
            ($rs['signature_count'] ?? $psr['signature_count'] ?? 0)
            + ($rs['privileged_count'] ?? $psr['privileged_count'] ?? 0)
        ),
        'special_access' => (int) ($rs['special_access_count'] ?? ($psr['special_access_count'] ?? 0)),
        'custom_defined' => (int) ($rs['custom_count'] ?? ($psr['custom_count'] ?? 0)),
    ];
    $permissionHighlights = [];
    foreach ($permissionRows as $row) {
        if (count($permissionHighlights) < 4) {
            $permissionHighlights[] = [
                'name' => (string) ($row['permission_name'] ?? ''),
                'protection' => (string) ($row['protection'] ?? '—'),
                'source' => (string) ($row['source'] ?? '—'),
                'weight' => (int) ($row['severity'] ?? 0),
            ];
        }
    }
    if (!is_array($permissionSummaryRow)) {
        foreach ($permissionRows as $row) {
            if ((int) ($row['is_runtime_dangerous'] ?? 0) === 1) {
                $permissionSummary['dangerous']++;
            }
            if ((int) ($row['is_signature'] ?? 0) === 1 || (int) ($row['is_privileged'] ?? 0) === 1) {
                $permissionSummary['signature_privileged']++;
            }
            if ((int) ($row['is_special_access'] ?? 0) === 1) {
                $permissionSummary['special_access']++;
            }
            if ((int) ($row['is_custom'] ?? 0) === 1) {
                $permissionSummary['custom_defined']++;
            }
        }
    }

    $providerHighlights = [];
    foreach ($fileProviders as $row) {
        $exported = (int) ($row['exported'] ?? 0) === 1;
        $guard = strtolower((string) ($row['effective_guard'] ?? ''));
        if ($exported && ($guard === '' || in_array($guard, ['none', 'weak'], true))) {
            $providerHighlights[] = [
                'provider_name' => (string) ($row['provider_name'] ?? $row['component_name'] ?? ''),
                'authority' => (string) ($row['authority'] ?? ''),
                'guard' => (string) ($row['effective_guard'] ?? '—'),
            ];
        }
        if (count($providerHighlights) >= 3) {
            break;
        }
    }

    $stringHighlights = [];
    foreach ($stringSamples as $row) {
        if (count($stringHighlights) >= 3) {
            break;
        }
        $stringHighlights[] = [
            'bucket' => (string) ($row['bucket'] ?? 'unknown'),
            'value' => (string) ($row['value_masked'] ?? ''),
            'risk_tag' => (string) ($row['risk_tag'] ?? ''),
        ];
    }

    $topRiskPatterns = [];
    if (!empty($scoreMeta['risk_band'])) {
        $topRiskPatterns[] = [
            'title' => 'Composite risk posture',
            'summary' => 'Normalized score ' . (string) $scoreMeta['normalized_score_text'] . ' · Risk band: ' . (string) $scoreMeta['risk_band'],
            'tone' => strtolower((string) ($scoreMeta['risk_band_tone'] ?? 'high')),
        ];
    }
    if ($componentSummary['weak_provider_guards'] > 0) {
        $topRiskPatterns[] = [
            'title' => 'Exported providers with weak guards',
            'summary' => $componentSummary['weak_provider_guards'] . ' provider surfaces need stronger ACLs or permissions.',
            'tone' => 'high',
        ];
    }
    if ($selectedDangerous > 0) {
        $topRiskPatterns[] = [
            'title' => 'Sensitive permission exposure',
            'summary' => $selectedDangerous . ' dangerous permissions are requested in the selected session.',
            'tone' => 'medium',
        ];
    }
    if ($selectedHighEntropy > 0) {
        $topRiskPatterns[] = [
            'title' => 'High-entropy string exposure',
            'summary' => $selectedHighEntropy . ' high-entropy string indicators were persisted for review.',
            'tone' => 'medium',
        ];
    }
    foreach ($topFindings as $row) {
        $title = (string) ($row['title'] ?? '');
        if ($title === '' || str_starts_with($title, 'Composite risk')) {
            continue;
        }
        $topRiskPatterns[] = [
            'title' => $title,
            'summary' => finding_evidence_excerpt($row['evidence'] ?? null, 140),
            'tone' => strtolower((string) ($row['severity'] ?? 'info')),
        ];
        if (count($topRiskPatterns) >= 5) {
            break;
        }
    }
    $topRiskPatterns = array_slice($topRiskPatterns, 0, 5);

    $scoreDrivers = [];
    if (!empty($scoreMeta['risk_band'])) {
        $scoreDrivers[] = 'Normalized static score ' . (string) $scoreMeta['normalized_score_text']
            . ' maps to the ' . (string) $scoreMeta['risk_band'] . ' analyst band.';
    }
    if ($selectedHigh > 0 || $selectedMed > 0) {
        $scoreDrivers[] = 'Severity totals are ' . fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)
            . ', so repeated high and medium findings are part of the posture.';
    }
    if ($componentSummary['weak_provider_guards'] > 0) {
        $scoreDrivers[] = $componentSummary['weak_provider_guards']
            . ' exported provider surfaces are missing strong guards in this selected session.';
    }
    if ($selectedDangerous > 0) {
        $scoreDrivers[] = $selectedDangerous
            . ' dangerous permissions are requested in the selected session.';
    }
    if ($selectedHighEntropy > 0) {
        $scoreDrivers[] = $selectedHighEntropy
            . ' persisted high-entropy string indicators contribute to manual review pressure.';
    }
    $scoreDrivers = array_slice($scoreDrivers, 0, 4);

    return [
        'dbErrorDuringPayload' => $dbErrorDuringPayload,
        'findingSummary' => $findingSummary,
        'reportSummaryRow' => $reportSummaryRow,
        'topFindings' => $topFindings,
        'permissionRows' => $permissionRows,
        'permissionSummaryRow' => $permissionSummaryRow,
        'stringsSummary' => $stringsSummary,
        'stringSamples' => $stringSamples,
        'fileProviders' => $fileProviders,
        'providerAcl' => $providerAcl,
        'componentSummaryRow' => $componentSummaryRow,
        'dynamicSummary' => $dynamicSummary,
        'dynamicRuns' => $dynamicRuns,
        'details' => $details,
        'selectedGrade' => $selectedGrade,
        'selectedScore' => $selectedScore,
        'selectedHigh' => $selectedHigh,
        'selectedMed' => $selectedMed,
        'selectedLow' => $selectedLow,
        'selectedInfo' => $selectedInfo,
        'selectedDangerous' => $selectedDangerous,
        'selectedHighEntropy' => $selectedHighEntropy,
        'sessionUsability' => $sessionUsability,
        'sessionUsabilitySummary' => $sessionUsabilitySummary,
        'sessionUsabilityHint' => $sessionUsabilityHint,
        'runHealthUrl' => $runHealthUrl,
        'scoreMeta' => $scoreMeta,
        'sessionHealth' => $sessionHealth,
        'findingPersistenceRuntime' => $findingPersistenceRuntime,
        'findingPersistenceCapped' => $findingPersistenceCapped,
        'findingPersistenceCappedText' => $findingPersistenceCappedText,
        'showFindingReconcile' => $showFindingReconcile,
        'componentSummary' => $componentSummary,
        'sessionType' => $sessionType,
        'permissionSummary' => $permissionSummary,
        'permissionHighlights' => $permissionHighlights,
        'providerHighlights' => $providerHighlights,
        'stringHighlights' => $stringHighlights,
        'topRiskPatterns' => $topRiskPatterns,
        'scoreDrivers' => $scoreDrivers,
    ];
}
