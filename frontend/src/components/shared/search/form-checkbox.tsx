import { FC, ReactNode } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface IFormCheckboxProps {
  isChecked: boolean;
  onCheckedChange: (checked: boolean) => void;
  label: ReactNode;
  fieldId: string;
  containerClassName?: string;
  labelClassName?: string;
}

export const FormCheckbox: FC<IFormCheckboxProps> = (
  {
    isChecked,
    onCheckedChange,
    label,
    fieldId,
    containerClassName,
    labelClassName
  }
) => {
  return (
    <div className={cn('flex items-center space-x-2', containerClassName)}>
      <Checkbox
        id={fieldId}
        checked={isChecked}
        onCheckedChange={(checkedState) => onCheckedChange(Boolean(checkedState))}
      />
      <Label
        htmlFor={fieldId}
        className={cn(
          'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer',
          labelClassName
        )}
      >
        {label}
      </Label>
    </div>
  );
};