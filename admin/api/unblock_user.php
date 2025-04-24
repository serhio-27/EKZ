<?php
require_once '../../config/database.php';
require_once '../../auth_check.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Проверяем роль пользователя
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Необходима авторизация']);
    exit();
}

if ($_SESSION['role'] !== 'Администратор') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

try {
    // Проверяем наличие ID пользователя
    if (!isset($_POST['user_id'])) {
        throw new Exception('ID пользователя не указан');
    }

    $userId = $_POST['user_id'];

    // Проверяем существование пользователя
    $checkQuery = "SELECT id, is_blocked FROM users WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $userId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Пользователь не найден');
    }

    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user['is_blocked']) {
        throw new Exception('Пользователь не заблокирован');
    }

    // Разблокируем пользователя
    $query = "UPDATE users SET is_blocked = 0, login_attempts = 0, last_attempt_time = NULL WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    
    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . print_r($errorInfo, true));
        throw new Exception('Ошибка при разблокировке пользователя: ' . $errorInfo[2]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Пользователь разблокирован'
    ]);

} catch (Exception $e) {
    error_log("Unblock Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Обратитесь к администратору системы'
    ]);
} 