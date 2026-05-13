<?php
$configPath = __DIR__ . '/db_config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

function db_env(string $key): ?string
{
    $fromEnv = getenv($key);
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    /**
     * Apache SetEnv / some reverse proxies populate $_SERVER while php-fpm clears process
     * environment (clear_env=yes). getenv() then misses values that ARE available here.
     */
    if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }

    $redirectKey = 'REDIRECT_' . $key;
    if (
        isset($_SERVER[$redirectKey])
        && is_string($_SERVER[$redirectKey])
        && $_SERVER[$redirectKey] !== ''
    ) {
        return $_SERVER[$redirectKey];
    }

    return null;
}

/**
 * Return the first non-empty env value from a list of keys.
 *
 * @param array<int,string> $keys
 */
function db_env_first(array $keys): ?string
{
    foreach ($keys as $key) {
        $value = db_env($key);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    return null;
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
        throw new RuntimeException(
            "Database setting {$constName} missing. Set environment variable {$envKey}, "
            . 'copy database/db_core/db_config.example.php to database/db_core/db_config.php '
            . 'with your credentials, OR add env[' . $envKey . '] in the php-fpm pool (SetEnv '
            . 'alone often does not reach PHP-FPM workers).'
        );
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
    $pass = db_env_first(['SCYTALEDROID_DB_PASS', 'SCYTALEDROID_DB_PASSWD']);
    if ($pass === null) {
        if (!defined('DB_PASS')) {
            throw new RuntimeException(
                'Database password missing: set SCYTALEDROID_DB_PASS or SCYTALEDROID_DB_PASSWD in the environment, '
                . 'php-fpm pool env[], or DB_PASS in database/db_core/db_config.php '
                . '(copied from database/db_core/db_config.example.php).'
            );
        }
        $pass = (string) constant('DB_PASS');
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException(
            'Failed to connect to the database: '
            . $e->getMessage()
            . ' (Tip: TCP to localhost on Linux often prefers host 127.0.0.1 or SCYTALEDROID_DB_SOCKET; '
            . 'ensure php-pdo_mysql is installed; with SELinux, httpd/php-fpm may need httpd_can_network_connect_db '
            . 'for remote DB TCP.)',
            (int) $e->getCode(),
            $e
        );
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
