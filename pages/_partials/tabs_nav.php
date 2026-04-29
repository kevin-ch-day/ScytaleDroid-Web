<?php
// pages/_partials/tabs_nav.php
// Expected:
// $packageName (string), $activeSession (?string), $activeTab (string)
$tabBase = [
  'report' => 'app_report.php',
  'findings' => 'app_findings.php',
  'components' => 'app_components.php',
  'permissions' => 'app_permissions.php',
  'strings' => 'app_strings.php',
  'dynamic' => 'app_dynamic.php',
];
?>

<?php if ($packageName): ?>
  <nav class="tab-nav" aria-label="App detail sections">
    <?php foreach ($tabBase as $tab => $file): ?>
      <?php
      $href = url('pages/' . $file) . '?pkg=' . urlencode($packageName);
      if ($activeSession && $tab !== 'dynamic') {
          $href .= '&session=' . urlencode($activeSession);
      }
      $label = $tab === 'report' ? 'Report' : ucfirst($tab);
      ?>
      <a
        class="tab-link<?= $activeTab === $tab ? ' is-active' : '' ?>"
        href="<?= e($href) ?>"
        <?= $activeTab === $tab ? 'aria-current="page"' : '' ?>
      ><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>
