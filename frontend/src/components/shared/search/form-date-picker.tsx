import { FC } from 'react';
import { Label } from '@/components/ui/label';
import { DatePicker } from '@/components/ui/date-picker';
import { cn } from '@/lib/utils';

interface IFormDatePickerProps {
  label: string;
  fieldId?: string;
  containerClassName?: string;
  date?: Date;
  setDate: (date: Date | undefined) => void;
  placeholder?: string;
  disabled?: boolean;
  fromDate?: Date;
  toDate?: Date;
}

export const FormDatePicker: FC<IFormDatePickerProps> = (
  {
    label,
    fieldId,
    containerClassName,
    date,
    setDate,
    placeholder,
    disabled,
    fromDate
  }
) => {
  return (
    <div className={cn('grid w-full min-w-[160px] items-center gap-1.5', containerClassName)}>
      {label && <Label htmlFor={fieldId}>{label}</Label>}
      <DatePicker
        date={date}
        setDate={setDate}
        placeholder={placeholder}
        disabled={disabled}
        fromDate={fromDate}
      />
    </div>
  );
};