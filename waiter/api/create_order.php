<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkRole('Официант');

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

// Проверяем наличие необходимых данных
if (!isset($data['shift_id']) || !isset($data['dishes']) || empty($data['dishes'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Отсутствуют необходимые данные'
    ]);
    exit;
}

try {
    $pdo = getPDO();
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Получаем ID статуса "Принят"
    $stmt = $pdo->prepare("SELECT id FROM order_statuses WHERE name = 'Принят'");
    $stmt->execute();
    $statusId = $stmt->fetchColumn();
    
    if (!$statusId) {
        throw new PDOException("Не найден статус 'Принят'");
    }
    
    // Создаем заказ
    $stmt = $pdo->prepare("
        INSERT INTO orders (shift_id, waiter_id, status_id, created_at, updated_at)
        VALUES (:shift_id, :waiter_id, :status_id, NOW(), NOW())
    ");
    
    $stmt->execute([
        ':shift_id' => $data['shift_id'],
        ':waiter_id' => $user['id'],
        ':status_id' => $statusId
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Добавляем блюда к заказу
    $stmt = $pdo->prepare("
        INSERT INTO order_dishes (order_id, dish_id, quantity)
        VALUES (:order_id, :dish_id, :quantity)
    ");
    
    foreach ($data['dishes'] as $dish) {
        if (!isset($dish['id']) || !isset($dish['quantity']) || $dish['quantity'] < 1) {
            throw new PDOException("Некорректные данные блюда");
        }
        
        $stmt->execute([
            ':order_id' => $orderId,
            ':dish_id' => $dish['id'],
            ':quantity' => $dish['quantity']
        ]);
    }
    
    // Завершаем транзакцию
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно создан',
        'data' => ['id' => $orderId]
    ]);
} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при создании заказа'
    ]);
} 