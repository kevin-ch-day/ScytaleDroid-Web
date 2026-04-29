<?php
// lib/sidebar_navigation.php

$navSections = [
  [
    'title' => 'Overview',
    'items' => [
      [
        'label' => 'Home',
        'href' => url('pages/index.php'),
        'desc' => 'Fleet dashboard and latest static posture',
        'match' => [
          url('pages/index.php'),
          '/pages/index.php',
        ],
      ],
      [
        'label' => 'Apps',
        'href' => url('pages/apps.php'),
        'desc' => 'Browse apps, sessions, grades, and sources',
        'match' => [
          url('pages/apps.php'),
          '/pages/apps.php',
        ],
      ],
    ],
  ],
  [
    'title' => 'Analysis',
    'items' => [
      [
        'label' => 'Findings Explorer',
        'href' => url('pages/findings.php'),
        'desc' => 'Search the latest static findings across the fleet',
        'match' => [
          url('pages/findings.php'),
          '/pages/findings.php',
        ],
      ],
      [
        'label' => 'Components',
        'href' => url('pages/components.php'),
        'desc' => 'Track exported providers and guard weaknesses',
        'match' => [
          url('pages/components.php'),
          '/pages/components.php',
        ],
      ],
      [
        'label' => 'Permission Intelligence',
        'href' => url('pages/android_permissions.php'),
        'desc' => 'Review permission prevalence, sources, and sensitive combinations',
        'match' => [
          url('pages/android_permissions.php'),
          '/pages/android_permissions.php',
        ],
      ],
      [
        'label' => 'App Reports',
        'href' => url('pages/app_report.php'),
        'desc' => 'Review one app across sessions and evidence',
        'match' => [
          url('pages/app_report.php'),
          url('pages/app_findings.php'),
          url('pages/app_strings.php'),
          url('pages/app_permissions.php'),
          url('pages/app_dynamic.php'),
          url('pages/dynamic_run.php'),
          '/pages/app_report.php',
          '/pages/app_findings.php',
          '/pages/app_strings.php',
          '/pages/app_permissions.php',
          '/pages/app_dynamic.php',
          '/pages/dynamic_run.php',
        ],
      ],
      [
        'label' => 'Runtime Deviation',
        'href' => url('pages/dynamic.php'),
        'desc' => 'Review dynamic runs, features, and regimes',
        'match' => [
          url('pages/dynamic.php'),
          '/pages/dynamic.php',
        ],
      ],
      [
        'label' => 'Run Health',
        'href' => url('pages/run_health.php'),
        'desc' => 'Check session completeness and data quality',
        'match' => [
          url('pages/run_health.php'),
          '/pages/run_health.php',
        ],
      ],
    ],
  ],
  [
    'title' => 'Reference',
    'items' => [
      [
        'label' => 'About',
        'href' => url('pages/about.php'),
        'desc' => 'Project overview and documentation',
        'match' => [
          url('pages/about.php'),
          '/pages/about.php',
        ],
      ],
    ],
  ],
];

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$readmeUrl   = url('README.md');
?>
<div class="sidebar-header">
  <div class="sidebar-brand">
    <span class="sidebar-brand-text">
      <?= e(APP_NAME ?? 'ScytaleDroid') ?>
      <small>Analysis Console</small>
    </span>
  </div>
  <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="sidebar-nav" aria-expanded="true">
    <span class="visually-hidden" data-sidebar-toggle-label>Collapse navigation</span>
    <span aria-hidden="true">☰</span>
  </button>
</div>
<p class="sidebar-meta">Browse fleet results, open app reports, and follow static plus dynamic evidence with less CLI friction.</p>
<nav class="sidebar-nav" aria-label="Primary">
  <?php foreach ($navSections as $section): ?>
    <div class="sidebar-section">
      <div class="sidebar-section-title"><?= e($section['title']) ?></div>
      <div class="sidebar-section-links">
        <?php foreach ($section['items'] as $item): ?>
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
      </div>
    </div>
  <?php endforeach; ?>
</nav>
<div class="sidebar-footer">
  <a href="<?= e($readmeUrl) ?>" target="_blank" rel="noopener">Project README</a>
  <small>Runs best with a read-only database user and local or environment-based DB config.</small>
</div>
