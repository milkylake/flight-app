FROM node:18-alpine AS builder
WORKDIR /app

# Копируем package.json и package-lock.json (или yarn.lock)
COPY package*.json ./

# Устанавливаем зависимости
RUN npm install

# Копируем остальной код приложения
COPY . .

# Собираем приложение Next.js
RUN npm run build

# Stage 2: Production Stage
FROM node:18-alpine
WORKDIR /app

# Устанавливаем минимальные зависимости для запуска
COPY --from=builder /app/package*.json ./
RUN npm install --only=production

# Копируем собранное приложение из стадии builder
COPY --from=builder /app/.next ./.next
COPY --from=builder /app/public ./public

# Открываем порт, который слушает Next.js (по умолчанию 3000)
EXPOSE 3000

# Команда для запуска приложения
CMD ["npm", "start"]