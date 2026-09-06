import { MetadataRoute } from 'next';
import { getSettings } from '@/lib/api';
import { SITE_URL } from '@/lib/constants';

// robots.txt теперь зависит от настроек в админке (robots_extra), поэтому
// перестал быть чисто статическим. Раз в час — более чем достаточно.
export const revalidate = 3600;

export default async function robots(): Promise<MetadataRoute.Robots> {
  const settings = await getSettings().catch(() => null);

  // robots_extra — поле в настройках для ручных правил (закрыть раздел,
  // добавить Crawl-delay и т.п.). Оно есть в админке с самого начала, но на
  // фронте не читалось: всё, что туда вписывали, никуда не попадало.
  const extra = typeof settings?.robots_extra === 'string' ? settings.robots_extra.trim() : '';

  const disallow = [
    '/api/',
    '/admin/',
    // Результаты поиска: бесконечное множество URL с контентом, дублирующим
    // каталог. В метаданных они уже noindex, здесь дополнительно экономим
    // краулинговый бюджет.
    '/search/',
    '/*/search/',
    // Сортировка каталога создаёт дубли одной и той же категории.
    '/*?sort=',
    '/*&sort=',
  ];

  // Дополнительные Disallow из админки подмешиваем к основным правилам:
  // MetadataRoute.Robots не даёт вставить произвольный текст, поэтому
  // разбираем строки вида «Disallow: /path».
  for (const line of extra.split('\n')) {
    const match = line.trim().match(/^disallow:\s*(\S+)$/i);
    if (match && !disallow.includes(match[1])) disallow.push(match[1]);
  }

  return {
    rules: {
      userAgent: '*',
      allow: '/',
      disallow,
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  };
}
