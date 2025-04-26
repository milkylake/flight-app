<?php
/**
 * Main API Entry Point
 * Handles requests prefixed with /api/
 */
declare(strict_types=1);

// Autoload Composer dependencies (if any are used directly here or by included files)
// Even if not directly used now, it's good practice if 'vendor' directory exists.
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require $autoloader;
}

// --- Send Correct HTTP Headers ---

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

// Handle HTTP OPTIONS preflight requests (sent by browsers before POST/PUT/DELETE etc.)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight request received, just send back the CORS headers and exit.
    http_response_code(200); // OK status for OPTIONS
    exit;
}

// --- Database Configuration & Connection ---
// Load configuration from environment variables (provided by Docker Compose)
$dbHost = $_ENV['DB_HOST'] ?? 'db'; // 'db' is the service name in docker-compose
$dbName = $_ENV['DB_DATABASE'] ?? 'flight_db';
$dbUser = $_ENV['DB_USER'] ?? 'user';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'password'; // Use the password from .env
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

// PDO options for error handling and fetch mode
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    // Establish database connection
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (\PDOException $e) {
    // Database connection failed - critical error
    http_response_code(500); // Internal Server Error
    // Log the detailed error for server admins
    error_log("FATAL: Database connection failed (index.php): " . $e->getMessage());
    // Send a generic error message to the client
    echo json_encode(['error' => 'Unable to connect to the database service.']);
    exit; // Stop script execution
}

// --- Simple Request Routing ---
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];


$requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Логируем для отладки, чтобы точно видеть, что приходит от Nginx
error_log("Received REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set'));
error_log("Parsed URI Path: " . $requestUriPath);



// --- Handle API Requests (prefixed with /api/) ---
if ($requestUriPath !== null) {
    // Extract the specific API route requested (e.g., 'airports', 'flights')
    $route = substr($requestUri, 1); // Remove the leading '/api/'

    try {
        // --- Route: /api/airports ---
        if ($route === 'airports' && $requestMethod === 'GET') {
            // Get search term from query parameters (e.g., /api/airports?search=Mos)
            $searchTerm = $_GET['search'] ?? '';

            // Base SQL query to select airport data
            $sql = "SELECT
                        airport_id as id,
                        iata_code,
                        name,
                        city,
                        country,
                        latitude,
                        longitude
                    FROM Airports"; // Use the correct table name 'Airports'

            $params = []; // Parameters for prepared statement

            if (!empty($searchTerm)) {
                // If search term provided, add WHERE clause to filter
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
                          LIMIT 20"; // Limit results for search suggestions

                // Prepare parameters for LIKE search
                $params[':search_iata'] = $searchTerm . '%'; // Match beginning for IATA
                $params[':search_name'] = '%' . $searchTerm . '%';
                $params[':search_city'] = '%' . $searchTerm . '%';
                // Params for priority sorting need exact same values
                $params[':search_iata_prio'] = $searchTerm . '%';
                $params[':search_city_prio'] = '%' . $searchTerm . '%';

            } else {
                // If no search term, return a limited list, ordered for browsing
                 $sql .= " ORDER BY country, city, name LIMIT 100";
            }

            // Prepare and execute the SQL statement
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Fetch all matching airports
            $airports = $stmt->fetchAll();

            // Send the results as JSON
            echo json_encode($airports);
            exit; // End script execution for this route
        }

        elseif ($route === 'hello' && $requestMethod === 'GET') {
            echo "hello";
            exit;
        }

        // --- Route: /api/flights ---
        elseif ($route === 'flights' && $requestMethod === 'GET') {
            // Get required parameters from query string
            $originIata = $_GET['origin'] ?? null;
            $destinationIata = $_GET['destination'] ?? null;
            $date = $_GET['date'] ?? null; // Expected format: YYYY-MM-DD

            // --- Validate Input Parameters ---
            if (!$originIata || !$destinationIata || !$date) {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Missing required parameters: origin (IATA), destination (IATA), date (YYYY-MM-DD)']);
                exit;
            }
            if (strlen($originIata) !== 3 || strlen($destinationIata) !== 3) {
                 http_response_code(400); // Bad Request
                 echo json_encode(['error' => 'Invalid IATA code format (must be 3 letters).']);
                 exit;
            }
             try {
                // Validate date format and value using DateTimeImmutable
                $dateTime = new DateTimeImmutable($date);
                $formattedDate = $dateTime->format('Y-m-d');
                // Extra check for invalid dates like 2024-02-31 which PHP might parse loosely
                if ($formattedDate !== $date) {
                    throw new Exception('Invalid date value provided.');
                }
            } catch (Exception $e) {
                 http_response_code(400); // Bad Request
                 echo json_encode(['error' => 'Invalid date format or value. Use YYYY-MM-DD. Details: ' . $e->getMessage()]);
                 exit;
             }
            // --- End Validation ---


            // --- Prepare SQL Query to Find Flights ---
            // Select detailed flight information by joining multiple tables
            $sql = "SELECT
                        f.flight_id,         -- Flight specific ID
                        f.flight_number,     -- e.g., SU1234
                        f.departure_time,    -- Departure timestamp
                        f.arrival_time,      -- Arrival timestamp
                        f.status as flight_status, -- Scheduled, OnTime, Delayed, etc.

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

                    FROM Flights f -- Base table: Flights (alias f)
                    -- Join with Airports table for departure details (alias orig)
                    JOIN Airports orig ON f.departure_airport_id = orig.airport_id
                    -- Join with Airports table for arrival details (alias dest)
                    JOIN Airports dest ON f.arrival_airport_id = dest.airport_id
                    -- Join with Airlines table for airline details (alias al)
                    JOIN Airlines al ON f.airline_id = al.airline_id
                    -- Join with Aircraft table for aircraft details (alias ac)
                    JOIN Aircraft ac ON f.aircraft_id = ac.aircraft_id

                    WHERE
                        orig.iata_code = :origin_iata         -- Filter by origin airport IATA code
                        AND dest.iata_code = :destination_iata  -- Filter by destination airport IATA code
                        AND DATE(f.departure_time) = :departure_date -- Filter by the date part of departure time
                        -- Optional: Filter out cancelled or already arrived flights if needed
                        -- AND f.status NOT IN ('Cancelled', 'Arrived')
                    ORDER BY f.departure_time ASC"; // Order results by departure time

            // Prepare the statement
            $stmt = $pdo->prepare($sql);

            // Bind parameters to the prepared statement
            $params = [
                ':origin_iata' => $originIata,
                ':destination_iata' => $destinationIata,
                ':departure_date' => $formattedDate // Use the validated date
            ];

            // Execute the query
            $stmt->execute($params);

            // Fetch all matching flights
            $flights = $stmt->fetchAll();

            // Send the flight data as JSON
            echo json_encode($flights);
            exit; // End script execution for this route
        }

        // --- Add other API endpoints here as needed ---
        // Example: GET /api/flights/{id}
        // elseif (preg_match('/^flights\/(\d+)$/', $route, $matches) && $requestMethod === 'GET') {
        //     $flightId = (int)$matches[1];
        //     // ... SQL and logic to fetch flight by $flightId ...
        //     echo json_encode($singleFlightData);
        //     exit;
        // }


        // --- API Route Not Found ---
        else {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'The requested API endpoint does not exist.', 'route' => $route]);
            exit;
        }

    } catch (\PDOException $e) {
        // --- Database Error Handling ---
        http_response_code(500); // Internal Server Error
        // Log the detailed database error
        error_log("ERROR: Database query failed (API: {$requestUri}): " . $e->getMessage());
        // Send a generic error message to the client
        echo json_encode(['error' => 'A database error occurred while processing your request.']);
        exit;
    } catch (\Throwable $e) {
        // --- General Error Handling ---
        http_response_code(500); // Internal Server Error
        // Log the general error
        error_log("ERROR: Unexpected error (API: {$requestUri}): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        // Send a generic error message
        echo json_encode(['error' => 'An unexpected server error occurred.']);
        exit;
    }

} else {
    // --- Request URI does not start with /api/ ---
    // This typically means a direct access attempt or misconfiguration.
    // The Nginx config should route valid non-API requests (like '/') to the frontend service.
    // Requests reaching here are likely errors or direct calls to index.php.
    http_response_code(404); // Not Found
    error_log("Request URI Path '" . $requestUriPath . "' does not start with /api/. Returning 404.");
    echo json_encode(['error' => 'Resource not found. This entry point only handles API requests under /api/.']);
    exit;
}

echo "asdasdsdsda";