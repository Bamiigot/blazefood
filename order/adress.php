<?php
include_once '../db.php';
session_start();
checkAuth();
if (getOrderDetailsByUser($_SESSION['user_id']) === null) {
    header("Location: ../index/index.php");
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Выбор адреса для доставки</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://api-maps.yandex.ru/2.1/?apikey=190736bd-6718-4e5a-acd6-fc0f61891997&lang=ru_RU" type="text/javascript"></script>
</head>

<body>
    <div class="container mt-4">
        <h2>Выберите адресс для доставки</h2>
        <div id="map" style="width: 100%; height: 400px;"></div>
        <button id="confirm-order" class="btn btn-success mt-3">Подтвердить заказ</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>
        ymaps.ready(init);
        var myMap, myPlacemark;

        function init() {
            var restaurantCoords = [59.971500, 30.313497];

            myMap = new ymaps.Map("map", {
                center: restaurantCoords,
                zoom: 10,
                bounds: [
                    [59.851, 30.092],
                    [60.091, 30.559]
                ]
            });

            var restaurantPlacemark = new ymaps.Placemark(restaurantCoords, {
                hintContent: 'Наш ресторан'
            });
            myMap.geoObjects.add(restaurantPlacemark);

            myMap.events.add('click', function(e) {
                var coords = e.get('coords');
                if (myPlacemark) {
                    myPlacemark.geometry.setCoordinates(coords);
                } else {
                    myPlacemark = createPlacemark(coords);
                    myMap.geoObjects.add(myPlacemark);
                }
            });

            function createPlacemark(coords) {
                return new ymaps.Placemark(coords, {}, {
                    draggable: false
                });
            }

            $('#confirm-order').on('click', function() {
                if (myPlacemark) {
                    var coords = myPlacemark.geometry.getCoordinates();
                    $.ajax({
                        url: 'adress_controller.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            latitude: coords[0],
                            longitude: coords[1]
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Заказ успешно создан');
                                window.location.href = '../user/profile.php';
                            } else {
                                alert(response.error);
                            }
                        },
                        error: function() {
                            alert('Ошибка при подтверждении заказа.');
                        }
                    });
                } else {
                    alert('Пожалуйста, выберите адрес доставки на карте.');
                }
            });
        }
    </script>
</body>

</html>
