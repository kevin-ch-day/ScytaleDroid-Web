<?php
// pages/index.php
require_once __DIR__ . '/../lib/header.php';

// helpers & db
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

// inputs
$q        = guard_search($_GET['q'] ?? null);
$category = guard_category($_GET['category'] ?? null);
[$size, $offset, $page] = pager_from_query($_GET);

// data (paginated)
$pg   = apps_directory_paged($category, $q, $page, $size);
$rows = $pg['rows'];
$total = $pg['total'];

// helper for query string persistence
$baseUrl = BASE_URL . '/pages/index.php';
$persist = ['q' => $q, 'category' => $category, 'size' => $size];
?>

<h1>Apps Directory</h1>

<div class="section">
    <form class="form-row" method="get" action="<?= e($baseUrl) ?>">
        <input type="search" name="q" placeholder="Search package or label" value="<?= e($q ?? '') ?>">
        <input type="text" name="category" placeholder="Category" value="<?= e($category ?? '') ?>">
        <select name="size" aria-label="Page size">
            <?php foreach ((defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100]) as $opt): ?>
                <option value="<?= (int)$opt ?>" <?= (int)$opt === (int)$size ? 'selected' : '' ?>><?= (int)$opt ?>/page</option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <div class="table-caption">
        <div class="title">Applications</div>
        <div class="muted">
            Latest snapshot per app
            <?php if ($total !== null): ?>
                &middot; <?= (int)$total ?> result<?= $total == 1 ? '' : 's' ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-sticky">
            <thead>
                <tr>
                    <th>App</th>
                    <th>Package</th>
                    <th>Category</th>
                    <th class="col-center">Grade</th>
                    <th class="col-num">Score</th>
                    <th class="col-center">H/M/L</th>
                    <th>Last Scanned</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="text-center muted p-4">
                            <em>No apps found. Try clearing filters.</em>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="cell-clip"><?= e($r['app_label'] ?? $r['package_name']) ?></td>
                            <td class="cell-clip"><?= e($r['package_name']) ?></td>
                            <td><?= e($r['category'] ?? 'Uncategorized') ?></td>
                            <td class="col-center"><?= grade_badge($r['grade'] ?? null) ?></td>
                            <td class="col-num"><?= e(isset($r['score_capped']) ? (string)$r['score_capped'] : '') ?></td>
                            <td class="col-center"><?= e(fmt_hml($r['high'] ?? 0, $r['med'] ?? 0, $r['low'] ?? 0)) ?></td>
                            <td><?= e(fmt_date($r['last_scanned'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Pager
    pager_render($baseUrl, (int)$total, (int)$page, (int)$size, array_filter($persist, fn($v) => $v !== null && $v !== ''));
    ?>
</div>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>