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
?>