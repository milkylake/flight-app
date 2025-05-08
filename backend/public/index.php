<?php
declare(strict_types=1);

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require $autoloader;
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

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

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);



if ($requestUriPath !== null) {
    $route = substr($requestUri, 1);
    try {
        // --- /api/hello ---
        if ($route === 'hello' && $requestMethod === 'GET') {
            echo "hello";
            exit;
        }

        // --- /api/airports ---
        elseif ($route === 'airports' && $requestMethod === 'GET') {
            $searchTerm = $_GET['search'] ?? '';
            $sql = "SELECT
                        airport_id as id,
                        iata_code,
                        name,
                        city,
                        country,
                        latitude,
                        longitude
                    FROM Airports";

            $params = [];

            if (!empty($searchTerm)) {
                $sql .= " WHERE iata_code LIKE :search_iata
                            OR name LIKE :search_name
                            OR city LIKE :search_city
                          ORDER BY
                            -- Prioritize matches: IATA > City > Name
                            CASE
                                WHEN iata_code LIKE :search_iata_prio THEN 0
                                WHEN city LIKE :search_city_prio THEN 1
                                ELSE 2
                            END,
                            city, name
                          LIMIT 20";

                $params[':search_iata'] = $searchTerm . '%';
                $params[':search_name'] = '%' . $searchTerm . '%';
                $params[':search_city'] = '%' . $searchTerm . '%';
                $params[':search_iata_prio'] = $searchTerm . '%';
                $params[':search_city_prio'] = '%' . $searchTerm . '%';
            } else {
                 $sql .= " ORDER BY country, city, name LIMIT 100";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $airports = $stmt->fetchAll();

            echo json_encode($airports);
            exit;
        }

        // --- Route: /api/flights ---
        elseif ($route === 'flights' && $requestMethod === 'GET') {
            $originIata = $_GET['origin'] ?? null;
            $destinationIata = $_GET['destination'] ?? null;
            $date = $_GET['date'] ?? null;

            if (!$originIata || !$destinationIata || !$date) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters: origin (IATA), destination (IATA), date (YYYY-MM-DD)']);
                exit;
            }

            try {
                $dateTime = new DateTimeImmutable($date);
                $formattedDate = $dateTime->format('Y-m-d');
                if ($formattedDate !== $date) {
                    throw new Exception('Invalid date value provided.');
                }
            } catch (Exception $e) {
                 http_response_code(400);
                 echo json_encode(['error' => 'Invalid date format or value. Use YYYY-MM-DD. Details: ' . $e->getMessage()]);
                 exit;
            }

            $sql = "SELECT
                        f.flight_id,
                        f.flight_number,
                        f.departure_time,
                        f.arrival_time,
                        f.status as flight_status,

                        orig.airport_id as origin_airport_id,
                        orig.iata_code as origin_iata,
                        orig.name as origin_name,
                        orig.city as origin_city,
                        orig.country as origin_country,
                        orig.latitude as origin_lat,
                        orig.longitude as origin_lon,
                        orig.timezone as origin_timezone,

                        dest.airport_id as destination_airport_id,
                        dest.iata_code as destination_iata,
                        dest.name as destination_name,
                        dest.city as destination_city,
                        dest.country as destination_country,
                        dest.latitude as destination_lat,
                        dest.longitude as destination_lon,
                        dest.timezone as destination_timezone,

                        al.airline_id,
                        al.name as airline_name,
                        al.iata_code as airline_iata,

                        ac.aircraft_id,
                        ac.model as aircraft_model,
                        ac.capacity as aircraft_capacity

                    FROM Flights f
                    JOIN Airports orig ON f.arrival_airport_id = orig.airport_id
                    JOIN Airports dest ON f.departure_airport_id = dest.airport_id
                    JOIN Airlines al ON f.airline_id = al.airline_id
                    JOIN Aircraft ac ON f.aircraft_id = ac.aircraft_id

                    WHERE
                        orig.iata_code = :origin_iata
                        AND dest.iata_code = :destination_iata
                        -- AND DATE(f.departure_time) = :departure_date
                    ORDER BY f.departure_time ASC";

            $stmt = $pdo->prepare($sql);

            $params = [
                ':origin_iata' => $originIata,
                ':destination_iata' => $destinationIata,
                // ':departure_date' => $formattedDate
            ];

            $stmt->execute($params);
            $flights = $stmt->fetchAll();

            echo json_encode($flights);
            exit;
        }

        // --- API Route Not Found ---
        else {
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint does not exist', 'route' => $route]);
            exit;
        }

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'A database error occurred while processing request']);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
        exit;
    }

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Resource not found']);
    exit;
}
