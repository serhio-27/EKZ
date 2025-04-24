-- Создание базы данных
CREATE DATABASE IF NOT EXISTS caman;
USE caman;

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
    is_blocked BOOLEAN DEFAULT FALSE,
    login_attempts INT DEFAULT 0,
    last_attempt_time TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Создание таблицы блюд
CREATE TABLE dishes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
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

-- Создание таблицы позиций заказа
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    dish_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (dish_id) REFERENCES dishes(id)
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

-- Блюда
INSERT INTO dishes (name, description, price) VALUES
('Борщ', 'Классический борщ со сметаной', 250.00),
('Цезарь с курицей', 'Салат с курицей, сыром и соусом', 350.00),
('Стейк из говядины', 'Стейк medium well с овощами', 750.00),
('Паста Карбонара', 'Спагетти с беконом в сливочном соусе', 450.00);

-- Статусы заказов
INSERT INTO order_statuses (name) VALUES
('Принят'),
('Готовится'),
('Готов'),
('Оплачен');

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
(1, 2, 2), -- Заказ готовится
(2, 3, 3), -- Заказ готов
(2, 3, 4); -- Заказ оплачен

-- Позиции заказов
INSERT INTO order_items (order_id, dish_id, quantity, price) VALUES
(1, 1, 2, 250.00), -- 2 борща в первом заказе
(1, 2, 1, 350.00), -- 1 цезарь в первом заказе
(2, 3, 1, 750.00), -- 1 стейк во втором заказе
(2, 4, 2, 450.00), -- 2 пасты во втором заказе
(3, 1, 3, 250.00), -- 3 борща в третьем заказе
(3, 3, 1, 750.00), -- 1 стейк в третьем заказе
(4, 2, 2, 350.00), -- 2 цезаря в четвертом заказе
(4, 4, 1, 450.00); -- 1 паста в четвертом заказе 