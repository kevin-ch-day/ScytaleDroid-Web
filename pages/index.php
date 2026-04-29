<?php
// pages/index.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$errorMsg = null;
$overview = [];
$topApps = [];
$categorySummary = [];
$recurringFindings = [];
$runtimeOverview = [];

try {
    $overview = fleet_dashboard_overview();
    $topApps = apps_directory_list(null, null, 8, 0);
    $categorySummary = fleet_category_summary(8);
    $recurringFindings = fleet_recurring_findings(10);
    $runtimeOverview = runtime_deviation_overview();
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] dashboard failed: ' . $e);
}

$PAGE_TITLE = 'Home';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= e($errorMsg) ?></div>
    <?php else: ?>
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h1 class="panel-title">Home</h1>
                    <p class="panel-subtitle">Fleet dashboard for static posture, recurring findings, and runtime coverage.</p>
                </div>
                <div class="panel-actions">
                    <a class="btn-ghost" href="<?= e(url('pages/apps.php')) ?>">Open Apps</a>
                    <a class="btn-ghost" href="<?= e(url('pages/findings.php')) ?>">Findings Explorer</a>
                </div>
            </div>
            <div class="panel-body">
                <div class="metrics-grid">
                    <div class="metric-card">
                        <span class="metric-label">Tracked Apps</span>
                        <span class="metric-value"><?= e((string)($overview['tracked_apps'] ?? 0)) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">Analyzed Apps</span>
                        <span class="metric-value info"><?= e((string)($overview['analyzed_apps'] ?? 0)) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">Catalog Only</span>
                        <span class="metric-value"><?= e((string)($overview['catalog_only_apps'] ?? 0)) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">High Findings</span>
                        <span class="metric-value bad"><?= e((string)($overview['high_total'] ?? 0)) ?></span>
                        <p class="muted">Medium <?= e((string)($overview['med_total'] ?? 0)) ?> • Low <?= e((string)($overview['low_total'] ?? 0)) ?></p>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">Static Sessions</span>
                        <span class="metric-value metric-value-session"><?= e((string)($overview['static_sessions'] ?? 0)) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">Dynamic Runs</span>
                        <span class="metric-value"><?= e((string)($runtimeOverview['dynamic_runs'] ?? 0)) ?></span>
                        <p class="muted">Packages <?= e((string)($runtimeOverview['dynamic_packages'] ?? 0)) ?> • Feature rows <?= e((string)($runtimeOverview['feature_rows'] ?? 0)) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-grid section-tight">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Top Risky Apps</h2>
                        <p class="panel-subtitle">Highest current composite static scores across the tracked fleet.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="detail-stack compact-stack">
                        <?php foreach ($topApps as $row): ?>
                            <?php $pkg = (string)($row['package_name'] ?? ''); ?>
                            <article class="card compact-card">
                                <div class="compact-row">
                                    <div>
                                        <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($pkg)) ?>"><?= e((string)($row['app_label'] ?? $pkg)) ?></a>
                                        <div class="table-subline"><?= e((string)($row['category'] ?? 'Uncategorized')) ?></div>
                                    </div>
                                    <div class="chip-row">
                                        <?= grade_badge((string)($row['grade'] ?? null)) ?>
                                        <?= chip('Score ' . (string)($row['score_capped'] ?? '0'), 'medium') ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Category Comparison</h2>
                        <p class="panel-subtitle">Quick view of which app groups concentrate the most analyzed exposure.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="col-num">Apps</th>
                                    <th class="col-num">Analyzed</th>
                                    <th class="col-num">Avg Score</th>
                                    <th class="col-num">High</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorySummary as $row): ?>
                                    <tr>
                                        <td><?= e((string)($row['category'] ?? 'Uncategorized')) ?></td>
                                        <td class="col-num"><?= e((string)($row['app_count'] ?? 0)) ?></td>
                                        <td class="col-num"><?= e((string)($row['analyzed_apps'] ?? 0)) ?></td>
                                        <td class="col-num"><?= e((string)($row['avg_score'] ?? '0')) ?></td>
                                        <td class="col-num"><?= e((string)($row['high_total'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <section class="section">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Recurring Findings</h2>
                        <p class="panel-subtitle">Most repeated finding titles across the current latest static surfaces.</p>
                    </div>
                    <div class="panel-actions">
                        <a class="btn-ghost" href="<?= e(url('pages/findings.php')) ?>">Explore Findings</a>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Finding</th>
                                    <th>Category</th>
                                    <th>MASVS</th>
                                    <th class="col-center">Severity</th>
                                    <th class="col-num">Apps</th>
                                    <th class="col-num">Rows</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recurringFindings as $row): ?>
                                    <tr>
                                        <td class="cell-clip"><?= e((string)($row['title'] ?? '')) ?></td>
                                        <td><?= e((string)($row['category'] ?? 'Uncategorized')) ?></td>
                                        <td><?= e((string)($row['masvs_area'] ?? 'Unmapped')) ?></td>
                                        <td class="col-center"><?= chip(strtoupper((string)($row['severity'] ?? 'info')), (string)($row['severity'] ?? 'muted')) ?></td>
                                        <td class="col-num"><?= e((string)($row['affected_apps'] ?? 0)) ?></td>
                                        <td class="col-num"><?= e((string)($row['finding_rows'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
