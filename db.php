<?php
function dbConnect()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "blazefood";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function userExists($login)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $exists;
}

function registerUser($login, $password)
{
    $conn = dbConnect();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $login, $hashed_password);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function loginUser($login, $password)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $stmt->close();
            $conn->close();
            session_start();
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["login"] = $user["login"];
            return true;
        }
    }
    $stmt->close();
    $conn->close();
    return false;
}

function isAdmin()
{

    $userId = $_SESSION["user_id"];
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isAdmin = $result->fetch_assoc()['admin'];
    $stmt->close();
    $conn->close();
    return $isAdmin;
}

function checkAuth()
{

    if (!isset($_SESSION["user_id"])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function checkNotAuth()
{

    if (isset($_SESSION["user_id"])) {
        header("Location: ../index/index.php");
        exit;
    }
}

function checkAdmin()
{
    if (!isAdmin()) {
        header("Location: ../index/index.php");
        exit;
    }
}

function getMeals()
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT * FROM meals");
    $stmt->execute();
    $result = $stmt->get_result();
    $meals = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $meals;
}

function deleteMeal($mealId)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->bind_param("i", $mealId);
    $stmt->execute();
    $success = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $success;
}


function getOrderDetailsByMeal($mealId, $userId)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT * FROM order_details WHERE meal_id = ? AND user_id = ? AND order_id IS NULL");
    $stmt->bind_param("ii", $mealId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $details;
}

function getOrderDetailsByUser($userId)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT * FROM order_details WHERE user_id = ? AND order_id IS NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return null;
    }
    $orderDetails = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $orderDetails;
}

function crudOrderDetail($mealId, $userId, $count)
{
    $conn = dbConnect();

    $stmt = $conn->prepare("SELECT id FROM order_details WHERE meal_id = ? AND user_id = ? AND order_id IS NULL LIMIT 1");
    $stmt->bind_param("ii", $mealId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $foundDetail = $result->fetch_assoc();
    $stmt->close();

    if (!$foundDetail && $count > 0) {
        $stmt = $conn->prepare("INSERT INTO order_details (meal_id, user_id, count) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $mealId, $userId, $count);
    } elseif ($foundDetail && $count == 0) {
        $stmt = $conn->prepare("DELETE FROM order_details WHERE id = ?");
        $stmt->bind_param("i", $foundDetail['id']);
    } elseif ($foundDetail && $count > 0) {
        $stmt = $conn->prepare("UPDATE order_details SET count = ? WHERE id = ?");
        $stmt->bind_param("ii", $count, $foundDetail['id']);
    }

    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
}

function createOrder($userId, $latitude, $longitude)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO orders (user_id, adress_latitude, adress_longitude, delivery_end_time, status) VALUES (?, ?, ?, ADDDATE(NOW(), INTERVAL ? SECOND), 'Готовиться')");
    $deliveryTimeInSeconds = calculateDeliveryTime($latitude, $longitude);

    $stmt->bind_param("iddi", $userId, $latitude, $longitude, $deliveryTimeInSeconds);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    if ($orderId) {
        updateOrderDetailsForUser($userId, $orderId);
        return $orderId;
    }

    return false;
}

function updateOrderDetailsForUser($userId, $orderId)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE order_details SET order_id = ? WHERE user_id = ? AND order_id IS NULL");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}


function calculateDeliveryTime($latitude, $longitude)
{
    $restaurantLatitude = 59.971500;
    $restaurantLongitude = 30.313497;

    $distance = intval(calculateDistance($latitude, $longitude, $restaurantLatitude, $restaurantLongitude) * 0.1);

    return min($distance, 180);
}

function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000;

    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function createMeal($title, $description, $image_url, $price)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO meals (title, description, image_url, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $title, $description, $image_url, $price);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

function getUserOrders($userId)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($orders as $key => $order) {
        $stmt = $conn->prepare("SELECT od.*, m.title, m.description, m.image_url, m.price FROM order_details od INNER JOIN meals m ON od.meal_id = m.id WHERE od.order_id = ?");
        $stmt->bind_param("i", $order['id']);
        $stmt->execute();
        $detailsResult = $stmt->get_result();
        $orders[$key]['details'] = $detailsResult->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    $conn->close();
    return $orders;
}



function updateOrderStatus($orderId, $deliveryEndTime, $currentTime)
{
    $conn = dbConnect();
    $status = calculateOrderStatus($deliveryEndTime, $currentTime);
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function calculateOrderStatus($deliveryEndTime, $currentTime)
{
    $deliveryEndTimestamp = strtotime($deliveryEndTime->format('Y-m-d H:i:s'));
    $currentTimestamp = strtotime($currentTime->format('Y-m-d H:i:s'));

    if ($currentTimestamp >= $deliveryEndTimestamp) {
        return 'Доставлен';
    } elseif ($deliveryEndTimestamp - $currentTimestamp <= 90) {
        return 'В пути';
    } else {
        return 'Готовиться';
    }
}
