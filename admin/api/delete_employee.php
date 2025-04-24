<?php
header('Content-Type: application/json');
$db = require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('ID не указан');
    }
    
    // Проверяем, не является ли пользователь последним администратором
    $check_query = "SELECT COUNT(*) as admin_count FROM users WHERE role_id = 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $admin_count = $check_stmt->fetch()['admin_count'];
    
    $user_query = "SELECT role_id FROM users WHERE id = :id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(":id", $data['id']);
    $user_stmt->execute();
    $user_role = $user_stmt->fetch()['role_id'];
    
    if ($admin_count <= 1 && $user_role == 1) {
        throw new Exception('Нельзя удалить последнего администратора');
    }
    
    // Удаляем связанные записи из shift_employees
    $shift_query = "DELETE FROM shift_employees WHERE employee_id = :id";
    $shift_stmt = $db->prepare($shift_query);
    $shift_stmt->bindParam(":id", $data['id']);
    $shift_stmt->execute();
    
    // Удаляем пользователя
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data['id']);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка при удалении');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 