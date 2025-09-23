<?php
header("Content-Type: application/json");

$redis = new Redis();
$redis->connect('redis', 6379); // host của Redis

// Nhận dữ liệu từ POST JSON
$input = json_decode(file_get_contents("php://input"), true);

$key = $input['key'] ?? '';
$value = $input['value'] ?? null;

if ($key && $value !== null) {
    $redis->set($key, json_encode($value));
    echo json_encode(["message" => "Saved to Redis", "key" => $key, "value" => $value]);
} else {
    echo json_encode(["error" => "Invalid data"]);
}
