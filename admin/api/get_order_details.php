<?php
header('Content-Type: application/json');
require_once '../../auth_check.php';

if ($_SESSION['role'] !== 'Администратор') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$db = require_once '../../config/database.php';

try {
    if (!isset($_GET['order_id'])) {
        throw new Exception('ID заказа не указан');
    }

    // Получаем основную информацию о заказе
    $query = "SELECT o.id,
                     DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') as created_at,
                     u.full_name as waiter_name,
                     o.status,
                     SUM(oi.quantity * oi.price) as total_amount
              FROM orders o
              JOIN users u ON o.waiter_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE o.id = :order_id
              GROUP BY o.id, o.created_at, u.full_name, o.status";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':order_id' => $_GET['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Заказ не найден');
    }

    // Получаем информацию о блюдах в заказе
    $query = "SELECT m.name,
                     oi.quantity,
                     oi.price,
                     (oi.quantity * oi.price) as total
              FROM order_items oi
              JOIN menu_items m ON oi.menu_item_id = m.id
              WHERE oi.order_id = :order_id
              ORDER BY m.name";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':order_id' => $_GET['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $order['items'] = $items;

    echo json_encode([
        'success' => true,
        'data' => $order
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 