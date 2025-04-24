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
    $shiftFilter = isset($_GET['shift_id']) && !empty($_GET['shift_id']) ? 
                  " AND o.shift_id = :shift_id" : "";

    $query = "SELECT 
                o.id,
                o.created_at,
                DATE_FORMAT(s.date, '%d.%m.%Y') as shift_date,
                CONCAT(TIME_FORMAT(s.start_time, '%H:%i'), ' - ', TIME_FORMAT(s.end_time, '%H:%i')) as shift_time,
                u.full_name as waiter_name,
                os.name as status,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
                COALESCE((SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id), 0) as total_amount
            FROM orders o
            JOIN shifts s ON o.shift_id = s.id
            JOIN users u ON o.waiter_id = u.id
            JOIN order_statuses os ON o.status_id = os.id
            WHERE 1=1" . $shiftFilter . "
            ORDER BY o.created_at DESC";

    $stmt = $db->prepare($query);
    
    if (!empty($shiftFilter)) {
        $stmt->bindParam(':shift_id', $_GET['shift_id']);
    }

    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем дату и время создания заказа
    foreach ($orders as &$order) {
        $order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));
        $order['total_amount'] = number_format($order['total_amount'], 2, '.', ' ');
    }

    echo json_encode([
        'success' => true,
        'data' => $orders
    ]);

} catch (Exception $e) {
    error_log("Error in get_orders.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при получении данных: ' . $e->getMessage()
    ]);
} 