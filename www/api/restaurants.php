<?php
require_once __DIR__ . '/../config.php';
header("Content-Type: application/json; charset=utf-8");

try {
    $pdo = createPdoUtf8();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "DB connection error"]);
    exit;
}

// ==================== ПРОВЕРКА ТОКЕНА ====================
$headers = getallheaders();
if (!isset($headers["Authorization"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Missing token"]);
    exit;
}
list($type, $token) = explode(" ", $headers["Authorization"], 2);
if (strtolower($type) !== "bearer" || !$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Invalid token"]);
    exit;
}

$stmt = $pdo->prepare("SELECT u.* FROM user_sessions s 
                       JOIN users u ON u.id = s.user_id
                       WHERE s.token = :token AND s.expires_at > NOW()
                       LIMIT 1");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Invalid or expired token"]);
    exit;
}

// ==================== ОБРАБОТКА ====================
$restaurants = [];
if (!empty($user["network"])) {
    $list = array_map("trim", explode(",", $user["network"]));
    foreach ($list as $i => $name) {
        if ($name !== "") {
            $restaurants[] = [
                "id" => $i + 1,
                "name" => $name
            ];
        }
    }
}

echo json_encode(["success" => true, "restaurants" => $restaurants], JSON_UNESCAPED_UNICODE);
?>