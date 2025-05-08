import { Airport } from '@/data/types/airport';
import { Flight } from '@/data/types/flight';

export interface FlightSearchState {
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

export interface FlightSearchActions {
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