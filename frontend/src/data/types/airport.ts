export interface Airport {
  id: number;
  iata_code: string;
  name: string; // Название аэропорта
  city: string;
  country: string;
  latitude: string; // Можно преобразовать в number при необходимости
  longitude: string;
}