<?php
// lib/footer.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/render.php';
?>
        <footer class="site-footer">
          <small><?= e(APP_NAME ?? 'ScytaleDroid') ?> &middot; <?= date('Y') ?></small>
        </footer>
      </div> <!-- /.container -->
    </div> <!-- /.app-main -->
  </div> <!-- /.app-shell -->
  <script src="<?= BASE_URL ?>/assets/js/script.js" defer></script>
</body>

</html>
