<?php
// Включаем обработку ошибок для JSON ответов
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Устанавливаем заголовок JSON сразу
header("Content-Type: application/json; charset=utf-8");

// Обработчик фатальных ошибок
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "error" => "PHP Error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']
        ]);
    }
});

try {
    require_once __DIR__ . '/../config.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Config error: " . $e->getMessage()]);
    exit;
}

try {
    $pdo = createPdoUtf8();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "DB connection error"]);
    exit;
}

// ==================== ЧТЕНИЕ ЗАПРОСА ОТ ФРОНТА ====================
$rawInput = file_get_contents("php://input");
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Empty request body"]);
    exit;
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid JSON: " . json_last_error_msg()]);
    exit;
}

$identifier = trim($input["identifier"] ?? "");
$password   = trim($input["password"] ?? "");

if (!$identifier || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing credentials"]);
    exit;
}

// ==================== ПОИСК ПОЛЬЗОВАТЕЛЯ ====================
try {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE email = :identifier OR phone = :identifier 
        LIMIT 1
    ");
    $stmt->execute([":identifier" => $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database query error: " . $e->getMessage()]);
    exit;
}

if (!$user) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "User not found"]);
    exit;
}

// ==================== ПРОВЕРКА ПАРОЛЯ ====================
if (!password_verify($password, $user["password_hash"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Invalid password"]);
    exit;
}

// ==================== СОЗДАНИЕ СЕССИИ ====================
try {
    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime("+1 day"));

    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, token, ip_address, user_agent, created_at, expires_at)
        VALUES (:user_id, :token, :ip, :ua, NOW(), :expires)
    ");
    $stmt->execute([
        ":user_id" => $user["id"],
        ":token"   => $token,
        ":ip"      => $_SERVER["REMOTE_ADDR"] ?? null,
        ":ua"      => $_SERVER["HTTP_USER_AGENT"] ?? null,
        ":expires" => $expires_at
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Failed to create session"]);
    exit;
}

// ==================== ВОЗВРАТ ДАННЫХ ====================
echo json_encode([
    "success" => true,
    "token"   => $token,
    "user"    => [
        "id"         => $user["id"],
        "firstName"  => $user["first_name"],
        "lastName"   => $user["last_name"],
        "fullName"   => $user["first_name"] . " " . $user["last_name"],
        "phone"      => $user["phone"],
        "email"      => $user["email"],
        "position"   => $user["position"],
        "network"    => $user["network"]
    ]
], JSON_UNESCAPED_UNICODE);
?>