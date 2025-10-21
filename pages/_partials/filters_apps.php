<?php
// pages/_partials/filters_apps.php
// Expected variables (extracted by index.php):
// $baseUrl, $q, $category, $size, $hasActiveFilters
?>

<section class="neon-panel" data-panel="filters">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">Filters</h2>
      <p class="panel-subtitle">Tune the radar sweep by name, category, or page size.</p>
    </div>
    <div class="panel-actions">
      <span class="chip-density" data-density-indicator>Density: Standard</span>
      <button type="button" class="panel-toggle" data-action="toggle-panel" aria-expanded="true">Collapse</button>
    </div>
  </div>
  <div class="panel-body">
    <form class="form-row" method="get" action="<?= e($baseUrl) ?>" data-filter-form>
      <label class="visually-hidden" for="filter-q">Search</label>
      <input id="filter-q" type="search" name="q" placeholder="Search package or label" value="<?= e($q ?? '') ?>" autocomplete="off">

      <label class="visually-hidden" for="filter-category">Category</label>
      <input id="filter-category" type="text" name="category" placeholder="Category" value="<?= e($category ?? '') ?>">

      <label class="visually-hidden" for="filter-size">Page size</label>
      <select id="filter-size" name="size" aria-label="Page size">
        <?php foreach ((defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100]) as $opt): ?>
          <option value="<?= (int)$opt ?>" <?= (int)$opt === (int)$size ? 'selected' : '' ?>><?= (int)$opt ?>/page</option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-primary" type="submit">Apply</button>
      <button class="btn-ghost" type="button" data-action="clear-filters" <?= $hasActiveFilters ? '' : 'disabled' ?>>Clear</button>
    </form>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">Search Tips</span>
        <span class="metric-value info" aria-hidden="true">%</span>
        <p class="muted">Use wildcards like <code>%vpn%</code> to find partial package names.</p>
      </div>
      <div class="metric-card">
        <span class="metric-label">Category Hint</span>
        <span class="metric-value">AI</span>
        <p class="muted">Leave blank to sweep all verticals. Enter exact category names for precise filters.</p>
      </div>
      <div class="metric-card">
        <span class="metric-label">Keyboard</span>
        <span class="metric-value warn">⌘/Ctrl + K</span>
        <p class="muted">Focus search instantly with the global shortcut.</p>
      </div>
    </div>
  </div>
</section>
