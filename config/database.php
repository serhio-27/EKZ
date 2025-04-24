<?php

function getPDO() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=caman;charset=utf8',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            throw new PDOException("Ошибка подключения к базе данных");
        }
    }
    
    return $pdo;
}

// Для обратной совместимости
$db = getPDO();
return $db;
?> 