<?php
require_once '../../config/database.php';
require_once '../../auth_check.php';

header('Content-Type: application/json');

// Проверяем роль пользователя
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

    // Разблокируем пользователя
    $query = "UPDATE users SET is_blocked = 0, login_attempts = 0, last_attempt_time = NULL WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Пользователь не найден');
    }

    echo json_encode(['success' => true, 'message' => 'Пользователь разблокирован']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 