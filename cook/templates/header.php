<?php
require_once '../auth_check.php';

// Проверка на роль повара
if ($_SESSION['role'] !== 'Повар') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель повара - Система управления кафе</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../style/admin.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar {
            background-color: #28a745;
            padding: 1rem;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: rgba(255,255,255,0.8) !important;
        }
        .card-header {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Панель повара</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Заказы</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Выход</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav> 