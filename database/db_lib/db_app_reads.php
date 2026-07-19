<?php
// database/db_lib/db_app_reads.php — compatibility loader for per-package/session DB helpers.

require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

require_once __DIR__ . '/db_app_reads/overview_sessions.php';
require_once __DIR__ . '/db_app_reads/findings.php';
require_once __DIR__ . '/db_app_reads/strings.php';
require_once __DIR__ . '/db_app_reads/permissions.php';
require_once __DIR__ . '/db_app_reads/components.php';
require_once __DIR__ . '/db_app_reads/diagnostics.php';
