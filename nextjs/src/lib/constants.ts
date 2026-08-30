// Базовые URL бэкенда (Laravel). На время миграции фронт работает со старым
// рабочим API, как и React-версия.
export const API_URL =
  process.env.NEXT_PUBLIC_API_URL || 'https://optech.kz:8000/api';
export const BACKEND_URL =
  process.env.NEXT_PUBLIC_STORAGE_URL || 'https://optech.kz:8000';
// Публичный адрес самого сайта (фронт) — для абсолютных URL в SEO-разметке.
export const SITE_URL =
  process.env.NEXT_PUBLIC_SITE_URL || 'https://optech.kz';
