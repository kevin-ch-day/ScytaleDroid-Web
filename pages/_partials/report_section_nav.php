<?php
// Expected:
// $sections: array<int,array{id:string,label:string}>
?>

<?php if (!empty($sections)): ?>
  <div class="report-outline">
    <div class="report-outline-label">On this page</div>
    <nav class="report-section-nav report-section-nav-subtle" aria-label="Report sections">
      <?php foreach ($sections as $section): ?>
        <a class="report-section-link report-section-link-subtle" href="#<?= e($section['id']) ?>"><?= e($section['label']) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
<?php endif; ?>
