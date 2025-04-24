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

try {
    $pdo = getPDO();
    $params = [];
    $where = [];
    
    // Базовое условие для статусов (используем статусы из order_statuses)
    $where[] = "os.name IN ('Принят', 'Готов')";
    
    // Фильтр по смене, если указан
    if (isset($_GET['shift_id']) && $_GET['shift_id'] !== '') {
        $where[] = "o.shift_id = :shift_id";
        $params[':shift_id'] = $_GET['shift_id'];
    }

    // Объединяем условия
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $query = "SELECT 
        o.id, 
        o.created_at,
        s.date as shift_date,
        s.start_time,
        s.end_time,
        u.full_name as waiter_name,
        os.name as status
    FROM orders o
    JOIN shifts s ON o.shift_id = s.id
    JOIN users u ON o.waiter_id = u.id
    JOIN order_statuses os ON o.status_id = os.id
    $whereClause
    ORDER BY 
        CASE os.name
            WHEN 'Принят' THEN 1
            WHEN 'Готов' THEN 2
            ELSE 3
        END,
        o.created_at DESC";

    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем даты на стороне PHP
    foreach ($orders as &$order) {
        $order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));
        $order['shift_date'] = date('d.m.Y', strtotime($order['shift_date']));
        $order['shift_time'] = date('H:i', strtotime($order['start_time'])) . ' - ' . 
                              date('H:i', strtotime($order['end_time']));
        unset($order['start_time'], $order['end_time']);
    }

    echo json_encode([
        'success' => true,
        'data' => $orders
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при получении списка заказов'
    ]);
} 