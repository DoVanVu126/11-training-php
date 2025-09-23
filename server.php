<?php
header("Content-Type: application/json");

$redis = new Redis();
// Kết nối tới service "redis" trong docker-compose
$redis->connect('redis', 6379);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $key = $input['key'] ?? '';
    $value = $input['value'] ?? null;

    if ($key && $value) {
        $redis->set($key, json_encode($value));
        echo json_encode(["message" => "Saved to Redis", "key" => $key, "value" => $value]);
    } else {
        echo json_encode(["error" => "Invalid data"]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $key = $_GET['key'] ?? '';
    if ($key) {
        $data = $redis->get($key);
        echo json_encode(["key" => $key, "value" => json_decode($data, true)]);
    } else {
        echo json_encode(["error" => "Missing key"]);
    }
    exit;
}
