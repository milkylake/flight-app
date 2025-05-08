export interface Flight {
  flight_id: number;
  flight_number: string;
  departure_time: string;
  arrival_time: string;
  flight_status: 'Scheduled' | 'OnTime' | 'Delayed' | 'Departed' | 'Arrived' | 'Cancelled';

  origin_airport_id: number;
  origin_iata: string;
  origin_name: string;
  origin_city: string;
  origin_country: string;
  origin_lat: string;
  origin_lon: string;
  origin_timezone: string | null;

  destination_airport_id: number;
  destination_iata: string;
  destination_name: string;
  destination_city: string;
  destination_country: string;
  destination_lat: string;
  destination_lon: string;
  destination_timezone: string | null;

  airline_id: number;
  airline_name: string;
  airline_iata: string;

  aircraft_id: number;
  aircraft_model: string;
  aircraft_capacity: number | null;
}