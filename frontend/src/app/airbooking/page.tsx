'use client';

import { AppSidebar } from '@/components/app-sidebar';
import { SiteHeader } from '@/components/site-header';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Search } from '@/components/shared/search/search';
import dynamic from 'next/dynamic';
import { FlightResults } from '@/components/shared/flight-results/flight-results';

const YandexMap = dynamic(
  () => import('../../components/shared/map/map').then(c => c.YandexMap),
  {
    ssr: false,
    loading: () => <p>Загрузка карты...</p>
  }
);

export default function Page() {
  return (
    <SidebarProvider>
      <AppSidebar variant="inset" />
      <SidebarInset className="flex flex-col items-center">
        <SiteHeader className="w-full" />
        <div className="w-full sm:p-8 p-4 flex flex-col gap-4">
          <Search />
          <YandexMap />
          <FlightResults/>
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}
