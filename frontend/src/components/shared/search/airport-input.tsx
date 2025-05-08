import { FC, useState, ChangeEvent, FocusEvent } from 'react';
import { useDebouncedCallback } from 'use-debounce';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';
import { Airport } from '@/data/types/airport';

interface IAirportInputProps {
  id: string;
  label: string;
  placeholder: string;
  searchTerm: string;
  suggestions: Airport[];
  isLoading: boolean;
  error: string | null;
  onSearchTermChange: (term: string) => void;
  onFetchSuggestions: (term: string) => void;
  onSelectSuggestion: (airport: Airport) => void;
  onClearSuggestions: () => void;
  onClearSelectedAirport: () => void;
}

export const AirportInput: FC<IAirportInputProps> = (
  {
    id,
    label,
    placeholder,
    searchTerm,
    suggestions,
    isLoading,
    error,
    onSearchTermChange,
    onFetchSuggestions,
    onSelectSuggestion,
    onClearSuggestions,
    onClearSelectedAirport
  }
) => {
  const [showSuggestions, setShowSuggestions] = useState(false);

  const debouncedFetch = useDebouncedCallback((term: string) => {
    onFetchSuggestions(term);
  }, 300);

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const term = event.target.value;
    onSearchTermChange(term);
    if (term) {
      setShowSuggestions(true);
      debouncedFetch(term);
    } else {
      setShowSuggestions(false);
      onClearSuggestions();
      onClearSelectedAirport();
    }
  };

  const handleSelect = (airport: Airport) => {
    onSelectSuggestion(airport);
    setShowSuggestions(false);
  };

  const handleFocus = () => {
    if (searchTerm && suggestions.length > 0) {
      setShowSuggestions(true);
    }
  };

  const handleBlur = (e: FocusEvent<HTMLInputElement>) => {
    setTimeout(() => {
      if (!e.relatedTarget || !(e.relatedTarget as HTMLElement).closest(`.suggestions-card-${id}`)) {
        setShowSuggestions(false);
      }
    }, 200);
  };

  return (
    <div className="grid w-full items-center gap-1.5 relative">
      <Label htmlFor={id}>{label}</Label>
      <Input
        type="text"
        id={id}
        placeholder={placeholder}
        value={searchTerm}
        onChange={handleChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        autoComplete="off"
      />
      {
        showSuggestions
        && (suggestions.length > 0 || isLoading || error)
        && (
          <Card className={cn('absolute top-full left-0 right-0 mt-1 z-10', `suggestions-card-${id}`)}>
            <CardContent className="p-2 max-h-60 overflow-y-auto">
              {
                isLoading
                && (
                  <div className="flex items-center justify-center p-2 text-muted-foreground">
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" /> Загрузка...
                  </div>
                )
              }
              {
                error
                && !isLoading
                && (
                  <p className="p-2 text-sm text-red-600">{error}</p>
                )
              }
              {
                !isLoading
                && !error
                && suggestions.length === 0
                && searchTerm
                && <p className="p-2 text-sm text-muted-foreground">Ничего не найдено</p>
              }
              {
                !isLoading
                && !error
                && suggestions.length > 0
                && <ul className="space-y-1">
                  {suggestions.slice(0, 5).map((airport) => (
                    <li
                      key={airport.id}
                      className="p-2 text-sm hover:bg-accent rounded cursor-pointer"
                      onMouseDown={() => handleSelect(airport)}
                    >
                      <strong>{airport.city}, {airport.country}</strong> ({airport.name} - {airport.iata_code})
                    </li>
                  ))}
                </ul>
              }
            </CardContent>
          </Card>
        )
      }
    </div>
  );
};