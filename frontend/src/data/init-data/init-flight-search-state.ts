import { FlightSearchState } from '@/data/abstractions/IFlightSearchStore';

export const initFlightSearchState: FlightSearchState = {
  originAirport: null,
  destinationAirport: null,
  departureDate: null,
  returnDate: null,
  isOneWay: false,

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