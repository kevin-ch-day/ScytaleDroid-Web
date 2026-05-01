<?php
// database/db_lib/db_filters.php — shared filter maps and WHERE builders for db_func domain modules.
require_once __DIR__ . '/db_utils.php';

const _APPS_DIR_FILTERS = [
    'category' => ['category = :category'],
    'q' => [
        '('
        . 'CONVERT(package_name USING utf8mb4) COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(app_label USING utf8mb4) '
        . 'COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

const _RUNTIME_RUN_FILTERS = [
    'q' => [
        '('
        . 'CONVERT(package_name USING utf8mb4) COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(app_label USING utf8mb4) '
        . 'COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR dynamic_run_id LIKE :q_run'
        . ')',
        'like',
    ],
    'status' => ['LOWER(status) = LOWER(:status)'],
    'tier' => ['LOWER(tier) = LOWER(:tier)'],
];

const _FINDINGS_EXPLORER_FILTERS = [
    'severity' => ['LOWER(COALESCE(latest.severity, \'\')) = LOWER(:severity)'],
    'category' => ['COALESCE(latest.category, \'Uncategorized\') = :category'],
    'masvs_area' => ['COALESCE(latest.masvs_area, \'Unmapped\') = :masvs_area'],
    'detector' => ['COALESCE(latest.detector, \'unknown\') = :detector'],
    'session_stamp' => ['COALESCE(latest.session_stamp, \'\') = :session_stamp'],
    'q' => [
        '('
        . 'CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(latest.app_label USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(latest.title USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_title AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

const _COMPONENT_EXPOSURE_FILTERS = [
    'exported' => ['fp.exported = :exported'],
    'guard' => ['LOWER(COALESCE(fp.effective_guard, \'\')) = LOWER(:guard)'],
    'q' => [
        '('
        . 'CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(latest.app_label USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(fp.provider_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_provider AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(fp.authority USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_authority AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

/**
 * Internal: resolve transform names used in filter maps.
 */
function _xf(string $name): callable
{
    switch ($name) {
        case 'like':
            return static function ($v) {
                return '%' . $v . '%';
            };
        default:
            return static function ($v) {
                return $v;
            };
    }
}

/**
 * Build WHERE/params using the shared filter map.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _apps_dir_where(array $filters): array
{
    $map = [];
    foreach (_APPS_DIR_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for runtime deviation run filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _runtime_runs_where(array $filters): array
{
    $map = [];
    foreach (_RUNTIME_RUN_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for latest-findings explorer filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _findings_explorer_where(array $filters): array
{
    $map = [];
    foreach (_FINDINGS_EXPLORER_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for component exposure filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _component_exposure_where(array $filters): array
{
    $map = [];
    foreach (_COMPONENT_EXPOSURE_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

function _positive_limit(int $limit, int $max = 250): int
{
    return max(1, min($limit, $max));
}
