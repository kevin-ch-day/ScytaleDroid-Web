<?php
// database/db_lib/db_queries/permission_intel.php — Permission intelligence query constants.

const SQL_PERMISSION_INTEL_SESSION_OPTIONS = <<<SQL
SELECT sar.session_stamp
FROM static_analysis_runs sar
JOIN static_permission_matrix spm
  ON spm.run_id = sar.id
LEFT JOIN static_analysis_sessions sas
  ON sas.static_session_id = sar.static_session_id
WHERE COALESCE(sar.session_stamp, '') <> ''
  AND UPPER(COALESCE(sar.status, '')) = 'COMPLETED'
  AND COALESCE(sas.web_visibility_default, 'public') <> 'hidden'
  AND LOWER(sar.session_stamp) NOT LIKE '%smoke%'
  AND LOWER(sar.session_stamp) NOT LIKE '%debug%'
  AND LOWER(sar.session_stamp) NOT LIKE '%qa%'
GROUP BY sar.session_stamp
HAVING COUNT(*) > 0
ORDER BY sar.session_stamp DESC
LIMIT 200
SQL;
