import { CreditCardIcon, HelpCircleIcon, SettingsIcon, TelescopeIcon, TicketsPlaneIcon, UsersIcon } from 'lucide-react';

export const sidebarData = {
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