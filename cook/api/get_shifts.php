<?php
require_once '../../config/database.php';
require_once '../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkAuth();
if (!$user || $user['role'] !== 'Повар') {
    echo json_encode([
        'success' => false,
        'message' => 'Доступ запрещен'
    ]);
    exit;
}

try {
    $pdo = getPDO();
    
    // Получаем текущую дату
    $currentDate = date('Y-m-d');
    
    // Получаем смены на текущую дату и будущие даты
    $stmt = $pdo->prepare("
        SELECT id, date, start_time, end_time 
        FROM shifts 
        WHERE date >= ? 
        ORDER BY date ASC, start_time ASC
    ");
    
    $stmt->execute([$currentDate]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $shifts
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при получении списка смен'
    ]);
} 