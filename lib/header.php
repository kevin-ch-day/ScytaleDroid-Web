<?php
// lib/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/render.php';

// Optional per-page title: set $PAGE_TITLE before including header.php
$__title = isset($PAGE_TITLE) && $PAGE_TITLE !== ''
    ? $PAGE_TITLE . ' — ' . APP_NAME
    : APP_NAME;

// Resolve sidebar partial (don’t fatal if missing)
$__sidebar = __DIR__ . '/sidebar_navigation.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($__title) ?></title>

    <!-- Canonical + theme color (nice-to-have) -->
    <link rel="canonical" href="<?= e(abs_url(ltrim($_SERVER['REQUEST_URI'] ?? 'pages/index.php', '/'))) ?>">
    <meta name="theme-color" content="#0e1116">

    <!-- Stylesheets (cache-busted via APP_VERSION) -->
    <link rel="stylesheet" href="<?= e(asset_url('css/theme_style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/table_style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/sidebar_nav.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/main_style.css')) ?>">

    <!-- No-JS fallback keeps nav visible -->
    <noscript>
        <style>
            [data-sidebar] {
                transform: none !important;
                opacity: 1 !important
            }

            [data-sidebar-toggle] {
                display: none !important
            }
        </style>
    </noscript>
</head>

<body data-theme="light">
    <div class="app-shell" data-shell>
        <aside class="sidebar" id="sidebar-nav" data-sidebar aria-label="Primary navigation" aria-hidden="false">
            <?php if (is_file($__sidebar)) {
                require $__sidebar;
            } ?>
        </aside>

        <div class="app-main">
            <header class="topbar" role="banner">
                <div class="topbar-brand">
                    <button type="button"
                        class="sidebar-toggle"
                        data-sidebar-toggle
                        aria-controls="sidebar-nav"
                        aria-expanded="true">
                        <span class="visually-hidden" data-sidebar-toggle-label>Collapse navigation</span>
                        <span aria-hidden="true">☰</span>
                    </button>
                    <a href="<?= e(url('pages/index.php')) ?>" class="brand" aria-label="<?= e(APP_NAME) ?> home">
                        <span class="brand-name"><?= e(APP_NAME) ?></span>
                    </a>
                </div>

                <div class="topbar-actions">
                    <span class="topbar-chip" data-sidebar-state>Sidebar: Expanded</span>
                    <button type="button" class="btn-ghost" data-theme-toggle data-theme-current="light">
                        <span class="visually-hidden">Toggle visual theme</span>
                        <span aria-hidden="true">Switch Theme</span>
                    </button>
                </div>
            </header>

            <main class="container" role="main">