<?php
// lib/sidebar_navigation.php

$navItems = [
  [
    'label' => 'Apps Directory',
    'href' => BASE_URL . '/pages/index.php',
    'desc' => 'Browse latest snapshots by package',
    'match' => [
      BASE_URL . '/pages/index.php',
      '/pages/index.php',
    ],
  ],
  [
    'label' => 'App Detail',
    'href' => BASE_URL . '/pages/view_app.php',
    'desc' => 'Drill into a package and session',
    'match' => [
      BASE_URL . '/pages/view_app.php',
      BASE_URL . '/pages/app_findings.php',
      BASE_URL . '/pages/app_strings.php',
      BASE_URL . '/pages/app_permissions.php',
      '/pages/view_app.php',
      '/pages/app_findings.php',
      '/pages/app_strings.php',
      '/pages/app_permissions.php',
    ],
  ],
  [
    'label' => 'About',
    'href' => BASE_URL . '/pages/about.php',
    'desc' => 'Project overview and documentation',
    'match' => [
      BASE_URL . '/pages/about.php',
      '/pages/about.php',
    ],
  ],
];

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$readmeUrl   = BASE_URL . '/README.md';
?>
<div class="sidebar-header">
  <div class="sidebar-brand">
    <span class="sidebar-brand-mark">SD</span>
    <span class="sidebar-brand-text">
      <?= e(APP_NAME ?? 'ScytaleDroid') ?>
      <small>Telemetry Console</small>
    </span>
  </div>
  <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="sidebar-nav" aria-expanded="true">
    <span class="visually-hidden" data-sidebar-toggle-label>Collapse navigation</span>
    <span aria-hidden="true">⟡</span>
  </button>
</div>
<p class="sidebar-meta">Read-only insights into the latest static analysis runs. Use filters to focus on the riskiest packages first.</p>
<nav class="sidebar-nav" aria-label="Primary">
  <?php foreach ($navItems as $item): ?>
    <?php
    $matches  = $item['match'];
    $isActive = in_array($currentPath, $matches, true);
    $label    = $item['label'];
    $desc     = $item['desc'] ?? null;
    $href     = $item['href'];
    ?>
    <a
      class="sidebar-link<?= $isActive ? ' is-active' : '' ?>"
      href="<?= e($href) ?>"
      data-label="<?= e($label) ?>"
      data-sidebar-link
      <?= $isActive ? 'aria-current="page"' : '' ?>
    >
      <span class="sidebar-link-text"><?= e($label) ?></span>
      <?php if ($desc): ?>
        <span class="sidebar-link-desc"><?= e($desc) ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
<div class="sidebar-footer">
  <a href="<?= e($readmeUrl) ?>" target="_blank" rel="noopener">Project README</a>
  <small>Runs best with a read-only database user and the shipped PDO config.</small>
</div>

