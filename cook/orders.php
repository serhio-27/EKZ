<?php require_once 'templates/header.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Управление заказами</h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="shift_filter" class="form-label">Фильтр по смене:</label>
                    <select class="form-select" id="shift_filter">
                        <option value="">Все смены</option>
                        <?php
                        $db = require_once '../config/database.php';
                        try {
                            $query = "SELECT DISTINCT s.id, 
                                     DATE_FORMAT(s.date, '%d.%m.%Y') as formatted_date,
                                     TIME_FORMAT(s.start_time, '%H:%i') as start_time,
                                     TIME_FORMAT(s.end_time, '%H:%i') as end_time
                                     FROM shifts s
                                     JOIN orders o ON o.shift_id = s.id
                                     ORDER BY s.date DESC, s.start_time DESC";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            
                            while ($row = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($row['id']) . "'>" . 
                                     htmlspecialchars($row['formatted_date'] . " (" . $row['start_time'] . " - " . $row['end_time'] . ")") .
                                     "</option>";
                            }
                        } catch(PDOException $e) {
                            echo "<option value=''>Ошибка загрузки смен</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата и время</th>
                            <th>Смена</th>
                            <th>Официант</th>
                            <th>Статус</th>
                            <th>Блюда</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="orders_list">
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра деталей заказа -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Детали заказа будут загружены через AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
function loadOrders(shiftId = '') {
    $.ajax({
        url: 'api/get_orders.php',
        type: 'GET',
        data: { shift_id: shiftId },
        success: function(response) {
            if(response.success) {
                let html = '';
                response.data.forEach(function(order) {
                    let statusButtons = '';
                    if (order.status === 'Новый') {
                        statusButtons = `
                            <button class="btn btn-warning btn-sm" onclick="updateOrderStatus(${order.id}, 'Готовится')">
                                Начать готовить
                            </button>
                        `;
                    } else if (order.status === 'Готовится') {
                        statusButtons = `
                            <button class="btn btn-success btn-sm" onclick="updateOrderStatus(${order.id}, 'Готов')">
                                Готово
                            </button>
                        `;
                    }

                    html += `
                        <tr>
                            <td>${order.id}</td>
                            <td>${order.created_at}</td>
                            <td>${order.shift_date} (${order.shift_time})</td>
                            <td>${order.waiter_name}</td>
                            <td><span class="badge bg-${getStatusColor(order.status)}">${order.status}</span></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="showOrderDetails(${order.id})">
                                    Показать блюда (${order.items_count})
                                </button>
                            </td>
                            <td>${statusButtons}</td>
                        </tr>
                    `;
                });
                $('#orders_list').html(html || '<tr><td colspan="7" class="text-center">Нет заказов</td></tr>');
            } else {
                $('#orders_list').html('<tr><td colspan="7" class="text-center">Ошибка при загрузке заказов</td></tr>');
            }
        },
        error: function() {
            $('#orders_list').html('<tr><td colspan="7" class="text-center">Ошибка при загрузке заказов</td></tr>');
        }
    });
}

function showOrderDetails(orderId) {
    $.ajax({
        url: 'api/get_order_details.php',
        type: 'GET',
        data: { order_id: orderId },
        success: function(response) {
            if(response.success) {
                let html = `
                    <div class="mb-3">
                        <h6>Информация о заказе:</h6>
                        <p>Дата и время: ${response.data.created_at}</p>
                        <p>Официант: ${response.data.waiter_name}</p>
                        <p>Статус: <span class="badge bg-${getStatusColor(response.data.status)}">${response.data.status}</span></p>
                    </div>
                    <div class="mb-3">
                        <h6>Блюда:</h6>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Количество</th>
                                    <th>Комментарий</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                response.data.items.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.comment || '-'}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#orderDetailsContent').html(html);
                $('#orderDetailsModal').modal('show');
            } else {
                alert('Ошибка при загрузке деталей заказа');
            }
        },
        error: function() {
            alert('Ошибка при загрузке деталей заказа');
        }
    });
}

function updateOrderStatus(orderId, newStatus) {
    if(confirm('Вы уверены, что хотите изменить статус заказа?')) {
        $.ajax({
            url: 'api/update_order_status.php',
            type: 'POST',
            data: JSON.stringify({
                order_id: orderId,
                status: newStatus
            }),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    loadOrders($('#shift_filter').val());
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function() {
                alert('Ошибка при обновлении статуса');
            }
        });
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'Новый': return 'primary';
        case 'Готовится': return 'warning';
        case 'Готов': return 'success';
        case 'Оплачен': return 'info';
        case 'Отменён': return 'danger';
        default: return 'secondary';
    }
}

// Загрузка заказов при загрузке страницы
$(document).ready(function() {
    loadOrders();
    
    // Обработчик изменения фильтра смены
    $('#shift_filter').change(function() {
        loadOrders($(this).val());
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 