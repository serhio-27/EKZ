<?php
require_once '../../config/database.php';
require_once '../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkAuth();
if (!$user || $user['role'] !== 'Повар') {
    echo json_encode([
        'success' => false,
        'message' => 'Доступ запрещен'
    ]);
    exit;
}

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

// Проверяем наличие необходимых данных
if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Отсутствуют необходимые данные'
    ]);
    exit;
}

// Проверяем корректность статуса
$allowedStatuses = ['В обработке', 'Готовится', 'Готов', 'Отменён'];
if (!in_array($data['status'], $allowedStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Некорректный статус заказа'
    ]);
    exit;
}

try {
    $pdo = getPDO();
    
    // Проверяем существование заказа
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$data['id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Заказ не найден'
        ]);
        exit;
    }
    
    // Обновляем статус заказа
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Статус заказа успешно обновлен'
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при обновлении статуса заказа'
    ]);
} 