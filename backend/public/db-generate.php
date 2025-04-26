<?php




// fetch('/db-generate.php', { method: 'POST' })
//   .then(res => res.ok ? res.json() : Promise.reject(res))
//   .then(data => console.log('DB Generate:', data))
//   .catch(err => console.error('Error generating DB:', err));






declare(strict_types=1);

// --- Конфигурация обработки ошибок ---
ini_set('display_errors', '0'); // НЕ показывать ошибки пользователю/в браузер
ini_set('log_errors', '1'); // ЛОГИРОВАТЬ ошибки (в лог файл сервера или Docker)
error_reporting(E_ALL); // Уровень ошибок: сообщать обо всех ошибках (чтобы они попали в лог)
// --- Конец конфигурации обработки ошибок ---

require __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;

// --- Заголовки CORS и Content-Type ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Осторожно в проде!
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Уточняем разрешенные методы
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Добавляем нужные заголовки

// Обработка Preflight запросов CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Разрешаем только POST запросы ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

// --- Конфигурация БД ---
$dbHost = $_ENV['DB_HOST'] ?? 'db';
$dbName = $_ENV['DB_DATABASE'] ?? 'flight_db';
$dbUser = $_ENV['DB_USER'] ?? 'user';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'password';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null; // Инициализируем вне try
$logMessages = []; // Массив для логов

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("DB Connection Error (db-generate): " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]); // Можно добавить детали при отладке
    exit;
}

$faker = Factory::create('ru_RU'); // Faker для генерации данных


// === ОСНОВНОЙ БЛОК TRY...CATCH ДЛЯ ВСЕГО ПРОЦЕССА ===
try {

    // === Шаг 0: Создание таблиц, если их нет (IF NOT EXISTS) ===
    $logMessages[] = "Starting: Ensuring tables exist...";
    try {
        // Используем `CREATE TABLE IF NOT EXISTS` - безопасно для повторного запуска
        // Порядок важен из-за FOREIGN KEYs (сначала основные таблицы, потом зависимые)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `Airlines` (
              `airline_id` int NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `iata_code` varchar(3) NOT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`airline_id`),
              UNIQUE KEY `airline_iata_code_unique` (`iata_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `Airports` (
              `airport_id` int NOT NULL AUTO_INCREMENT,
              `iata_code` varchar(3) NOT NULL,
              `name` varchar(255) NOT NULL,
              `city` varchar(100) NOT NULL,
              `country` varchar(100) NOT NULL,
              `latitude` decimal(10,7) NOT NULL,
              `longitude` decimal(10,7) NOT NULL,
              `timezone` varchar(50) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`airport_id`),
              UNIQUE KEY `airport_iata_code_unique` (`iata_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

         $pdo->exec("
            CREATE TABLE IF NOT EXISTS `Aircraft` (
              `aircraft_id` int NOT NULL AUTO_INCREMENT,
              `model` varchar(100) NOT NULL,
              `capacity` int DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`aircraft_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
         ");

         $pdo->exec("
            CREATE TABLE IF NOT EXISTS `Flights` (
              `flight_id` int NOT NULL AUTO_INCREMENT,
              `flight_number` varchar(10) NOT NULL,
              `departure_airport_id` int NOT NULL,
              `arrival_airport_id` int NOT NULL,
              `departure_time` datetime NOT NULL,
              `arrival_time` datetime NOT NULL,
              `airline_id` int NOT NULL,
              `aircraft_id` int NOT NULL,
              `status` enum('Scheduled','OnTime','Delayed','Departed','Arrived','Cancelled') DEFAULT 'Scheduled',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`flight_id`),
              INDEX `idx_flight_departure_time` (`departure_time`),
              CONSTRAINT `fk_flight_dep_airport` FOREIGN KEY (`departure_airport_id`) REFERENCES `Airports` (`airport_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_flight_arr_airport` FOREIGN KEY (`arrival_airport_id`) REFERENCES `Airports` (`airport_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_flight_airline` FOREIGN KEY (`airline_id`) REFERENCES `Airlines` (`airline_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_flight_aircraft` FOREIGN KEY (`aircraft_id`) REFERENCES `Aircraft` (`aircraft_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
         ");

         $pdo->exec("
             CREATE TABLE IF NOT EXISTS `Users` (
              `user_id` int NOT NULL AUTO_INCREMENT,
              `first_name` varchar(50) NOT NULL,
              `last_name` varchar(50) NOT NULL,
              `email` varchar(100) NOT NULL,
              `password_hash` varchar(255) NOT NULL,
              `phone_number` varchar(20) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`user_id`),
              UNIQUE KEY `user_email_unique` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
         ");

         $pdo->exec("
            CREATE TABLE IF NOT EXISTS `Passengers` (
              `passenger_id` int NOT NULL AUTO_INCREMENT,
              `first_name` varchar(50) NOT NULL,
              `last_name` varchar(50) NOT NULL,
              `date_of_birth` date DEFAULT NULL,
              `passport_number` varchar(50) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`passenger_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
         ");

        $pdo->exec("
             CREATE TABLE IF NOT EXISTS `Bookings` (
              `booking_id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `booking_reference` varchar(10) NOT NULL,
              `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
              `status` enum('PendingPayment','Confirmed','Cancelled') DEFAULT 'PendingPayment',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`booking_id`),
              UNIQUE KEY `booking_reference_unique` (`booking_reference`),
              CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $pdo->exec("
             CREATE TABLE IF NOT EXISTS `Tickets` (
              `ticket_id` int NOT NULL AUTO_INCREMENT,
              `booking_id` int NOT NULL,
              `passenger_id` int NOT NULL,
              `flight_id` int NOT NULL,
              `seat_number` varchar(5) DEFAULT NULL,
              `class` enum('Economy','PremiumEconomy','Business','First') DEFAULT 'Economy',
              `price` decimal(10,2) NOT NULL,
              `ticket_number` varchar(20) NOT NULL,
              `status` enum('Issued','Cancelled','CheckedIn','Boarded') DEFAULT 'Issued',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`ticket_id`),
              UNIQUE KEY `ticket_number_unique` (`ticket_number`),
              CONSTRAINT `fk_ticket_booking` FOREIGN KEY (`booking_id`) REFERENCES `Bookings` (`booking_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_ticket_passenger` FOREIGN KEY (`passenger_id`) REFERENCES `Passengers` (`passenger_id`) ON DELETE CASCADE,
              CONSTRAINT `fk_ticket_flight` FOREIGN KEY (`flight_id`) REFERENCES `Flights` (`flight_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $logMessages[] = "Completed: Table check/creation finished.";

    } catch (PDOException $e) {
        error_log("Critical Error during table creation: " . $e->getMessage());
        // Если таблицы не создались, дальше нет смысла идти
        throw new RuntimeException("Failed to create necessary tables. Check logs.", 0, $e);
    }

    // === Шаг 1: Очистка таблиц (ВНЕ транзакции) ===
    $logMessages[] = "Starting: Clearing tables...";
    try {
        // Порядок TRUNCATE важен из-за внешних ключей,
        // либо отключаем проверку ключей на время очистки
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        // Усекаем таблицы (в обратном порядке зависимостей или любом при FOREIGN_KEY_CHECKS=0)
        $pdo->exec("TRUNCATE TABLE Tickets;");
        $pdo->exec("TRUNCATE TABLE Bookings;");
        $pdo->exec("TRUNCATE TABLE Passengers;");
        $pdo->exec("TRUNCATE TABLE Users;");
        $pdo->exec("TRUNCATE TABLE Flights;");
        $pdo->exec("TRUNCATE TABLE Aircraft;");
        $pdo->exec("TRUNCATE TABLE Airports;");
        $pdo->exec("TRUNCATE TABLE Airlines;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); // Включаем обратно!
        $logMessages[] = "Completed: Tables truncated.";
    } catch (PDOException $e) {
        error_log("Error during table truncation: " . $e->getMessage());
        // Перебрасываем ошибку, т.к. без очистки сидинг может быть некорректным
        throw new RuntimeException("Failed to clear tables before seeding. Check logs.", 0, $e);
    }

    // === Шаг 2: Заполнение данными (ВНУТРИ ТРАНЗАКЦИИ) ===
    $logMessages[] = "Starting: Seeding data transaction...";
    $pdo->beginTransaction(); // Начинаем транзакцию ТОЛЬКО для INSERT'ов

    try {
        // Авиакомпании
        $airlinesData = [
            ['Аэрофлот', 'SU'], ['S7 Airlines', 'S7'], ['Уральские авиалинии', 'U6'],
            ['Победа', 'DP'], ['Россия', 'FV'], ['Turkish Airlines', 'TK'], ['Emirates', 'EK']
        ];
        $airlineStmt = $pdo->prepare("INSERT INTO Airlines (name, iata_code) VALUES (?, ?)");
        foreach ($airlinesData as $al) {
            $airlineStmt->execute($al);
        }
        $airlineIds = $pdo->query("SELECT airline_id FROM Airlines")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Airlines seeded: " . count($airlineIds);

        // Аэропорты
        $airportsData = [
            // Россия (основные + региональные)
                        ['SVO', 'Шереметьево', 'Москва', 'Россия', 55.9727780, 37.4147220, 'Europe/Moscow'],
                        ['DME', 'Домодедово', 'Москва', 'Россия', 55.4088890, 37.9061110, 'Europe/Moscow'],
                        ['VKO', 'Внуково', 'Москва', 'Россия', 55.5913890, 37.2613890, 'Europe/Moscow'],
                        ['LED', 'Пулково', 'Санкт-Петербург', 'Россия', 59.8002780, 30.2625000, 'Europe/Moscow'],
                        ['AER', 'Сочи', 'Сочи', 'Россия', 43.4450000, 39.9477780, 'Europe/Moscow'],
                        ['SVX', 'Кольцово', 'Екатеринбург', 'Россия', 56.7430560, 60.8041670, 'Asia/Yekaterinburg'],
                        ['KZN', 'Казань', 'Казань', 'Россия', 55.6086110, 49.2791670, 'Europe/Moscow'],
                        ['OVB', 'Толмачёво', 'Новосибирск', 'Россия', 55.0127780, 82.6669440, 'Asia/Novosibirsk'],
                        ['KJA', 'Емельяново', 'Красноярск', 'Россия', 56.173056, 92.489167, 'Asia/Krasnoyarsk'],
                        ['ROV', 'Платов', 'Ростов-на-Дону', 'Россия', 47.490556, 39.918889, 'Europe/Moscow'], // Платов заменил старый RVI
                        ['KUF', 'Курумоч', 'Самара', 'Россия', 53.505833, 50.154167, 'Europe/Samara'],
                        ['UFA', 'Уфа', 'Уфа', 'Россия', 54.5575000, 55.8741670, 'Asia/Yekaterinburg'],
                        ['IKT', 'Иркутск', 'Иркутск', 'Россия', 52.2677780, 104.3500000, 'Asia/Irkutsk'],
                        ['KGD', 'Храброво', 'Калининград', 'Россия', 54.8900000, 20.5925000, 'Europe/Kaliningrad'],
                        ['MRV', 'Минеральные Воды', 'Минеральные Воды', 'Россия', 44.2250000, 43.0816670, 'Europe/Moscow'],
                        ['VVO', 'Кневичи', 'Владивосток', 'Россия', 43.3830560, 132.1488890, 'Asia/Vladivostok'],
                        ['GOJ', 'Стригино', 'Нижний Новгород', 'Россия', 56.2300000, 43.7841670, 'Europe/Moscow'],
                        ['CEK', 'Баландино', 'Челябинск', 'Россия', 55.3058330, 61.5038890, 'Asia/Yekaterinburg'],

                        // Международные (популярные направления)
                        ['IST', 'Стамбул (Новый)', 'Стамбул', 'Турция', 41.2588890, 28.7455560, 'Europe/Istanbul'],
                        ['DXB', 'Дубай (Международный)', 'Дубай', 'ОАЭ', 25.2527780, 55.3644440, 'Asia/Dubai'],
                        ['EVN', 'Звартноц', 'Ереван', 'Армения', 40.1472000, 44.3958000, 'Asia/Yerevan'],
                        ['TBS', 'Тбилиси', 'Тбилиси', 'Грузия', 41.6692000, 44.9547000, 'Asia/Tbilisi'],
                        ['AYT', 'Анталья', 'Анталья', 'Турция', 36.8987000, 30.8005000, 'Europe/Istanbul'],
                        ['PEK', 'Пекин Столичный', 'Пекин', 'Китай', 40.0800000, 116.5844440, 'Asia/Shanghai'],
                        ['BKK', 'Суварнабхуми', 'Бангкок', 'Таиланд', 13.6900000, 100.7501000, 'Asia/Bangkok'],
                        ['DEL', 'Им. Индиры Ганди', 'Дели', 'Индия', 28.5562000, 77.1000000, 'Asia/Kolkata'],
                        ['LHR', 'Хитроу', 'Лондон', 'Великобритания', 51.4700000, -0.4543000, 'Europe/London'],
                        ['CDG', 'Шарль-де-Голль', 'Париж', 'Франция', 49.0097000, 2.5479000, 'Europe/Paris'],
                        ['FRA', 'Франкфурт-на-Майне', 'Франкфурт', 'Германия', 50.0379000, 8.5622000, 'Europe/Berlin'],
                        ['AMS', 'Схипхол', 'Амстердам', 'Нидерланды', 52.3105000, 4.7683000, 'Europe/Amsterdam'],
                        ['JFK', 'Им. Джона Кеннеди', 'Нью-Йорк', 'США', 40.6413000, -73.7781000, 'America/New_York'],
                        ['LAX', 'Лос-Анджелес', 'Лос-Анджелес', 'США', 33.9416000, -118.4085000, 'America/Los_Angeles'],
        ];
        // Убедитесь, что количество плейсхолдеров (?) совпадает с количеством полей
        $airportStmt = $pdo->prepare("INSERT INTO Airports (iata_code, name, city, country, latitude, longitude, timezone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($airportsData as $ap) {
            $airportStmt->execute($ap);
        }
        $airportIds = $pdo->query("SELECT airport_id FROM Airports")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Airports seeded: " . count($airportIds);

        // Самолеты
        $aircraftData = [
            ['Boeing 737-800', 180], ['Airbus A320neo', 186], ['Sukhoi Superjet 100', 98],
            ['Boeing 777-300ER', 400], ['Airbus A350-900', 315], ['Embraer E190', 100]
        ];
        $aircraftStmt = $pdo->prepare("INSERT INTO Aircraft (model, capacity) VALUES (?, ?)");
        foreach ($aircraftData as $ac) {
            $aircraftStmt->execute($ac);
        }
        $aircraftIds = $pdo->query("SELECT aircraft_id FROM Aircraft")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Aircraft seeded: " . count($aircraftIds);

        // Рейсы (Flights)
        $flightStmt = $pdo->prepare("INSERT INTO Flights (flight_number, departure_airport_id, arrival_airport_id, departure_time, arrival_time, airline_id, aircraft_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $flightCount = 0;
        $maxFlights = 150;
        $statuses = ['Scheduled', 'OnTime', 'Delayed', 'Cancelled', 'Departed', 'Arrived']; // Добавим статусы

        if (!empty($airportIds) && !empty($airlineIds) && !empty($aircraftIds)) { // Проверка что массивы ID не пусты
            for ($i = 0; $i < $maxFlights; $i++) {
                $depAirportId = $faker->randomElement($airportIds);
                $arrAirportId = $faker->randomElement(array_diff($airportIds, [$depAirportId]));
                if ($arrAirportId === null) continue;

                $airlineId = $faker->randomElement($airlineIds);
                $aircraftId = $faker->randomElement($aircraftIds);

                // Получаем iata_code авиакомпании для номера рейса
                $airlineIataStmt = $pdo->prepare("SELECT iata_code FROM Airlines WHERE airline_id = ?");
                $airlineIataStmt->execute([$airlineId]);
                $airlineIata = $airlineIataStmt->fetchColumn();
                if (!$airlineIata) $airlineIata = 'XX'; // Запасной вариант, если не нашли

                $flightNumber = $airlineIata . $faker->numberBetween(100, 9999);

                $departureTime = $faker->dateTimeBetween('-10 days', '+30 days'); // Увеличим диапазон
                $durationHours = $faker->numberBetween(1, 10);
                $durationMinutes = $faker->numberBetween(0, 59);
                $arrivalTime = clone $departureTime;
                $arrivalTime->add(new DateInterval("PT{$durationHours}H{$durationMinutes}M"));
                $status = $faker->randomElement($statuses);

                // Логика статусов в зависимости от времени
                $now = new DateTime();
                if ($departureTime > $now && !in_array($status, ['Scheduled', 'Cancelled'])) {
                    $status = 'Scheduled'; // Будущие рейсы должны быть Scheduled или Cancelled
                } elseif ($arrivalTime < $now && !in_array($status, ['Arrived', 'Cancelled'])) {
                    $status = 'Arrived'; // Прошедшие рейсы должны быть Arrived или Cancelled
                } elseif ($departureTime < $now && $arrivalTime > $now && !in_array($status, ['Departed', 'Delayed', 'OnTime', 'Cancelled'])) {
                     // Рейсы в процессе выполнения
                     $status = $faker->randomElement(['Departed', 'Delayed', 'OnTime']);
                }

                $flightStmt->execute([
                    $flightNumber, $depAirportId, $arrAirportId,
                    $departureTime->format('Y-m-d H:i:s'), $arrivalTime->format('Y-m-d H:i:s'),
                    $airlineId, $aircraftId, $status
                ]);
                $flightCount++;
            }
        } else {
            $logMessages[] = "- WARNING: Skipping Flights seeding due to empty Airports, Airlines or Aircraft IDs.";
        }
        $logMessages[] = "- Flights seeded: " . $flightCount;
        // Получаем ID только активных рейсов для билетов
        $flightIds = $pdo->query("SELECT flight_id FROM Flights WHERE status IN ('Scheduled', 'OnTime', 'Delayed', 'Departed')")->fetchAll(PDO::FETCH_COLUMN);


        // Пользователи (Users)
        $userStmt = $pdo->prepare("INSERT INTO Users (first_name, last_name, email, password_hash, phone_number) VALUES (?, ?, ?, ?, ?)");
        $userIds = [];
        for ($i=0; $i<20; $i++) { // Увеличим количество пользователей
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $email = $faker->unique()->safeEmail;
            $passwordHash = password_hash('password123', PASSWORD_DEFAULT); // Используем хеширование!
            $phone = $faker->optional(0.8)->phoneNumber; // Телефон у 80%
            $userStmt->execute([$firstName, $lastName, $email, $passwordHash, $phone]);
            $userIds[] = $pdo->lastInsertId();
        }
        $logMessages[] = "- Users seeded: " . count($userIds);

        // Пассажиры (Passengers)
        $passengerStmt = $pdo->prepare("INSERT INTO Passengers (first_name, last_name, date_of_birth, passport_number) VALUES (?, ?, ?, ?)");
        $passengerIds = [];
        for ($i=0; $i<50; $i++) { // Увеличим количество пассажиров
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $dob = $faker->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'); // Шире диапазон дат рождения
            $passport = $faker->optional(0.7)->bothify('## ######'); // Паспорт у 70%
            $passengerStmt->execute([$firstName, $lastName, $dob, $passport]);
            $passengerIds[] = $pdo->lastInsertId();
        }
        $logMessages[] = "- Passengers seeded: " . count($passengerIds);


        // Бронирования (Bookings) и Билеты (Tickets)
        $bookingStmt = $pdo->prepare("INSERT INTO Bookings (user_id, booking_reference, total_amount, status) VALUES (?, ?, ?, ?)");
        $ticketStmt = $pdo->prepare("INSERT INTO Tickets (booking_id, passenger_id, flight_id, seat_number, class, price, ticket_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $bookingCount = 0;
        $ticketCount = 0;
        $maxBookings = 80; // Увеличим количество броней
        $bookingStatuses = ['PendingPayment', 'Confirmed', 'Cancelled'];
        $ticketStatuses = ['Issued', 'Cancelled', 'CheckedIn', 'Boarded']; // Добавим статусы
        $classes = ['Economy', 'PremiumEconomy', 'Business', 'First'];

        if (!empty($userIds) && !empty($passengerIds) && !empty($flightIds)) { // Проверка что есть ID для связей
            for ($i = 0; $i < $maxBookings; $i++) {
                $userId = $faker->randomElement($userIds);
                $bookingRef = $faker->unique()->regexify('[A-Z0-9]{6}');
                $bookingStatus = $faker->randomElement($bookingStatuses);
                $totalAmount = 0; // Рассчитаем

                $bookingStmt->execute([$userId, $bookingRef, 0, $bookingStatus]);
                $bookingId = $pdo->lastInsertId();
                $bookingCount++;

                $numTickets = $faker->numberBetween(1, 5); // До 5 билетов
                $currentFlightId = $faker->randomElement($flightIds); // Рейс для всей брони
                $passengersInBooking = $faker->randomElements($passengerIds, $numTickets); // Уникальные пассажиры для брони

                foreach ($passengersInBooking as $passengerId) {
                    $seat = $faker->optional(0.9)->regexify('[1-4]\d[A-F]'); // Место у 90%
                    $class = $faker->randomElement($classes);
                    $price = $faker->randomFloat(2, 3000, 80000); // Увеличим макс. цену
                    $ticketNumber = $faker->unique()->numerify('#########'); // Уникальный номер билета
                    $ticketStatus = 'Cancelled'; // По умолчанию

                    if ($bookingStatus === 'Confirmed') {
                         // Если бронь подтверждена, билет может быть выписан, зарегистрирован или посажен
                         // (В зависимости от статуса рейса)
                        $flightStatusStmt = $pdo->prepare("SELECT status FROM Flights WHERE flight_id = ?");
                        $flightStatusStmt->execute([$currentFlightId]);
                        $currentFlightStatus = $flightStatusStmt->fetchColumn();

                        if ($currentFlightStatus === 'Departed' || $currentFlightStatus === 'Arrived') {
                             $ticketStatus = $faker->randomElement(['CheckedIn', 'Boarded']);
                        } elseif ($currentFlightStatus === 'Scheduled' || $currentFlightStatus === 'OnTime' || $currentFlightStatus === 'Delayed') {
                             $ticketStatus = $faker->randomElement(['Issued', 'CheckedIn']);
                        } else { // Cancelled
                             $ticketStatus = 'Cancelled';
                        }
                    } elseif ($bookingStatus === 'PendingPayment') {
                        // Если оплата ожидается, статус билета не может быть Issued/CheckedIn/Boarded
                         $ticketStatus = $faker->randomElement(['Cancelled']); // Или какой-то 'Pending' статус, если бы он был
                    } else { // Booking Cancelled
                         $ticketStatus = 'Cancelled';
                    }


                    $ticketStmt->execute([$bookingId, $passengerId, $currentFlightId, $seat, $class, $price, $ticketNumber, $ticketStatus]);
                    // Добавляем стоимость только для не отмененных билетов и броней
                    if ($bookingStatus !== 'Cancelled' && $ticketStatus !== 'Cancelled') {
                        $totalAmount += $price;
                    }
                    $ticketCount++;
                }

                // Обновляем сумму бронирования
                $updateBookingStmt = $pdo->prepare("UPDATE Bookings SET total_amount = ? WHERE booking_id = ?");
                $updateBookingStmt->execute([$totalAmount, $bookingId]);
            }
        } else {
            $logMessages[] = "- WARNING: Skipping Bookings/Tickets seeding due to empty User, Passenger or available Flight IDs.";
        }
        $logMessages[] = "- Bookings seeded: " . $bookingCount;
        $logMessages[] = "- Tickets seeded: " . $ticketCount;


        // --- Коммит транзакции ---
        $logMessages[] = "Attempting to commit data transaction...";
        if ($pdo->inTransaction()) { // Доп. проверка перед коммитом
             $pdo->commit();
             $logMessages[] = "Completed: Data seeding transaction committed.";
        } else {
             $logMessages[] = "Warning: No active transaction to commit (might indicate an issue).";
             // Возможно, стоит выбросить исключение, если коммит ожидался
             // throw new RuntimeException("Commit failed: No active transaction found unexpectedly.");
        }


        // Успешный ответ
        http_response_code(200);
        echo json_encode([
            'message' => 'Database tables ensured/created, cleared and seeded successfully.',
            'log' => $logMessages
        ]);

    } catch (Exception $e) { // Ловим ошибки ТОЛЬКО на этапе вставки данных
        $originalErrorMessage = $e->getMessage();
        error_log("Seeding Error - Data Insertion Exception: " . $originalErrorMessage . "\n" . $e->getTraceAsString());

        // Откат транзакции вставки данных
        if ($pdo !== null && $pdo->inTransaction()) {
            try {
                $pdo->rollBack();
                error_log("Data seeding transaction successfully rolled back.");
            } catch (PDOException $rollbackException) {
                error_log("PDOException during data seeding rollback: " . $rollbackException->getMessage());
            }
        } else {
             error_log("Data seeding rollback skipped: No active transaction found after error.");
        }

        // Перебрасываем ошибку во внешний catch, чтобы он сформировал ответ
        throw $e;
    }


} catch (Exception $e) { // Внешний catch ловит ошибки создания/очистки или переброшенные ошибки вставки
    $originalErrorMessage = $e->getMessage();
    // Лог ошибки уже должен был произойти во внутреннем блоке catch
    error_log("Caught exception in outer block: " . $originalErrorMessage);

    http_response_code(500);
    echo json_encode([
        'error' => 'Database seeding failed. Check server logs for details.',
        'details' => $originalErrorMessage // Сообщение исходной ошибки
    ]);
}

exit; // Завершаем скрипт