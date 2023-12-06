<?php
include_once '../db.php';
session_start();
checkAuth();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h2>Личный кабинет пользователя <?php echo $_SESSION['login']; ?></h2>
        <div id="orders-container"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            loadOrders();
            setInterval(loadOrders, 1000);

            function loadOrders() {
                $.ajax({
                    url: 'profile_controller.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayOrders(response.data);
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function() {
                        alert('Ошибка при загрузке заказов.');
                    }
                });
            }

            function displayOrders(orders) {
                var container = $('#orders-container');
                container.empty();

                orders.forEach(function(order) {
                    var remainingTime = calculateRemainingTime(order.delivery_end_time);
                    var orderCard = $(`<div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Заказ №${order.id}</h5>
                <p>Адрес доставки: ${order.adress_latitude}, ${order.adress_longitude}</p>
                <p>Оставшееся время: ${remainingTime !== null ? remainingTime : 'Заказ доставлен'}</p>
                <p>Статус: ${order.status}</p>
                <div class="order-details"></div>
            </div>
        </div>`);

                    order.details.forEach(function(detail) {
                        orderCard.find('.order-details').append(`
                <div class="d-flex align-items-center mb-1">
                    <img src="${detail.image_url}" style="width: 60px; height: 60px; margin-right: 10px;">
                    <p>${detail.title} (${detail.count} шт.)</p>
                </div>
            `);
                    });

                    container.append(orderCard);
                });
            }

            function calculateRemainingTime(deliveryEndTime) {
                var deliveryEnd = new Date(deliveryEndTime).getTime();
                var now = new Date().getTime();
                var timeLeft = deliveryEnd - now;

                if (timeLeft <= 0) {
                    return null;
                }

                var minutes = Math.floor(timeLeft / 60000);
                var seconds = ((timeLeft % 60000) / 1000).toFixed(0);
                return minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
            }



        });
    </script>
</body>

</html>
