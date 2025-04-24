<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkRole('Официант');

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

try {
    $pdo = getPDO();
    
    // Проверяем, что заказ принадлежит этому официанту
    $stmt = $pdo->prepare("
        SELECT o.id, o.status_id, os.name as status
        FROM orders o
        JOIN order_statuses os ON o.status_id = os.id
        WHERE o.id = ? AND o.waiter_id = ?
    ");
    $stmt->execute([$data['id'], $user['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Заказ не найден или у вас нет прав на его изменение'
        ]);
        exit;
    }
    
    // Проверяем корректность статуса
    $allowedStatuses = ['Принят', 'Оплачен', 'Закрыт'];
    if (!in_array($data['status'], $allowedStatuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'Некорректный статус заказа'
        ]);
        exit;
    }
    
    // Проверяем логику перехода статусов
    $currentStatus = $order['status'];
    if ($data['status'] === 'Закрыт' && $currentStatus !== 'Оплачен') {
        echo json_encode([
            'success' => false,
            'message' => 'Нельзя закрыть неоплаченный заказ'
        ]);
        exit;
    }
    
    // Получаем ID нового статуса
    $stmt = $pdo->prepare("SELECT id FROM order_statuses WHERE name = ?");
    $stmt->execute([$data['status']]);
    $newStatusId = $stmt->fetchColumn();
    
    if (!$newStatusId) {
        throw new PDOException("Не найден указанный статус");
    }
    
    // Обновляем статус заказа
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status_id = ?, updated_at = NOW()
        WHERE id = ? AND waiter_id = ?
    ");
    $stmt->execute([$newStatusId, $data['id'], $user['id']]);
    
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