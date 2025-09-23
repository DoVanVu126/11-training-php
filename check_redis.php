<?php
$redis = new Redis();
$redis->connect('redis', 6379);

// Thay bằng token bạn lưu
$token = 'abc123';

$data = $redis->get("login:$token");

if ($data) {
    echo "Redis có dữ liệu: " . $data;
} else {
    echo "Không tìm thấy dữ liệu trong Redis";
}