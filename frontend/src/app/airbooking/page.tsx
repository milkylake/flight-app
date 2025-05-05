'use client';

import { AppSidebar } from '@/components/app-sidebar';
import { SiteHeader } from '@/components/site-header';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Search } from '@/components/shared/search/search';
import dynamic from 'next/dynamic';

const demoCoordinates = [
  { lat: 55.7558, lng: 37.6173 }, // Москва
  { lat: 59.9343, lng: 30.3351 }, // Санкт-Петербург
  { lat: 56.8380, lng: 60.6031 } // Екатеринбург
];

const YandexMap = dynamic(
  () => import('../../components/shared/map/map').then(c => c.YandexMap),
  {
    ssr: false,
    loading: () => <p>Загрузка карты...</p>
  }
);

export default function Page() {
  const yandexMapsApiKey = process.env.NEXT_PUBLIC_YANDEX_MAPS_API_KEY;

  console.log(yandexMapsApiKey);

  if (!yandexMapsApiKey || yandexMapsApiKey === 'YOUR_YANDEX_MAPS_API_KEY') {
    return (
      <div>
        Ошибка: Не указан Yandex Maps API Key. Добавьте NEXT_PUBLIC_YANDEX_MAPS_API_KEY в ваш .env.local файл.
      </div>
    );
  }

  return (
    <SidebarProvider>
      <AppSidebar variant="inset" />
      <SidebarInset>
        <SiteHeader />
        <div className="w-full p-6 flex flex-col gap-4">
          <Search />
          <YandexMap coordinates={demoCoordinates} apiKey={yandexMapsApiKey} />
        </div>
        {/*<div className="flex flex-1 flex-col">*/}
        {/*  <div className="@container/main flex flex-1 flex-col gap-2">*/}
        {/*    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">*/}
        {/*      <SectionCards />*/}
        {/*      <div className="px-4 lg:px-6">*/}
        {/*        <ChartAreaInteractive />*/}
        {/*      </div>*/}
        {/*      <DataTable data={data} />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
      </SidebarInset>
    </SidebarProvider>
  );
}
