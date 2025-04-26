-- docker/mysql/init.sql
CREATE DATABASE IF NOT EXISTS flight_db;
USE flight_db;

-- Включаем проверку внешних ключей (на всякий случай)
SET FOREIGN_KEY_CHECKS=0; -- Выключаем для DROP

-- Удаляем таблицы в обратном порядке зависимостей, если они существуют
DROP TABLE IF EXISTS Tickets;
DROP TABLE IF EXISTS Bookings;
DROP TABLE IF EXISTS Passengers;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Flights;
DROP TABLE IF EXISTS Aircraft;
DROP TABLE IF EXISTS Airlines;
DROP TABLE IF EXISTS Airports;

-- Включаем обратно перед созданием
SET FOREIGN_KEY_CHECKS=1;

-- Создание таблицы Аэропорты
CREATE TABLE Airports (
                          airport_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор аэропорта',
                          iata_code VARCHAR(3) NOT NULL UNIQUE COMMENT 'Трехбуквенный код IATA (SVO)',
                          name VARCHAR(255) NOT NULL COMMENT 'Название аэропорта',
                          city VARCHAR(100) NOT NULL COMMENT 'Город',
                          country VARCHAR(100) NOT NULL COMMENT 'Страна',
                          latitude decimal(10,7) NOT NULL,
                          longitude decimal(10,7) NOT NULL,
                          timezone VARCHAR(50) NOT NULL COMMENT 'Часовой пояс (например, Europe/Moscow)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Справочник аэропортов';

-- Создание таблицы Авиакомпании
CREATE TABLE Airlines (
                          airline_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор авиакомпании',
                          name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Название авиакомпании',
                          iata_code VARCHAR(2) NOT NULL UNIQUE COMMENT 'Двухбуквенный код IATA (SU)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Справочник авиакомпаний';

-- Создание таблицы Самолеты
CREATE TABLE Aircraft (
                          aircraft_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор самолета',
                          model VARCHAR(100) NOT NULL COMMENT 'Модель самолета (Boeing 737-800)',
                          capacity INT NOT NULL COMMENT 'Вместимость (количество мест)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Справочник воздушных судов';

-- Создание таблицы Рейсы
CREATE TABLE Flights (
                         flight_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор конкретного рейса',
                         flight_number VARCHAR(10) NOT NULL COMMENT 'Номер рейса (SU1234)',
                         departure_airport_id INT NOT NULL COMMENT 'FK: Аэропорт вылета',
                         arrival_airport_id INT NOT NULL COMMENT 'FK: Аэропорт прилета',
                         departure_time DATETIME NOT NULL COMMENT 'Дата и время вылета (локальное)',
                         arrival_time DATETIME NOT NULL COMMENT 'Дата и время прилета (локальное)',
                         airline_id INT NOT NULL COMMENT 'FK: Авиакомпания',
                         aircraft_id INT NOT NULL COMMENT 'FK: Самолет, назначенный на рейс',
                         status ENUM('Scheduled', 'OnTime', 'Delayed', 'Departed', 'Arrived', 'Cancelled') NOT NULL DEFAULT 'Scheduled' COMMENT 'Статус рейса',

                         INDEX idx_flight_departure (departure_airport_id, departure_time),
                         INDEX idx_flight_arrival (arrival_airport_id, arrival_time),
                         INDEX idx_flight_number_date (flight_number, departure_time), -- Полезно для поиска конкретного рейса

                         FOREIGN KEY (departure_airport_id) REFERENCES Airports(airport_id) ON DELETE RESTRICT ON UPDATE CASCADE,
                         FOREIGN KEY (arrival_airport_id) REFERENCES Airports(airport_id) ON DELETE RESTRICT ON UPDATE CASCADE,
                         FOREIGN KEY (airline_id) REFERENCES Airlines(airline_id) ON DELETE RESTRICT ON UPDATE CASCADE,
                         FOREIGN KEY (aircraft_id) REFERENCES Aircraft(aircraft_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Информация о конкретных рейсах';

-- Создание таблицы Пользователи
CREATE TABLE Users (
                       user_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор пользователя',
                       first_name VARCHAR(100) NOT NULL COMMENT 'Имя',
                       last_name VARCHAR(100) NOT NULL COMMENT 'Фамилия',
                       email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email (логин)',
                       phone_number VARCHAR(20) NULL COMMENT 'Номер телефона (опционально)',
                       password_hash VARCHAR(255) NOT NULL COMMENT 'Хеш пароля', -- Не забудьте хешировать пароли!
                       date_registered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата регистрации'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Зарегистрированные пользователи системы';

-- Создание таблицы Пассажиры
CREATE TABLE Passengers (
                            passenger_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор пассажира',
                            first_name VARCHAR(100) NOT NULL COMMENT 'Имя пассажира',
                            last_name VARCHAR(100) NOT NULL COMMENT 'Фамилия пассажира',
                            date_of_birth DATE NOT NULL COMMENT 'Дата рождения',
                            passport_number VARCHAR(50) NULL COMMENT 'Номер паспорта или другого документа (может быть обязателен)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Данные пассажиров (могут отличаться от пользователя)';

-- Создание таблицы Бронирования
CREATE TABLE Bookings (
                          booking_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор бронирования',
                          user_id INT NOT NULL COMMENT 'FK: Пользователь, сделавший бронь',
                          booking_reference VARCHAR(10) NOT NULL UNIQUE COMMENT 'Уникальный код брони (PNR)',
                          booking_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время создания брони',
                          total_amount DECIMAL(10, 2) NOT NULL COMMENT 'Общая стоимость брони',
                          status ENUM('PendingPayment', 'Confirmed', 'Cancelled', 'Expired') NOT NULL DEFAULT 'PendingPayment' COMMENT 'Статус бронирования',

                          FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Информация о бронированиях';

-- Создание таблицы Билеты
CREATE TABLE Tickets (
                         ticket_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор билета',
                         booking_id INT NOT NULL COMMENT 'FK: Бронирование, к которому относится билет',
                         passenger_id INT NOT NULL COMMENT 'FK: Пассажир, на которого выписан билет',
                         flight_id INT NOT NULL COMMENT 'FK: Рейс, на который выписан билет',
                         seat_number VARCHAR(5) NULL COMMENT 'Номер места (12A), может быть NULL до регистрации',
                         class ENUM('Economy', 'PremiumEconomy', 'Business', 'First') NOT NULL DEFAULT 'Economy' COMMENT 'Класс обслуживания',
                         price DECIMAL(10, 2) NOT NULL COMMENT 'Стоимость конкретного билета',
                         ticket_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Уникальный номер электронного билета (длинный)',
                         status ENUM('Issued', 'CheckedIn', 'Boarded', 'Used', 'Refunded', 'Cancelled', 'NoShow') NOT NULL DEFAULT 'Issued' COMMENT 'Статус билета',

                         INDEX idx_ticket_passenger (passenger_id),
                         INDEX idx_ticket_flight (flight_id),

                         FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
                         FOREIGN KEY (passenger_id) REFERENCES Passengers(passenger_id) ON DELETE RESTRICT ON UPDATE CASCADE,
                         FOREIGN KEY (flight_id) REFERENCES Flights(flight_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Информация о конкретных авиабилетах';

-- Убедимся, что проверка внешних ключей включена после создания таблиц
SET FOREIGN_KEY_CHECKS=1;