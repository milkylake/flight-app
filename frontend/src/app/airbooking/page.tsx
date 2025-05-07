'use client';

import { AppSidebar } from '@/components/app-sidebar';
import { SiteHeader } from '@/components/site-header';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { Search } from '@/components/shared/search/search';
import dynamic from 'next/dynamic';

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
        <div className="w-full p-8 flex flex-col gap-4">
          <Search />
          <YandexMap />
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}
