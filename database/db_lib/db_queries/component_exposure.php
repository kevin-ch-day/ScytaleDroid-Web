<?php
// database/db_lib/db_queries/component_exposure.php — Component exposure query constants.

const SQL_COMPONENT_EXPOSURE_BASE = <<<SQL
SELECT
  latest.package_name,
  latest.app_label,
  COALESCE(cat.category_name, 'Uncategorized') AS category,
  latest.session_stamp,
  fp.provider_name,
  fp.component_name,
  fp.authority,
  fp.exported,
  fp.effective_guard,
  fp.risk,
  fp.read_permission,
  fp.write_permission,
  fp.base_permission,
  fp.created_at
FROM vw_static_finding_surfaces_latest latest
LEFT JOIN apps a
  ON CONVERT(a.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
LEFT JOIN android_app_categories cat
  ON cat.category_id = a.category_id
JOIN static_fileproviders fp
  ON CONVERT(fp.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND CONVERT(fp.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci
SQL;
const SQL_COMPONENT_EXPOSURE_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM vw_static_finding_surfaces_latest latest
JOIN static_fileproviders fp
  ON CONVERT(fp.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND CONVERT(fp.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci
SQL;

const SQL_COMPONENT_EXPOSURE_ORDER = <<<SQL
ORDER BY fp.exported DESC, latest.app_label ASC, fp.provider_name ASC
SQL;

const SQL_COMPONENT_EXPOSURE_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS provider_rows,
  SUM(CASE WHEN fp.exported = 1 THEN 1 ELSE 0 END) AS exported_rows,
  SUM(CASE WHEN fp.exported = 1 AND LOWER(COALESCE(fp.effective_guard, '')) IN ('', 'none', 'weak') THEN 1 ELSE 0 END) AS weak_guard_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps
FROM vw_static_finding_surfaces_latest latest
JOIN static_fileproviders fp
  ON CONVERT(fp.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND CONVERT(fp.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci =
     CONVERT(latest.session_stamp USING utf8mb4) COLLATE utf8mb4_unicode_ci
SQL;
