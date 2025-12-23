<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        // Логика на случай, если .env отсутствует
        error_log("Warning: .env file not found at $path");
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Проверяем существование переменных, иначе используем значения по умолчанию или бросаем исключение
$db_host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : null;
$db_name = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : null;
$db_user = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : null;
$db_pass = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : null;
$grafana_url = isset($_ENV['GRAFANA_URL']) ? $_ENV['GRAFANA_URL'] : null;
$grafana_token = isset($_ENV['GRAFANA_TOKEN']) ? $_ENV['GRAFANA_TOKEN'] : null;

if (!$db_host || !$db_name || !$db_user || $db_pass === null || !$grafana_url || !$grafana_token) {
    // Более безопасно, чем просто ошибка, но в реальном приложении нужна логика обработки
    http_response_code(500);
    die('Configuration error: Missing required environment variables.');
}

// Определяем константы
define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('GRAFANA_URL', $grafana_url);
define('GRAFANA_TOKEN', $grafana_token);

/**
 * Создает PDO с явной установкой UTF-8/utf8mb4, чтобы кириллица не искажалась.
 */
function createPdoUtf8()
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];

    // Если доступна опция и драйвер, передадим INIT_COMMAND (не во всех сборках есть MYSQL_ATTR_INIT_COMMAND).
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Дополнительно фиксируем кодировку соединения на случай строгих настроек сервера.
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
    return $pdo;
}

// Явно устанавливаем внутреннюю кодировку PHP, если доступно расширение mbstring.
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}
?>