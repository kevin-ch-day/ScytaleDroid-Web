<?php
// database/db_lib/db_utils.php
require_once __DIR__ . '/../db_core/db_engine.php';

/**
 * Bind parameters on a prepared PDO statement (named or positional).
 * - For named keys, we normalize to ":name".
 * - Types: NULL/BOOL/INT -> correct PDO type; everything else -> STR.
 *
 * @param PDOStatement $st
 * @param array<int|string,mixed> $params
 */
function _db_bind_params(PDOStatement $st, array $params): void
{
    foreach ($params as $k => $v) {
        // positional: 0-based array index maps to 1-based placeholder
        if (is_int($k)) {
            $name = $k + 1;
        } else {
            // normalize to named (":name"), without relying on str_starts_with
            $name = ':' . ltrim((string)$k, ':');
        }

        if ($v === null) {
            $type = PDO::PARAM_NULL;
        } elseif (is_bool($v)) {
            $type = PDO::PARAM_BOOL;
        } elseif (is_int($v)) {
            $type = PDO::PARAM_INT;
        } else {
            $type = PDO::PARAM_STR;
        }

        $st->bindValue($name, $v, $type);
    }
}

/**
 * Prepare and execute a SELECT, returning all rows.
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
    return $st->fetchAll();
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
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
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
    if ($row === null) return null;
    if (is_int($col)) {
        // convert assoc to numeric order reliably
        $vals = array_values($row);
        return $vals[$col] ?? null;
    }
    return $row[$col] ?? null;
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
    $sizes   = defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100];
    $default = defined('DEFAULT_PAGE_SIZE') ? DEFAULT_PAGE_SIZE : 25;

    $size = isset($q['size']) && in_array((int)$q['size'], $sizes, true) ? (int)$q['size'] : $default;
    // hard cap to avoid accidental huge scans
    $size = max(1, min($size, 500));

    $page = max(1, (int)($q['page'] ?? 1));
    $offset = ($page - 1) * $size;

    return [$size, $offset, $page];
}

/**
 * Build a WHERE clause and parameter map from user-provided filters.
 *
 * @param array<string,mixed> $filters Filter values (usually guarded request input).
 * @param array<string,array{0:string,1?:callable(mixed):mixed}> $map Map of key => [SQL, transform?].
 * @return array{0:string,1:array<string,mixed>} [whereSql, params]
 */
function sql_filters(array $filters, array $map): array
{
    $clauses = [];
    $params  = [];

    foreach ($map as $key => $tpl) {
        if (!array_key_exists($key, $filters)) continue;
        $val = $filters[$key];
        if ($val === '' || $val === null) continue;

        $sql = $tpl[0] ?? '';
        $xf  = $tpl[1] ?? null;
        if ($sql === '') continue;

        if (is_callable($xf)) {
            $val = $xf($val);
        }

        $clauses[] = $sql;

        // support one or more named params in the clause; assign same value to each
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
 * @param string              $orderBy  ORDER BY for deterministic paging.
 * @param array<string,mixed> $params   Params from sql_filters().
 * @param int                 $page     1-indexed page number.
 * @param int                 $size     Page size.
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function db_paged(string $baseSql, string $countSql, string $where, string $orderBy, array $params, int $page, int $size): array
{
    // compute safe integers
    $size   = max(1, (int)$size);
    $offset = max(0, ((int)$page - 1) * $size);

    // inline LIMIT/OFFSET as integers (no binding!)
    $limitSql = trim($orderBy) . " LIMIT $size OFFSET $offset";

    $rows = db_all("$baseSql $where $limitSql", $params);
    $total = (int)(db_col("$countSql $where", $params, 'c') ?? 0);

    return ['rows' => $rows, 'total' => $total, 'page' => (int)$page, 'size' => $size];
}
