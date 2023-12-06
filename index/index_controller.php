<?php
include_once '../db.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'checkOrderDetails') {
    $userId = $_SESSION['user_id'];
    $orderDetails = getOrderDetailsByUser($userId);

    if ($orderDetails !== null) {
        echo json_encode(['success' => true, 'data' => $orderDetails]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Нет активных заказов']);
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $meals = getMeals();
    $userId = $_SESSION['user_id'];

    $mealsWithDetails = array_map(function ($meal) use ($userId) {
        $orderDetail = getOrderDetailsByMeal($meal['id'], $userId);
        $meal['orderDetail'] = $orderDetail ? $orderDetail['count'] : null;
        return $meal;
    }, $meals);

    echo json_encode(['success' => true, 'data' => $mealsWithDetails, 'isAdmin' => isAdmin()]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deleteMeal') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'error' => 'Недостаточно прав для выполнения операции']);
        exit;
    }

    $mealId = $_POST['mealId'] ?? null;
    if (!$mealId) {
        echo json_encode(['success' => false, 'error' => 'Неверный товар для удаления']);
        exit;
    }

    $result = deleteMeal($mealId);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Ошибка удаления']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mealId = $_POST['mealId'] ?? null;
    $userId = $_SESSION['user_id'];
    $count = $_POST['count'] ?? 0;

    if ($count < 0) {
        echo json_encode(['success' => false, 'error' => 'Количество не может быть меньше нуля']);
        exit;
    }

    crudOrderDetail($mealId, $userId, $count);
    echo json_encode(['success' => true]);
    exit;
}
