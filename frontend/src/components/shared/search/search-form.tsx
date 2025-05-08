'use client';

import { FC } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useFlightSearchStore } from '@/store/search.store';
import { AirportInput } from '@/components/shared/search/airport-input';
import { FormDatePicker } from '@/components/shared/search/form-date-picker';
import { FormCheckbox } from '@/components/shared/search/form-checkbox';

interface ISearchProps {
  className?: string;
}

export const SearchForm: FC<ISearchProps> = ({ className }) => {
  const {
    originAirport,
    destinationAirport,
    departureDate,
    returnDate,
    isOneWay,
    originSearchTerm,
    originSuggestions,
    isOriginLoading,
    originError,
    destinationSearchTerm,
    destinationSuggestions,
    isDestinationLoading,
    destinationError,
    setOriginAirport,
    setDestinationAirport,
    setDepartureDate,
    setReturnDate,
    setIsOneWay,
    setOriginSearchTerm,
    fetchOriginSuggestions,
    selectOriginSuggestion,
    clearOriginSuggestions,
    setDestinationSearchTerm,
    fetchDestinationSuggestions,
    selectDestinationSuggestion,
    clearDestinationSuggestions,
    searchFlights,
    isFlightsLoading
  } = useFlightSearchStore();


  const handleSearchSubmit = async () => {
    await searchFlights();
  };

  const handleDepartureDateSelect = (selectedDate: Date | undefined) => {
    setDepartureDate(selectedDate ?? null);

    if (
      !isOneWay
      && returnDate
      && selectedDate
      && selectedDate > returnDate
    ) {
      setReturnDate(selectedDate);
    }
  };

  const handleReturnDateSelect = (selectedDate: Date | undefined) => {
    setReturnDate(selectedDate ?? null);
  };

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  return (
    <div className={cn('relative w-full', className)}>
      <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
        <AirportInput
          id="origin"
          label="Откуда"
          placeholder="Город или аэропорт"
          searchTerm={originSearchTerm}
          suggestions={originSuggestions}
          isLoading={isOriginLoading}
          error={originError}
          onSearchTermChange={setOriginSearchTerm}
          onFetchSuggestions={fetchOriginSuggestions}
          onSelectSuggestion={selectOriginSuggestion}
          onClearSuggestions={clearOriginSuggestions}
          onClearSelectedAirport={() => setOriginAirport(null)}
        />

        <AirportInput
          id="destination"
          label="Куда"
          placeholder="Город или аэропорт"
          searchTerm={destinationSearchTerm}
          suggestions={destinationSuggestions}
          isLoading={isDestinationLoading}
          error={destinationError}
          onSearchTermChange={setDestinationSearchTerm}
          onFetchSuggestions={fetchDestinationSuggestions}
          onSelectSuggestion={selectDestinationSuggestion}
          onClearSuggestions={clearDestinationSuggestions}
          onClearSelectedAirport={() => setDestinationAirport(null)}
        />

        <FormDatePicker
          label="Туда"
          fieldId="departure-date"
          date={departureDate ?? undefined}
          setDate={handleDepartureDateSelect}
          placeholder="Дата вылета"
          fromDate={today}
        />

        <FormDatePicker
          label="Обратно"
          fieldId="return-date"
          date={returnDate ?? undefined}
          setDate={handleReturnDateSelect}
          placeholder="Дата возврата"
          disabled={isOneWay}
          fromDate={departureDate ? new Date(departureDate) : today}
        />

        <FormCheckbox
          fieldId="oneWay"
          label="В одну сторону"
          isChecked={isOneWay}
          onCheckedChange={(checked) => setIsOneWay(Boolean(checked))}
          containerClassName="pt-4 md:pt-0 h-[40px] self-center md:self-end justify-start md:justify-center"
        />

        <Button
          className=""
          onClick={handleSearchSubmit}
          disabled={!originAirport || !destinationAirport || !departureDate || isFlightsLoading}
        >
          {
            isFlightsLoading
              ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" /> Поиск...
                </>
              ) : (
                'Найти'
              )
          }
        </Button>
      </div>
    </div>
  );
};