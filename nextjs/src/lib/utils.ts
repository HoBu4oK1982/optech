const STORAGE_URL = process.env.NEXT_PUBLIC_STORAGE_URL || 'https://api.optech.kz';

// Весь сайт раздаёт картинки по своей схеме {BACKEND_URL}/assets/images/{folder}/{file}
// (см. CatalogItems/SmartSearch и т.д.), а НЕ через стандартный лара-симлинк
// /storage/{folder}/{file}. Бэкенд (в частности поисковый fallback) иногда
// отдаёт полный URL уже в лара-формате (https://.../storage/products/x.png) —
// раньше storageUrl() просто возвращал такой URL как есть (раз он уже
// начинается с http), а /storage/... на этом домене не резолвится в
// картинку. Приводим оба варианта к единому /assets/images/... — то же
// самое уже сделано в SmartSearch.tsx (toImg()), просто здесь этого не было.
function normalizeToAssetsPath(path: string): string {
  const m = path.match(/\/storage\/(.+)$/);
  return m ? `/assets/images/${m[1]}` : path;
}

export function storageUrl(path: string | null | undefined): string {
  if (!path) return '/images/placeholder.png';
  if (path.startsWith('http')) {
    const normalized = normalizeToAssetsPath(path);
    return normalized.startsWith('http') ? normalized : `${STORAGE_URL}${normalized}`;
  }
  const normalized = normalizeToAssetsPath(path);
  // Remove leading slash if present to avoid double slashes
  const cleanPath = normalized.startsWith('/') ? normalized : `/${normalized}`;
  return `${STORAGE_URL}${cleanPath}`;
}

export function truncateHtml(html: string, maxLength: number = 150): string {
  const text = html.replace(/<[^>]*>/g, '');
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

// Rich-текст (описания статей/товаров/проектов и т.д.) редактируется в
// админке через Summernote и хранится как сырой HTML. Картинки, вставленные
// через загрузчик Summernote, попадают в разметку относительным путём вида
// src="/upload/articles/xxx.png" — раньше это резолвилось нормально, т.к.
// фронт и бэкенд были на одном домене. После переезда на api.optech.kz
// такой путь резолвится относительно optech.kz, где файла физически нет —
// картинки в тексте статей/карточек ломаются. Абсолютизируем ТОЛЬКО src
// (картинки/медиа), а не href — в контенте встречаются намеренно
// относительные ссылки на страницы самого фронта (например, в
// settings.copyright есть <a href="/contacts">), их трогать нельзя.
export function absolutizeRichContent(html: string | null | undefined): string {
  if (!html) return '';
  return html.replace(
    /(\ssrc=["'])\/(?!\/)([^"']*)(["'])/gi,
    (_match, prefix, path, suffix) => `${prefix}${STORAGE_URL}/${path}${suffix}`
  );
}
