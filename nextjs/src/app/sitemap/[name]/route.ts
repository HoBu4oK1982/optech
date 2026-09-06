import { notFound } from 'next/navigation';
import {
  SITEMAP_SOURCES,
  SITEMAP_TYPES,
  SitemapType,
  partsCount,
  renderUrlset,
  sliceForPart,
  xmlResponse,
} from '@/lib/sitemap';

export const revalidate = 3600;

/**
 * Под-карта одного типа: /sitemap/products.xml, /sitemap/categories.xml и т.д.
 * Если раздел не помещается в лимит sitemap.org (50 000 URL), он разбивается
 * на части: /sitemap/products-2.xml, -3 и далее.
 */
function parseName(name: string): { type: SitemapType; part: number } | null {
  const match = name.match(/^([a-z]+)(?:-(\d+))?\.xml$/);
  if (!match) return null;

  const type = match[1] as SitemapType;
  if (!SITEMAP_TYPES.includes(type)) return null;

  const part = match[2] ? Number(match[2]) : 1;
  if (!Number.isInteger(part) || part < 1) return null;

  return { type, part };
}

export async function GET(_request: Request, { params }: { params: { name: string } }) {
  const parsed = parseName(params.name);
  if (!parsed) notFound();

  const items = await SITEMAP_SOURCES[parsed.type]();
  if (parsed.part > partsCount(items.length)) notFound();

  return xmlResponse(renderUrlset(sliceForPart(items, parsed.part)));
}
