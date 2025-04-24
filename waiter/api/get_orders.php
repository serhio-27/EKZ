<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkRole('Официант');

try {
    $pdo = getPDO();
    $params = [];
    $where = ["o.waiter_id = :waiter_id"];
    $params[':waiter_id'] = $user['id'];
    
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
        os.name as status,
        (SELECT COUNT(*) FROM order_dishes WHERE order_id = o.id) as dishes_count,
        (SELECT SUM(d.price * od.quantity) 
         FROM order_dishes od 
         JOIN dishes d ON od.dish_id = d.id 
         WHERE od.order_id = o.id) as total_amount
    FROM orders o
    JOIN shifts s ON o.shift_id = s.id
    JOIN order_statuses os ON o.status_id = os.id
    $whereClause
    ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем даты
    foreach ($orders as &$order) {
        $order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));
        $order['shift_date'] = date('d.m.Y', strtotime($order['shift_date']));
        $order['shift_time'] = date('H:i', strtotime($order['start_time'])) . ' - ' . 
                              date('H:i', strtotime($order['end_time']));
        $order['total_amount'] = number_format($order['total_amount'] ?? 0, 2, '.', ' ') . ' ₽';
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