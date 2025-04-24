<?php
header('Content-Type: application/json');
require_once '../../auth_check.php';

if ($_SESSION['role'] !== 'Администратор') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$db = require_once '../../config/database.php';

try {
    if (!isset($_GET['shift_id'])) {
        throw new Exception('ID смены не указан');
    }

    // Получаем информацию о смене
    $shift_query = "SELECT date, start_time, end_time FROM shifts WHERE id = :shift_id";
    $shift_stmt = $db->prepare($shift_query);
    $shift_stmt->execute([':shift_id' => $_GET['shift_id']]);
    $shift = $shift_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shift) {
        throw new Exception('Смена не найдена');
    }

    // Получаем список сотрудников, которые не заняты в это время
    $query = "SELECT u.id, u.full_name, r.name as role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE (r.name = 'Официант' OR r.name = 'Повар')
             AND u.id NOT IN (
                 SELECT se.employee_id
                 FROM shift_employees se
                 JOIN shifts s ON se.shift_id = s.id
                 WHERE s.date = :date
                 AND (
                     (s.start_time <= :end_time AND s.end_time >= :start_time)
                     OR (s.start_time >= :start_time AND s.start_time < :end_time)
                     OR (s.end_time > :start_time AND s.end_time <= :end_time)
                 )
             )
             ORDER BY r.name, u.full_name";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':date' => $shift['date'],
        ':start_time' => $shift['start_time'],
        ':end_time' => $shift['end_time']
    ]);
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 