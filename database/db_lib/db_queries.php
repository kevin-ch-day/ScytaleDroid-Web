<?php
// database/db_lib/db_queries.php — compatibility loader for SQL query constants.
//
// Keep page/domain modules requiring this file. Query text lives in smaller
// domain files under database/db_lib/db_queries/ so SQL changes stay reviewable.

require_once __DIR__ . '/db_queries/apps_directory.php';
require_once __DIR__ . '/db_queries/dashboard.php';
require_once __DIR__ . '/db_queries/app_detail.php';
require_once __DIR__ . '/db_queries/findings.php';
require_once __DIR__ . '/db_queries/permission_intel.php';
require_once __DIR__ . '/db_queries/component_exposure.php';
require_once __DIR__ . '/db_queries/static_health_diag.php';
require_once __DIR__ . '/db_queries/dynamic_runtime.php';
require_once __DIR__ . '/db_queries/dynamic_app.php';
require_once __DIR__ . '/db_queries/dynamic_run.php';
require_once __DIR__ . '/db_queries/dynamic_collection_queue.php';
