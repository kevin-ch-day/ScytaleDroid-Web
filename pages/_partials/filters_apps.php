<?php
// pages/_partials/filters_apps.php
// Expected variables (extracted by index.php):
// $baseUrl, $q, $category, $categoryOptions, $size, $hasActiveFilters, $includeCatalogOnly
?>

<section class="panel" data-panel="filters">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">Filters</h2>
      <p class="panel-subtitle">Search by package or label, then narrow by category if needed.</p>
    </div>
  </div>
  <div class="panel-body">
    <form class="form-row" method="get" action="<?= e($baseUrl) ?>" data-filter-form>
      <label class="visually-hidden" for="filter-q">Search</label>
      <input id="filter-q" type="search" name="q" placeholder="Search package or label" value="<?= e($q ?? '') ?>" autocomplete="off">

      <label class="visually-hidden" for="filter-category">Category</label>
      <select id="filter-category" name="category" aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categoryOptions as $opt): ?>
          <option value="<?= e($opt) ?>" <?= $category === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="visually-hidden" for="filter-size">Page size</label>
      <select id="filter-size" name="size" aria-label="Page size">
        <?php foreach ((defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100]) as $opt): ?>
          <option value="<?= (int)$opt ?>" <?= (int)$opt === (int)$size ? 'selected' : '' ?>><?= (int)$opt ?>/page</option>
        <?php endforeach; ?>
      </select>

      <label class="inline-check" title="Include inventory/catalog package records that do not currently have finalized static-analysis results.">
        <input type="checkbox" name="include_catalog" value="1" <?= !empty($includeCatalogOnly) ? 'checked' : '' ?>>
        <span>Include catalog-only related packages</span>
      </label>

      <button class="btn btn-primary" type="submit">Apply</button>
      <button class="btn-ghost" type="button" data-action="clear-filters" <?= $hasActiveFilters ? '' : 'disabled' ?>>Clear</button>
    </form>

    <p class="inline-hint">
      Wildcards like <code>%vpn%</code> work in search. Shortcut: <code>Ctrl/Cmd + K</code>. Catalog-only rows are inventory context, not evidence of zero risk.
    </p>
  </div>
</section>
