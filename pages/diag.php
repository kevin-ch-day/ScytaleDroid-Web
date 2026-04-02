<?php
require_once __DIR__ . '/../database/db_core/db_engine.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo  = db();
    $ver  = $pdo->query('SELECT VERSION() v')->fetch()['v'] ?? '?';
    $runs = $pdo->query('SELECT COUNT(*) c FROM runs')->fetch()['c'] ?? 0;
    $snap = $pdo->query('SELECT COUNT(*) c FROM permission_audit_snapshots')->fetch()['c'] ?? 0;
    $apps = $pdo->query('SELECT COUNT(*) c FROM permission_audit_apps')->fetch()['c'] ?? 0;
    echo "DB OK\nVersion: $ver\nruns: $runs\nsnapshots: $snap\napps: $apps\n";
} catch (Throwable $e) {
    echo "DB ERROR\n" . $e->getMessage() . "\n";
}
