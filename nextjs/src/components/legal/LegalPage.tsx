import Breadcrumbs from '@/components/ui/Breadcrumbs';
import './docPage.css';

export type LegalSection = { heading: string; blocks: LegalBlock[] };
export type LegalBlock =
  | { type: 'p'; text: string }
  | { type: 'list'; items: string[] }
  | { type: 'requisites'; rows: { label: string; value: string }[] };

/**
 * Единый макет для правовых страниц (Правовая информация / Политика
 * конфиденциальности). Контент передаётся структурой секций, чтобы не
 * дублировать вёрстку между двумя страницами.
 *
 * ВАЖНО: классы здесь названы docPage, docSection, docP, docList, docReq —
 * НЕ legalHead / legalPage (как было раньше). На проде тёмный синий фон
 * под заголовком не пропадал, хотя в исходнике background:transparent уже
 * стоял — похоже на закэшированный где-то (браузер/CDN) старый CSS-файл
 * под тем же именем/классом. Новые уникальные имена класса и файла
 * (docPage.css вместо legalPage.css) гарантированно не совпадают ни с чем
 * старым, так что подмены больше произойти не может, кэш там или нет.
 */
export default function LegalPage({
  locale,
  home,
  crumb,
  title,
  published,
  sections,
}: {
  locale: string;
  home: string;
  crumb: string;
  title: string;
  published: string;
  sections: LegalSection[];
}) {
  return (
    <div className="docPage">
      <div className="container">
        <Breadcrumbs items={[{ label: home, href: `/${locale}` }, { label: crumb }]} />

        <header className="docPage__head">
          <h1 className="docPage__title">{title}</h1>
          <p className="docPage__date">{published}</p>
        </header>

        <div className="docPage__body">
          {sections.map((s, i) => (
            <section className="docSection" key={i}>
              <h2 className="docSection__heading">
                <span className="docSection__num">{String(i + 1).padStart(2, '0')}</span>
                {s.heading}
              </h2>
              {s.blocks.map((b, j) => {
                if (b.type === 'p') return <p key={j} className="docP">{b.text}</p>;
                if (b.type === 'list')
                  return (
                    <ul key={j} className="docList">
                      {b.items.map((it, k) => (
                        <li key={k}>{it}</li>
                      ))}
                    </ul>
                  );
                return (
                  <div key={j} className="docReq">
                    {b.rows.map((r, k) => (
                      <div className="docReq__row" key={k}>
                        <span className="docReq__label">{r.label}</span>
                        <span className="docReq__value">{r.value}</span>
                      </div>
                    ))}
                  </div>
                );
              })}
            </section>
          ))}
        </div>
      </div>
    </div>
  );
}
