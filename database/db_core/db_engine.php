<?php
$configPath = __DIR__ . '/db_config.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('Database config missing. Provide credentials in database/db_core/db_config.php.');
}
require_once $configPath;

function db_env(string $key): ?string
{
    $value = getenv($key);
    if ($value === false) {
        return null;
    }

    return (string) $value;
}

/**
 * @param non-empty-string $constName
 */
function db_config_value(string $constName, string $envKey): string
{
    $env = db_env($envKey);
    if ($env !== null) {
        return $env;
    }

    if (!defined($constName)) {
        throw new RuntimeException("Database constant {$constName} missing from configuration file.");
    }

    return (string) constant($constName);
}

function db_dsn(): string
{
    $dsn = db_env('SCYTALEDROID_DB_DSN');
    if ($dsn !== null) {
        return $dsn;
    }

    $charset = db_env('SCYTALEDROID_DB_CHARSET') ?? 'utf8mb4';
    $dbName = db_config_value('DB_NAME', 'SCYTALEDROID_DB_NAME');

    $socket = db_env('SCYTALEDROID_DB_SOCKET');
    if ($socket !== null) {
        return 'mysql:unix_socket=' . $socket . ';dbname=' . $dbName . ';charset=' . $charset;
    }

    $host = db_config_value('DB_HOST', 'SCYTALEDROID_DB_HOST');
    $port = db_config_value('DB_PORT', 'SCYTALEDROID_DB_PORT');

    return 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName . ';charset=' . $charset;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = db_dsn();
    $user = db_config_value('DB_USER', 'SCYTALEDROID_DB_USER');
    $pass = db_config_value('DB_PASS', 'SCYTALEDROID_DB_PASS');

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Failed to connect to the database. Verify credentials and network access.', 0, $e);
    }

    return $pdo;
}

/* Optional: quick health check */
function db_ping(): bool
{
    try {
        db()->query('SELECT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
