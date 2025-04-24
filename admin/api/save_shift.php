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
    
    if (!isset($input['date']) || !isset($input['start_time']) || !isset($input['end_time'])) {
        throw new Exception('Не все обязательные поля заполнены');
    }

    $query = "INSERT INTO shifts (date, start_time, end_time) VALUES (:date, :start_time, :end_time)";
    $stmt = $db->prepare($query);
    
    $stmt->execute([
        ':date' => $input['date'],
        ':start_time' => $input['start_time'],
        ':end_time' => $input['end_time']
    ]);

    echo json_encode(['success' => true, 'message' => 'Смена успешно создана']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 