<?php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/app_report_payload.php';

$context = load_app_detail_context(
    guard_package_name($_GET['pkg'] ?? null),
    guard_session($_GET['session'] ?? null)
);

$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$activeSessionUsable = $context['active_session_usable'];
$activeSessionRow = $context['active_session_row'];
$preferredSession = $context['preferred_session'];
$preferredSessionRow = $context['preferred_session_row'];
$newerIncompleteSessionRow = $context['newer_incomplete_session_row'];
$errorMsg = $context['error'];

$canLoadReportPayload = (
    $packageName
    && $activeSession
    && !$errorMsg
    && is_array($app)
);

if ($canLoadReportPayload) {
    $payload = build_app_report_payload($context);
    $dbErrorDuringPayload = $payload['dbErrorDuringPayload'] ?? null;
    unset($payload['dbErrorDuringPayload']);
    if (is_string($dbErrorDuringPayload) && $dbErrorDuringPayload !== '') {
        $errorMsg = $dbErrorDuringPayload;
    } else {
        extract($payload, EXTR_OVERWRITE);
    }
}

$PAGE_TITLE = $packageName ? ('App Report: ' . $packageName) : 'App Report';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null || !is_array($app)): ?>
  <?php
  $title = 'App Report';
  $message = $packageName === null
      ? 'Select an app to open a full report with static, permission, string, and dynamic sections.'
      : 'This package is not available in the current app directory.';
  require __DIR__ . '/_partials/app_lookup_empty.php';
  ?>
<?php elseif (!$activeSession): ?>
  <?php
  $activeTab = 'report';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_report.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>
  <div class="alert alert-warning">
    No static session stamp is available yet for this package. Choose a session above when rows exist, or sync static analysis data.
  </div>
<?php else: ?>
  <?php
  $activeTab = 'report';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_report.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <?php require __DIR__ . '/_partials/app_report_incomplete_banner.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_overview.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_session_health.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_explore.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_static_risk.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_permissions.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_components.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_strings.php'; ?>

  <?php require __DIR__ . '/_partials/app_report_dynamic.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
