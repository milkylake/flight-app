'use client';

import * as React from 'react';
import {
  CreditCardIcon,
  HelpCircleIcon, Plane,
  SettingsIcon, TelescopeIcon, TicketsPlaneIcon,
  UsersIcon
} from 'lucide-react';

import { NavMain } from '@/components/nav-main';
import { NavSecondary } from '@/components/nav-secondary';
import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar';

const data = {
  user: {
    name: 'Нурислам Айналиев',
    email: 'ajnaliev.nz@edu.spbstu.ru',
    avatar: 'https://github.com/shadcn.png'
  },
  navMain: [
    {
      title: 'Мои бронирования',
      url: '#',
      icon: TicketsPlaneIcon
    },
    {
      title: 'Способы оплаты',
      url: '#',
      icon: CreditCardIcon
    },
    {
      title: 'Исследовать',
      url: '#',
      icon: TelescopeIcon
    }
  ],
  navSecondary: [
    {
      title: 'Настройки',
      url: '#',
      icon: SettingsIcon
    },
    {
      title: 'Помощь',
      url: '#',
      icon: HelpCircleIcon
    },
    {
      title: 'Поддержка',
      url: '#',
      icon: UsersIcon
    }
  ]
};

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  return (
    <Sidebar collapsible="offcanvas" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              asChild
              className="data-[slot=sidebar-menu-button]:!p-1.5"
            >
              <a href="#">
                <Plane className="h-5 w-5" />
                <span className="text-base font-semibold">AirBooking</span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={data.navMain} />
        <NavSecondary items={data.navSecondary} className="mt-auto" />
      </SidebarContent>
      <SidebarFooter>
        <NavUser user={data.user} />
      </SidebarFooter>
    </Sidebar>
  );
}
