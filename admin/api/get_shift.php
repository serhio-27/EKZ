<?php
header('Content-Type: application/json');
require_once '../../auth_check.php';

if ($_SESSION['role'] !== 'Администратор') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$db = require_once '../../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID смены не указан');
    }

    $query = "SELECT id, date, TIME_FORMAT(start_time, '%H:%i') as start_time, TIME_FORMAT(end_time, '%H:%i') as end_time 
             FROM shifts 
             WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->execute([':id' => $_GET['id']]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shift) {
        throw new Exception('Смена не найдена');
    }

    echo json_encode([
        'success' => true, 
        'data' => [
            'id' => $shift['id'],
            'date' => $shift['date'],
            'start_time' => $shift['start_time'],
            'end_time' => $shift['end_time']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 