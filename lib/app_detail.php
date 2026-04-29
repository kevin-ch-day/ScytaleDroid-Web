<?php
// lib/app_detail.php
require_once __DIR__ . '/guards.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

/**
 * Build shared package/session context for app detail pages.
 *
 * @return array{
 *   package_name:?string,
 *   requested_session:?string,
 *   active_session:?string,
 *   active_session_usable:bool,
 *   active_session_row:?array<string,mixed>,
 *   preferred_session:?string,
 *   preferred_session_row:?array<string,mixed>,
 *   newer_incomplete_session_row:?array<string,mixed>,
 *   app:?array<string,mixed>,
 *   sessions:array<int,array<string,mixed>>,
 *   error:?string
 * }
 */
function load_app_detail_context(?string $packageRaw, ?string $sessionRaw): array
{
    $packageName = guard_package_name($packageRaw);
    $requestedSession = guard_session($sessionRaw);

    $context = [
        'package_name' => $packageName,
        'requested_session' => $requestedSession,
        'active_session' => null,
        'active_session_usable' => false,
        'active_session_row' => null,
        'preferred_session' => null,
        'preferred_session_row' => null,
        'newer_incomplete_session_row' => null,
        'app' => null,
        'sessions' => [],
        'error' => null,
    ];

    if ($packageName === null) {
        return $context;
    }

    try {
        $context['app'] = app_overview($packageName);
        $context['sessions'] = app_sessions($packageName, 24);
    } catch (Throwable $e) {
        $context['error'] = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app detail context failed: ' . $e);
        return $context;
    }

    foreach ($context['sessions'] as $row) {
        if ((int)($row['is_usable_complete'] ?? 0) === 1) {
            $context['preferred_session_row'] = $row;
            $context['preferred_session'] = guard_session((string)($row['session_stamp'] ?? ''));
            break;
        }
    }

    $selectedRow = null;
    if ($requestedSession !== null) {
        foreach ($context['sessions'] as $row) {
            if ((string)($row['session_stamp'] ?? '') === $requestedSession) {
                $selectedRow = $row;
                break;
            }
        }
    }

    if ($selectedRow === null) {
        if (is_array($context['preferred_session_row'])) {
            $selectedRow = $context['preferred_session_row'];
        } elseif (!empty($context['sessions'])) {
            $selectedRow = $context['sessions'][0];
        }
    }

    if ($selectedRow !== null) {
        $context['active_session_row'] = $selectedRow;
        $context['active_session'] = guard_session((string)($selectedRow['session_stamp'] ?? ''));
        $context['active_session_usable'] = (int)($selectedRow['is_usable_complete'] ?? 0) === 1;
    }

    if (
        is_array($context['preferred_session_row'])
        && !empty($context['sessions'])
    ) {
        $firstRow = $context['sessions'][0];
        $firstStamp = (string)($firstRow['session_stamp'] ?? '');
        $preferredStamp = (string)($context['preferred_session_row']['session_stamp'] ?? '');
        if (
            $firstStamp !== ''
            && $firstStamp !== $preferredStamp
            && strtolower((string)($firstRow['session_usability'] ?? '')) === 'in_progress_no_rows'
        ) {
            $context['newer_incomplete_session_row'] = $firstRow;
        }
    }

    return $context;
}


/**
 * Decode JSON into an assoc array with a predictable fallback.
 *
 * @return array<string,mixed>
 */
function decode_assoc_json(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }

    try {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    } catch (Throwable $e) {
        return [];
    }
}
