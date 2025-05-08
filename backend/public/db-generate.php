<?php

// fetch('/db-generate.php', { method: 'POST' })
//   .then(res => res.ok ? res.json() : Promise.reject(res))
//   .then(data => console.log('DB Generate:', data))
//   .catch(err => console.error('Error generating DB:', err));

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

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

$pdo = null;
$logMessages = [];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database connection failed" . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
    exit;
}

$faker = Factory::create('ru_RU');




try {
    $logMessages[] = "Starting: Обеспечиваем существование таблиц...";
    try {
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

        $logMessages[] = "Completed: создание/проверка таблиц завершена.";

    } catch (PDOException $e) {
        error_log("Failed to create tables" . $e->getMessage());
        throw new RuntimeException("Failed to create tables", 0, $e);
    }



    $logMessages[] = "Starting: очистка таблиц...";
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE Tickets;");
        $pdo->exec("TRUNCATE TABLE Bookings;");
        $pdo->exec("TRUNCATE TABLE Passengers;");
        $pdo->exec("TRUNCATE TABLE Users;");
        $pdo->exec("TRUNCATE TABLE Flights;");
        $pdo->exec("TRUNCATE TABLE Aircraft;");
        $pdo->exec("TRUNCATE TABLE Airports;");
        $pdo->exec("TRUNCATE TABLE Airlines;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $logMessages[] = "Completed: таблицы очищены.";
    } catch (PDOException $e) {
        error_log("Failed to clear tables before seeding" . $e->getMessage());
        throw new RuntimeException("Failed to clear tables before seeding", 0, $e);
    }



    $logMessages[] = "Starting: начинаем заполнение данными...";
    $pdo->beginTransaction();

    try {
        $airlinesData = [
            ['Аэрофлот', 'SU'],
            ['S7 Airlines', 'S7'],
            ['Уральские авиалинии', 'U6'],
            ['Победа', 'DP'],
            ['Россия', 'FV'],
            ['Turkish Airlines', 'TK'],
            ['Emirates', 'EK']
        ];
        $airlineStmt = $pdo->prepare("INSERT INTO Airlines (name, iata_code) VALUES (?, ?)");
        foreach ($airlinesData as $al) {
            $airlineStmt->execute($al);
        }
        $airlineIds = $pdo->query("SELECT airline_id FROM Airlines")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Airlines созданы: " . count($airlineIds);



        $airportsData = [
            ['SVO', 'Шереметьево', 'Москва', 'Россия', 55.9727780, 37.4147220, 'Europe/Moscow'],
            ['DME', 'Домодедово', 'Москва', 'Россия', 55.4088890, 37.9061110, 'Europe/Moscow'],
            ['VKO', 'Внуково', 'Москва', 'Россия', 55.5913890, 37.2613890, 'Europe/Moscow'],
            ['LED', 'Пулково', 'Санкт-Петербург', 'Россия', 59.8002780, 30.2625000, 'Europe/Moscow'],
            ['AER', 'Сочи', 'Сочи', 'Россия', 43.4450000, 39.9477780, 'Europe/Moscow'],
            ['SVX', 'Кольцово', 'Екатеринбург', 'Россия', 56.7430560, 60.8041670, 'Asia/Yekaterinburg'],
            ['KZN', 'Казань', 'Казань', 'Россия', 55.6086110, 49.2791670, 'Europe/Moscow'],
            ['OVB', 'Толмачёво', 'Новосибирск', 'Россия', 55.0127780, 82.6669440, 'Asia/Novosibirsk'],
            ['KJA', 'Емельяново', 'Красноярск', 'Россия', 56.173056, 92.489167, 'Asia/Krasnoyarsk'],
            ['ROV', 'Платов', 'Ростов-на-Дону', 'Россия', 47.490556, 39.918889, 'Europe/Moscow'],
            ['KUF', 'Курумоч', 'Самара', 'Россия', 53.505833, 50.154167, 'Europe/Samara'],
            ['UFA', 'Уфа', 'Уфа', 'Россия', 54.5575000, 55.8741670, 'Asia/Yekaterinburg'],
            ['IKT', 'Иркутск', 'Иркутск', 'Россия', 52.2677780, 104.3500000, 'Asia/Irkutsk'],
            ['KGD', 'Храброво', 'Калининград', 'Россия', 54.8900000, 20.5925000, 'Europe/Kaliningrad'],
            ['MRV', 'Минеральные Воды', 'Минеральные Воды', 'Россия', 44.2250000, 43.0816670, 'Europe/Moscow'],
            ['VVO', 'Кневичи', 'Владивосток', 'Россия', 43.3830560, 132.1488890, 'Asia/Vladivostok'],
            ['GOJ', 'Стригино', 'Нижний Новгород', 'Россия', 56.2300000, 43.7841670, 'Europe/Moscow'],
            ['CEK', 'Баландино', 'Челябинск', 'Россия', 55.3058330, 61.5038890, 'Asia/Yekaterinburg'],
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
        $airportStmt = $pdo->prepare("INSERT INTO Airports (iata_code, name, city, country, latitude, longitude, timezone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($airportsData as $ap) {
            $airportStmt->execute($ap);
        }
        $airportIds = $pdo->query("SELECT airport_id FROM Airports")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Airports созданы: " . count($airportIds);



        $aircraftData = [
            ['Boeing 737-800', 180],
            ['Airbus A320neo', 186],
            ['Sukhoi Superjet 100', 98],
            ['Boeing 777-300ER', 400],
            ['Airbus A350-900', 315],
            ['Embraer E190', 100]
        ];
        $aircraftStmt = $pdo->prepare("INSERT INTO Aircraft (model, capacity) VALUES (?, ?)");
        foreach ($aircraftData as $ac) {
            $aircraftStmt->execute($ac);
        }
        $aircraftIds = $pdo->query("SELECT aircraft_id FROM Aircraft")->fetchAll(PDO::FETCH_COLUMN);
        $logMessages[] = "- Aircraft созданы: " . count($aircraftIds);



        $flightStmt = $pdo->prepare("INSERT INTO Flights (flight_number, departure_airport_id, arrival_airport_id, departure_time, arrival_time, airline_id, aircraft_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $flightCount = 0;
        $maxFlights = 3000;
        $statuses = ['Scheduled', 'OnTime', 'Delayed', 'Cancelled', 'Departed', 'Arrived'];

        if (!empty($airportIds) && !empty($airlineIds) && !empty($aircraftIds)) {
            for ($i = 0; $i < $maxFlights; $i++) {
                $depAirportId = $faker->randomElement($airportIds);
                $arrAirportId = $faker->randomElement(array_diff($airportIds, [$depAirportId]));
                if ($arrAirportId === null) continue;

                $airlineId = $faker->randomElement($airlineIds);
                $aircraftId = $faker->randomElement($aircraftIds);

                $airlineIataStmt = $pdo->prepare("SELECT iata_code FROM Airlines WHERE airline_id = ?");
                $airlineIataStmt->execute([$airlineId]);
                $airlineIata = $airlineIataStmt->fetchColumn();
                if (!$airlineIata) $airlineIata = 'XX';

                $flightNumber = $airlineIata . $faker->numberBetween(100, 9999);

                $departureTime = $faker->dateTimeBetween('-10 days', '+30 days');
                $durationHours = $faker->numberBetween(1, 10);
                $durationMinutes = $faker->numberBetween(0, 59);
                $arrivalTime = clone $departureTime;
                $arrivalTime->add(new DateInterval("PT{$durationHours}H{$durationMinutes}M"));
                $status = $faker->randomElement($statuses);

                $now = new DateTime();
                if ($departureTime > $now && !in_array($status, ['Scheduled', 'Cancelled'])) {
                    $status = 'Scheduled';
                } elseif ($arrivalTime < $now && !in_array($status, ['Arrived', 'Cancelled'])) {
                    $status = 'Arrived';
                } elseif ($departureTime < $now && $arrivalTime > $now && !in_array($status, ['Departed', 'Delayed', 'OnTime', 'Cancelled'])) {
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
            $logMessages[] = "- WARNING: пропуск создания Flights. Airports, Airlines или Aircraft IDs пусты";
        }
        $logMessages[] = "- Flights созданы: " . $flightCount;
        $flightIds = $pdo->query("SELECT flight_id FROM Flights WHERE status IN ('Scheduled', 'OnTime', 'Delayed', 'Departed')")->fetchAll(PDO::FETCH_COLUMN);



        $logMessages[] = "коммит транзакции...";
        if ($pdo->inTransaction()) {
             $pdo->commit();
             $logMessages[] = "Completed: транзакция закоммичена.";
        } else {
             $logMessages[] = "Warning: нет активной транзакции для коммита";
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Database tables ensured/created, cleared and seeded successfully',
            'log' => $logMessages
        ]);

    } catch (Exception $e) {
        error_log("Seeding Error" . $e->getMessage());

        if ($pdo !== null && $pdo->inTransaction()) {
            try {
                $pdo->rollBack();
                error_log("Data seeding transaction rolled back");
            } catch (PDOException $rollbackException) {
                error_log("PDOException during data seeding rollback: " . $rollbackException->getMessage());
            }
        }

        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database seeding failed.',
        'details' => $e->getMessage()
    ]);
}

exit;