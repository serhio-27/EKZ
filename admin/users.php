<?php
require_once '../config/database.php';
require_once '../auth_check.php';

// Проверяем роль пользователя
if ($_SESSION['role'] !== 'Администратор') {
    header('Location: /login.php');
    exit();
}

// Получаем список пользователей
$query = "SELECT id, username, role, is_blocked, login_attempts, last_attempt_time FROM users";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="/style/admin.css">
</head>
<body>
    <header>
        <nav>
            <a href="/admin/orders.php">Заказы</a>
            <a href="/admin/shifts.php">Смены</a>
            <a href="/admin/users.php" class="active">Пользователи</a>
            <a href="/logout.php">Выход</a>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1>Управление пользователями</h1>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Попытки входа</th>
                        <th>Последняя попытка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $user['is_blocked'] ? 'Заблокирован' : 'Активен' ?></td>
                        <td><?= htmlspecialchars($user['login_attempts']) ?></td>
                        <td><?= $user['last_attempt_time'] ? date('d.m.Y H:i:s', strtotime($user['last_attempt_time'])) : '-' ?></td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                            <button onclick="unblockUser(<?= $user['id'] ?>)" class="btn-success">Разблокировать</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
    function unblockUser(userId) {
        if (!confirm('Вы уверены, что хотите разблокировать этого пользователя?')) {
            return;
        }

        const formData = new FormData();
        formData.append('user_id', userId);

        fetch('/admin/api/unblock_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Пользователь успешно разблокирован');
                location.reload();
            } else {
                alert(data.error || 'Произошла ошибка при разблокировке пользователя');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при разблокировке пользователя');
        });
    }
    </script>
</body>
</html> 