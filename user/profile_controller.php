<?php
include_once '../db.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_SESSION['user_id'];
    $currentTime = new DateTime();
    $currentTime->setTimezone(new DateTimeZone('Europe/Moscow'));
    $orders = getUserOrders($userId);

    foreach ($orders as &$order) {
        $orderId = $order['id'];
        $deliveryEndTime = new DateTime($order['delivery_end_time']);
        updateOrderStatus($orderId, $deliveryEndTime, $currentTime);
    }
    unset($order);

    echo json_encode(['success' => true, 'data' => $orders]);
    exit;
}
