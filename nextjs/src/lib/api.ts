const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.optech.kz/api';

function getAcceptLanguage(locale: string): string {
  const map: Record<string, string> = {
    ru: 'ru',
    en: 'en',
    kz: 'kz',
  };
  return map[locale] || 'ru';
}

async function fetchAPI(endpoint: string, locale: string = 'ru') {
  const isSearchEndpoint = endpoint.startsWith('/v1/search') || endpoint.startsWith('/searchresult/');

  const res = await fetch(`${API_BASE_URL}${endpoint}`, {
    headers: {
      'Accept-Language': getAcceptLanguage(locale),
      'Content-Type': 'application/json',
    },
    ...(isSearchEndpoint ? { cache: 'no-store' as const } : { next: { revalidate: 60 } }),
  });

  if (!res.ok) {
    throw new Error(`API error: ${res.status} ${res.statusText}`);
  }

  return res.json();
}

// Settings
export async function getSettings() {
  const data = await fetchAPI('/getSettings');
  return data.settings;
}

// Slider
export async function getSlider() {
  const data = await fetchAPI('/getSlider');
  return data.sliders;
}

// Partners
export async function getPartners() {
  const data = await fetchAPI('/getPartners');
  return data.partners;
}

// Licenses
export async function getLicenses() {
  const data = await fetchAPI('/getLicenses');
  return data.licenses;
}

// Categories
export async function getAllCategories(locale: string = 'ru') {
  const data = await fetchAPI('/categories', locale);
  return data.categories;
}

export async function getCategory(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/catalog/${slug}`, locale);
  return data.category;
}

export async function getSubCategory(categorySlug: string, subcategorySlug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/getSubCategory/${categorySlug}/${subcategorySlug}`, locale);
  return data;
}

export async function getSubSubCategory(categorySlug: string, subcategorySlug: string, subsubcategorySlug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/getSubSubCategory/${categorySlug}/${subcategorySlug}/${subsubcategorySlug}`, locale);
  return data;
}

// Products
export async function getProductsByCategory(categorySlug: string, sort?: string) {
  const qs = sort ? `?sort=${encodeURIComponent(sort)}` : '';
  const data = await fetchAPI(`/getProductsByCategory/${categorySlug}${qs}`);
  return data.products;
}

export async function getProduct(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/getProduct/${slug}`, locale);
  return data;
}

// Brands
export async function getBrands() {
  const data = await fetchAPI('/brands');
  return data.brands;
}

export async function getOneBrand(slug: string) {
  const data = await fetchAPI(`/brand/${slug}`);
  return data;
}

// Search

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

function buildSearchVariants(searchWord: string): string[] {
  const variants: string[] = [];
  const add = (value: string) => {
    const clean = value.trim();
    if (clean && !variants.includes(clean)) variants.push(clean);
  };

  add(searchWord);
  if (/[a-z`\[\];',.]/i.test(searchWord) && !/[а-яёәғқңөұүһі]/i.test(searchWord)) {
    add(fixKeyboardLayoutLatToCyr(searchWord));
  }
  return variants;
}

function hasProducts(data: any): boolean {
  return data?.status === 200 && Array.isArray(data.products) && data.products.length > 0;
}

export async function searchProducts(searchWord: string, locale: string = 'ru') {
  const word = decodeURIComponent(searchWord).trim();
  const variants = buildSearchVariants(word);
  let lastData: any = { status: 404 };

  for (const variant of variants) {
    const data = await fetchAPI(`/searchresult/${encodeURIComponent(variant)}`, locale);
    if (hasProducts(data)) return data;
    lastData = data;
  }

  return lastData;
}


export type SearchEntityType = 'product' | 'category' | 'article' | 'solution' | 'solcategory' | 'project' | 'service' | 'offer';

export type SearchResultItem = {
  id: number;
  type: SearchEntityType | string;
  title: string;
  url: string;
  image?: string | null;
  price?: number | null;
  currency?: string | null;
  score?: number;
  match?: {
    body_only?: boolean;
    requires_body?: boolean;
    matched_fields?: string[];
    manual_boost?: number;
    how?: Record<string, unknown>;
  };
};

export type SearchAllResponse = {
  query: string;
  corrected: { query: string } | null;
  total: number;
  results: SearchResultItem[];
  groups: Record<string, SearchResultItem[]>;
};

function hasSearchResults(data: any): boolean {
  return Number(data?.total || 0) > 0 && Array.isArray(data?.results);
}

function legacyImagePath(image: string | null | undefined): string | null {
  if (!image) return null;
  if (/^https?:\/\//.test(image)) return image;
  if (image.startsWith('/')) return image;
  return `/assets/images/products/${image}`;
}

function legacyProductsToSearchResponse(query: string, data: any): SearchAllResponse {
  const products = Array.isArray(data?.products) ? data.products : [];
  const results: SearchResultItem[] = products
    .map((item: any) => {
      const slug = item?.slug || String(item?.url || '').split('/').filter(Boolean).pop();
      const title = item?.title || item?.name;
      if (!slug || !title) return null;

      return {
        id: Number(item.id || 0),
        type: 'product',
        title,
        url: item.url || `/product/${slug}`,
        image: legacyImagePath(item.images || item.image),
        price: item.price ?? null,
        currency: item.currency ?? null,
        score: 0,
        match: { matched_fields: ['title'] },
      } as SearchResultItem;
    })
    .filter((item: SearchResultItem | null): item is SearchResultItem => item !== null);

  return {
    query,
    corrected: null,
    total: results.length,
    results,
    groups: { product: results },
  };
}

export async function searchAll(
  searchWord: string,
  locale: string = 'ru',
  type?: SearchEntityType | string,
  limit: number = 50
): Promise<SearchAllResponse> {
  const word = decodeURIComponent(searchWord).trim();
  const variants = buildSearchVariants(word);
  let lastData: SearchAllResponse = { query: word, corrected: null, total: 0, results: [], groups: {} };
  let v1Available = false;

  for (const variant of variants) {
    const params = new URLSearchParams({
      q: variant,
      locale,
      limit: String(limit),
    });
    if (type) params.set('type', type);

    try {
      const data = await fetchAPI(`/v1/search?${params.toString()}`, locale);
      v1Available = true;

      const normalized: SearchAllResponse = {
        query: word,
        corrected: data?.corrected || (variant !== word && hasSearchResults(data) ? { query: variant } : null),
        total: Number(data?.total || 0),
        results: Array.isArray(data?.results) ? data.results : [],
        groups: data?.groups && typeof data.groups === 'object' ? data.groups : {},
      };

      lastData = normalized;
      if (hasSearchResults(data) || data?.corrected?.query) return normalized;
    } catch (error) {
      // /v1/search может быть временно недоступен при переносе. Не ломаем
      // страницу: ниже включится старый strict fallback /searchresult.
      lastData = { query: word, corrected: null, total: 0, results: [], groups: {} };
    }
  }

  // Если новый индекс ответил 0, всё равно включаем старый strict fallback.
  // Сейчас при переносе React -> Next search_documents может быть пустой или
  // не переиндексированный, а товары в products уже есть. Legacy endpoint после
  // наших правок ищет только по name/SKU/meta/brand/category, без description.
  if ((!type || type === 'product') && (!v1Available || Number(lastData.total || 0) === 0)) {
    for (const variant of variants) {
      try {
        const legacy = await fetchAPI(`/searchresult/${encodeURIComponent(variant)}`, locale);
        if (hasProducts(legacy)) {
          const normalized = legacyProductsToSearchResponse(word, legacy);
          if (variant !== word) normalized.corrected = { query: variant };
          return normalized;
        }
      } catch {
        // ignore legacy fallback errors
      }
    }
  }

  return lastData;
}

// Projects
export async function getProjects(locale: string = 'ru') {
  const data = await fetchAPI('/projects', locale);
  return data.projects;
}

export async function getProject(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/project/${slug}`, locale);
  return data.project;
}

// Articles
export async function getArticles(locale: string = 'ru') {
  const data = await fetchAPI('/articles', locale);
  return data.articles;
}

export async function getArticle(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/article/${slug}`, locale);
  return data.article;
}

// База знаний / SEO-статьи. Бэкенд-эндпоинты (/knowledge-base,
// /knowledge-base/{slug}) ещё не реализованы — делаем фронт сейчас,
// backend подключим отдельным шагом. До этого момента запросы будут падать
// в try/catch на страницах (пустой список / notFound), это ожидаемо.
export async function getKnowledgeArticles(locale: string = 'ru') {
  const data = await fetchAPI('/knowledge-base', locale);
  return data.articles;
}

export async function getKnowledgeArticle(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/knowledge-base/${slug}`, locale);
  return data.article;
}

// Services
export async function getServices(locale: string = 'ru') {
  const data = await fetchAPI('/services', locale);
  return data.services;
}

export async function getService(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/service/${slug}`, locale);
  return data.service;
}

// Special Offers
export async function getOffers(locale: string = 'ru') {
  const data = await fetchAPI('/offers', locale);
  return data.offers;
}

export async function getOffer(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/offer/${slug}`, locale);
  return data.offer;
}

// Solution Categories
export async function getSolCategories(locale: string = 'ru') {
  const data = await fetchAPI('/solcategories', locale);
  return data.solcategories;
}

export async function getSolCategory(slug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/solcategory/${slug}`, locale);
  return data;
}

// Solutions
export async function getSolution(solCategorySlug: string, solutionSlug: string, locale: string = 'ru') {
  const data = await fetchAPI(`/solutions/${solCategorySlug}/${solutionSlug}`, locale);
  return data;
}

// Forms (client-side POST)
export async function submitOrder(formData: {
  name: string;
  phone: string;
  email: string;
  comment: string;
}) {
  const res = await fetch(`${API_BASE_URL}/setOrder`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData),
  });
  return res.json();
}

export async function submitPriceRequest(formData: {
  name: string;
  phone: string;
  email: string;
  comment: string;
  product: string;
}) {
  const res = await fetch(`${API_BASE_URL}/setPrice`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData),
  });
  return res.json();
}

// ---- Sitemap-only lightweight fetchers (см. FrontendController::*ForSitemap) ----
// Используются только в src/app/sitemap.ts. Отдают slug/path + SEO-флаги
// (in_sitemap/is_indexable/priority/changefreq), которые раньше sitemap не учитывал.
export async function getCategoriesForSitemap() {
  try {
    const data = await fetchAPI('/sitemap/categories');
    return data.categories || [];
  } catch {
    return [];
  }
}

export async function getProductsForSitemap() {
  try {
    const data = await fetchAPI('/sitemap/products');
    return data.products || [];
  } catch {
    return [];
  }
}

export async function getSolutionsForSitemap() {
  try {
    const data = await fetchAPI('/sitemap/solutions');
    return data.solutions || [];
  } catch {
    return [];
  }
}
