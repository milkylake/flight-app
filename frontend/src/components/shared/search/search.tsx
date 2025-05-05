import { FC } from 'react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface ISearchProps {
  className?: string;
}

export const Search: FC<ISearchProps> = ({ className }) => {
  return (
    <div className={cn('flex flex-row gap-4 w-full', className)}>
      <div className="grid w-full max-w-sm items-center gap-1.5">
        <Input type="text" id="from" placeholder="Откуда" />
      </div>
      <div className="grid w-full max-w-sm items-center gap-1.5">
        <Input type="text" id="to" placeholder="Куда" />
      </div>
      <div className="grid w-full max-w-sm items-center gap-1.5">
        <Input type="date" id="departure"/>
      </div>
      <div className="grid w-full max-w-sm items-center gap-1.5">
        <Input type="date" id="arrival"/>
      </div>
      <Button className=''>
        Поиск
      </Button>
    </div>
  );
};
