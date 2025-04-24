<?php
header('Content-Type: application/json');
$db = require_once '../../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID не указан');
    }
    
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $employee = $stmt->fetch();
        unset($employee['password']); // Не отправляем пароль
        
        echo json_encode([
            'success' => true,
            'data' => $employee
        ]);
    } else {
        throw new Exception('Сотрудник не найден');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 