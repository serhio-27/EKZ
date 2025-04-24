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

// Проверка наличия ID заказа
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Некорректный ID заказа'
    ]);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    $pdo = getPDO();
    
    // Получаем основную информацию о заказе
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.created_at,
            o.updated_at,
            os.name as status,
            u.full_name as waiter_name,
            s.date as shift_date,
            s.start_time,
            s.end_time
        FROM orders o
        JOIN users u ON o.waiter_id = u.id
        JOIN shifts s ON o.shift_id = s.id
        JOIN order_statuses os ON o.status_id = os.id
        WHERE o.id = ?
    ");
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Заказ не найден'
        ]);
        exit;
    }
    
    // Форматируем даты
    $order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));
    $order['updated_at'] = date('d.m.Y H:i', strtotime($order['updated_at']));
    $order['shift_date'] = date('d.m.Y', strtotime($order['shift_date']));
    $order['shift_time'] = date('H:i', strtotime($order['start_time'])) . ' - ' . 
                          date('H:i', strtotime($order['end_time']));
    
    // Удаляем ненужные поля
    unset($order['start_time'], $order['end_time']);
    
    // Получаем блюда в заказе
    $stmt = $pdo->prepare("
        SELECT od.quantity, d.name, d.price
        FROM order_dishes od
        JOIN dishes d ON od.dish_id = d.id
        WHERE od.order_id = ?
    ");
    
    $stmt->execute([$orderId]);
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Добавляем блюда к информации о заказе
    $order['dishes'] = $dishes;
    
    echo json_encode([
        'success' => true,
        'data' => $order
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при получении деталей заказа'
    ]);
} 