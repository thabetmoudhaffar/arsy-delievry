<?php
function loadEnvFile(string $filePath): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if (
            (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match("/^'([^']*)'$/", $value, $matches))
            && isset($matches[1])
        ) {
            $value = $matches[1];
        }

        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function envFlag(string $key, bool $default = false): bool
{
    $value = envValue($key);
    if ($value === null) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

loadEnvFile(__DIR__ . '/../.env');

$isVercel = envValue('VERCEL') === '1' || getenv('NOW_REGION') !== false;
$databaseUrl = envValue('DATABASE_URL')
    ?? envValue('DB_URL')
    ?? envValue('MYSQL_URL')
    ?? envValue('MYSQL_URI')
    ?? envValue('AIVEN_MYSQL_URI');
$parsedDatabaseUrl = $databaseUrl ? parse_url($databaseUrl) : null;

$databaseQuery = [];
if (is_array($parsedDatabaseUrl) && !empty($parsedDatabaseUrl['query'])) {
    parse_str($parsedDatabaseUrl['query'], $databaseQuery);
}

$databaseNameFromUrl = null;
if (is_array($parsedDatabaseUrl) && !empty($parsedDatabaseUrl['path'])) {
    $databaseNameFromUrl = ltrim($parsedDatabaseUrl['path'], '/');
    if ($databaseNameFromUrl === '') {
        $databaseNameFromUrl = null;
    }
}

define('APP_ENV', envValue('APP_ENV', 'local'));

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

define('DB_HOST', is_array($parsedDatabaseUrl) && !empty($parsedDatabaseUrl['host']) ? $parsedDatabaseUrl['host'] : envValue('DB_HOST', 'localhost'));
define('DB_PORT', (string) (is_array($parsedDatabaseUrl) && !empty($parsedDatabaseUrl['port']) ? $parsedDatabaseUrl['port'] : envValue('DB_PORT', '3306')));
define('DB_NAME', $databaseNameFromUrl ?: envValue('DB_NAME', 'arsy_delivery'));
define('DB_USER', is_array($parsedDatabaseUrl) && array_key_exists('user', $parsedDatabaseUrl) ? $parsedDatabaseUrl['user'] : envValue('DB_USER', 'root'));
define('DB_PASS', is_array($parsedDatabaseUrl) && array_key_exists('pass', $parsedDatabaseUrl) ? $parsedDatabaseUrl['pass'] : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
define('DB_CHARSET', $databaseQuery['charset'] ?? envValue('DB_CHARSET', 'utf8mb4'));
define('DB_SSL_MODE', strtolower($databaseQuery['sslmode'] ?? envValue('DB_SSL_MODE', $isVercel ? 'require' : 'prefer')));
define('DB_SSL_CA', envValue('DB_SSL_CA', ''));
define('DB_SSL_VERIFY_SERVER_CERT', envFlag('DB_SSL_VERIFY_SERVER_CERT', false));

$defaultBasePath = $isVercel ? '' : '/arsy%20delievry';
$defaultAppUrl = $isVercel ? 'https://' . envValue('VERCEL_URL', '') : 'http://localhost/arsy%20delievry';

define('BASE_PATH', getenv('BASE_PATH') !== false ? getenv('BASE_PATH') : $defaultBasePath);
define('APP_URL', envValue('APP_URL', $defaultAppUrl));

function resolveDbSslCaPath(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (file_exists($value)) {
        return $value;
    }

    $normalized = str_replace(["\\r\\n", "\\n", "\\r"], PHP_EOL, $value);
    if (!str_contains($normalized, 'BEGIN CERTIFICATE')) {
        return $value;
    }

    $certificatePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'arsy-db-ca-' . md5($normalized) . '.pem';
    if (!file_exists($certificatePath)) {
        file_put_contents($certificatePath, $normalized);
    }

    return $certificatePath;
}

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $sslCaPath = resolveDbSslCaPath(DB_SSL_CA);

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (DB_SSL_MODE !== '' && DB_SSL_MODE !== 'disable') {
        // Pour PDO MySQL, on utilise les attributs MYSQL_ATTR_SSL_* plutôt que le DSN
        if (defined('PDO::MYSQL_ATTR_SSL_CA') && $sslCaPath !== '' && file_exists($sslCaPath)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
        }
        
        // Aiven nécessite souvent SSL, mais si on n'a pas le CA, on peut désactiver la vérification stricte
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DB_SSL_VERIFY_SERVER_CERT;
        } else {
            // Par défaut pour Aiven si non défini
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        error_log(sprintf(
            'Database connection failed [%s:%s/%s]: %s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            $exception->getMessage()
        ));
        throw $exception;
    }

    return $pdo;
}
