<?php
require_once __DIR__ . '/../db_core/db_engine.php';

/** Run SELECT and return all rows */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ":$k"), $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll();
}

/** Run SELECT and return first row or null */
function db_one(string $sql, array $params = []): ?array
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

/** Run INSERT/UPDATE/DELETE */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ":$k"), $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    return $st->rowCount();
}

/** Simple pagination resolver using global defaults */
function page_resolve(array $q): array
{
    $sizes   = defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100];
    $default = defined('DEFAULT_PAGE_SIZE') ? DEFAULT_PAGE_SIZE : 25;
    $size = isset($q['size']) && in_array((int)$q['size'], $sizes, true) ? (int)$q['size'] : $default;
    $page = max(1, (int)($q['page'] ?? 1));
    $offset = ($page - 1) * $size;
    return [$size, $offset, $page];
}

/**
 * Filter builder: pass an associative array and a map of [key => [sql, transform?]]
 * Returns [whereSql, params].
 *
 * Example:
 *   [$where,$p] = sql_filters(
 *     ['category'=>$cat,'q'=>$q],
 *     [
 *       'category' => ['cat.category = :category'],
 *       'q'        => ['(a.package_name LIKE :q OR COALESCE(ad.app_label,a.app_label) LIKE :q)', fn($v)=>"%$v%"]
 *     ]
 *   );
 */
function sql_filters(array $filters, array $map): array
{
    $clauses = [];
    $params  = [];

    foreach ($map as $key => $tpl) {
        // $tpl should be: [sqlString, optionalTransformCallable]
        if (!array_key_exists($key, $filters) || $filters[$key] === '' || $filters[$key] === null) {
            continue;
        }

        $sql = $tpl[0] ?? '';
        $xf  = $tpl[1] ?? null;

        $val = $filters[$key];
        if (is_callable($xf)) {
            $val = $xf($val);
        }

        if ($sql === '') {
            continue; // skip malformed map entries
        }

        $clauses[] = $sql;

        // Extract the first named param from the clause (assumes one per clause)
        if (preg_match('/:(\w+)/', $sql, $m)) {
            $params[$m[1]] = $val;
        }
    }

    $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
    return [$where, $params];
}


/**
 * db_paged: build a complete paginated result from a base SELECT.
 * - $baseSql: SELECT ... FROM ... (NO WHERE/LIMIT)
 * - $where:   "WHERE ..." or "" (use sql_filters)
 * - $orderBy: "ORDER BY ..." or "" (required for deterministic paging)
 * Returns ['rows'=>[], 'total'=>int, 'page'=>int, 'size'=>int]
 */
function db_paged(string $baseSql, string $countSql, string $where, string $orderBy, array $params, int $page, int $size): array
{
    $limitSql = $orderBy . " LIMIT :limit OFFSET :offset";
    $rows = db_all("$baseSql $where $limitSql", $params + ['limit' => $size, 'offset' => ($page - 1) * $size]);
    $cnt  = db_one("$countSql $where", $params);
    $total = (int)($cnt['c'] ?? 0);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'size' => $size];
}
