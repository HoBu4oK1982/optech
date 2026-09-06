import { SITE_URL } from '@/lib/constants';
import { htmlLang } from '@/lib/seo';

/**
 * Общесайтовая разметка @graph: Organization (+LocalBusiness, если заполнен
 * адрес) и WebSite с SearchAction.
 *
 * Все NAP-данные (название, адрес, телефоны, часы работы, карта, соцсети)
 * уже лежали в таблице settings и заполняются в админке, но на фронте не
 * читались вообще — ни одной микроразметки об организации на сайте не было.
 * Для локальной выдачи по Алматы это ключевой сигнал.
 */

// Логотип берём из статики фронта, а не из settings.logo: путь, записанный
// в настройках, на бэкенде сейчас отдаёт 404, а битый URL в logo — прямая
// причина отклонения разметки в Search Console.
const LOGO_URL = `${SITE_URL}/assets/images/headerLogo.png`;

type Settings = Record<string, any> | null | undefined;

const DAY_MAP: Record<string, string> = {
  пн: 'Monday', вт: 'Tuesday', ср: 'Wednesday', чт: 'Thursday',
  пт: 'Friday', сб: 'Saturday', вс: 'Sunday',
  mo: 'Monday', tu: 'Tuesday', we: 'Wednesday', th: 'Thursday',
  fr: 'Friday', sa: 'Saturday', su: 'Sunday',
};
const DAY_ORDER = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/** «+7 (771) 759-59-49, +7 (771) 746-06-02» -> два отдельных номера. */
function splitPhones(raw: unknown): string[] {
  if (typeof raw !== 'string') return [];
  return raw.split(/[,;\n]/).map((p) => p.trim()).filter(Boolean);
}

/**
 * «г. Алматы, ул. Кенесары хана, дом 83/1, 1 этаж, офис 1Б»
 * -> { addressLocality: 'Алматы', streetAddress: 'ул. Кенесары хана, ...' }
 * Город ищем по префиксу «г.»/«город» в любом сегменте, иначе берём первый —
 * без привязки к конкретному городу, чтобы разметка не сломалась при переезде.
 */
function parseAddress(raw: unknown) {
  if (typeof raw !== 'string' || !raw.trim()) return null;
  const parts = raw.split(',').map((p) => p.trim()).filter(Boolean);
  if (!parts.length) return null;

  const cityRe = /^(г\.|г\s|город)\s*/i;
  let idx = parts.findIndex((p) => cityRe.test(p));
  if (idx < 0) idx = 0;

  const locality = parts[idx].replace(cityRe, '').trim();
  const street = parts.filter((_, i) => i !== idx).join(', ');

  return {
    '@type': 'PostalAddress',
    streetAddress: street || undefined,
    addressLocality: locality || undefined,
    addressCountry: 'KZ',
  };
}

/**
 * «Пн - Пт: 09:00 - 18:00» -> OpeningHoursSpecification.
 * Если строку разобрать не удалось (например «Круглосуточно»), возвращаем
 * null: лучше не отдать часы вообще, чем отдать выдуманные.
 */
function parseOpeningHours(raw: unknown) {
  if (typeof raw !== 'string') return undefined;
  const src = raw.toLowerCase();

  const times = src.match(/\d{1,2}:\d{2}/g);
  if (!times || times.length < 2) return undefined;

  const tokens = src.match(/пн|вт|ср|чт|пт|сб|вс|mo|tu|we|th|fr|sa|su/g);
  if (!tokens || !tokens.length) return undefined;

  const first = DAY_MAP[tokens[0]];
  const last = DAY_MAP[tokens[tokens.length - 1]];
  const from = DAY_ORDER.indexOf(first);
  const to = DAY_ORDER.indexOf(last);
  if (from < 0 || to < 0 || to < from) return undefined;

  return [
    {
      '@type': 'OpeningHoursSpecification',
      dayOfWeek: DAY_ORDER.slice(from, to + 1),
      opens: times[0],
      closes: times[1],
    },
  ];
}

/** social_links — JSON-колонка: массив строк, массив объектов или объект-словарь. */
function parseSameAs(raw: unknown): string[] | undefined {
  const pick = (v: any): string | null => {
    if (typeof v === 'string') return v.trim() || null;
    if (v && typeof v === 'object') {
      const u = v.url || v.link || v.href || v.value;
      return typeof u === 'string' && u.trim() ? u.trim() : null;
    }
    return null;
  };

  const list = Array.isArray(raw)
    ? raw
    : raw && typeof raw === 'object'
    ? Object.values(raw)
    : [];

  const urls = list.map(pick).filter((u): u is string => !!u && /^https?:\/\//i.test(u));
  return urls.length ? urls : undefined;
}

export default function SiteJsonLd({
  settings,
  locale,
}: {
  settings: Settings;
  locale: string;
}) {
  const s = settings || {};

  const name = s.site_name || 'OPTECH';
  const phones = [...splitPhones(s.phone), ...splitPhones(s.city_phone)];
  const address = parseAddress(s.address);
  const lat = Number(s.geo_lat);
  const lng = Number(s.geo_lng);
  const hasGeo = Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);

  const organization: Record<string, any> = {
    // LocalBusiness добавляем только когда есть реальный адрес: тип
    // «локальный бизнес» без NAP Google расценивает как некорректную разметку.
    '@type': address ? ['Organization', 'LocalBusiness'] : 'Organization',
    '@id': `${SITE_URL}/#organization`,
    name,
    legalName: s.org_legal_name || undefined,
    taxID: s.bin || undefined,
    url: SITE_URL,
    logo: {
      '@type': 'ImageObject',
      '@id': `${SITE_URL}/#logo`,
      url: LOGO_URL,
      contentUrl: LOGO_URL,
      caption: name,
    },
    image: { '@id': `${SITE_URL}/#logo` },
    description: s.slogan || undefined,
    email: s.email || undefined,
    telephone: phones[0] || undefined,
    address: address || undefined,
    geo: hasGeo ? { '@type': 'GeoCoordinates', latitude: lat, longitude: lng } : undefined,
    hasMap: typeof s.map === 'string' && /^https?:\/\//i.test(s.map) ? s.map : undefined,
    openingHoursSpecification: parseOpeningHours(s.work_time),
    sameAs: parseSameAs(s.social_links),
    areaServed: { '@type': 'Country', name: 'Kazakhstan' },
    contactPoint: phones.length
      ? phones.map((telephone) => ({
          '@type': 'ContactPoint',
          telephone,
          contactType: 'sales',
          areaServed: 'KZ',
          availableLanguage: ['ru', 'kk', 'en'],
        }))
      : undefined,
  };

  const website = {
    '@type': 'WebSite',
    '@id': `${SITE_URL}/#website`,
    url: `${SITE_URL}/${locale}`,
    name,
    description: s.slogan || undefined,
    publisher: { '@id': `${SITE_URL}/#organization` },
    inLanguage: htmlLang(locale),
    // Поиск на сайте работает по пути, а не по query-параметру:
    // /{locale}/search/{запрос} (см. SmartSearch.tsx).
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${SITE_URL}/${locale}/search/{search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };

  const graph = { '@context': 'https://schema.org', '@graph': [organization, website] };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(graph) }}
    />
  );
}
