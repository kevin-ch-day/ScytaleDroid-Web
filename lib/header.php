<?php
// lib/header.php
require_once __DIR__ . '/../config/config.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= APP_NAME ?? 'ScytaleDroid' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main_style.css">
</head>

<body>
    <div class="container">
        <nav aria-label="Primary">
            <a href="<?= BASE_URL ?>/pages/index.php">Apps</a>
        </nav>
        <hr>