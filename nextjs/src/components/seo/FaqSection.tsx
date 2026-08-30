import './faqSection.css';

export type FaqItem = { question?: string | null; answer?: string | null };

/**
 * Визуальный блок FAQ (аккордеон на <details>/<summary>, без JS) — под пару
 * к buildFaqJsonLd() из lib/seo.ts. Рендерится на странице товара/категории/
 * статьи/решения/проекта, если у сущности заполнено поле faq в админке.
 */
export default function FaqSection({ items, title }: { items?: FaqItem[] | null; title?: string }) {
  const list = (Array.isArray(items) ? items : []).filter((f) => f?.question && f?.answer);
  if (!list.length) return null;

  return (
    <section className="faqSection">
      <h2 className="faqSection__title">{title || 'Частые вопросы'}</h2>
      <div className="faqSection__list">
        {list.map((f, i) => (
          <details key={i} className="faqItem">
            <summary className="faqItem__q">
              {f.question}
              <span className="faqItem__caret" aria-hidden="true" />
            </summary>
            <div className="faqItem__a">{f.answer}</div>
          </details>
        ))}
      </div>
    </section>
  );
}
