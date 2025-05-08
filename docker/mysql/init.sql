CREATE DATABASE IF NOT EXISTS flight_db;
USE flight_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Tickets;
DROP TABLE IF EXISTS Bookings;
DROP TABLE IF EXISTS Passengers;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Flights;
DROP TABLE IF EXISTS Aircraft;
DROP TABLE IF EXISTS Airlines;
DROP TABLE IF EXISTS Airports;

SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE Airports
(
    airport_id INT AUTO_INCREMENT PRIMARY KEY,
    iata_code  VARCHAR(3)     NOT NULL UNIQUE,
    name       VARCHAR(255)   NOT NULL,
    city       VARCHAR(100)   NOT NULL,
    country    VARCHAR(100)   NOT NULL,
    latitude   decimal(10, 7) NOT NULL,
    longitude  decimal(10, 7) NOT NULL,
    timezone   VARCHAR(50)    NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Airlines
(
    airline_id INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    iata_code  VARCHAR(2)   NOT NULL UNIQUE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Aircraft
(
    aircraft_id INT AUTO_INCREMENT PRIMARY KEY,
    model       VARCHAR(100) NOT NULL,
    capacity    INT          NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Flights
(
    flight_id            INT AUTO_INCREMENT PRIMARY KEY,
    flight_number        VARCHAR(10)                                                                 NOT NULL,
    departure_airport_id INT                                                                         NOT NULL,
    arrival_airport_id   INT                                                                         NOT NULL,
    departure_time       DATETIME                                                                    NOT NULL,
    arrival_time         DATETIME                                                                    NOT NULL,
    airline_id           INT                                                                         NOT NULL,
    aircraft_id          INT                                                                         NOT NULL,
    status               ENUM ('Scheduled', 'OnTime', 'Delayed', 'Departed', 'Arrived', 'Cancelled') NOT NULL DEFAULT 'Scheduled',

    INDEX idx_flight_departure (departure_airport_id, departure_time),
    INDEX idx_flight_arrival (arrival_airport_id, arrival_time),
    INDEX idx_flight_number_date (flight_number, departure_time),

    FOREIGN KEY (departure_airport_id) REFERENCES Airports (airport_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (arrival_airport_id) REFERENCES Airports (airport_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (airline_id) REFERENCES Airlines (airline_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (aircraft_id) REFERENCES Aircraft (aircraft_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Users
(
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    phone_number    VARCHAR(20)  NULL,
    password_hash   VARCHAR(255) NOT NULL,
    date_registered DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Passengers
(
    passenger_id    INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    date_of_birth   DATE         NOT NULL,
    passport_number VARCHAR(50)  NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Bookings
(
    booking_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT                                                          NOT NULL,
    booking_reference VARCHAR(10)                                                  NOT NULL UNIQUE,
    booking_date      DATETIME                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount      DECIMAL(10, 2)                                               NOT NULL,
    status            ENUM ('PendingPayment', 'Confirmed', 'Cancelled', 'Expired') NOT NULL DEFAULT 'PendingPayment',

    FOREIGN KEY (user_id) REFERENCES Users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


CREATE TABLE Tickets
(
    ticket_id     INT AUTO_INCREMENT PRIMARY KEY,
    booking_id    INT                                                                                NOT NULL,
    passenger_id  INT                                                                                NOT NULL,
    flight_id     INT                                                                                NOT NULL,
    seat_number   VARCHAR(5)                                                                         NULL,
    class         ENUM ('Economy', 'PremiumEconomy', 'Business', 'First')                            NOT NULL DEFAULT 'Economy',
    price         DECIMAL(10, 2)                                                                     NOT NULL,
    ticket_number VARCHAR(20)                                                                        NOT NULL UNIQUE,
    status        ENUM ('Issued', 'CheckedIn', 'Boarded', 'Used', 'Refunded', 'Cancelled', 'NoShow') NOT NULL DEFAULT 'Issued',

    INDEX idx_ticket_passenger (passenger_id),
    INDEX idx_ticket_flight (flight_id),

    FOREIGN KEY (booking_id) REFERENCES Bookings (booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES Passengers (passenger_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES Flights (flight_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


SET FOREIGN_KEY_CHECKS = 1;