import { create } from 'zustand';
import { Airport } from '@/data/types/airport';
import { Flight } from '@/data/types/flight';
import { FlightSearchActions, FlightSearchState } from '@/data/abstractions/IFlightSearchStore';
import { initFlightSearchState } from '@/data/init-data/init-flight-search-state';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || '';

export const useFlightSearchStore = create<FlightSearchState & FlightSearchActions>((set, get) => ({
  ...initFlightSearchState,


  setOriginAirport: (airport) => set({ originAirport: airport }),
  setDestinationAirport: (airport) => set({ destinationAirport: airport }),
  setDepartureDate: (date) => set({ departureDate: date }),
  setReturnDate: (date) => set({ returnDate: date }),
  setIsOneWay: (oneWay) => set((state) => ({
    isOneWay: oneWay,
    returnDate: oneWay ? null : state.returnDate
  })),


  setOriginSearchTerm: (term) => set({ originSearchTerm: term, originError: null }),
  fetchOriginSuggestions: async (term) => {
    if (!term.trim()) {
      set({ originSuggestions: [], isOriginLoading: false });
      return;
    }

    set({ isOriginLoading: true, originError: null, originSuggestions: [] });

    try {
      const response = await fetch(`${API_BASE_URL}/api/airports?search=${encodeURIComponent(term.trim())}`);

      if (!response.ok) {
        let errorMsg = `Ошибка сети: ${response.status}`;
        const errorData = await response.json();
        errorMsg = errorData.error || errorMsg;
        throw new Error(errorMsg);
      }

      const data: Airport[] = await response.json();
      set({ originSuggestions: data, isOriginLoading: false });
    } catch (error: any) {
      set({
        originError: error.message || 'Не удалось загрузить аэропорты',
        isOriginLoading: false,
        originSuggestions: []
      });
    }
  },
  selectOriginSuggestion: (airport) => set({
    originAirport: airport,
    originSearchTerm: `${airport.city}, ${airport.name} (${airport.iata_code})`,
    originSuggestions: [],
    originError: null,
    isOriginLoading: false
  }),
  clearOriginSuggestions: () => set({
    originSuggestions: [],
    isOriginLoading: false,
    originError: null
  }),


  setDestinationSearchTerm: (term) => set({ destinationSearchTerm: term, destinationError: null }),
  fetchDestinationSuggestions: async (term) => {
    if (!term.trim()) {
      set({ destinationSuggestions: [], isDestinationLoading: false });
      return;
    }

    set({
      isDestinationLoading: true,
      destinationError: null,
      destinationSuggestions: []
    });

    try {
      const response = await fetch(`${API_BASE_URL}/api/airports?search=${encodeURIComponent(term.trim())}`);

      if (!response.ok) {
        let errorMsg = `Ошибка сети: ${response.status}`;
        const errorData = await response.json();
        errorMsg = errorData.error || errorMsg;
        throw new Error(errorMsg);
      }

      const data: Airport[] = await response.json();
      set({ destinationSuggestions: data, isDestinationLoading: false });
    } catch (error: any) {
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


  resetSearch: () => set(initFlightSearchState),


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

    set({
      isFlightsLoading: true,
      flightsError: null,
      foundFlights: []
    });

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

      if (!response.ok) {
        let errorMsg = `Ошибка при поиске рейсов: ${response.status}`;
        const errorData = await response.json();
        errorMsg = errorData.error || errorMsg;
        throw new Error(errorMsg);
      }

      const flightsData: Flight[] = await response.json();

      set({
        foundFlights: flightsData,
        isFlightsLoading: false,
        flightsError: null
      });
    } catch (error: any) {
      set({
        flightsError: error.message || 'Не удалось найти рейсы.',
        isFlightsLoading: false,
        foundFlights: []
      });
    }
  },

  clearFoundFlights: () => set({
    foundFlights: [],
    flightsError: null,
    isFlightsLoading: false
  })
}));