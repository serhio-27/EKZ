<?php
header('Content-Type: application/json');
$db = require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['username']) || 
        !isset($data['full_name']) || !isset($data['role_id'])) {
        throw new Exception('Неверные данные');
    }
    
    // Проверка существования пользователя с таким же именем
    $check_query = "SELECT id FROM users WHERE username = :username AND id != :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":username", $data['username']);
    $check_stmt->bindParam(":id", $data['id']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Пользователь с таким именем уже существует');
    }
    
    if (!empty($data['password'])) {
        // Если указан новый пароль, обновляем его
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET 
                  username = :username,
                  password = :password,
                  full_name = :full_name,
                  role_id = :role_id
                  WHERE id = :id";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
    } else {
        // Если пароль не указан, обновляем остальные поля
        $query = "UPDATE users SET 
                  username = :username,
                  full_name = :full_name,
                  role_id = :role_id
                  WHERE id = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $data['username']);
    $stmt->bindParam(":full_name", $data['full_name']);
    $stmt->bindParam(":role_id", $data['role_id']);
    $stmt->bindParam(":id", $data['id']);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка при обновлении');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 