'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import gsap from 'gsap';
import { API_URL, BACKEND_URL } from '@/lib/constants';
import './smartSearch.css';

type Suggestion = { text: string; type: string; url: string; score?: number };
type Product = {
  id: number;
  type: string;
  title: string;
  url: string;
  image?: string | null;
  price?: number | null;
  currency?: string | null;
  score?: number;
  match?: { body_only?: boolean; requires_body?: boolean; matched_fields?: string[]; manual_boost?: number; how?: Record<string, unknown> };
};
type SuggestData = {
  query: string;
  corrected?: { query: string } | null;
  suggestions: Suggestion[];
  products: Product[];
  popular?: string[];
};

type LegacyProduct = {
  id: number;
  name?: string;
  title?: string;
  images?: string | null;
  image?: string | null;
  slug?: string;
  url?: string;
  price?: number | null;
  currency?: string | null;
};

// backend url (/product/{slug}, /category/{slug}, ...) -> Next.js route
function toFrontUrl(locale: string, type: string, backendUrl: string): string {
  const cleanUrl = (backendUrl || '').split('?')[0];
  const parts = cleanUrl.split('/').filter(Boolean);
  const slug = parts.pop() || '';

  if (!slug) return `/${locale}`;

  if (type === 'solution') {
    // If backend already returns /solutions/{category}/{solution}, preserve both slugs.
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

// backend image ({host}/storage/{folder}/{file}) -> {BACKEND_URL}/assets/images/{folder}/{file}
function toImg(image?: string | null): string | null {
  if (!image) return null;
  const m = image.match(/\/storage\/(.+)$/);
  if (m) return `${BACKEND_URL}/assets/images/${m[1]}`;
  if (/^https?:\/\//.test(image)) return image;
  return `${BACKEND_URL}/${image.replace(/^\/+/, '')}`;
}

function legacyProductToSmart(product: LegacyProduct): Product | null {
  const slug = product.slug || (product.url || '').split('/').filter(Boolean).pop();
  const title = product.title || product.name;

  if (!slug || !title) return null;

  const legacyImage = product.images
    ? `${BACKEND_URL}/assets/images/products/${product.images}`
    : product.image || null;

  return {
    id: product.id,
    type: 'product',
    title,
    url: product.url || `/product/${slug}`,
    image: legacyImage,
    price: product.price ?? null,
    currency: product.currency ?? null,
  };
}

function normalizeSuggestResponse(json: any, query: string): SuggestData {
  const suggestions: Suggestion[] = Array.isArray(json?.suggestions)
    ? json.suggestions
        .map((item: any) => ({
          text: item?.text || item?.title || item?.name || '',
          type: item?.type || 'product',
          url: item?.url || (item?.slug ? `/product/${item.slug}` : ''),
          score: item?.score,
        }))
        .filter((item: Suggestion) => item.text && item.url)
    : [];

  const products: Product[] = Array.isArray(json?.products)
    ? json.products
        .map((item: any) => {
          if (item?.title && item?.url) {
            return {
              id: item.id,
              type: item.type || 'product',
              title: item.title,
              url: item.url,
              image: item.image ?? null,
              price: item.price ?? null,
              currency: item.currency ?? null,
              score: item.score ?? undefined,
              match: item.match ?? undefined,
            } as Product;
          }
          return legacyProductToSmart(item);
        })
        .filter((item: Product | null): item is Product => item !== null)
    : [];

  return {
    query: json?.query || query,
    corrected: json?.corrected || null,
    suggestions,
    products,
    popular: Array.isArray(json?.popular) ? json.popular : [],
  };
}

async function fetchJson(url: string, signal?: AbortSignal) {
  const res = await fetch(url, {
    signal,
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`Search API error: ${res.status}`);
  return res.json();
}


const EN_TO_RU_KEYMAP: Record<string, string> = {
  q: 'й', w: 'ц', e: 'у', r: 'к', t: 'е', y: 'н', u: 'г', i: 'ш', o: 'щ', p: 'з', '[': 'х', ']': 'ъ',
  a: 'ф', s: 'ы', d: 'в', f: 'а', g: 'п', h: 'р', j: 'о', k: 'л', l: 'д', ';': 'ж', "'": 'э',
  z: 'я', x: 'ч', c: 'с', v: 'м', b: 'и', n: 'т', m: 'ь', ',': 'б', '.': 'ю', '`': 'ё',
};

function fixKeyboardLayoutLatToCyr(value: string): string {
  return value
    .toLowerCase()
    .split('')
    .map((char) => EN_TO_RU_KEYMAP[char] || char)
    .join('')
    .replace(/\s+/g, ' ')
    .trim();
}

function buildQueryVariants(query: string): string[] {
  const variants: string[] = [];
  const add = (value: string) => {
    const clean = value.trim();
    if (clean && !variants.includes(clean)) variants.push(clean);
  };

  add(query);

  // Пользователь хотел набрать русское слово, но клавиатура была в EN-раскладке:
  // vtnyf nhj,f -> метна/медна труба. Оригинал всё равно пробуем первым,
  // чтобы реальные английские бренды и модели (FLEX, Cisco, APC) не ломались.
  if (/[a-z`\[\];',.]/i.test(query) && !/[а-яёәғқңөұүһі]/i.test(query)) {
    add(fixKeyboardLayoutLatToCyr(query));
  }

  return variants;
}

function hasSmartResults(data: SuggestData): boolean {
  return data.suggestions.length > 0 || data.products.length > 0;
}


const SUGGEST_MIN_SCORE = 1;

function passesMinScore(score: unknown, keepUnknownScores: boolean): boolean {
  return typeof score === 'number' ? score >= SUGGEST_MIN_SCORE : keepUnknownScores;
}

function filterSuggestDataByMinScore(data: SuggestData, keepUnknownScores = false): SuggestData {
  return {
    ...data,
    suggestions: data.suggestions.filter((item) => passesMinScore(item.score, keepUnknownScores)),
    products: data.products.filter((item) => passesMinScore(item.score, keepUnknownScores)),
  };
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
          <mark className="ss__mark" key={`${part}-${idx}`}>{part}</mark>
        ) : (
          <span key={`${part}-${idx}`}>{part}</span>
        )
      )}
    </>
  );
}

const TYPE_LABEL: Record<string, string> = {
  category: 'Раздел каталога',
  solcategory: 'Решения',
  solution: 'Решение',
  article: 'Новости',
  service: 'Услуга',
  offer: 'Акция',
  project: 'Проект',
};

export default function SmartSearch({
  locale,
  placeholder = 'Поиск по каталогу...',
  onNavigate,
}: {
  locale: string;
  placeholder?: string;
  /** Вызывается при переходе (клик по результату / Enter / "Показать все").
   *  Нужен, когда SmartSearch отрендерен внутри модалки/оверлея (см.
   *  MobileSearchOverlay) — раньше переход происходил, а сам оверлей
   *  поверх страницы оставался открытым, пока его не закрывали крестиком. */
  onNavigate?: () => void;
}) {
  const router = useRouter();
  const [q, setQ] = useState('');
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<SuggestData | null>(null);
  const [active, setActive] = useState(-1);

  const rootRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);
  const debounceRef = useRef<number | null>(null);
  const reportedNoResultsRef = useRef<Set<string>>(new Set());

  // ---- sections (non-product suggestions) & products ----
  const sections = (data?.suggestions || []).filter((s) => s.type !== 'product').slice(0, 5);
  const products = (data?.products || []).slice(0, 6);
  const popular = data?.popular || [];
  const corrected = data?.corrected?.query && data.corrected.query !== q ? data.corrected.query : null;

  // flat navigable list (for keyboard)
  type NavItem = { href: string; type?: string; id?: number; label: string };
  const nav: NavItem[] = [];
  products.forEach((p) => nav.push({ href: toFrontUrl(locale, 'product', p.url), type: 'product', id: p.id, label: p.title }));
  sections.forEach((s) => nav.push({ href: toFrontUrl(locale, s.type, s.url), type: s.type, label: s.text }));
  const hasResults = nav.length > 0;

  const fetchData = useCallback(
    async (query: string) => {
      abortRef.current?.abort();
      const ac = new AbortController();
      abortRef.current = ac;
      const trimmed = query.trim();

      setLoading(true);

      try {
        if (trimmed.length < 2) {
          try {
            const json = await fetchJson(`${API_URL}/v1/search/popular?locale=${locale}`, ac.signal);
            setData({ query, suggestions: [], products: [], popular: Array.isArray(json?.data) ? json.data : [] });
          } catch {
            setData({ query, suggestions: [], products: [], popular: [] });
          }
          return;
        }

        const variants = buildQueryVariants(trimmed);
        for (const variant of variants) {
          try {
            const json = await fetchJson(
              `${API_URL}/v1/search/suggest?q=${encodeURIComponent(variant)}&locale=${locale}&limit=8`,
              ac.signal
            );
            const normalized = filterSuggestDataByMinScore(normalizeSuggestResponse(json, variant));
            const dataWithCorrection: SuggestData =
              variant !== trimmed && hasSmartResults(normalized)
                ? { ...normalized, query: trimmed, corrected: { query: variant } }
                : normalized;

            if (hasSmartResults(dataWithCorrection)) {
              setData(dataWithCorrection);
              return;
            }
          } catch (error) {
            if ((error as Error).name === 'AbortError') return;
          }
        }

        // Даже если /v1/suggest технически ответил 200, он может вернуть пусто,
        // когда search_documents ещё не переиндексирован. В этом случае включаем
        // старый strict fallback. Он уже не ищет по description/body, поэтому
        // мусорная выдача не возвращается.
        for (const variant of variants) {
          try {
            const legacy = await fetchJson(`${API_URL}/searchresult/${encodeURIComponent(variant)}`, ac.signal);
            const normalized = filterSuggestDataByMinScore(normalizeSuggestResponse(legacy, variant), true);
            if (hasSmartResults(normalized)) {
              setData(
                variant !== trimmed
                  ? { ...normalized, query: trimmed, corrected: { query: variant } }
                  : normalized
              );
              return;
            }
          } catch (error) {
            if ((error as Error).name === 'AbortError') return;
          }
        }

        setData({ query: trimmed, suggestions: [], products: [], popular: [] });
      } catch (e) {
        if ((e as Error).name !== 'AbortError') {
          setData({ query: trimmed, suggestions: [], products: [], popular: [] });
        }
      } finally {
        if (abortRef.current === ac) {
          setLoading(false);
        }
      }
    },
    [locale]
  );

  // debounce
  useEffect(() => {
    if (!open) return;
    if (debounceRef.current) window.clearTimeout(debounceRef.current);
    debounceRef.current = window.setTimeout(() => fetchData(q), 180);
    return () => {
      if (debounceRef.current) window.clearTimeout(debounceRef.current);
    };
  }, [q, open, fetchData]);

  // open/close panel with GSAP
  useEffect(() => {
    const panel = panelRef.current;
    if (!panel) return;
    if (open) {
      gsap.killTweensOf(panel);
      gsap.fromTo(
        panel,
        { autoAlpha: 0, y: -10, scale: 0.985 },
        { autoAlpha: 1, y: 0, scale: 1, duration: 0.32, ease: 'power3.out' }
      );
    } else {
      gsap.to(panel, { autoAlpha: 0, y: -8, duration: 0.2, ease: 'power2.in' });
    }
  }, [open]);

  // stagger result items whenever the data changes
  useEffect(() => {
    if (!open || !panelRef.current) return;
    const items = panelRef.current.querySelectorAll('.ss__item, .ss__chip');
    if (items.length) {
      gsap.fromTo(
        items,
        { autoAlpha: 0, y: 8 },
        { autoAlpha: 1, y: 0, duration: 0.28, stagger: 0.03, ease: 'power2.out' }
      );
    }
    setActive(-1);
  }, [data, open]);

  // логируем запросы без результатов из dropdown, чтобы видеть дыры в каталоге.
  useEffect(() => {
    const query = q.trim();
    if (query.length < 2 || loading || hasResults || corrected || !data) return;
    if ((data.query || '').trim() !== query) return;

    const key = `${locale}:${query.toLowerCase()}`;
    if (reportedNoResultsRef.current.has(key)) return;
    reportedNoResultsRef.current.add(key);

    fetch(`${API_URL}/v1/search/no-results?locale=${locale}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ query }),
    }).catch(() => {});
  }, [data, loading, hasResults, corrected, q, locale]);

  // outside click + escape
  useEffect(() => {
    const onDown = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setOpen(false);
        inputRef.current?.blur();
      }
    };
    document.addEventListener('mousedown', onDown);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onDown);
      document.removeEventListener('keydown', onKey);
    };
  }, []);

  const go = (href: string, type?: string, id?: number) => {
    // fire-and-forget click tracking
    if (q.trim()) {
      fetch(`${API_URL}/v1/search/click?locale=${locale}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ query: q, type, id }),
      }).catch(() => {});
    }
    setOpen(false);
    if (typeof window !== 'undefined') window.dispatchEvent(new Event('optech:pt-start'));
    router.push(href);
    onNavigate?.();
  };

  const goAll = () => {
    const query = (corrected || q).trim();
    if (!query) return;
    setOpen(false);
    if (typeof window !== 'undefined') window.dispatchEvent(new Event('optech:pt-start'));
    router.push(`/${locale}/search/${encodeURIComponent(query)}`);
    onNavigate?.();
  };

  const onKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActive((a) => Math.min(a + 1, nav.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActive((a) => Math.max(a - 1, -1));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (active >= 0 && nav[active]) go(nav[active].href, nav[active].type, nav[active].id);
      else goAll();
    }
  };

  let idx = -1; // running index across rendered nav items

  return (
    <div className="ss" ref={rootRef}>
      <div className={`ss__field ${open ? 'is-open' : ''}`}>
        <button
          type="button"
          className="ss__iconBtn"
          aria-label="Найти"
          onClick={() => {
            if (q.trim().length >= 2) goAll();
            else inputRef.current?.focus();
          }}
        >
          <svg className="ss__icon" viewBox="0 0 512 512" aria-hidden>
            <path
              fill="currentColor"
              d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"
            />
          </svg>
        </button>
        <input
          ref={inputRef}
          className="ss__input"
          type="text"
          value={q}
          placeholder={placeholder}
          onChange={(e) => setQ(e.target.value)}
          onFocus={() => setOpen(true)}
          onKeyDown={onKeyDown}
          autoComplete="off"
          aria-label={placeholder}
        />
        {q && (
          <button className="ss__clear" onClick={() => { setQ(''); inputRef.current?.focus(); }} aria-label="Очистить" type="button">
            x
          </button>
        )}
        <span className={`ss__bar ${loading ? 'is-loading' : ''}`} />
      </div>

      {open && (
        <div className="ss__panel" ref={panelRef}>
          {corrected && (
            <button
              className="ss__corrected"
              type="button"
              onClick={() => { setQ(corrected); inputRef.current?.focus(); }}
            >
              Возможно, вы искали: <b>{corrected}</b>
            </button>
          )}

          {q.trim().length < 2 && popular.length > 0 && (
            <div className="ss__group">
              <div className="ss__groupTitle">Популярные запросы</div>
              <div className="ss__chips">
                {popular.map((p, i) => (
                  <button key={i} className="ss__chip" type="button" onClick={() => { setQ(p); inputRef.current?.focus(); }}>
                    {p}
                  </button>
                ))}
              </div>
            </div>
          )}

          {products.length > 0 && (
            <div className="ss__group">
              <div className="ss__groupTitle">Товары</div>
              {products.map((p) => {
                idx++;
                const href = toFrontUrl(locale, 'product', p.url);
                const img = toImg(p.image);
                const curr = idx;
                return (
                  <button
                    key={`p${p.id}`}
                    type="button"
                    className={`ss__item ss__prod ${active === curr ? 'is-active' : ''}`}
                    onMouseEnter={() => setActive(curr)}
                    onClick={() => go(href, 'product', p.id)}
                  >
                    <span className="ss__thumb">
                      {img ? (
                        <img src={img} alt="" loading="lazy" onError={(e) => ((e.currentTarget as HTMLImageElement).style.visibility = 'hidden')} />
                      ) : (
                        <span className="ss__thumbPh" />
                      )}
                    </span>
                    <span className="ss__prodTitle"><HighlightText text={p.title} query={corrected || q} /></span>
                    {p.price ? (
                      <span className="ss__prodPrice">
                        {p.price} {p.currency || '₸'}
                      </span>
                    ) : null}
                  </button>
                );
              })}
            </div>
          )}

          {sections.length > 0 && (
            <div className="ss__group">
              <div className="ss__groupTitle">Категории, решения и новости</div>
              {sections.map((s, i) => {
                idx++;
                const href = toFrontUrl(locale, s.type, s.url);
                const curr = idx;
                return (
                  <button
                    key={`s${i}`}
                    type="button"
                    className={`ss__item ss__sec ${active === curr ? 'is-active' : ''}`}
                    onMouseEnter={() => setActive(curr)}
                    onClick={() => go(href, s.type)}
                  >
                    <span className="ss__secIco" aria-hidden>›</span>
                    <span className="ss__secText"><HighlightText text={s.text} query={corrected || q} /></span>
                    <span className="ss__secType">{TYPE_LABEL[s.type] || 'Раздел'}</span>
                  </button>
                );
              })}
            </div>
          )}

          {q.trim().length >= 2 && !loading && !hasResults && (
            <div className="ss__empty">Ничего не найдено по «{q}»</div>
          )}

          {q.trim().length >= 2 && (
            <button className="ss__all" type="button" onClick={goAll}>
              Показать все результаты по «{q.trim()}»
            </button>
          )}
        </div>
      )}
    </div>
  );
}
