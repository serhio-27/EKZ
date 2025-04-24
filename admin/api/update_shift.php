<?php
header('Content-Type: application/json');
require_once '../../auth_check.php';

if ($_SESSION['role'] !== 'Администратор') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$db = require_once '../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['date']) || !isset($input['start_time']) || !isset($input['end_time'])) {
        throw new Exception('Не все обязательные поля заполнены');
    }

    $query = "UPDATE shifts SET date = :date, start_time = :start_time, end_time = :end_time WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->execute([
        ':id' => $input['id'],
        ':date' => $input['date'],
        ':start_time' => $input['start_time'],
        ':end_time' => $input['end_time']
    ]);

    echo json_encode(['success' => true, 'message' => 'Смена успешно обновлена']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 