<?php
include_once '../db.php';
session_start();
checkAuth();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>BlazeFood - Главная</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>

<body>
    <header class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a href="../user/profile.php" class="btn btn-outline-primary">Мои заказы</a>
            <a href="../auth/logout.php" class="btn btn-outline-danger">Выйти из аккаунта</a>
        </div>
    </header>

    <div class="container mt-4 mb-4">
        <section class="mb-4 text-center">
            <h2 class="font-weight-bold">Добро пожаловать в BlazeFood!</h2>
            <p>Здесь вы можете выбрать себе еду и настроить ее количество.</p>
        </section>
        <?php if (isAdmin()) : ?>
            <a href="../admin/add_meal.php" class="btn btn-success mb-4">Добавить продукт</a>
        <?php endif; ?>
        <div id="meals-container" class="row"></div>
        <button id="checkout-btn" class="btn btn-success mt-4">Сделать заказ</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script>
        $(document).ready(function() {
            loadMeals();
            setInterval(loadMeals, 5000);

            function loadMeals() {
                $.ajax({
                    url: 'index_controller.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayMeals(response.data, response.isAdmin);
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function() {
                        alert('Ошибка при загрузке блюд.');
                    }
                });
            }

            function displayMeals(meals, isAdmin) {
                var container = $('#meals-container');
                container.empty();

                meals.forEach(function(meal) {
                    var count = meal.orderDetail !== null ? meal.orderDetail : 0;
                    var mealCard = $(`
                    <div class="col-md-4 mb-4">
                        <div class="card" data-meal-id="${meal.id}">
                            <img src="${meal.image_url}" class="card-img-top" style="height: 250px; object-fit: fill;">
                            <div class="card-body">
                                <h5 class="card-title">${meal.title}</h5>
                                <p class="card-text">${meal.description}</p>
                                <p class="card-text font-weight-bold">Цена: ${meal.price} руб.</p>
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    <button class="btn btn-danger btn-sm mr-2">-</button>
                                    <span class="count-number">${count}</span>
                                    <button class="btn btn-success btn-sm ml-2">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                    if (isAdmin) {
                        mealCard.find('.card-body').append('<button class="btn btn-outline-danger btn-sm delete-meal">Удалить продукт</button>');
                    }
                    container.append(mealCard);
                });
            }

            $('#meals-container').on('click', '.btn-success', function() {
                var countElement = $(this).siblings('.count-number');
                var count = parseInt(countElement.text());
                countElement.text(count + 1);
                updateOrderDetail($(this).closest('.card').data('mealId'), count + 1);
            });

            $('#meals-container').on('click', '.btn-danger', function() {
                var countElement = $(this).siblings('.count-number');
                var count = parseInt(countElement.text());
                if (count > 0) {
                    countElement.text(count - 1);
                    updateOrderDetail($(this).closest('.card').data('mealId'), count - 1);
                }
            });

            $('#meals-container').on('click', '.delete-meal', function() {
                var mealId = $(this).closest('.card').data('mealId');
                $.ajax({
                    url: 'index_controller.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'deleteMeal',
                        mealId: mealId
                    },
                    success: function(response) {
                        if (response.success) {
                            loadMeals();
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function() {
                        alert('Ошибка при удалении продукта.');
                    }
                });
            });

            $('#checkout-btn').on('click', function() {
                $.ajax({
                    url: 'index_controller.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'checkOrderDetails',
                    },
                    success: function(response) {
                        if (response.success && response.data !== null) {
                            window.location.href = '../order/adress.php';
                        } else {
                            alert('У вас пустая корзина');
                        }
                    },
                    error: function() {
                        alert('Ошибка при проверке деталей заказа');
                    }
                });
            });


            function updateOrderDetail(mealId, count) {
                $.ajax({
                    url: 'index_controller.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        mealId: mealId,
                        count: count
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert(response.error);
                        }
                    },
                    error: function() {
                        alert('Ошибка при обновлении деталей заказа.');
                    }
                });
            }

        });
    </script>

</body>

</html>
