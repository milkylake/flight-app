import { create } from 'zustand';
import { Airport } from '@/data/types/airport';

// Определим тип Airport (создайте файл types/airport.ts, если его нет)
// src/types/airport.ts
/*
export interface Airport {
  id: number;
  iata_code: string;
  name: string; // Название аэропорта
  city: string;
  country: string;
  latitude: string; // API возвращает строки, но лучше преобразовать в number при использовании
  longitude: string;
}
*/


interface FlightSearchState {
  originAirport: Airport | null;
  destinationAirport: Airport | null;
  departureDate: Date | null;
  returnDate: Date | null; // Дата возврата
  isOneWay: boolean;      // Флаг "в одну сторону"

  // Состояние для поиска Origin
  originSearchTerm: string;
  originSuggestions: Airport[];
  isOriginLoading: boolean;
  originError: string | null;

  // Состояние для поиска Destination
  destinationSearchTerm: string;
  destinationSuggestions: Airport[];
  isDestinationLoading: boolean;
  destinationError: string | null;
}

interface FlightSearchActions {
  setOriginAirport: (airport: Airport | null) => void;
  setDestinationAirport: (airport: Airport | null) => void;
  setDepartureDate: (date: Date | null) => void;
  setReturnDate: (date: Date | null) => void;
  setIsOneWay: (oneWay: boolean) => void;

  // Поиск Origin
  setOriginSearchTerm: (term: string) => void;
  fetchOriginSuggestions: (term: string) => Promise<void>;
  selectOriginSuggestion: (airport: Airport) => void;
  clearOriginSuggestions: () => void;

  // Поиск Destination
  setDestinationSearchTerm: (term: string) => void;
  fetchDestinationSuggestions: (term: string) => Promise<void>;
  selectDestinationSuggestion: (airport: Airport) => void;
  clearDestinationSuggestions: () => void;

  // Общий сброс поиска (может быть полезно)
  resetSearch: () => void;
}

const initialState: FlightSearchState = {
  originAirport: null,
  destinationAirport: null,
  departureDate: null,
  returnDate: null,
  isOneWay: false, // По умолчанию - туда-обратно

  originSearchTerm: '',
  originSuggestions: [],
  isOriginLoading: false,
  originError: null,

  destinationSearchTerm: '',
  destinationSuggestions: [],
  isDestinationLoading: false,
  destinationError: null,
};

// Базовый URL для API (лучше вынести в переменные окружения)
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost'; // Убедитесь, что NEXT_PUBLIC_API_URL настроен или замените на '/api' если используете прокси Nginx

export const useFlightSearchStore = create<FlightSearchState & FlightSearchActions>((set, get) => ({
  ...initialState,

  // --- Сеттеры для выбранных значений ---
  setOriginAirport: (airport) => set({ originAirport: airport }),
  setDestinationAirport: (airport) => set({ destinationAirport: airport }),
  setDepartureDate: (date) => set({ departureDate: date }),
  setReturnDate: (date) => set({ returnDate: date }),
  setIsOneWay: (oneWay) => set((state) => ({
    isOneWay: oneWay,
    // Если переключили на "в одну сторону", сбрасываем дату возврата
    returnDate: oneWay ? null : state.returnDate,
  })),

  // --- Логика поиска Origin ---
  setOriginSearchTerm: (term) => set({ originSearchTerm: term, originError: null }), // Сбрасываем ошибку при новом вводе
  fetchOriginSuggestions: async (term) => {
    if (!term.trim()) {
      set({ originSuggestions: [], isOriginLoading: false });
      return;
    }
    set({ isOriginLoading: true, originError: null, originSuggestions: [] }); // Начинаем загрузку, сбрасываем ошибки и старые результаты
    try {
      const response = await fetch(`${API_BASE_URL}/api/airports?search=${encodeURIComponent(term.trim())}`);
      if (!response.ok) {
        let errorMsg = `Ошибка сети: ${response.status}`;
        try { // Попытка прочитать тело ошибки, если есть
          const errorData = await response.json();
          errorMsg = errorData.error || errorMsg;
        } catch (e) { /* игнорируем ошибку парсинга json ошибки */ }
        throw new Error(errorMsg);
      }
      const data: Airport[] = await response.json();
      set({ originSuggestions: data, isOriginLoading: false });
    } catch (error: any) {
      console.error("Failed to fetch origin suggestions:", error);
      set({ originError: error.message || 'Не удалось загрузить аэропорты', isOriginLoading: false, originSuggestions: [] });
    }
  },
  selectOriginSuggestion: (airport) => set({
    originAirport: airport,       // Устанавливаем выбранный аэропорт
    originSearchTerm: `${airport.city}, ${airport.name} (${airport.iata_code})`, // Показываем выбранное в инпуте
    originSuggestions: [],      // Очищаем подсказки
    originError: null,          // Сбрасываем ошибки
    isOriginLoading: false,     // Останавливаем загрузку
  }),
  clearOriginSuggestions: () => set({ originSuggestions: [], isOriginLoading: false, originError: null }),

  // --- Логика поиска Destination ---
  setDestinationSearchTerm: (term) => set({ destinationSearchTerm: term, destinationError: null }),
  fetchDestinationSuggestions: async (term) => {
    if (!term.trim()) {
      set({ destinationSuggestions: [], isDestinationLoading: false });
      return;
    }
    set({ isDestinationLoading: true, destinationError: null, destinationSuggestions: [] });
    try {
      const response = await fetch(`${API_BASE_URL}/api/airports?search=${encodeURIComponent(term.trim())}`);
      if (!response.ok) {
        let errorMsg = `Ошибка сети: ${response.status}`;
        try {
          const errorData = await response.json();
          errorMsg = errorData.error || errorMsg;
        } catch (e) { /* игнорируем */ }
        throw new Error(errorMsg);
      }
      const data: Airport[] = await response.json();
      set({ destinationSuggestions: data, isDestinationLoading: false });
    } catch (error: any) {
      console.error("Failed to fetch destination suggestions:", error);
      set({ destinationError: error.message || 'Не удалось загрузить аэропорты', isDestinationLoading: false, destinationSuggestions: [] });
    }
  },
  selectDestinationSuggestion: (airport) => set({
    destinationAirport: airport,
    destinationSearchTerm: `${airport.city}, ${airport.name} (${airport.iata_code})`,
    destinationSuggestions: [],
    destinationError: null,
    isDestinationLoading: false,
  }),
  clearDestinationSuggestions: () => set({ destinationSuggestions: [], isDestinationLoading: false, destinationError: null }),

  // --- Сброс ---
  resetSearch: () => set(initialState), // Возвращает к начальному состоянию
}));