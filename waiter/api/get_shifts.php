<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = checkRole('Официант');

try {
    $pdo = getPDO();
    
    // Получаем текущую дату
    $currentDate = date('Y-m-d');
    
    // Получаем смены, в которых участвует официант
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.date, s.start_time, s.end_time
        FROM shifts s
        JOIN shift_employees se ON s.id = se.shift_id
        WHERE se.employee_id = ? AND s.date >= ?
        ORDER BY s.date ASC, s.start_time ASC
    ");
    
    $stmt->execute([$user['id'], $currentDate]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем даты
    foreach ($shifts as &$shift) {
        $shift['date'] = date('d.m.Y', strtotime($shift['date']));
        $shift['start_time'] = date('H:i', strtotime($shift['start_time']));
        $shift['end_time'] = date('H:i', strtotime($shift['end_time']));
    }
    
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