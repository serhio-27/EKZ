<?php
header('Content-Type: application/json');
$db = require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['username']) || !isset($data['password']) || 
        !isset($data['full_name']) || !isset($data['role_id'])) {
        throw new Exception('Неверные данные');
    }
    
    // Проверка существования пользователя
    $check_query = "SELECT id FROM users WHERE username = :username";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":username", $data['username']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Пользователь с таким именем уже существует');
    }
    
    // Хеширование пароля
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (username, password, full_name, role_id) 
              VALUES (:username, :password, :full_name, :role_id)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $data['username']);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":full_name", $data['full_name']);
    $stmt->bindParam(":role_id", $data['role_id']);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Ошибка при сохранении');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 