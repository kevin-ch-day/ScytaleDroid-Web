<?php
// database/db_lib/db_func.php — barrel include: load SQL + domain query modules.
//
// Domain modules live alongside this file (db_apps_directory.php, …) so each area can be
// reviewed and tested independently without changing page-level require paths.

require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

require_once __DIR__ . '/db_apps_directory.php';
require_once __DIR__ . '/db_fleet_runtime.php';
require_once __DIR__ . '/db_dynamic.php';
require_once __DIR__ . '/db_permission_intel.php';
require_once __DIR__ . '/db_findings_explorer.php';
require_once __DIR__ . '/db_static_session_health.php';
require_once __DIR__ . '/db_component_exposure.php';
require_once __DIR__ . '/db_app_reads.php';
