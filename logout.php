<?php
// Запускаем сессию
session_start();

// Очищаем все данные сессии
$_SESSION = array();

// Уничтожаем сессионную cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу логина
header("Location: login.php");
exit();
?> 