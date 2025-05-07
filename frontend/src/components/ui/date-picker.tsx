// src/components/DatePicker.tsx (или где он у вас)
"use client"; // Компонент клиентский

import * as React from 'react';
import { format } from 'date-fns';
import { ru } from 'date-fns/locale'; // Импортируем русскую локаль
import { Calendar as CalendarIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';

interface IDatePickerProps {
  className?: string;
  date: Date | undefined;
  setDate: (date: Date | undefined) => void;
  placeholder?: string;
  disabled?: boolean;
  fromDate?: Date;
}

export const DatePicker: React.FC<IDatePickerProps> = ({
  className,
  date,
  setDate,
  placeholder = "Выберите дату",
  disabled = false,
  fromDate,
}) => {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant={'outline'}
          disabled={disabled}
          className={cn(
            'w-full justify-start text-left font-normal', // Уберем фикс. ширину w-[280px] для гибкости
            !date && 'text-muted-foreground',
            className
          )}
        >
          <CalendarIcon className="mr-2 h-4 w-4" />
          {/* Используем русскую локаль для format */}
          {date ? format(date, 'PPP', { locale: ru }) : <span>{placeholder}</span>}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0">
        <Calendar
          locale={ru} // Указываем русскую локаль для календаря
          mode="single"
          selected={date}
          onSelect={setDate}
          initialFocus
          disabled={disabled} // Передаем disabled в календарь
          fromDate={fromDate} // Устанавливаем минимальную дату
        />
      </PopoverContent>
    </Popover>
  );
};