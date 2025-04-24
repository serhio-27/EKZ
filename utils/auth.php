<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, r.name as role
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Функция для проверки роли пользователя
function checkRole($requiredRole) {
    $user = checkAuth();
    if (!$user || $user['role'] !== $requiredRole) {
        header('Location: /login.php');
        exit;
    }
    return $user;
} 