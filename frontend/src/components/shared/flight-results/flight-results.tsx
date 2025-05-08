'use client';

import { useFlightSearchStore } from '@/store/search.store';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Loader2 } from 'lucide-react';
import { format } from 'date-fns';
import { ru } from 'date-fns/locale';
import { cn } from '@/lib/utils';

export const FlightResults = () => {
  const { foundFlights, isFlightsLoading, flightsError } = useFlightSearchStore();

  if (isFlightsLoading) {
    return (
      <div className="mt-6 flex items-center justify-center p-4">
        <Loader2 className="mr-2 h-8 w-8 animate-spin text-primary" />
        <p className="text-lg">Ищем рейсы...</p>
      </div>
    );
  }

  if (flightsError) {
    return (
      <div className="mt-6 p-4 border border-destructive/50 bg-destructive/10 rounded-md">
        <p className="text-destructive text-center font-semibold">Ошибка при поиске рейсов:</p>
        <p className="text-destructive/80 text-center text-sm">{flightsError}</p>
      </div>
    );
  }

  if (!isFlightsLoading && foundFlights.length === 0) {
    return (
      <div className="mt-6 text-center text-muted-foreground">
        <p>По вашему запросу рейсов не найдено.</p>
      </div>
    );
  }

  if (foundFlights.length === 0) return null;

  return (
    <div className="mt-6 space-y-4">
      <h2 className="text-2xl font-semibold">Найденные рейсы:</h2>
      {foundFlights.map((flight) => (
        <Card key={flight.flight_id}>
          <CardHeader>
            <CardTitle className="flex justify-between items-center">
              <span>
                {flight.origin_city}
                ({flight.origin_iata}) → {flight.destination_city}
                ({flight.destination_iata})
              </span>
              <span className="text-sm font-normal text-muted-foreground">
                {flight.flight_number} ({flight.airline_iata})
              </span>
            </CardTitle>
            <CardDescription>
              {flight.airline_name} - {flight.aircraft_model}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <p className="font-medium">Вылет:</p>
                <p>{format(new Date(flight.departure_time), 'dd MMMM yyyy, HH:mm', { locale: ru })}</p>
                <p className="text-xs text-muted-foreground">{flight.origin_name}</p>
              </div>
              <div>
                <p className="font-medium">Прибытие:</p>
                <p>{format(new Date(flight.arrival_time), 'dd MMMM yyyy, HH:mm', { locale: ru })}</p>
                <p className="text-xs text-muted-foreground">{flight.destination_name}</p>
              </div>
              <div>
                <p className="font-medium">Статус:</p>
                <p className={cn(
                  flight.flight_status === 'Cancelled' && 'text-red-600',
                  flight.flight_status === 'Delayed' && 'text-orange-500',
                  flight.flight_status === 'OnTime' && 'text-green-600'
                )}>
                  {flight.flight_status}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
};