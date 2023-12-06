<?php
include_once '../db.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $price = $_POST['price'] ?? 0;

    if (empty($title) || empty($description) || empty($image_url) || !$price) {
        echo json_encode(['success' => false, 'error' => 'Все поля должны быть заполнены']);
        exit;
    }

    if ($price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Цена должна быть положительным числом']);
        exit;
    }

    if (createMeal($title, $description, $image_url, $price)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при добавлении продукта']);
    }
}
