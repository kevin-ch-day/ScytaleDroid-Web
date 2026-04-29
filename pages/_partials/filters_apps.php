<?php
// pages/_partials/filters_apps.php
// Expected variables (extracted by index.php):
// $baseUrl, $q, $category, $size, $hasActiveFilters
?>

<section class="panel" data-panel="filters">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">Filters</h2>
      <p class="panel-subtitle">Search by package, label, or category.</p>
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

    <p class="inline-hint">
      Wildcards like <code>%vpn%</code> work in search. Shortcut: <code>Ctrl/Cmd + K</code>.
    </p>
  </div>
</section>
