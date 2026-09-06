import { absolutizeRichContent } from '@/lib/utils';
import './seoText.css';

/**
 * SEO-текст категории (seo_text_top / seo_text_bottom) с необязательным
 * подзаголовком seo_h2.
 *
 * Эти поля есть в таблице categories и заполняются в админке с самого начала,
 * но на фронте не читались нигде — весь написанный текст лежал в базе мёртвым
 * грузом. Для коммерческих категорий это основной источник текстового
 * контента: без него страница категории — это только сетка карточек.
 */
export default function SeoText({
  html,
  heading,
  variant,
}: {
  html?: string | null;
  heading?: string | null;
  variant: 'top' | 'bottom';
}) {
  const hasHtml = typeof html === 'string' && html.trim().length > 0;
  const hasHeading = typeof heading === 'string' && heading.trim().length > 0;
  if (!hasHtml && !hasHeading) return null;

  return (
    <section className={`seoText seoText--${variant}`}>
      {hasHeading && <h2 className="seoText__heading">{heading}</h2>}
      {hasHtml && (
        <div
          className="rich-content seoText__body"
          dangerouslySetInnerHTML={{ __html: absolutizeRichContent(html as string) }}
        />
      )}
    </section>
  );
}
