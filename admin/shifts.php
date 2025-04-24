<?php require_once 'templates/header.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Управление сменами</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                Добавить смену
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Начало</th>
                            <th>Окончание</th>
                            <th>Сотрудники</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $db = require_once '../config/database.php';
                        
                        try {
                            $query = "SELECT s.*, 
                                     GROUP_CONCAT(CONCAT(u.full_name, ' (', r.name, ')') SEPARATOR ', ') as employees
                                     FROM shifts s
                                     LEFT JOIN shift_employees se ON s.id = se.shift_id
                                     LEFT JOIN users u ON se.employee_id = u.id
                                     LEFT JOIN roles r ON u.role_id = r.id
                                     GROUP BY s.id
                                     ORDER BY s.date DESC, s.start_time";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            
                            while ($row = $stmt->fetch()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('d.m.Y', strtotime($row['date']))) . "</td>";
                                echo "<td>" . htmlspecialchars(date('H:i', strtotime($row['start_time']))) . "</td>";
                                echo "<td>" . htmlspecialchars(date('H:i', strtotime($row['end_time']))) . "</td>";
                                echo "<td>" . htmlspecialchars($row['employees'] ?: 'Нет сотрудников') . "</td>";
                                echo "<td>
                                        <button class='btn btn-success btn-sm me-2' onclick='editShift(" . $row['id'] . ")'>
                                            Редактировать
                                        </button>
                                        <button class='btn btn-info btn-sm me-2' onclick='manageEmployees(" . $row['id'] . ")'>
                                            Сотрудники
                                        </button>
                                        <button class='btn btn-danger btn-sm' onclick='deleteShift(" . $row['id'] . ")'>
                                            Удалить
                                        </button>
                                    </td>";
                                echo "</tr>";
                            }
                        } catch(PDOException $e) {
                            echo "<tr><td colspan='6' class='text-danger'>Ошибка при получении данных: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления смены -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить смену</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addShiftForm">
                    <div class="mb-3">
                        <label for="date" class="form-label">Дата</label>
                        <input type="date" class="form-control" id="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Время начала</label>
                        <input type="time" class="form-control" id="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label">Время окончания</label>
                        <input type="time" class="form-control" id="end_time" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" onclick="saveShift()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования смены -->
<div class="modal fade" id="editShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать смену</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editShiftForm">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Дата</label>
                        <input type="date" class="form-control" id="edit_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_start_time" class="form-label">Время начала</label>
                        <input type="time" class="form-control" id="edit_start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_end_time" class="form-label">Время окончания</label>
                        <input type="time" class="form-control" id="edit_end_time" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" onclick="updateShift()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно управления сотрудниками смены -->
<div class="modal fade" id="manageEmployeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Управление сотрудниками смены</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Официанты</h6>
                        <div id="waiters_list" class="mb-3"></div>
                        <select class="form-control" id="add_waiter">
                            <option value="">Выберите официанта</option>
                        </select>
                        <button class="btn btn-success btn-sm mt-2" onclick="addEmployee('waiter')">Добавить официанта</button>
                    </div>
                    <div class="col-md-6">
                        <h6>Повара</h6>
                        <div id="cooks_list" class="mb-3"></div>
                        <select class="form-control" id="add_cook">
                            <option value="">Выберите повара</option>
                        </select>
                        <button class="btn btn-success btn-sm mt-2" onclick="addEmployee('cook')">Добавить повара</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentShiftId = null;

function saveShift() {
    const data = {
        date: $('#date').val(),
        start_time: $('#start_time').val(),
        end_time: $('#end_time').val()
    };

    $.ajax({
        url: 'api/save_shift.php',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при сохранении');
        }
    });
}

function editShift(id) {
    $.ajax({
        url: 'api/get_shift.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if(response.success) {
                $('#edit_id').val(response.data.id);
                $('#edit_date').val(response.data.date);
                $('#edit_start_time').val(response.data.start_time);
                $('#edit_end_time').val(response.data.end_time);
                $('#editShiftModal').modal('show');
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при получении данных');
        }
    });
}

function updateShift() {
    const data = {
        id: $('#edit_id').val(),
        date: $('#edit_date').val(),
        start_time: $('#edit_start_time').val(),
        end_time: $('#edit_end_time').val()
    };

    $.ajax({
        url: 'api/update_shift.php',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при обновлении');
        }
    });
}

function deleteShift(id) {
    if(confirm('Вы уверены, что хотите удалить эту смену?')) {
        $.ajax({
            url: 'api/delete_shift.php',
            type: 'POST',
            data: JSON.stringify({ id: id }),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function() {
                alert('Ошибка при удалении');
            }
        });
    }
}

function manageEmployees(shiftId) {
    currentShiftId = shiftId;
    loadShiftEmployees(shiftId);
    loadAvailableEmployees(shiftId);
    $('#manageEmployeesModal').modal('show');
}

function loadShiftEmployees(shiftId) {
    $.ajax({
        url: 'api/get_shift_employees.php',
        type: 'GET',
        data: { shift_id: shiftId },
        success: function(response) {
            if(response.success) {
                $('#waiters_list').empty();
                $('#cooks_list').empty();
                
                response.data.forEach(function(employee) {
                    const html = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>${employee.full_name}</span>
                            <button class="btn btn-danger btn-sm" onclick="removeEmployee(${employee.id})">
                                Удалить
                            </button>
                        </div>
                    `;
                    if(employee.role_name === 'Официант') {
                        $('#waiters_list').append(html);
                    } else if(employee.role_name === 'Повар') {
                        $('#cooks_list').append(html);
                    }
                });
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при загрузке сотрудников');
        }
    });
}

function loadAvailableEmployees(shiftId) {
    $.ajax({
        url: 'api/get_available_employees.php',
        type: 'GET',
        data: { shift_id: shiftId },
        success: function(response) {
            if(response.success) {
                $('#add_waiter').html('<option value="">Выберите официанта</option>');
                $('#add_cook').html('<option value="">Выберите повара</option>');
                
                response.data.forEach(function(employee) {
                    const option = `<option value="${employee.id}">${employee.full_name}</option>`;
                    if(employee.role_name === 'Официант') {
                        $('#add_waiter').append(option);
                    } else if(employee.role_name === 'Повар') {
                        $('#add_cook').append(option);
                    }
                });
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при загрузке доступных сотрудников');
        }
    });
}

function addEmployee(type) {
    const employeeId = type === 'waiter' ? $('#add_waiter').val() : $('#add_cook').val();
    if(!employeeId) return;

    $.ajax({
        url: 'api/add_shift_employee.php',
        type: 'POST',
        data: JSON.stringify({
            shift_id: currentShiftId,
            employee_id: employeeId
        }),
        contentType: 'application/json',
        success: function(response) {
            if(response.success) {
                loadShiftEmployees(currentShiftId);
                loadAvailableEmployees(currentShiftId);
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при добавлении сотрудника');
        }
    });
}

function removeEmployee(employeeId) {
    if(!confirm('Вы уверены, что хотите удалить сотрудника из смены?')) return;

    $.ajax({
        url: 'api/remove_shift_employee.php',
        type: 'POST',
        data: JSON.stringify({
            shift_id: currentShiftId,
            employee_id: employeeId
        }),
        contentType: 'application/json',
        success: function(response) {
            if(response.success) {
                loadShiftEmployees(currentShiftId);
                loadAvailableEmployees(currentShiftId);
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при удалении сотрудника');
        }
    });
}
</script>

<?php require_once 'templates/footer.php'; ?> 