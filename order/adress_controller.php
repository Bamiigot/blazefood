<?php
include_once '../db.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $userId = $_SESSION['user_id'];

    if (!$latitude || !$longitude) {
        echo json_encode(['success' => false, 'error' => 'Метка не установлена']);
        exit;
    }

    if (getOrderDetailsByUser($userId) === null) {
        echo json_encode(['success' => false, 'error' => 'У вас пустая корзина']);
        exit;
    }

    $orderId = createOrder($userId, $latitude, $longitude);
    if ($orderId) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при создании заказа']);
    }
}
