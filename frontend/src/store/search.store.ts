import { create } from 'zustand';
import { Airport } from '@/data/types/airport';
import { Flight } from '@/data/types/flight';

interface FlightSearchState {
  originAirport: Airport | null;
  destinationAirport: Airport | null;
  departureDate: Date | null;
  returnDate: Date | null;
  isOneWay: boolean;

  originSearchTerm: string;
  originSuggestions: Airport[];
  isOriginLoading: boolean;
  originError: string | null;

  destinationSearchTerm: string;
  destinationSuggestions: Airport[];
  isDestinationLoading: boolean;
  destinationError: string | null;

  foundFlights: Flight[];
  isFlightsLoading: boolean;
  flightsError: string | null;
}

interface FlightSearchActions {
  setOriginAirport: (airport: Airport | null) => void;
  setDestinationAirport: (airport: Airport | null) => void;
  setDepartureDate: (date: Date | null) => void;
  setReturnDate: (date: Date | null) => void;
  setIsOneWay: (oneWay: boolean) => void;

  setOriginSearchTerm: (term: string) => void;
  fetchOriginSuggestions: (term: string) => Promise<void>;
  selectOriginSuggestion: (airport: Airport) => void;
  clearOriginSuggestions: () => void;

  setDestinationSearchTerm: (term: string) => void;
  fetchDestinationSuggestions: (term: string) => Promise<void>;
  selectDestinationSuggestion: (airport: Airport) => void;
  clearDestinationSuggestions: () => void;

  resetSearch: () => void;

  searchFlights: () => Promise<void>;
  clearFoundFlights: () => void;
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

  foundFlights: [],
  isFlightsLoading: false,
  flightsError: null
};

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || '';

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
    returnDate: oneWay ? null : state.returnDate
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
        } catch (e) { /* игнорируем ошибку парсинга json ошибки */
        }
        throw new Error(errorMsg);
      }
      const data: Airport[] = await response.json();
      set({ originSuggestions: data, isOriginLoading: false });
    } catch (error: any) {
      console.error('Failed to fetch origin suggestions:', error);
      set({
        originError: error.message || 'Не удалось загрузить аэропорты',
        isOriginLoading: false,
        originSuggestions: []
      });
    }
  },
  selectOriginSuggestion: (airport) => set({
    originAirport: airport,       // Устанавливаем выбранный аэропорт
    originSearchTerm: `${airport.city}, ${airport.name} (${airport.iata_code})`, // Показываем выбранное в инпуте
    originSuggestions: [],      // Очищаем подсказки
    originError: null,          // Сбрасываем ошибки
    isOriginLoading: false     // Останавливаем загрузку
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
        } catch (e) { /* игнорируем */
        }
        throw new Error(errorMsg);
      }
      const data: Airport[] = await response.json();
      set({ destinationSuggestions: data, isDestinationLoading: false });
    } catch (error: any) {
      console.error('Failed to fetch destination suggestions:', error);
      set({
        destinationError: error.message || 'Не удалось загрузить аэропорты',
        isDestinationLoading: false,
        destinationSuggestions: []
      });
    }
  },
  selectDestinationSuggestion: (airport) => set({
    destinationAirport: airport,
    destinationSearchTerm: `${airport.city}, ${airport.name} (${airport.iata_code})`,
    destinationSuggestions: [],
    destinationError: null,
    isDestinationLoading: false
  }),
  clearDestinationSuggestions: () => set({
    destinationSuggestions: [],
    isDestinationLoading: false,
    destinationError: null
  }),

  resetSearch: () => set(initialState),

  searchFlights: async () => {
    const { originAirport, destinationAirport, departureDate } = get();

    if (!originAirport || !destinationAirport || !departureDate) {
      set({
        flightsError: 'Выберите аэропорт отправления, назначения и дату вылета.',
        foundFlights: [],
        isFlightsLoading: false
      });
      return;
    }

    set({ isFlightsLoading: true, flightsError: null, foundFlights: [] });

    const formatDate = (date: Date): string => {
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    try {
      const params = new URLSearchParams({
        origin: originAirport.iata_code,
        destination: destinationAirport.iata_code,
        date: formatDate(departureDate)
      });

      const response = await fetch(`${API_BASE_URL}/api/flights?${params.toString()}`);
      console.log(response);
      if (!response.ok) {
        let errorMsg = `Ошибка при поиске рейсов: ${response.status}`;
        try {
          const errorData = await response.json();
          errorMsg = errorData.error || errorMsg;
        } catch (e) {
        }
        throw new Error(errorMsg);
      }
      const flightsData: Flight[] = await response.json();
      set({ foundFlights: flightsData, isFlightsLoading: false, flightsError: null });
    } catch (error: any) {
      console.error('Failed to search flights:', error);
      set({ flightsError: error.message || 'Не удалось найти рейсы.', isFlightsLoading: false, foundFlights: [] });
    }
  },
  clearFoundFlights: () => set({
    foundFlights: [],
    flightsError: null,
    isFlightsLoading: false
  })
}));