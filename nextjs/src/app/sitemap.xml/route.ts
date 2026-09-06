import {
  SITEMAP_SOURCES,
  SITEMAP_TYPES,
  latestLastmod,
  partsCount,
  renderSitemapIndex,
  sitemapUrl,
  xmlResponse,
} from '@/lib/sitemap';

// Раз в час: карта сайта не тот файл, ради которого стоит ходить в Laravel
// на каждый запрос бота.
export const revalidate = 3600;

/**
 * Индекс карты сайта. Раньше /sitemap.xml был одним файлом на весь сайт —
 * статика, категории, товары, решения, бренды, статьи и проекты вперемешку.
 * Разделение по типам даёт две вещи: в Search Console видно покрытие и
 * ошибки отдельно по каждому разделу, и бот перекачивает только тот файл,
 * у которого изменился lastmod.
 */
export async function GET() {
  const entries: Array<{ loc: string; lastmod?: string | null }> = [];

  const results = await Promise.all(
    SITEMAP_TYPES.map(async (type) => ({ type, items: await SITEMAP_SOURCES[type]() }))
  );

  for (const { type, items } of results) {
    // Пустые разделы (проекты и акции сейчас без записей, база знаний ждёт
    // бэкенда) в индекс не попадают: пустой urlset — ошибка в Search Console.
    if (!items.length) continue;

    const lastmod = latestLastmod(items);
    for (let part = 1; part <= partsCount(items.length); part++) {
      entries.push({ loc: sitemapUrl(type, part), lastmod });
    }
  }

  return xmlResponse(renderSitemapIndex(entries));
}
