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
 *   active_session_row:?array<string,mixed>,
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
        'active_session_row' => null,
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

    $activeSession = $requestedSession;
    if ($activeSession === null && !empty($context['sessions'])) {
        $activeSession = (string)($context['sessions'][0]['session_stamp'] ?? '');
    }

    if ($activeSession === null && is_array($context['app'])) {
        $activeSession = guard_session(
            (string)(
                $context['app']['latest_static_session']
                ?? $context['app']['latest_audit_session']
                ?? ''
            )
        );
    }

    if ($activeSession !== null) {
        foreach ($context['sessions'] as $row) {
            if ((string)($row['session_stamp'] ?? '') === $activeSession) {
                $context['active_session_row'] = $row;
                break;
            }
        }
    }

    $context['active_session'] = $activeSession;
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
