<?php
// Local-only template: copy verbatim to db_config.php (gitignored).
//
// Credentials can also come from SCYTALEDROID_DB_* environment variables — but with PHP-FPM,
// getenv() often stays empty unless you add env[SCYTALEDROID_DB_HOST] (etc.) under the pool in
// /etc/php-fpm.d/www.conf, or rely on db_config.php. Apache SetEnv alone is not always visible
// to FPM worker processes when clear_env=yes.
//
// Connection tips (Linux/MariaDB):
// - PDO "mysql:host=localhost" may use a Unix socket path that differs from mysqld — try 127.0.0.1
//   for TCP or set SCYTALEDROID_DB_SOCKET to mysqld.sock if you intentionally use sockets.

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'scytaledroid_db_dev';
const DB_USER = 'scytaledroid_readonly';
const DB_PASS = 'change-me';
// Password may instead be set via SCYTALEDROID_DB_PASS or SCYTALEDROID_DB_PASSWD (see db_engine.php).
