export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import Link from 'next/link';
import { getTranslations } from '@/i18n/translations';
import { searchAll, type SearchResultItem } from '@/lib/api';
import { storageUrl } from '@/lib/utils';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import '@/components/search/searchPage.css';

type SearchFilter = 'all' | 'product' | 'category' | 'solutions' | 'article' | 'service' | 'offer' | 'project';

const FILTERS: Array<{ key: SearchFilter; label: string; types: string[] }> = [
  { key: 'all', label: 'Все', types: [] },
  { key: 'product', label: 'Товары', types: ['product'] },
  { key: 'category', label: 'Категории', types: ['category'] },
  { key: 'solutions', label: 'Решения', types: ['solution', 'solcategory'] },
  { key: 'service', label: 'Услуги', types: ['service'] },
  { key: 'article', label: 'Новости', types: ['article'] },
  { key: 'offer', label: 'Акции', types: ['offer'] },
  { key: 'project', label: 'Проекты', types: ['project'] },
];

const TYPE_LABEL: Record<string, string> = {
  product: 'Товар',
  category: 'Категория',
  article: 'Новость',
  solution: 'Решение',
  solcategory: 'Раздел решений',
  service: 'Услуга',
  offer: 'Спецпредложение',
  project: 'Проект',
};

function normalizeFilter(value: unknown): SearchFilter {
  const filter = Array.isArray(value) ? value[0] : value;
  return FILTERS.some((item) => item.key === filter) ? (filter as SearchFilter) : 'all';
}

function filterResults(results: SearchResultItem[], filter: SearchFilter): SearchResultItem[] {
  if (filter === 'all') return results;
  const rule = FILTERS.find((item) => item.key === filter);
  if (!rule || rule.types.length === 0) return results;
  return results.filter((item) => rule.types.includes(item.type));
}

function countForFilter(groups: Record<string, SearchResultItem[]> = {}, filter: SearchFilter, total: number): number {
  if (filter === 'all') return total;
  const rule = FILTERS.find((item) => item.key === filter);
  if (!rule) return 0;
  return rule.types.reduce((sum, type) => sum + (groups[type]?.length || 0), 0);
}

function toFrontUrl(locale: string, type: string, backendUrl: string): string {
  const cleanUrl = (backendUrl || '').split('?')[0];
  const parts = cleanUrl.split('/').filter(Boolean);
  const slug = parts.pop() || '';

  if (!slug) return `/${locale}`;

  if (type === 'solution') {
    const solutionIndex = parts.lastIndexOf('solutions');
    if (solutionIndex >= 0) {
      const rest = parts.slice(solutionIndex + 1).concat(slug).join('/');
      return `/${locale}/solutions/${rest}`;
    }
    return `/${locale}/search/${encodeURIComponent(slug)}`;
  }

  const seg: Record<string, string> = {
    product: 'product',
    category: 'catalog',
    article: 'article',
    project: 'project',
    solcategory: 'solutions',
    service: 'service',
    offer: 'offer',
  };

  return `/${locale}/${seg[type] || 'catalog'}/${encodeURIComponent(slug)}`;
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function highlightTerms(query: string): string[] {
  return query
    .toLowerCase()
    .replace(/[«»"'()]/g, ' ')
    .split(/[\s\-]+/)
    .map((term) => term.trim())
    .filter((term) => term.length >= 2)
    .sort((a, b) => b.length - a.length)
    .filter((term, index, arr) => arr.indexOf(term) === index);
}

function HighlightText({ text, query }: { text: string; query: string }) {
  const terms = highlightTerms(query);
  if (!terms.length) return <>{text}</>;

  const re = new RegExp(`(${terms.map(escapeRegExp).join('|')})`, 'gi');
  const parts = String(text).split(re);

  return (
    <>
      {parts.map((part, idx) =>
        terms.includes(part.toLowerCase()) ? (
          <mark key={`${part}-${idx}`} className="searchMark">{part}</mark>
        ) : (
          <span key={`${part}-${idx}`}>{part}</span>
        )
      )}
    </>
  );
}

export async function generateMetadata({ params }: { params: { locale: string; searchWord: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return { title: `${t.common.searchResults}: ${decodeURIComponent(params.searchWord)}` };
}

export default async function SearchPage({
  params,
  searchParams,
}: {
  params: { locale: string; searchWord: string };
  searchParams?: { type?: string | string[] };
}) {
  const { locale, searchWord } = params;
  const t = getTranslations(locale);
  const query = decodeURIComponent(searchWord).trim();
  const selectedFilter = normalizeFilter(searchParams?.type);

  let data = {
    query,
    corrected: null as { query: string } | null,
    total: 0,
    results: [] as SearchResultItem[],
    groups: {} as Record<string, SearchResultItem[]>,
  };

  try {
    data = await searchAll(query, locale, undefined, 120);
  } catch (e) {
    console.error(e);
  }

  const effectiveQuery = data.corrected?.query || query;
  const results = filterResults(data.results || [], selectedFilter);

  return (
    <main className="searchPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: `${t.common.searchResults}: ${query}` }]} />

        <section className="searchHero">
          <div className="searchHero__content">
            <div>
              <span className="searchHero__eyebrow">Умный поиск OPTECH</span>
              <h1 className="searchHero__title">
                Результаты по запросу <span className="searchHero__query">«{query}»</span>
              </h1>
              {data.corrected?.query && data.corrected.query !== query ? (
                <p className="searchHero__note">
                  Показаны результаты с учётом исправления: <b>{data.corrected.query}</b>
                </p>
              ) : (
                <p className="searchHero__note">
                  Поиск учитывает название, категорию, бренд, SKU, ключевые слова, синонимы и раскладку клавиатуры.
                </p>
              )}
            </div>

            <div className="searchHero__meta" aria-label="Количество найденных результатов">
              <span className="searchHero__count">{results.length}</span>
              <span className="searchHero__label">результатов в текущем фильтре</span>
            </div>
          </div>
        </section>

        <nav className="searchTabs" aria-label="Фильтры результатов поиска">
          {FILTERS.map((filter) => {
            const count = countForFilter(data.groups, filter.key, data.total);
            const active = selectedFilter === filter.key;
            const href = filter.key === 'all'
              ? `/${locale}/search/${encodeURIComponent(query)}`
              : `/${locale}/search/${encodeURIComponent(query)}?type=${filter.key}`;

            return (
              <Link key={filter.key} href={href} className={`searchTab ${active ? 'is-active' : ''}`}>
                <span>{filter.label}</span>
                <span className="searchTab__count">{count}</span>
              </Link>
            );
          })}
        </nav>

        {results.length > 0 ? (
          <section className="searchGrid" aria-label="Результаты поиска">
            {results.map((item) => {
              const href = toFrontUrl(locale, item.type, item.url);
              const img = item.image ? storageUrl(item.image) : null;
              const isPriority = Number(item.match?.manual_boost || 0) > 0;
              const matchedFields = item.match?.matched_fields || [];

              return (
                <Link key={`${item.type}-${item.id}`} href={href} className="searchCard">
                  <div className="searchCard__media">
                    {img ? (
                      <img src={img} alt={item.title} loading="lazy" />
                    ) : (
                      <span className="searchCard__placeholder">OPTECH</span>
                    )}
                  </div>

                  <div className="searchCard__body">
                    <div className="searchCard__badges">
                      <span className="searchBadge">{TYPE_LABEL[item.type] || item.type}</span>
                      {isPriority ? <span className="searchBadge searchBadge--orange">приоритет</span> : null}
                      {matchedFields.includes('title') ? <span className="searchBadge">по названию</span> : null}
                    </div>

                    <h2 className="searchCard__title">
                      <HighlightText text={item.title} query={effectiveQuery} />
                    </h2>
                  </div>
                </Link>
              );
            })}
          </section>
        ) : (
          <section className="searchEmpty">
            <h2 className="searchEmpty__title">{t.common.nothingFound}</h2>
            <p className="searchEmpty__text">
              Запрос сохранится в лог “ничего не найдено”. По нему будет видно, каких товаров, категорий или синонимов не хватает в каталоге.
            </p>
            <Link className="searchEmpty__link" href={`/${locale}/catalog`}>Перейти в каталог</Link>
          </section>
        )}
      </div>
    </main>
  );
}
