-- Создание базы данных
CREATE DATABASE IF NOT EXISTS cafe_management;
USE cafe_management;

-- Создание таблицы ролей
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL
);

-- Создание таблицы пользователей
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Создание таблицы смен
CREATE TABLE shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
);

-- Создание таблицы статусов заказов
CREATE TABLE order_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL
);

-- Создание таблицы заказов
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    waiter_id INT NOT NULL,
    status_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shifts(id),
    FOREIGN KEY (waiter_id) REFERENCES users(id),
    FOREIGN KEY (status_id) REFERENCES order_statuses(id)
);

-- Создание таблицы связи смен и сотрудников
CREATE TABLE shift_employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    employee_id INT NOT NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id),
    FOREIGN KEY (employee_id) REFERENCES users(id)
);

-- Вставка тестовых данных

-- Роли
INSERT INTO roles (name) VALUES 
('Администратор'),
('Официант'),
('Повар');

-- Пользователи (пароли захешированы)
INSERT INTO users (username, password, full_name, role_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Иванов Иван Иванович', 1),
('waiter1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Петров Петр Петрович', 2),
('waiter2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Сидоров Сидор Сидорович', 2),
('cook1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Кузнецов Кузьма Кузьмич', 3),
('cook2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Смирнов Смир Смирнович', 3);

-- Статусы заказов
INSERT INTO order_statuses (name) VALUES
('Принят'),
('Готов'),
('Оплачен'),
('Закрыт');

-- Смены
INSERT INTO shifts (date, start_time, end_time) VALUES
('2024-03-20', '08:00:00', '16:00:00'),
('2024-03-20', '16:00:00', '00:00:00'),
('2024-03-21', '08:00:00', '16:00:00'),
('2024-03-21', '16:00:00', '00:00:00');

-- Связь смен и сотрудников
INSERT INTO shift_employees (shift_id, employee_id) VALUES
(1, 2), -- Официант 1 на первую смену
(1, 4), -- Повар 1 на первую смену
(2, 3), -- Официант 2 на вторую смену
(2, 5); -- Повар 2 на вторую смену

-- Заказы
INSERT INTO orders (shift_id, waiter_id, status_id) VALUES
(1, 2, 1), -- Заказ принят
(1, 2, 2), -- Заказ готов
(2, 3, 3), -- Заказ оплачен
(2, 3, 4); -- Заказ закрыт 