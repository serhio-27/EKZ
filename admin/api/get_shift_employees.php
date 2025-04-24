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

    $query = "SELECT u.id, u.full_name, r.name as role_name
             FROM users u
             JOIN shift_employees se ON u.id = se.employee_id
             JOIN roles r ON u.role_id = r.id
             WHERE se.shift_id = :shift_id
             ORDER BY r.name, u.full_name";
             
    $stmt = $db->prepare($query);
    $stmt->execute([':shift_id' => $_GET['shift_id']]);
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 