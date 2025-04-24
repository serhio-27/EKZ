<?php
header('Content-Type: application/json');
require_once '../../auth_check.php';

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SESSION['role'] !== 'Администратор') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

try {
    $db = require_once '../../config/database.php';

    $params = [];
    $where = "";
    
    if (isset($_GET['shift_id']) && $_GET['shift_id'] !== '') {
        $where = "WHERE o.shift_id = :shift_id";
        $params[':shift_id'] = $_GET['shift_id'];
    }

    // Изменяем запрос для совместимости с MySQL 5.7 и выше
    $query = "SELECT 
        o.id, 
        DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') as created_at,
        DATE_FORMAT(s.date, '%d.%m.%Y') as shift_date,
        CONCAT(TIME_FORMAT(s.start_time, '%H:%i'), ' - ', TIME_FORMAT(s.end_time, '%H:%i')) as shift_time,
        u.full_name as waiter_name,
        o.status,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
        COALESCE((SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id), 0) as total_amount
    FROM orders o
    JOIN shifts s ON o.shift_id = s.id
    JOIN users u ON o.waiter_id = u.id
    $where
    ORDER BY o.created_at DESC";

    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Ошибка подготовки запроса: " . print_r($db->errorInfo(), true));
    }
    
    $success = $stmt->execute($params);
    
    if (!$success) {
        throw new Exception("Ошибка выполнения запроса: " . print_r($stmt->errorInfo(), true));
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $orders
    ]);
} catch (Exception $e) {
    error_log("Ошибка в get_orders.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 