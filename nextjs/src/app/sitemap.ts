import { MetadataRoute } from 'next';
import {
  getBrands,
  getProjects,
  getArticles,
  getServices,
  getOffers,
  getSolCategories,
  getCategoriesForSitemap,
  getProductsForSitemap,
  getSolutionsForSitemap,
} from '@/lib/api';

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://optech.kz';
const LOCALES = ['ru', 'en', 'kz'];

type Freq = MetadataRoute.Sitemap[number]['changeFrequency'];

const VALID_FREQ: Freq[] = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

function normFreq(value: unknown, fallback: Freq): Freq {
  return VALID_FREQ.includes(value as Freq) ? (value as Freq) : fallback;
}

function normPriority(value: unknown, fallback: number): number {
  const n = Number(value);
  return Number.isFinite(n) && n >= 0 && n <= 1 ? n : fallback;
}

function lastMod(value: unknown): Date {
  if (!value) return new Date();
  const d = new Date(value as string);
  return Number.isNaN(d.getTime()) ? new Date() : d;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const entries: MetadataRoute.Sitemap = [];

  // Static pages
  const staticPages = ['', '/catalog', '/brands', '/projects', '/articles', '/basa-znani', '/services', '/offers', '/solutions', '/contacts', '/about', '/license', '/terms', '/privacy'];
  for (const locale of LOCALES) {
    for (const page of staticPages) {
      entries.push({
        url: `${BASE_URL}/${locale}${page}`,
        lastModified: new Date(),
        changeFrequency: page === '' ? 'daily' : 'weekly',
        priority: page === '' ? 1.0 : 0.8,
      });
    }
  }

  try {
    const [categories, products, solutions, brands, projects, articles, services, offers, solCategories] =
      await Promise.all([
        getCategoriesForSitemap(),
        getProductsForSitemap(),
        getSolutionsForSitemap(),
        getBrands().catch(() => []),
        getProjects('ru').catch(() => []),
        getArticles('ru').catch(() => []),
        getServices('ru').catch(() => []),
        getOffers('ru').catch(() => []),
        getSolCategories('ru').catch(() => []),
      ]);

    for (const locale of LOCALES) {
      // Категории всех уровней — по полному пути слагов, с учётом флагов из админки.
      categories
        ?.filter((c: any) => c.in_sitemap !== false && c.is_indexable !== false)
        .forEach((c: any) => {
          const path = Array.isArray(c.path) ? c.path.join('/') : c.slug;
          entries.push({
            url: `${BASE_URL}/${locale}/catalog/${path}`,
            lastModified: lastMod(c.updated_at),
            changeFrequency: normFreq(c.sitemap_changefreq, 'weekly'),
            priority: normPriority(c.sitemap_priority, 0.7),
          });
        });

      // Товары — раньше в sitemap не попадали вообще.
      products
        ?.filter((p: any) => p.in_sitemap !== false && p.is_indexable !== false)
        .forEach((p: any) => {
          entries.push({
            url: `${BASE_URL}/${locale}/product/${p.slug}`,
            lastModified: lastMod(p.updated_at),
            changeFrequency: normFreq(p.sitemap_changefreq, 'weekly'),
            priority: normPriority(p.sitemap_priority, 0.6),
          });
        });

      // Решения — вложенный URL /solutions/{cat}/{solution}.
      solutions
        ?.filter((s: any) => s.in_sitemap !== false && s.is_indexable !== false)
        .forEach((s: any) => {
          entries.push({
            url: `${BASE_URL}/${locale}/solutions/${s.category_slug}/${s.slug}`,
            lastModified: lastMod(s.updated_at),
            changeFrequency: normFreq(s.sitemap_changefreq, 'weekly'),
            priority: normPriority(s.sitemap_priority, 0.6),
          });
        });

      brands?.forEach((b: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/brand/${b.slug}`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.6 });
      });
      projects?.forEach((i: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/project/${i.slug}`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.6 });
      });
      articles?.forEach((i: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/article/${i.slug}`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.6 });
      });
      services?.forEach((i: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/service/${i.slug}`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.6 });
      });
      offers?.forEach((i: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/offer/${i.slug}`, lastModified: new Date(), changeFrequency: 'monthly', priority: 0.6 });
      });
      solCategories?.forEach((cat: any) => {
        entries.push({ url: `${BASE_URL}/${locale}/solutions/${cat.slug}`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.6 });
      });
    }
  } catch (error) {
    console.error('Error generating sitemap:', error);
  }

  return entries;
}
