<?php
// database/db_lib/db_utils.php
declare(strict_types=1);

require_once __DIR__ . '/../db_core/db_engine.php';

/**
 * Bind parameters on a prepared PDO statement (named or positional).
 * - Positional: 0-based index in $params -> 1-based placeholder.
 * - Named: normalizes keys to ':name'.
 * - Types: NULL/BOOL/INT mapped to PDO types; everything else -> STR.
 *
 * @param \PDOStatement $st
 * @param array<int|string,mixed> $params
 */
function _db_bind_params(\PDOStatement $st, array $params): void
{
    foreach ($params as $k => $v) {
        // positional (0,1,2,...) -> (1,2,3,...)
        if (is_int($k)) {
            $name = $k + 1;
        } else {
            // normalize to named (e.g., ':q')
            $name = ':' . ltrim((string)$k, ':');
        }

        if ($v === null) {
            $type = \PDO::PARAM_NULL;
        } elseif (is_bool($v)) {
            $type = \PDO::PARAM_BOOL;
        } elseif (is_int($v)) {
            $type = \PDO::PARAM_INT;
        } else {
            $type = \PDO::PARAM_STR;
        }

        $st->bindValue($name, $v, $type);
    }
}

/**
 * Prepare and execute a SELECT, returning all rows (assoc).
 *
 * @param string $sql
 * @param array<int|string,mixed> $params
 * @return array<int,array<string,mixed>>
 */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    _db_bind_params($st, $params);
    $st->execute();
    /** @var array<int,array<string,mixed>> $rows */
    $rows = $st->fetchAll();
    return $rows ?: [];
}

/**
 * Execute a SELECT and return the first row or null when none are found.
 *
 * @param string $sql
 * @param array<int|string,mixed> $params
 * @return array<string,mixed>|null
 */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    _db_bind_params($st, $params);
    $st->execute();
    /** @var array<string,mixed>|false $row */
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/**
 * Execute a SELECT and return a single column from the first row.
 *
 * @param string $sql
 * @param array<int|string,mixed> $params
 * @param string|int $col Column key (name or 0-based index)
 * @return mixed|null
 */
function db_col(string $sql, array $params = [], $col = 0)
{
    $row = db_one($sql, $params);
    if ($row === null) {
        return null;
    }

    if (is_int($col)) {
        // If driver returns assoc only, convert to numeric order
        $vals = array_values($row);
        return $vals[$col] ?? null;
    }

    // Named column
    if (array_key_exists($col, $row)) {
        return $row[$col];
    }

    // Fallback: case-insensitive lookup (some drivers normalize case)
    foreach ($row as $k => $v) {
        if (strcasecmp((string)$k, (string)$col) === 0) {
            return $v;
        }
    }
    return null;
}

/**
 * Execute a data-changing statement (INSERT/UPDATE/DELETE).
 *
 * @param string $sql
 * @param array<int|string,mixed> $params
 * @return int Rows affected.
 */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    _db_bind_params($st, $params);
    $st->execute();
    return $st->rowCount();
}

/**
 * Resolve page, limit, and offset from a query array using global defaults.
 *
 * @param array<string,mixed> $q
 * @return array{0:int,1:int,2:int} [limit, offset, page]
 */
function page_resolve(array $q): array
{
    $sizes   = defined('PAGE_SIZES') ? (array)PAGE_SIZES : [25, 50, 100];
    $default = defined('DEFAULT_PAGE_SIZE') ? (int)DEFAULT_PAGE_SIZE : 25;

    $reqSize = isset($q['size']) ? (int)$q['size'] : $default;
    $size = in_array($reqSize, $sizes, true) ? $reqSize : $default;
    // hard cap to avoid accidental huge scans
    $size = max(1, min($size, 500));

    $page = max(1, (int)($q['page'] ?? 1));
    $offset = ($page - 1) * $size;

    return [$size, $offset, $page];
}

/**
 * Build a WHERE clause and parameter map from user-provided filters.
 *
 * Map format: key => [ "sql with :named params", optional_transform_callable ]
 *
 * Example:
 *   [$where,$p] = sql_filters(
 *     ['category'=>$cat,'q'=>$q],
 *     [
 *       'category' => ['cat.category = :category'],
 *       'q'        => ['(a.package_name LIKE :q OR COALESCE(ad.app_label,a.app_label) LIKE :q)', fn($v)=>"%$v%"]
 *     ]
 *   );
 *
 * @param array<string,mixed> $filters
 * @param array<string,array{0:string,1?:callable(mixed):mixed}> $map
 * @return array{0:string,1:array<string,mixed>} [whereSql, params]
 */
function sql_filters(array $filters, array $map): array
{
    $clauses = [];
    $params  = [];

    foreach ($map as $key => $tpl) {
        if (!array_key_exists($key, $filters)) {
            continue;
        }
        $val = $filters[$key];
        if ($val === '' || $val === null) {
            continue;
        }

        $sql = $tpl[0] ?? '';
        if ($sql === '') {
            continue; // guard malformed map entries
        }
        $xf  = $tpl[1] ?? null;

        if (is_callable($xf)) {
            $val = $xf($val);
        }

        $clauses[] = $sql;

        // Support one or more named params in the clause; assign same value to each occurrence
        if (preg_match_all('/:(\w+)/', $sql, $m)) {
            foreach ($m[1] as $pname) {
                $params[$pname] = $val;
            }
        }
    }

    $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
    return [$where, $params];
}

/**
 * Execute a paginated query and return rows plus metadata.
 * Note: LIMIT/OFFSET are inlined as validated integers (MySQL native prepares cannot bind LIMIT/OFFSET).
 *
 * @param string              $baseSql  SELECT (no WHERE/LIMIT).
 * @param string              $countSql COUNT(*) to match base joins.
 * @param string              $where    WHERE built by sql_filters().
 * @param string              $orderBy  ORDER BY for deterministic paging (from code, not user).
 * @param array<string,mixed> $params   Params from sql_filters().
 * @param int                 $page     1-indexed page number.
 * @param int                 $size     Page size.
 * @param int                 $countCacheTtl Optional TTL for count query caching.
 * @param string|null         $countCacheKey Optional stable cache-key suffix for count queries.
 * @return array{rows:array<int,array<string,mixed>>, total:int, page:int, size:int}
 */
function db_paged(
    string $baseSql,
    string $countSql,
    string $where,
    string $orderBy,
    array $params,
    int $page,
    int $size,
    int $countCacheTtl = 0,
    ?string $countCacheKey = null
): array
{
    // validate numbers
    $size   = max(1, (int)$size);
    $page   = max(1, (int)$page);
    $offset = ($page - 1) * $size;

    // Inline LIMIT/OFFSET (never bind these in MySQL)
    $limitSql = trim($orderBy) . " LIMIT $size OFFSET $offset";

    $rows = db_all("$baseSql $where $limitSql", $params);
    $countLoader = static function () use ($countSql, $where, $params): int {
        return (int)(db_col("$countSql $where", $params, 'c') ?? 0);
    };
    if ($countCacheTtl > 0) {
        $cacheKey = $countCacheKey ?: sha1(json_encode([
            'countSql' => $countSql,
            'where' => $where,
            'params' => $params,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $countSql . $where);
        $total = (int)web_cache_remember("db_paged_count_v1_{$cacheKey}", $countCacheTtl, $countLoader);
    } else {
        $total = $countLoader();
    }

    return [
        'rows'  => $rows,
        'total' => $total,
        'page'  => $page,
        'size'  => $size,
    ];
}

/**
 * Small file-backed cache for read-only web query results.
 *
 * @template T
 * @param string $key
 * @param int $ttlSeconds
 * @param callable():T $loader
 * @return T
 */
function web_cache_remember(string $key, int $ttlSeconds, callable $loader)
{
    static $memo = [];
    $ttlSeconds = max(1, $ttlSeconds);
    $safeKey = preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) ?? md5($key);
    $cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'scytaledroid-web-cache';
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $safeKey . '.cache.php';
    $now = time();

    if (array_key_exists($safeKey, $memo)) {
        $entry = $memo[$safeKey];
        if (is_array($entry) && (int)($entry['expires_at'] ?? 0) >= $now) {
            return $entry['value'];
        }
    }

    if (is_file($cacheFile)) {
        $entry = @include $cacheFile;
        if (is_array($entry) && (int)($entry['expires_at'] ?? 0) >= $now) {
            $memo[$safeKey] = $entry;
            return $entry['value'];
        }
    }

    $value = $loader();
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    $entry = [
        'expires_at' => $now + $ttlSeconds,
        'value' => $value,
    ];
    $payload = '<?php return ' . var_export($entry, true) . ';';
    @file_put_contents($cacheFile, $payload, LOCK_EX);
    $memo[$safeKey] = $entry;

    return $value;
}
