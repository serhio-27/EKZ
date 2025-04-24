<?php 
require_once 'templates/header.php';
$db = require_once '../config/database.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Управление сотрудниками</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                Добавить сотрудника
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя пользователя</th>
                            <th>ФИО</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="employeesTable">
                        <?php
                        try {
                            $query = "SELECT u.id, u.username, u.full_name, r.name as role, u.is_blocked, u.login_attempts 
                                     FROM users u 
                                     JOIN roles r ON u.role_id = r.id 
                                     ORDER BY u.id";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            
                            while ($row = $stmt->fetch()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                echo "<td>" . ($row['is_blocked'] ? '<span class="status-blocked">Заблокирован</span>' : '<span class="status-active">Активен</span>') . "</td>";
                                echo "<td>
                                        <button class='btn btn-success btn-sm me-2' onclick='editEmployee(" . $row['id'] . ")'>
                                            Редактировать
                                        </button>
                                        <button class='btn btn-danger btn-sm' onclick='deleteEmployee(" . $row['id'] . ")'>
                                            Удалить
                                        </button>";
                                if ($row['is_blocked']) {
                                    echo "<button class='btn btn-success btn-sm ms-2' onclick='unblockUser(" . $row['id'] . ")'>Разблокировать</button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } catch(PDOException $e) {
                            echo "<div class='alert alert-danger'>Ошибка при получении данных: " . $e->getMessage() . "</div>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления сотрудника -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ФИО</label>
                        <input type="text" class="form-control" id="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Роль</label>
                        <select class="form-control" id="role" required>
                            <?php
                            try {
                                $query = "SELECT * FROM roles";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            } catch(PDOException $e) {
                                echo "<option value=''>Ошибка загрузки ролей</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" onclick="saveEmployee()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования сотрудника -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editEmployeeForm">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Новый пароль (оставьте пустым, если не меняете)</label>
                        <input type="password" class="form-control" id="edit_password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">ФИО</label>
                        <input type="text" class="form-control" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Роль</label>
                        <select class="form-control" id="edit_role" required>
                            <?php
                            try {
                                $query = "SELECT * FROM roles";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            } catch(PDOException $e) {
                                echo "<option value=''>Ошибка загрузки ролей</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" onclick="updateEmployee()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function saveEmployee() {
    const data = {
        username: $('#username').val(),
        password: $('#password').val(),
        full_name: $('#full_name').val(),
        role_id: $('#role').val()
    };

    $.ajax({
        url: 'api/save_employee.php',
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

function editEmployee(id) {
    $.ajax({
        url: 'api/get_employee.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if(response.success) {
                $('#edit_id').val(response.data.id);
                $('#edit_username').val(response.data.username);
                $('#edit_full_name').val(response.data.full_name);
                $('#edit_role').val(response.data.role_id);
                $('#editEmployeeModal').modal('show');
            } else {
                alert('Ошибка: ' + response.message);
            }
        },
        error: function() {
            alert('Ошибка при получении данных');
        }
    });
}

function updateEmployee() {
    const data = {
        id: $('#edit_id').val(),
        username: $('#edit_username').val(),
        password: $('#edit_password').val(),
        full_name: $('#edit_full_name').val(),
        role_id: $('#edit_role').val()
    };

    $.ajax({
        url: 'api/update_employee.php',
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

function deleteEmployee(id) {
    if(confirm('Вы уверены, что хотите удалить этого сотрудника?')) {
        $.ajax({
            url: 'api/delete_employee.php',
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

function unblockUser(userId) {
    if (!confirm('Вы уверены, что хотите разблокировать этого сотрудника?')) {
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);

    fetch('api/unblock_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json().then(data => ({
        ok: response.ok,
        status: response.status,
        data: data
    })))
    .then(result => {
        if (result.ok && result.data.success) {
            alert('Сотрудник успешно разблокирован');
            location.reload();
        } else {
            const errorMessage = result.data.error || 'Произошла ошибка при разблокировке сотрудника';
            console.error('Error:', result.data);
            alert(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при разблокировке сотрудника');
    });
}
</script>

<?php require_once 'templates/footer.php'; ?> 