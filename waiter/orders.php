<?php
require_once __DIR__ . '/../utils/auth.php';
$user = checkRole('Официант');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами | Панель официанта</title>
    <link rel="stylesheet" href="../style/waiter.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Панель официанта</div>
            <div class="nav-items">
                <span>Заказы</span>
                <span><?= htmlspecialchars($user['full_name']) ?></span>
                <a href="../logout.php">Выход</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1>Управление заказами</h1>
            
            <div class="filters">
                <div class="filter-group">
                    <label for="shift-filter">Фильтр по смене:</label>
                    <select id="shift-filter">
                        <option value="">Все смены</option>
                    </select>
                </div>
                <button id="create-order" class="btn btn-primary">Создать заказ</button>
            </div>

            <div class="orders-list">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата и время</th>
                            <th>Смена</th>
                            <th>Статус</th>
                            <th>Кол-во блюд</th>
                            <th>Сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <!-- Данные будут добавлены через JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Модальное окно создания заказа -->
        <div id="create-order-modal" class="modal">
            <div class="modal-content">
                <h2>Создание заказа</h2>
                <form id="create-order-form">
                    <div class="form-group">
                        <label for="shift">Смена:</label>
                        <select id="shift" name="shift_id" required>
                            <!-- Опции будут добавлены через JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Блюда:</label>
                        <div id="dishes-list">
                            <!-- Список блюд будет добавлен через JavaScript -->
                        </div>
                        <button type="button" id="add-dish" class="btn btn-secondary">Добавить блюдо</button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Создать</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Загрузка списка смен
        async function loadShifts() {
            try {
                const response = await fetch('/waiter/api/get_shifts.php');
                const data = await response.json();
                
                if (data.success) {
                    const shifts = data.data;
                    const shiftFilter = document.getElementById('shift-filter');
                    const shiftSelect = document.getElementById('shift');
                    
                    shifts.forEach(shift => {
                        const option = new Option(
                            `${shift.date} (${shift.start_time} - ${shift.end_time})`,
                            shift.id
                        );
                        shiftFilter.appendChild(option.cloneNode(true));
                        shiftSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Ошибка при загрузке смен:', error);
            }
        }

        // Загрузка списка заказов
        async function loadOrders(shiftId = '') {
            try {
                const url = `/waiter/api/get_orders.php${shiftId ? `?shift_id=${shiftId}` : ''}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('orders-table-body');
                    tbody.innerHTML = '';
                    
                    data.data.forEach(order => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${order.id}</td>
                            <td>${order.created_at}</td>
                            <td>${order.shift_date}<br>${order.shift_time}</td>
                            <td>${order.status}</td>
                            <td>${order.dishes_count}</td>
                            <td>${order.total_amount}</td>
                            <td>
                                ${getActionButtons(order.status)}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (error) {
                console.error('Ошибка при загрузке заказов:', error);
            }
        }

        // Получение кнопок действий в зависимости от статуса
        function getActionButtons(status) {
            switch (status) {
                case 'Принят':
                    return `
                        <button onclick="updateStatus(${order.id}, 'Оплачен')" class="btn btn-success">Оплатить</button>
                    `;
                case 'Оплачен':
                    return `
                        <button onclick="updateStatus(${order.id}, 'Закрыт')" class="btn btn-primary">Закрыть</button>
                    `;
                default:
                    return '';
            }
        }

        // Обновление статуса заказа
        async function updateStatus(orderId, newStatus) {
            try {
                const response = await fetch('/waiter/api/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: orderId,
                        status: newStatus
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    loadOrders(document.getElementById('shift-filter').value);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Ошибка при обновлении статуса:', error);
                alert('Произошла ошибка при обновлении статуса заказа');
            }
        }

        // Открытие модального окна создания заказа
        function openCreateOrderModal() {
            document.getElementById('create-order-modal').style.display = 'block';
        }

        // Закрытие модального окна
        function closeModal() {
            document.getElementById('create-order-modal').style.display = 'none';
        }

        // Создание заказа
        async function createOrder(event) {
            event.preventDefault();
            
            const formData = {
                shift_id: document.getElementById('shift').value,
                dishes: []
            };
            
            // Собираем данные о блюдах
            const dishElements = document.querySelectorAll('.dish-item');
            dishElements.forEach(element => {
                const dishId = element.querySelector('select').value;
                const quantity = element.querySelector('input').value;
                
                if (dishId && quantity > 0) {
                    formData.dishes.push({
                        id: parseInt(dishId),
                        quantity: parseInt(quantity)
                    });
                }
            });
            
            if (formData.dishes.length === 0) {
                alert('Добавьте хотя бы одно блюдо');
                return;
            }
            
            try {
                const response = await fetch('/waiter/api/create_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                if (data.success) {
                    closeModal();
                    loadOrders(document.getElementById('shift-filter').value);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Ошибка при создании заказа:', error);
                alert('Произошла ошибка при создании заказа');
            }
        }

        // Инициализация страницы
        document.addEventListener('DOMContentLoaded', () => {
            loadShifts();
            loadOrders();
            
            // Обработчики событий
            document.getElementById('shift-filter').addEventListener('change', (e) => {
                loadOrders(e.target.value);
            });
            
            document.getElementById('create-order').addEventListener('click', openCreateOrderModal);
            
            document.getElementById('create-order-form').addEventListener('submit', createOrder);
            
            // Закрытие модального окна при клике вне его
            window.addEventListener('click', (e) => {
                const modal = document.getElementById('create-order-modal');
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html> 