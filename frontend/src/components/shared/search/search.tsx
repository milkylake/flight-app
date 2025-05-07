"use client"; // Делаем компонент клиентским, т.к. используем хуки и обработчики

import { FC, useState, ChangeEvent, FocusEvent } from 'react';
import { useDebouncedCallback } from 'use-debounce';
import { cn } from '@/lib/utils';

// Компоненты Shadcn/ui
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card"; // Для отображения подсказок
import { Loader2 } from 'lucide-react';
import { useFlightSearchStore } from '@/store/search.store';
import { Airport } from '@/data/types/airport';
import { DatePicker } from '@/components/ui/date-picker'; // Иконка загрузки

// Наш стор

interface ISearchProps {
  className?: string;
  onSearchSubmit?: () => void; // Callback для кнопки поиска (опционально)
}

export const Search: FC<ISearchProps> = ({ className, onSearchSubmit }) => {
  // Получаем состояние и действия из стора
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
    setOriginAirport, // Нам не нужен прямой вызов, используем select...
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
  } = useFlightSearchStore();

  // Состояние для отображения/скрытия подсказок
  const [showOriginSuggestions, setShowOriginSuggestions] = useState(false);
  const [showDestinationSuggestions, setShowDestinationSuggestions] = useState(false);

  // --- Debounce для поиска ---
  const debouncedFetchOrigin = useDebouncedCallback((term: string) => {
    fetchOriginSuggestions(term);
  }, 300);

  const debouncedFetchDestination = useDebouncedCallback((term: string) => {
    fetchDestinationSuggestions(term);
  }, 300);

  // --- Обработчики событий ---
  const handleOriginChange = (event: ChangeEvent<HTMLInputElement>) => {
    const term = event.target.value;
    setOriginSearchTerm(term); // Обновляем значение в инпуте немедленно
    if (term) {
      setShowOriginSuggestions(true); // Показываем контейнер подсказок
      debouncedFetchOrigin(term);   // Вызываем debounced поиск
    } else {
      setShowOriginSuggestions(false); // Скрываем, если поле пустое
      clearOriginSuggestions();     // Очищаем старые подсказки
      setOriginAirport(null);       // Сбрасываем выбор, если поле очищено
    }
  };

  const handleDestinationChange = (event: ChangeEvent<HTMLInputElement>) => {
    const term = event.target.value;
    setDestinationSearchTerm(term);
    if (term) {
      setShowDestinationSuggestions(true);
      debouncedFetchDestination(term);
    } else {
      setShowDestinationSuggestions(false);
      clearDestinationSuggestions();
      setDestinationAirport(null);
    }
  };

  const handleSelectOrigin = (airport: Airport) => {
    selectOriginSuggestion(airport); // Обновляем стор
    setShowOriginSuggestions(false); // Скрываем подсказки
  };

  const handleSelectDestination = (airport: Airport) => {
    selectDestinationSuggestion(airport);
    setShowDestinationSuggestions(false);
  };

  const handleDepartureDateChange = (event: ChangeEvent<HTMLInputElement>) => {
    const dateValue = event.target.value;
    setDepartureDate(dateValue ? new Date(dateValue) : null);
  };

  const handleReturnDateChange = (event: ChangeEvent<HTMLInputElement>) => {
    const dateValue = event.target.value;
    setReturnDate(dateValue ? new Date(dateValue) : null);
  };

  // Форматирование даты для input type="date" (YYYY-MM-DD)
  const formatDateForInput = (date: Date | null): string => {
    if (!date) return '';
    // Проверка на валидность даты, т.к. new Date(null) может вернуть Invalid Date
    if (isNaN(date.getTime())) return '';
    // Добавляем +1 к месяцу, т.к. getMonth() возвращает 0-11
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  // Скрытие подсказок при потере фокуса (простой вариант)
  const handleOriginBlur = (e: FocusEvent<HTMLInputElement>) => {
    // Небольшая задержка, чтобы успел сработать onClick на подсказке
    setTimeout(() => {
      // Проверяем, куда ушел фокус. Если на подсказку - не скрываем.
      // Это сложнее, простой вариант - всегда скрывать через ~200ms
      if (!e.relatedTarget || !(e.relatedTarget as HTMLElement).closest('.suggestions-card')) {
        setShowOriginSuggestions(false);
      }
    }, 200);
  };
  const handleDestinationBlur = (e: FocusEvent<HTMLInputElement>) => {
    setTimeout(() => {
      if (!e.relatedTarget || !(e.relatedTarget as HTMLElement).closest('.suggestions-card')) {
        setShowDestinationSuggestions(false);
      }
    }, 200);
  };


  const handleDepartureDateSelect = (selectedDate: Date | undefined) => {
    setDepartureDate(selectedDate ?? null); // Преобразуем undefined в null для стора
    // Если дата вылета выбрана позже даты возврата (и это не one way),
    // сбрасываем дату возврата или устанавливаем ее равной дате вылета
    if (!isOneWay && returnDate && selectedDate && selectedDate > returnDate) {
      setReturnDate(selectedDate); // Устанавливаем дату возврата = дате вылета
      // или setReturnDate(null); // Сбрасываем дату возврата
    }
  };

  const handleReturnDateSelect = (selectedDate: Date | undefined) => {
    setReturnDate(selectedDate ?? null);
  };

  // Получаем сегодняшнюю дату для min Date в DatePicker
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Убираем время, чтобы сравнивать только даты


  return (
    <div className={cn('relative w-full', className)}> {/* Относительное позиционирование для подсказок */}
      <div className="flex flex-col md:flex-row gap-4 items-end"> {/* Используем flex-col на мобильных */}
        {/* Откуда */}
        <div className="grid w-full items-center gap-1.5 relative">
          <Label htmlFor="from">Откуда</Label>
          <Input
            type="text"
            id="from"
            placeholder="Город или аэропорт"
            value={originSearchTerm}
            onChange={handleOriginChange}
            onFocus={() => originSearchTerm && setShowOriginSuggestions(true)} // Показать при фокусе если есть текст
            onBlur={handleOriginBlur} // Скрыть при потере фокуса
            autoComplete="off" // Отключаем автозаполнение браузера
          />
          {/* Блок подсказок для "Откуда" */}
          {showOriginSuggestions && (originSuggestions.length > 0 || isOriginLoading || originError) && (
            <Card className="absolute top-full left-0 right-0 mt-1 z-10 suggestions-card">
              <CardContent className="p-2 max-h-60 overflow-y-auto">
                {isOriginLoading && (
                  <div className="flex items-center justify-center p-2 text-muted-foreground">
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" /> Загрузка...
                  </div>
                )}
                {originError && !isOriginLoading && (
                  <p className="p-2 text-sm text-red-600">{originError}</p>
                )}
                {!isOriginLoading && !originError && originSuggestions.length === 0 && originSearchTerm && (
                  <p className="p-2 text-sm text-muted-foreground">Ничего не найдено</p>
                )}
                {!isOriginLoading && !originError && originSuggestions.length > 0 && (
                  <ul className="space-y-1">
                    {originSuggestions.slice(0, 5).map((airport) => (
                      <li
                        key={airport.id}
                        className="p-2 text-sm hover:bg-accent rounded cursor-pointer"
                        // Используем onMouseDown вместо onClick для срабатывания до onBlur инпута
                        onMouseDown={() => handleSelectOrigin(airport)}
                      >
                        <strong>{airport.city}, {airport.country}</strong> ({airport.name} - {airport.iata_code})
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>
          )}
        </div>

        {/* Куда */}
        <div className="grid w-full items-center gap-1.5 relative">
          <Label htmlFor="to">Куда</Label>
          <Input
            type="text"
            id="to"
            placeholder="Город или аэропорт"
            value={destinationSearchTerm}
            onChange={handleDestinationChange}
            onFocus={() => destinationSearchTerm && setShowDestinationSuggestions(true)}
            onBlur={handleDestinationBlur}
            autoComplete="off"
          />
          {/* Блок подсказок для "Куда" */}
          {showDestinationSuggestions && (destinationSuggestions.length > 0 || isDestinationLoading || destinationError) && (
            <Card className="absolute top-full left-0 right-0 mt-1 z-10 suggestions-card">
              <CardContent className="p-2 max-h-60 overflow-y-auto">
                {isDestinationLoading && (
                  <div className="flex items-center justify-center p-2 text-muted-foreground">
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" /> Загрузка...
                  </div>
                )}
                {destinationError && !isDestinationLoading && (
                  <p className="p-2 text-sm text-red-600">{destinationError}</p>
                )}
                {!isDestinationLoading && !destinationError && destinationSuggestions.length === 0 && destinationSearchTerm && (
                  <p className="p-2 text-sm text-muted-foreground">Ничего не найдено</p>
                )}
                {!isDestinationLoading && !destinationError && destinationSuggestions.length > 0 && (
                  <ul className="space-y-1">
                    {destinationSuggestions.slice(0, 5).map((airport) => (
                      <li
                        key={airport.id}
                        className="p-2 text-sm hover:bg-accent rounded cursor-pointer"
                        onMouseDown={() => handleSelectDestination(airport)}
                      >
                        <strong>{airport.city}, {airport.country}</strong> ({airport.name} - {airport.iata_code})
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>
          )}
        </div>

        {/* Дата вылета */}
        <div className="grid w-full min-w-[160px] items-center gap-1.5">
          <Label htmlFor="departure">Туда</Label>
          <DatePicker
            // id="departure" // ID не обязателен для DatePicker, если Label не использует htmlFor
            date={departureDate ?? undefined} // Преобразуем null в undefined для пропса
            setDate={handleDepartureDateSelect}
            placeholder="Дата вылета"
            fromDate={today} // Минимальная дата - сегодня
          />
        </div>


        {/* Дата возврата (скрыта если isOneWay) */}
        {!isOneWay && (
          <div className="grid w-full min-w-[160px] items-center gap-1.5">
            <Label htmlFor="arrival">Обратно</Label>
            <DatePicker
              // id="arrival"
              date={returnDate ?? undefined}
              setDate={handleReturnDateSelect}
              placeholder="Дата возврата"
              disabled={isOneWay || !departureDate} // Блокируем, если one way ИЛИ не выбрана дата вылета
              // Минимальная дата - дата вылета или сегодня
              fromDate={departureDate ? new Date(departureDate) : today}
            />
          </div>
        )}

        {/* Чекбокс "В одну сторону" */}
        <div className="flex items-center space-x-2 pt-4 md:pt-0"> {/* Отступ сверху на мобильных */}
          <Checkbox
            id="oneWay"
            checked={isOneWay}
            onCheckedChange={(checked) => setIsOneWay(Boolean(checked))}
          />
          <Label htmlFor="oneWay" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            В одну сторону
          </Label>
        </div>


        {/* Кнопка поиска */}
        <Button className='' onClick={onSearchSubmit} disabled={!originAirport || !destinationAirport || !departureDate}>
          Найти
        </Button>
      </div>
    </div>
  );
};