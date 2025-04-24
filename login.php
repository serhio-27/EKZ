<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Если пользователь уже авторизован, перенаправляем на главную
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = require_once 'config/database.php';
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if(empty($username) || empty($password)) {
            throw new Exception("Пожалуйста, заполните все поля");
        }
        
        // Получаем данные пользователя
        $query = "SELECT u.*, r.name as role_name 
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 WHERE u.username = :username";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            throw new Exception("Неверное имя пользователя или пароль");
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Проверяем, не заблокирован ли пользователь
        if($user['is_blocked']) {
            throw new Exception("Вы заблокированы. Обратитесь к администратору");
        }
            
        if(password_verify($password, $user['password'])) {
            // Сбрасываем счетчик попыток при успешном входе
            $resetQuery = "UPDATE users SET login_attempts = 0, last_attempt_time = NULL WHERE id = :id";
            $resetStmt = $db->prepare($resetQuery);
            $resetStmt->bindParam(":id", $user['id']);
            $resetStmt->execute();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];
            
            // Перенаправляем в зависимости от роли
            switch($user['role_name']) {
                case 'Администратор':
                    header("Location: admin/employees.php");
                    break;
                case 'Повар':
                    header("Location: cook/orders.php");
                    break;
                case 'Официант':
                    header("Location: waiter/orders.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            // Увеличиваем счетчик попыток
            $attempts = (int)$user['login_attempts'] + 1;
            
            // Обновляем количество попыток и блокируем при достижении лимита
            $updateQuery = "UPDATE users 
                          SET login_attempts = :attempts,
                              last_attempt_time = CURRENT_TIMESTAMP,
                              is_blocked = :is_blocked
                          WHERE id = :id";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(":attempts", $attempts);
            $updateStmt->bindParam(":id", $user['id']);
            $is_blocked = ($attempts >= 3) ? 1 : 0;
            $updateStmt->bindParam(":is_blocked", $is_blocked, PDO::PARAM_BOOL);
            
            if(!$updateStmt->execute()) {
                error_log("SQL Error: " . print_r($updateStmt->errorInfo(), true));
                throw new Exception("Ошибка при обновлении данных");
            }

            if($attempts >= 3) {
                throw new Exception("Превышено количество попыток входа. Ваш аккаунт заблокирован");
            } else {
                throw new Exception("Неверное имя пользователя или пароль. Осталось попыток: " . (3 - $attempts));
            }
        }
        
    } catch(Exception $e) {
        $error = $e->getMessage();
        error_log("Login Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - Система управления кафе</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style/login.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Вход в систему</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Имя пользователя</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 