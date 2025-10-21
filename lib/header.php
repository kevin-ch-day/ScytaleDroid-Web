<?php
// lib/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/render.php';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(APP_NAME ?? 'ScytaleDroid') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main_style.css">
</head>

<body data-theme="tron">
  <div class="backdrop-gradient" aria-hidden="true"></div>
  <div class="app-shell" data-shell>
    <aside class="sidebar" id="sidebar-nav" data-sidebar aria-label="Primary navigation" aria-hidden="false">
      <?php require __DIR__ . '/sidebar_navigation.php'; ?>
    </aside>
    <div class="app-main">
      <header class="topbar">
        <div class="topbar-brand">
          <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="sidebar-nav" aria-expanded="true">
            <span class="visually-hidden" data-sidebar-toggle-label>Collapse navigation</span>
            <span aria-hidden="true">☰</span>
          </button>
          <div class="brand">
            <span class="brand-mark">SD</span>
            <span class="brand-name"><?= e(APP_NAME ?? 'ScytaleDroid') ?></span>
          </div>
        </div>
        <div class="topbar-actions">
          <span class="topbar-chip" data-sidebar-state>Sidebar: Expanded</span>
          <button type="button" class="btn-ghost" data-theme-toggle>
            <span class="visually-hidden">Toggle visual theme</span>
            <span aria-hidden="true">Switch Theme</span>
          </button>
        </div>
      </header>
      <div class="container">
