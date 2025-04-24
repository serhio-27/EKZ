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
    
    if (!isset($input['id'])) {
        throw new Exception('ID смены не указан');
    }

    $query = "DELETE FROM shifts WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->execute([':id' => $input['id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Смена не найдена');
    }

    echo json_encode(['success' => true, 'message' => 'Смена успешно удалена']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 